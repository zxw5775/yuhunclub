<?php
class Tactics extends Model
{
	static $table_name = 'tactics';
	const STATUS_NEW    	= 0;
	const STATUS_SENED    	= 1;
	const STATUS_DELETED	= 2;

	static function status_table(){
		static $s = array(
			self::STATUS_NEW      => '正常', 
			self::STATUS_DELETED  => '已发送告警', 
			self::STATUS_DELETED  => '删除', 
		);
		return $s;
	}

	function status_text(){
		$s = self::status_table();
		return $s[$this->status];
	}
	
	function set_new(){
		$this->update(array(
			'status' => self::STATUS_NEW,
			));
		Logger::info("设置策略新建 {$this->id}");
	}
	
	function set_send(){
		$this->update(array(
			'status' => self::STATUS_SENED,
			));
		Logger::info("设置策略已经发送告警 {$this->id}");
	}
	
	function set_del(){
		$this->update(array(
			'status' => self::STATUS_DELETED,
			));
		Logger::info("设置策略删除 {$this->id}");
	}


}
