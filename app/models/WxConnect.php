<?php
class WxConnect extends Model
{
	static $table_name = 'wx_connects';

	const WX_SUBSCRIBED   = 1;
	const WX_UNSUBSCRIBED = 0;

	const PRJ_SUBSCRIBED   = 1;
	const PRJ_UNSUBSCRIBED = 0;
	
	static function wx_sub_table(){
		return array(
			self::WX_SUBSCRIBED   => '是',
			self::WX_UNSUBSCRIBED => '否',
		);
	}
	
	static function prj_sub_table(){
		return array(
			self::PRJ_SUBSCRIBED   => '是',
			self::PRJ_UNSUBSCRIBED => '否',
		);
	}
	
	static function bind($uid, $wx_openid){
		if(!$uid || !$wx_openid){
			return;
		}
		$wx_user_connect = WxConnect::get_by('user_id', $uid);
		if($wx_user_connect){
			return;
		}
		$connect = WxConnect::get_by('wx_openid', $wx_openid);
		if($connect){
			return;
		}
		
		$attrs = array(
				'time'          => date('Y-m-d H:i:s'),
				'user_id'       => $uid,
				'wx_openid'     => $wx_openid,
				'prj_subscribe' => WxConnect::PRJ_SUBSCRIBED,
				'wx_subscribe'  => WxConnect::WX_SUBSCRIBED,
		);
		WxConnect::save($attrs);
		Logger::info("自动绑定微信账号成功 uid : {$uid} openid : {$wx_openid}");
		return true;
	}

	static function list_user($page, $size, $where = '') {
		if ($page < 1) {
			$page = 1;
		}
		if(strlen($where)){
			$where = " where 1 and $where";
		}

		$start = ($page - 1) * $size;
		$limit = "limit $start, $size";

		$sql = "select user_id from " . self::table();
		$sql .= " {$where} {$limit}";
		$ret = Db::find($sql);
		return $ret;
	}

	static function get_count($where = '') {
		if(strlen($where)){
			$where = " where 1 and $where";
		}
		$sql = "select count(*) from " . WxConnect::table() . $where;
		$count = Db::get_num($sql);
		return $count;
	}
}
