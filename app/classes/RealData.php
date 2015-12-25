<?php
class RealData{
	const SSDB_KEY = 'ASHR_REAL_DATA';
	
	static function set_cache($amount, $date){
		$data = array(
			'amount'  => $amount,
			'date'    => $date,
		);
		try{
			$ssdb = Util::ssdb();
			if(false === $ssdb->set(self::SSDB_KEY, json_encode($data))){
				_throw("写入实时数据缓存失败!");
			}
		}catch (Exception $e){
			Logger::error("写入实时数据缓存失败: [ amount: $amount , date: $date ], 错误信息" . $e->getMessage());
			return false;
		}
		return true;
	}
	
	static function get_cache(){
		$re = array();
		try{
			$ssdb = Util::ssdb();
			$re = $ssdb->get(self::SSDB_KEY);
			if(false === $re){
				_throw("获取实时数据缓存失败!");
			}
		} catch (Exception $e) {
			Logger::error("获取实时数据缓存失败 错误信息" . $e->getMessage());
			return false;
		}
		if($re){
			$re = json_decode($re, true);
		}
		return $re;
	}
}

