<?php
class WxReplyItem extends Model
{
	static $table_name = 'wx_reply_items';
	
	/*
	type: text, news, mixed
	text: 文本消息
	news: 折叠的全图文消息
	mixed: 单独发送的多条消息, 每一条可以是文本或者图文
	*/
	
	function keywords(){
		return WxReplyKeyword::find(0, 100, "item_id='{$this->id}'");
	}
	
	function reset_keywords($new_kws_str, $type='equal'){
		$ks = $this->keywords();
		$old_kws = array();
		foreach($ks as $k){
			$old_kws[] = $k->keyword;
		}
		
		$ps = explode(',', $new_kws_str);
		$kws = array();
		foreach($ps as $p){
			$p = trim($p);
			if(strlen($p)){
				$kws[$p] = $p;
			}
		}
			
		$to_del = array_diff($old_kws, $kws);
		foreach($to_del as $k){
			Db::escape($k);
			$sql = "delete from wx_reply_keywords where item_id='{$this->id}' and keyword='$k'";
			Db::query($sql);
		}
		$to_add = array_diff($kws, $old_kws);
		foreach($to_add as $k){
			WxReplyKeyword::save(array(
				'type' => $type,
				'keyword' => $k,
				'item_id' => $this->id,
			));
		}
		$sql = "update " . WxReplyKeyword::table() . " set type='$type' where item_id='{$this->id}'";
		Db::update($sql);
	}
}
