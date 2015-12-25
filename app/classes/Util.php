<?php
class Util
{
	static function ssdb(){
		static $ssdb = null;
		if($ssdb === null){
			$conf = App::$config['ssdb'];
			if(!$conf){
				return null;
			}
			try{
				$ssdb = new SimpleSSDB($conf['host'], $conf['port']);
			}catch(Exception $e){
				Logger::error("$e");
				_throw("ssdb error");
			}
		}
		return $ssdb;
	}
	
	static function days_to_months($days){
		return round($days/30);
	}
	
	static function to_array($obj, $keys) {
		$arr = array();
		foreach ($keys as $key) {
			$arr[$key] = isset($obj->$key) ? $obj->$key : '';
		}
	
		return $arr;
	}
	
	static function validate_date($date) {
		$d = DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') == $date;
	}
	static function validate_datetime($datetime) {
		$d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
		return $d && $d->format('Y-m-d H:i:s') == $datetime;
	}
	
	static function fake_user_name($uname) {
		return substr_replace($uname, '***', 1, -1);
	}
}
