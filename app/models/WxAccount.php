<?php
class WxAccount extends Model
{
	const TYPE_DY	= 0;
	const TYPE_FW	= 1;
	const TYPE_QIYE	= 2;
	const DEFAULT_WXACCOUNT_ID = 1; // 懒投资小助手
	
	const DAWANJIA_QIYE_DATA_CENTER_ID = 3; // 大玩家企业 - 懒投资数据中心id
	
	static $the_staff_ids = array(
			self::DAWANJIA_QIYE_DATA_CENTER_ID,
	);
	
	protected $noncestr;
			
	static $table_name = 'wx_accounts';

	static function type_table(){
		static $map = array(
			self::TYPE_DY => '订阅号',
			self::TYPE_FW => '服务号',
			self::TYPE_QIYE => '内部企业号',
			);
		return $map;
	}

	function type_text(){
		$map = self::type_table();
		return $map[$this->type];
	}

	static function status_table(){
		static $map = array(
			0 => '正常',
			1 => '禁用',
			);
		return $map;
	}

	function status_text(){
		$map = self::status_table();
		return $map[$this->status];
	}
	
	function get_access_token(){
		$cache_key = "wx_access_token|{$this->app_id}";
		$token = Util::ssdb()->get($cache_key);
		return $token;
	}
	
	function get_js_token(){
		$cache_key = "wx_js_token|{$this->app_id}";
		$token = Util::ssdb()->get($cache_key);
		return $token;
	}

	function refresh_access_token(){
		$cache_key = "wx_access_token|{$this->app_id}";
		$urlbase = 'https://api.weixin.qq.com/cgi-bin/token';
		$url = "$urlbase?grant_type=client_credential&appid={$this->app_id}&secret={$this->app_secret}";
		$str = Http::get($url);
		Logger::info("[token]weixin get_access_token response: $str");
		$res = json_decode($str, true);
		$token = $res['access_token'];
		if($token){
			Util::ssdb()->set($cache_key, $token);
		}else{
			Logger::error("refresh_access_token failed! $str");
		}
		return $token;
	}
	
	function refresh_js_token($debug_access_token=''){
		$cache_key = "wx_js_token|{$this->app_id}";
		$urlbase = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
		if($debug_access_token && ENV == 'dev'){
			$access_token = $debug_access_token;
		}else{
			$access_token = $this->access_token();
		}
		$url = "$urlbase?access_token={$access_token}&type=jsapi";
		$str = Http::get($url);
		Logger::info("[token]weixin get_js_token response: $str");
		$res = json_decode($str, true);
		$token = $res['ticket'];
		if($token){
			Util::ssdb()->set($cache_key, $token);
		}else{
			Logger::error("refresh_js_token failed! $str");
		}
		return $token;
	}
	
	// 获取 token
	function access_token(){
		$cache_key = "wx_access_token|{$this->app_id}";
		$token = Util::ssdb()->get($cache_key);
		if($token === null || strlen($token) == 0){
			$token = $this->refresh_access_token();
		}
		return $token;
	}
	
	// 获取 js token
	function js_token(){
		$cache_key = "wx_js_token|{$this->app_id}";
		$token = Util::ssdb()->get($cache_key);
		if($token === null || strlen($token) == 0){
			$token = $this->refresh_js_token();
		}
		return $token;
	}
	
	function sign($url){
		$noncestr  = WxAccount::generate_randstr();
		$timestamp = time();
		$js_token  = $this->js_token();
		
		$data = array(
			'jsapi_ticket='.$js_token,
			'noncestr='.$noncestr,
			'timestamp='.$timestamp,
			'url='.$url,
		);
		$sign = sha1(implode('&', $data));
		
		return array(
			'noncestr'  => $noncestr,
			'timestamp' => $timestamp,
			'sign'      => $sign,
		);
	}
	
	static function generate_randstr($length=16){
		$basestring = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$noncestr = '';
		$_len = strlen($basestring);
		for($i=1;$i<=$length;$i++){
			$noncestr .= $basestring[rand(0, $_len-1)];
		}
		return $noncestr;
	}
	
}
