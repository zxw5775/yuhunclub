<?php
class WxReplyKeyword extends Model
{
	static $table_name = 'wx_reply_keywords';
	
	const EVENT_TYPE_SCAN = 'scan';
	const EVENT_TYPE_SUBSCRIBE = 'subscribe';
	const CHANNEL_SUBSCRIBE_PREFIE = 'channel_subscribe_';
	const QRCODE_SUBSCRIBE_EVENTKEY_PREFIX = 'qrscene_';
	
	const KEYWORD_TYPE_EQUAL   = 'equal';
	const KEYWORD_TYPE_CLICK   = 'click';
	const KEYWORD_TYPE_EVENT   = 'event';
	const KEYWORD_TYPE_CONTAIN = 'contain';
	
	static $keyword_type_table = array(
			self::KEYWORD_TYPE_EQUAL => '关键词 - 全匹配',
			self::KEYWORD_TYPE_CONTAIN => '关键词 - 模糊匹配',
			self::KEYWORD_TYPE_CLICK => '菜单点击',
			self::KEYWORD_TYPE_EVENT => '事件',
	);
	static function check_wechat_subscribe_scene_id($event_key) {
		$channel_info = null;
		$channel_pattern = '#' . self::QRCODE_SUBSCRIBE_EVENTKEY_PREFIX . '([a-zA-Z0-9]+)#';
		if (preg_match($channel_pattern, $event_key, $arr)) {
			if (isset($arr[1])) {
				$channel_id = $arr[1];
				// 先尝试根据pcode取
				$channel_info = Channel::get_by_code($channel_id);
				if (!$channel_info) {
					$channel_info = Channel::get($channel_id);
				}
			}
		}
	
		return $channel_info;
	}
	// 检测某关键词是否是渠道关注事件，如果是，则返回渠道信息
	static function check_channel_subscribe_keyword($keyword) {
		$channel_info = null;
		$channel_pattern = '#' . self::CHANNEL_SUBSCRIBE_PREFIE . '([a-zA-Z0-9]+)#';
		if (preg_match($channel_pattern, $keyword, $arr)) {
			if (isset($arr[1])) {
				$channel_id = $arr[1];
				// 先尝试根据pcode取
				$channel_info = Channel::get_by_code($channel_id);
				if (!$channel_info) {
					$channel_info = Channel::get($channel_id);
				}
			}
		}
		
		return $channel_info;
	}
}
