<?php
class Contract extends Model
{
	static $table_name = 'contracts';
	const STATUS_NEW    		= 0;
	const STATUS_DELETED    	= 1;

	static function status_table(){
		static $s = array(
			self::STATUS_NEW      => '正常', 
			self::STATUS_DELETED  => '删除', 
		);
		return $s;
	}

	function status_text(){
		$s = self::status_table();
		return $s[$this->status];
	}
	
	function set_del(){
		$this->update(array(
			'status' => self::STATUS_DELETED,
			));
		Logger::info("设置合约删除 {$this->id}");
	}


}
