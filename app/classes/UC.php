<?php
class UC
{
	static function gen_login_token(){
		return md5(mt_rand() . microtime(1) . 'dDSIO&*^%)NB');
	}
	
	static function validate_name($name){
		if(strlen($name) < 4){
			throw new Exception("用户名必须至少 4 个字符");
		}
		if(strlen($name) > 16){
			throw new Exception("用户名必须不得多于 16 个字符");
		}
		if(!preg_match('/^[a-z][a-z0-9]*$/i', $name)){
			throw new Exception("用户名只能包含字母和数字, 以字母开头");
		}
	}

	static function validate_email($email){
		if(!preg_match('/^[0-9a-z_\.\-]+@[0-9a-z_\-]+(\.[0-9a-z_\-]+)+$/i', $email)){
			throw new Exception('Email 不合法');
		}
		if(strlen($email) > 128){
			throw new Exception("Email 过长");
		}
	}
	
	static function validate_password($password){
		if(strlen($password) < 8){
			throw new Exception('密码太短, 应该至少8个字符');
		}
	}

	static function register_by_email($email, $password){
		self::validate_email($email);
		self::validate_password($password);

		$m = User::getBy('email', $email);
		if($m){
			throw new Exception('账号已存在');
		}
		$salt = User::gen_salt();
		$password = User::encode_password($password, $salt);
		$row = array(
				'email' => $email,
				'status' => User::STATUS_INACTIVE,
				'password' => $password,
				'salt' => $salt,
				'reg_time' => date('Y-m-d H:i:s'),
				'reg_ip' => ip(),
				);
		$m = User::save($row);
		return $m;
	}
	
	static function register_by_name($name, $password){
		$black_kws = array('admin', 'system', 'root', 'test');
		foreach($black_kws as $kw){
			if(stripos($name, $kw) !== false){
				throw new Exception("用户名包含非法词");
			}
		}
		self::validate_name($name);
		self::validate_password($password);

		$m = User::getBy('name', $name);
		if($m){
			throw new Exception('用户名已被占用');
		}
		$salt = User::gen_salt();
		$password = User::encode_password($password, $salt);
		$row = array(
				'name' => $name,
				'status' => User::STATUS_OK,
				'password' => $password,
				'salt' => $salt,
				'reg_time' => date('Y-m-d H:i:s'),
				'reg_ip' => ip(),
				);
		$m = User::save($row);
		return $m;
	}
	
	static function account_type($account){
		if(strpos($account, '@')){
			return 'email';
		}else if(preg_match('/^[0-9]{11}$/', $account)){
			return 'mobile';
		}else{
			return 'name';
		}
	}
	
	static function login($account, $password){
		$type = self::account_type($account);
		switch($type){
			case 'email':
				self::validate_email($account);
				break;
			case 'mobile':
				break;
			case 'name':
				self::validate_name($account);
				break;
		}
		$m = User::getBy($type, $account);

		if(!$m || !$m->password || !$m->salt){
			_throw("用户名或者密码错误");
		}
		if(!$m->test_password($password)){
			_throw("用户名或者密码错误");
		}
		if($m->status != 0){
			_throw("账号未激活");
		}

		$ttl = 86400 * 30;
		return self::force_login($m, $ttl);
	}
	
	static function force_login($m, $ttl=0){
		$ret = array();
		if($ttl <= 0){
			$ttl = 7 * 86400;
		}
		$expire = time() + $ttl;

		$attrs = array(
				'login_time' => date('Y-m-d H:i:s'),
				'login_expire' => date('Y-m-d H:i:s', $expire),
				'login_ip' => ip(),
				'login_token' => self::gen_login_token(),
				);
		$m->update($attrs);

		$S = "{$m->id}_{$m->login_token}";
		setcookie('S', $S, $expire, '/'/*, $domail*/);
		$_COOKIE['S'] = $S;

		$ret = self::_user_info($m);
		return $ret;
	}

	static function _user_info($m){
		$ret = array();
		$ret['id'] = $m->id;
		$ret['name'] = $m->name;
		if(strpos($m->email, 'null.axelahome.com') === false){
			$ret['email'] = $m->email;
		}else{
			$ret['email'] = '';
		}
		$ret['mobile'] = $m->mobile;
		$ret['login_time'] = $m->login_time;
		$ret['login_expire'] = $m->login_expire;
		$ret['login_ip'] = $m->login_ip;
		$ret['login_token'] = $m->login_token;
		return $ret;
	}

	static function auth(){
		$ret = array();
		$S = $_COOKIE['S'];
		$ps = explode('_', $S);
		if(count($ps) != 2){
			return $ret;
		}
		$id = intval($ps[0]);
		$token = $ps[1];
		$m = User::get($id);
		if(!$m || strlen($m->login_token) != 32 || $m->login_token !== $token){
			return $ret;
		}
		$expire = strtotime($m->login_expire);
		if($expire > 0 && $expire < time()){
			return $ret;
		}
		$ret = self::_user_info($m);
		$ret['is_bao_user'] = $m->is_bao_user();
		return $ret;
	}

	static function logout(){
		$sess = self::auth();
		if($sess){
			$m = User::get($sess['id']);
			if($m){
				$attrs = array(
						'login_time' => date('Y-m-d H:i:s'),
						'login_expire' => date('Y-m-d H:i:s'),
						'login_token' => '',
						);
				$m->update($attrs);
			}
		}
		setcookie('S', '', 1, '/'/*, $domail*/);
		$_COOKIE['S'] = '';
	}
}

