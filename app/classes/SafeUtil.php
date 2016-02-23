<?php
// 基于过期时间和 token 的安全机制
class SafeUtil
{
	const CAPTCHA_FIELD_NAME = '_captcha_code';
	const ENCRYPT_FIELD_NAME = '_encrypt_code';

	private static $ssdb_prefix = 'safe_';
	static $salt = '2a9w20nadw@&^%$!1';
	
	// 生成一个 32 位长的 token
	static function token(){
		return md5(uniqid() . mt_rand() . microtime(1) . self::$salt);
	}
	
	// 限制 uid 在 ttl 秒内只能做 action 最多 1 次, 超过返回 false, 否则返回 true.
	static function act_once($uid, $action, $ttl){
		return self::act_limit($uid, $action, 1, $ttl);
	}
	
	// 操作次数限制函数: 限制 uid 在 ttl 秒内能操作 action 最多 max_count 次.
	// 如果超过限制, 返回 false.
	static function act_limit($uid, $action, $max_count, $ttl){
		$key = "safe_act|$uid|$action";
		$count = Util::ssdb()->incr($key);
		if($count == 1){
			Util::ssdb()->expire($key, $ttl);
		}
		if($count > $max_count){
			return false;
		}
		return true;
	}
	
	// 保存一份 token 对应的数据, 有效期为 $ttl 秒
	static function set_data($token, $data, $ttl){
		$token = self::$ssdb_prefix . $token;
		$data = json_encode($data);
		Util::ssdb()->setx($token, $data, $ttl);
	}
	
	// 根据 token 获取相应的数据, 如果不存在返回 false, 否则返回之前 set_data 时存的数据
	static function get_data($token){
		if(strlen($token) > 128){
			return false;
		}
		$token = self::$ssdb_prefix . $token;
		$data = Util::ssdb()->get($token);
		if(!$data){
			return false;
		}
		$data = json_decode($data, true);
		return $data;
	}
	
	static function del_data($token){
		$token = self::$ssdb_prefix . $token;
		Util::ssdb()->del($token);
	}
	
	// 保存浏览器用户的验证码, 将在 get_captcha 时返回 
	static function set_captcha($code, $ttl){
		$token = self::token();
		self::set_data('captcha_' . $token, $code, $ttl);
		setcookie(self::CAPTCHA_FIELD_NAME, $token, time() + $ttl, '/'/*, $domail*/);
		return $token;
	}
	
	static function get_captcha($token=false){
		if($token === false){
			$token = $_REQUEST[self::CAPTCHA_FIELD_NAME];
		}
		if(!$token){
			return false;
		}
		$token = 'captcha_' . $token;
		$saved_code = self::get_data($token);
		return $saved_code;
	}
	
	// 获取当前浏览器用户所对应的验证码
	static function verify_captcha($code){
		if(!$code){
			return false;
		}
		$code = strtolower($code);
		$token = $_REQUEST[self::CAPTCHA_FIELD_NAME];
		if(!$token){
			return false;
		}
		$token = 'captcha_' . $token;
		$saved_code = self::get_data($token);
		if($saved_code && strtolower($saved_code) === $code){
			self::del_data($token);
			return true;
		}
		return false;
	}
	
	static function send_mobile_code($mobile, $ttl=600){
		$vcode = substr(sprintf("%06d", mt_rand()), 0, 6);
		self::set_data('sms_' . $mobile, $vcode, $ttl);
		SMS::send_code($mobile, $vcode);
		return $vcode;
	}

	static function verify_mobile_code($mobile, $code){
		if(!$code){
			return false;
		}
		$token = 'sms_' . $mobile;
		$code = strtolower($code);
		$saved_code = self::get_data($token);
		if($saved_code && strtolower($saved_code) === $code){
			self::del_data($token);
			return true;
		}
		return false;
	}
	
	/*
	使用方式:
	1. 调用 create_encrypt_info() 生成公钥和私钥
	2. 将公钥告诉用户, set_encrypt_info() 保存公钥和私钥
	3. 收到用户的数据后, 调用 safe_decrypt() 解密数据, 返回时会删除公钥和私钥
	*/
	
	static function create_encrypt_info(){
		$config = array(
				"digest_alg" => "sha512",
				"private_key_bits" => 1024,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
				);
		$res = openssl_pkey_new($config);
		$private_key = '';
		openssl_pkey_export($res, $private_key);
		$details = openssl_pkey_get_details($res);
		$public_key = $details["key"];
		if(!$private_key || !$public_key){
			_throw("get_encrypt_keys failed");
		}
		return array(
			'public_key' => $public_key,
			'private_key' => $private_key,
		);
	}

	static function set_encrypt_info($encrypt, $ttl){
		$token = SafeUtil::token();
		self::set_data('encrypt_' . $token, $encrypt, $ttl);
		setcookie(self::ENCRYPT_FIELD_NAME, $token, time() + $ttl, '/'/*, $domail*/);
		return $token;
	}
	
	// $src: array, string
	static function safe_decrypt($src){
		$token = $_REQUEST[self::ENCRYPT_FIELD_NAME];
		if(!$token){
			return false;
		}
		$token = 'encrypt_' . $token;
		$info = self::get_data($token);
		if($info){
			$private_key = $info['private_key'];
			if(is_string($src)){
				$out = '';
				$s = openssl_private_decrypt(base64_decode(trim($src)), $out, $private_key);
				if(!$s){
					return false;
				}
				$ret = $out;
			}
			if(is_array($src)){
				$ret = array();
				foreach($src as $k=>$v){
					$out = '';
					$s = openssl_private_decrypt(base64_decode(trim($v)), $out, $private_key);
					if(!$s){
						return false;
					}
					$ret[$k] = $out;
				}
			}
			self::del_data($token);
			return $ret;
		}
		return false;
	}
}
