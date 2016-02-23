<?php
class WxReply
{
	private $account = null;
		
	function __construct($account){
		$this->account = $account;
	}
	
	function send_reply_list($from, $to, $reply_list, $wrap=false){
		if(!$from){
			$from = $this->account->wx_id;
		}
		/*
		if($this->account->type == WxAccount::TYPE_DY){
			return $this->imm_reply($from, $to, $reply_list);
		}else{
			return $this->async_reply($to, $reply_list, $wrap);
		}
		*/
		return $this->imm_reply($from, $to, $reply_list);
	}
	
	// 被动回复(立即回复)
	function imm_reply($from, $to, $reply_list){
		$news_arr = array();
		foreach($reply_list as $r){
			if($r['type'] == 'text'){
				if($news_arr){
					// 被动回复不支持多条不同的消息一次回复, 在保留图片的前提忽略掉文本.
					// 要相实现文本和图文混合, 只能走服务号的客服接口.
					continue;
				}
				$r['content'] = self::format_text($r['content']);
				$this->imm_reply_text($from, $to, $r['content']);
				// 文本不能一次回复多条, 所以立即结束
				return;
			}
			$r['desc'] = self::format_text($r['desc']);
			$news_arr[] = $r;
		}
		$this->imm_reply_news($from, $to, $news_arr);
	}

	function imm_reply_text($from, $to, $text){
		$str = self::text($from, $to, $text);
		echo $str;
		Logger::debug("send: " . $str);
	}

	// news_arr = [{title:'', desc:'', img_url:'', link:''}];
	function imm_reply_news($from, $to, $news_arr){
		$str = self::news($from, $to, $news_arr);
		echo $str;
		Logger::debug("send: " . $str);
	}

	function async_reply($to, $reply_list, $wrap){
		$token = $this->account->access_token();
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$token";
		$separate = array();
		$group = array();
		foreach($reply_list as $r){
			if($r['type'] == 'text'){
				$msg = array(
					'touser' => $to,
					'msgtype' => $r['type'],
					'text' => array(
						'content' => self::format_text($r['content']),
					),
				);
				$separate[] = $msg;
			}else if($r['type'] == 'news'){
				$msg = array(
					'title' => $r['title'],
					'description' => self::format_text($r['desc']),
					'url' => $r['link'],
					'picurl' => $r['img_url'],
				);
				if($wrap){
					$group[] = $msg;
				}else{
					$separate[] = $msg;
				}
			}else{
				continue;
			}
		}
		
		foreach($separate as $msg){
			$msg = array(
				'touser' => $to,
				'msgtype' => 'news',
				'news' => array(
					'articles' => array($msg),
				),
			);
			$str = Text::json_encode($msg);
			$ret = Http::post($url, $str);
			Logger::debug("send: $str, recv: $ret");
		}
		if($group){
			$msg = array(
				'touser' => $to,
				'msgtype' => 'news',
				'news' => array(
					'articles' => $group,
				),
			);
			$str = Text::json_encode($msg);
			$ret = Http::post($url, $str);
			Logger::debug("send: $str, recv: $ret");
		}
	}
	
	function default_reply($xml){
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
		$kw = WxReplyKeyword::findOne("type='equal' and (keyword='default_reply' or keyword='默认回复')");
		if($kw){
			$item = WxReplyItem::get($kw->item_id);
			$wrap = ($item->type == 'news')? true : false;
			$str = $item->content;
			$reply_list = @json_decode($str, 1);
			if($reply_list){
				$this->send_reply_list($to, $from, $reply_list, $wrap);
			}
		}else{
			$text = "消息我收到啦。\n";
			$this->imm_reply_text($to, $from, $text);
		}
	}
	
	// 如果命中自动回复, 返回 true.
	function auto_reply($xml){
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
		$type = $xml->MsgType;
		
		$item = null;
		if($type == 'text'){
			$t = trim($xml->Content);
			$t = Db::escape($t);
			$kw = WxReplyKeyword::findOne("type='equal' and keyword='$t'", 'id desc');
			if($kw){
				$item = WxReplyItem::get($kw->item_id);
			}else{
				$kws = WxReplyKeyword::find(0, 1000, "type='contain'", 'id desc');
				foreach($kws as $kw){
					if(stripos($t, $kw->keyword) !== false){
						$item = WxReplyItem::get($kw->item_id);
						break;
					}
				}
			}
		}else if($type == 'event'){
			$t = $xml->Event;
			$t = Db::escape($t);
			if($t == 'CLICK'){
				$t = $xml->EventKey;
				$t = Db::escape($t);
				$kw = WxReplyKeyword::findOne("type='click' and keyword='$t'", 'id desc');
			}else{
				$kw = WxReplyKeyword::findOne("type='event' and keyword='$t'", 'id desc');
			}
			if($kw){
				$item = WxReplyItem::get($kw->item_id);
			}
		}
		if($item){
			$wrap = ($item->type == 'news')? true : false;
			$str = $item->content;
			$reply_list = @json_decode($str, 1);
			if($reply_list){
				$this->send_reply_list($to, $from, $reply_list, $wrap);
			}
			return true;
		}
		return false;
	}
	
	static function format_text($s){
		return str_replace(
				array("\r\n\r\n", "\n\n"),
				array("\r\n", "\n"),
				$s);
	}

	static function text($from, $to, $text){
		$time = time();
		$str = ''
			. "<xml>"
			. "<ToUserName><![CDATA[$to]]></ToUserName>"
			. "<FromUserName><![CDATA[$from]]></FromUserName>"
			. "<CreateTime>$time</CreateTime>"
			. "<MsgType><![CDATA[text]]></MsgType>"
			. "<Content><![CDATA[$text]]></Content>"
			. "</xml>";
		return $str;
	}

	// news_arr = [{title:'', desc:'', img_url:'', link:''}];
	static function news($from, $to, $news_arr){
		$time = time();
		$count = count($news_arr);
		$str = ''
			. "<xml>"
			. "<ToUserName><![CDATA[$to]]></ToUserName>"
			. "<FromUserName><![CDATA[$from]]></FromUserName>"
			. "<CreateTime>$time</CreateTime>"
			. "<MsgType><![CDATA[news]]></MsgType>"
			. "<ArticleCount>$count</ArticleCount>"
			. "<Articles>";
		$sess = null;
		foreach($news_arr as $news){
			/* update @2015-05-14 23:48:22 */
			/* 这里将所有回复给微信的带有axelahome和axelahome的链接 均设置为jump形式 */
			$link = $news['link'];
			$ours_domains = array('axelahome.com');
			$do_job = false;			
			foreach ($ours_domains as $domain) {
				if(preg_match("/^http(s)?:\/\/[^\/]*$domain\/weixin\/jump/", $link)) {
					continue;
				}
				if(preg_match("/^http(s)?:\/\/[^\/]*$domain\/weixin\/oauth/", $link)) {
					continue;
				}
				if(preg_match("/^http(s)?:\/\/[^\/]*$domain\//", $link)) {
					$do_job = true;
					break;
				}
			}
			if ($do_job) {
				$link = 'https://axelahome.com/weixin/oauth?jump=' . urlencode($link);
			}
			
			$str .= "<item>"
			. "<Title><![CDATA[{$news['title']}]]></Title> "
			. "<Description><![CDATA[{$news['desc']}]]></Description>"
			. "<PicUrl><![CDATA[{$news['img_url']}]]></PicUrl>"
			. "<Url><![CDATA[{$link}]]></Url>"
			. "</item>";
		}
		$str .= "</Articles>"
			. "</xml>";
		return $str;
	}
	
	function reply_channel_subscribe($xml) {
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
		$type = $xml->MsgType;
		$event = strtolower($xml->Event);
		$event_key = $xml->EventKey;
		$ticket = $xml->Ticket;
		
		$in_event = array(
				WxReplyKeyword::EVENT_TYPE_SCAN,
				WxReplyKeyword::EVENT_TYPE_SUBSCRIBE,
		);
		
		if ($type == WxReplyKeyword::KEYWORD_TYPE_EVENT && in_array($event, $in_event)) {
			$channel_info = WxReplyKeyword::check_wechat_subscribe_scene_id($event_key);
			if (!$channel_info) {
				return false;
			}
			
			$channel_id = $channel_info->id;
			$wx_channel_info = WxChannelInfo::get($channel_id);
			if (!$wx_channel_info || $wx_channel_info->status != WxChannelInfo::STATUS_YES || $wx_channel_info->qrcode_ticket != $ticket) {
				return false;
			}
			
			$keyword = WxReplyKeyword::CHANNEL_SUBSCRIBE_PREFIE . $channel_id;
			$where = "type = '{$type}' and keyword = '{$keyword}'";
			$kw = WxReplyKeyword::findOne($where, 'id desc');
			if ($kw) {
				$item = WxReplyItem::get($kw->item_id);
				if ($item) {
					$wrap = ($item->type == 'news') ? true : false;
					$str = $item->content;
					$reply_list = @json_decode($str, true);
					if ($reply_list) {
						$this->send_reply_list($to, $from, $reply_list, $wrap);
					}
					Logger::info("扫描渠道关注二维码, 粉丝关注事件, 自动回复成功, 微信openid : {$from} ,服务号微信id {$to} 渠道ID {$channel_info->id}");
					return true;
				}
			}
		}
		return false;
	}
}