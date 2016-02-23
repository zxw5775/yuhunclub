<?php
class WxOpenid extends Model
{
	static $table_name = 'wx_openids';

	const STATUS_NO = 0;
	const STATUS_YES = 1;
	
	static $status_table = array(
			self::STATUS_YES => '已关注',
			self::STATUS_NO => '未关注',
	);
	
	function status_text(){
		return self::$status_table[$this->status];
	}
	
	// 未绑定账号用户 1. 该表status = 1的 2. connects表wx_subsrcibe为0的
	static function get_no_bind_users($wx_id) {
		$self_table = self::$table_name;
		$wx_connect_table = WxConnect::table();
		
		$sql = "select {$self_table}.wx_openid from {$self_table}";
		$sql .= " left join {$wx_connect_table}";
		$sql .= " on {$self_table}.wx_openid = {$wx_connect_table}.wx_openid";
		$sql .= " where {$self_table}.wx_id = '{$wx_id}'";
		$sql .= " and {$self_table}.status = " . self::STATUS_YES;
		$sql .= " and {$wx_connect_table}.wx_openid is null";
		return Db::find($sql);
	}
}
