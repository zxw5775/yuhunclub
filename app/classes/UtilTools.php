<?php

class UtilTools{
	const RETRY_COUNT = 3;
	static function getHtml($url){
		$html = '';
		for($retry_count=0; $retry_count < self::RETRY_COUNT; $retry_count++){
			try{
				$html = file_get_contents($url);
			}catch(Exception $e){
				if($retry_count < self::RETRY_COUNT-1){
					sleep(3);//休眠3秒
					continue;
				}
				Logger::error("抓取YAHOO数据失败 错误信息" . $e->getMessage());
				return false;
			}
			if(!$html && $retry_count < self::RETRY_COUNT-1){
				sleep(3);//休眠3秒
				continue;
			}
			break;
		}
		if(!$html){
			Logger::error('抓取YAHOO数据失败，返回数据空');
			return false;
		}
		return $html;
	}
}

