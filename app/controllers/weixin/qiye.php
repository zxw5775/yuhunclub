<?php
require_once(dirname(__FILE__) . '/../../classes/WxQiye/WXBizMsgCrypt.php');

// 封装内部消息接口
class QiyeController extends Controller
{
	/* 定义内部员工特定关键字和事件 */
	const STAFF_KW_RCTJ = 0;
	static $the_staff_keywords = array(
			self::STAFF_KW_RCTJ => '日常统计', 
	);
	const STAFF_EVENT_RCTJ = 0;
	static $the_staff_events = array(
			self::STAFF_EVENT_RCTJ => 'richang_tongji', 
	);
	
	private $wx_account = null;
	private $wx_cpt_utl = null;
	
	function init($ctx){
		$this->layout = false;
	}
	
	function index($ctx){
		$this->view($ctx);
	}

	function view($ctx){
		App::set_base_url('https://axelahome.com/');
		
		$id = intval($_GET['id']);
		$this->wx_account = WxQiyeAccount::get($id);
		if (!$this->wx_account) {
			return;
		}
		
		$this->wx_cpt_utl = new WxQiyeUtil($this->wx_account);
		
		$msg_signature = isset($_GET['msg_signature']) ? $_GET['msg_signature'] : '';
		$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : '';
		$nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
		$this->wx_cpt_utl->init_meta_params($msg_signature, $timestamp, $nonce);
		
		/* 如果是验证url */
		if (isset($_GET["echostr"]) && $_GET["echostr"]) {
			echo $this->wx_cpt_utl->validate_url(trim($_GET['echostr']));
			return;
		}
		/* 验证url结束 */
		
		/* 下面是加密解密消息体的逻辑 兼容dev环境 */
		$str = $GLOBALS["HTTP_RAW_POST_DATA"]; // 接收到的原生POST数据
		$msg = ''; // 解析后的明文
		Logger::debug("weixin qiye recv: " . $str);
				
		/* 验证消息 */
		if (ENV == 'dev' && isset($_GET['debug'])) {
			// dev模式下 hook
			$msg = "
				<xml>
				<ToUserName><![CDATA[{$this->wx_account->corp_id}]]></ToUserName>
				<FromUserName><![CDATA[{$_GET['user']}]]></FromUserName>
				<CreateTime>1451968899</CreateTime>
				<MsgType><![CDATA[text]]></MsgType>
				<Content><![CDATA[{$_GET['text']}]]></Content>
				<MsgId>4368083058310512650</MsgId>
				<AgentID>{$this->wx_account->agent_id}</AgentID>
				</xml>
			";
		} else {
			$msg = $this->wx_cpt_utl->decrypt($str);
		}
		
		if (!$msg) {
			return;
		}
		
		$xml = Text::xml_to_obj($msg);
		if (!$xml) {
			Logger::error("bad data from weixin qiye : {$str}");
			return;
		}
		if ($xml->AgentID != $this->wx_account->agent_id) {
			Logger::error("推送的agent_id与配置的agent_id不一致 来自微信的 {$xml->AgentID} ;配置的agent_id {$this->wx_account->agent_id}");
		}
		
		// 模板消息 直接忽略
		if ($xml->MsgType == 'event' && $xml->Event == 'TEMPLATESENDJOBFINISH') {
			return;
		}
		
		// 初始化xml
		$this->wx_cpt_utl->init_xml($xml);
		
		// 根据应用（agent_id）进行回复
		$agent_id = intval($xml->AgentID);
		$text = '';
		switch ($agent_id) {
			case WxQiyeUtil::AGENT_ID_axelahome_DATA_CENTER:
				// 懒投资数据中心的应用
				$text = $this->do_lantozui_data_center($xml);
				break;
			default:
				return;
		}
		$text = trim($text);
		if (!$text) {
			return;
		}
		
		// 回复一个文本消息
		$this->wx_cpt_utl->reply_text($text);
	}
	
	// 懒投资 数据中心的回复
	private function do_lantozui_data_center($xml) {
		$type = $xml->MsgType;
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
				
		$content = '暂未支持该关键词回复哦';
		if ($type == 'text') {
			$text = trim('' . $xml->Content);
			if (in_array($text, self::$the_staff_keywords)) {
				switch ($text) {
					case self::STAFF_KW_RCTJ:
						$content = StaffUtil::get_richang_statis(date('Y-m-d'));
						break;
					default:
						break;
				}
			}
		} elseif ($type == 'event') {
			$e = strtolower($xml->Event);
			if($e == 'click') {
				$event = trim(''.$xml->EventKey);
				if (in_array($event, self::$the_staff_events)) {
					switch ($text) {
						case self::STAFF_EVENT_RCTJ:
							$content = StaffUtil::get_richang_statis(date('Y-m-d'));
							break;
						default:
							break;
					}
				}
			}
		}
		return $content;
	}
}
