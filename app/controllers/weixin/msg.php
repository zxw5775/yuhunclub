<?php
// 消息接口
class MsgController extends Controller
{
	/* 定义内部员工uid  可通过特定关键字得到回复 */
	static $the_staff_uids = array(
			'1', // wzy
			'505335', // cyy
			'500016', // zl
			'505258', // ly
			'507648', // mj
// 			'505178', // ll
// 			'500020', // xxb
	);
	/* 定义内部员工特定关键字 */
	const STAFF_KW_RCTJ = 0;
	static $the_staff_keywords = array(
			self::STAFF_KW_RCTJ => '日常统计', 
	);
	
	private $wx_account = null;
	private $wx_reply = null;
	private $user_profile = null;

	const BIND_STATUS_NEW      = 0;
	const BIND_STATUS_UNVERIFY = 1;
	const BIND_STATUS_VERIFYED = 2;

	private function check_sign(){
		if(ENV == 'dev'){
			return true;
		}
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
		$token = $this->wx_account->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			if($signature){
				Logger::error("weixin signature error! " . Text::json_encode($_GET));
			}
			return false;
		}
	}

	function init($ctx){
		$this->layout = false;
	}

	function index($ctx){
		$str = $GLOBALS["HTTP_RAW_POST_DATA"];
		if(strpos($str, '<Event><![CDATA[TEMPLATESENDJOBFINISH]]></Event>') !== false){
			return;
		}
		$this->view($ctx);
	}

	function view($ctx){
		App::set_base_url('https://axelahome.com/');
		$id = intval($_GET['id']);
		$this->wx_account = WxAccount::get($id);
		if(!$this->wx_account){
			return;
		}
		$this->wx_reply = new WxReply($this->wx_account);

		if($_GET["echostr"]){
			return $this->validate_wx($ctx);
		}

		if(!$this->check_sign()){
			return;
		}
		// TEST
		if(ENV == 'dev'){
			if ($_GET['debug']) {
				$_s = "<xml>
 <ToUserName><![CDATA[{$this->wx_account->wx_id}]]></ToUserName>
 <FromUserName><![CDATA[{$_GET['user']}]]></FromUserName>
 <CreateTime>1348831860</CreateTime>
 <MsgType><![CDATA[text]]></MsgType>
 <Content><![CDATA[{$_GET['text']}]]></Content>
 <MsgId>1234567890123456</MsgId>
 </xml>";
				$GLOBALS["HTTP_RAW_POST_DATA"] = $_s;
			} elseif($_GET['content']) {
				$c = $_GET['content'];
				$GLOBALS["HTTP_RAW_POST_DATA"] = "<xml><ToUserName>{$this->wx_account->wx_id}</ToUserName><MsgType>text</MsgType><Content>$c</Content></xml>";
			}
		}
		$str = $GLOBALS["HTTP_RAW_POST_DATA"];
		
		Logger::debug("recv: " . $str);
		$xml = Text::xml_to_obj($str);
		if(!$xml){
			Logger::error("bad data from weixin: $str");
			return;
		}
		
		if($xml->MsgType == 'event' && $xml->Event == 'TEMPLATESENDJOBFINISH'){
			return;
		}
		if($xml->MsgType == 'event' && $xml->Event == 'unsubscribe'){
			return;
		}

		// 首先, 进行程序回复
		if($id == 1){
			if($this->route($xml) === true){
				return;
			}
		}

		// 其次, 尝试自动回复
		if($this->wx_reply->auto_reply($xml) === true){
			return;
		}

		$this->default_reply($xml);
	}

	private function default_reply($xml){
		$ssdb = Util::ssdb();
		if(!$ssdb){
			return;
		}
		$from = $xml->FromUserName ;

		$default_reply_key = "wx_dft_reply|$from";
		if($ssdb->exists($default_reply_key)){
			if(ENV != 'dev'){
				return;
			}
		}
		$ssdb->setx($default_reply_key, '1', 3600);

		$this->wx_reply->default_reply($xml);
	}

	private function route($xml){
		$type = $xml->MsgType;
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;

		if($type == 'text'){
			$text = trim('' . $xml->Content);
			if($text == '取消项目订阅'){
				$this->projects_unsubscribe($xml);
				return true;
			} elseif($text == '订阅'){
				$this->projects_subscribe($xml);
				return true;
			} elseif ($text == '绑定') {
				$this->bind($xml);
				return true;
			} elseif ($text == '取消绑定') {
				$this->unbind($xml);
				return true;
			} elseif ($text == '我要投资') {
				$this->to_buy($xml);
				return true;
			} elseif ($text == '邀请好友') {
				$this->invite_link($xml);
				return true;
			} elseif (array_key_exists($text, EdaixiCoupon::get_infoid_prize_text(EdaixiCoupon::INFO_TOUR))) {
				$this->event_tour($xml);
				return true;
			} elseif ($text == '爱奇艺' || $text == '乐视') {
				$this->event_year_end($xml);
				return true;
			} elseif ($text == '净值') {
				$this->apollo($xml);
				return true;
			} elseif (in_array($text, self::$the_staff_keywords)) {
				$this->do_the_staff_reply($xml);
				return true;
			} elseif(substr($text, 0, DragonpassCode::CODE_PREFIX_COUNT) == "LT") {
				//激活龙腾卡逻辑
				$this->active_dragonpass($xml);
				return true;
			}
		}else if($type == 'event'){
			$e = $xml->Event;
			if($e == 'subscribe'){
				$this->subscribe($xml);
				return true;
			}
			if($e == 'SCAN'){
				return $this->scan($xml);
			}
			if($e == 'unsubscribe'){
				$this->unsubscribe($xml);
				return true;
			}
			if($e == 'CLICK'){
				$key = trim(''.$xml->EventKey);
				switch ($key) {
					case '我要投资':
						$this->to_buy($xml);
						break;
					case 'PROJECT_ALL':
						$this->projects_all($xml);
						break;
					case 'PROJECT_PRE':
						$this->projects_pre($xml);
						break;
					case 'PROJECT_OPEN':
						$this->projects_open($xml);
						break;
					case 'ACCOUNT_SUMMARY':
						$this->account_summary($xml);
						break;
					case 'DATALIST_ORDER':
						$this->datalist_order($xml);
						break;
					case 'DATALIST_TRADE':
						$this->datalist_trade($xml);
						break;
					case 'USER_INFO':
						$this->user_info($xml);
						break;
					case 'PROJECTS_SUBSCRIBE':
						$this->projects_subscribe($xml);
						break;
					case 'BIND':
						$this->bind($xml);
						break;
					case 'UNBIND':
						$this->unbind($xml);
						break;
					case '邀请好友':
						$this->invite_link($xml);
						break;
					case 'RECHARGE': 	//充值
						$this->recharge($xml);
						break;
					case 'WITHDRAW':		//提现
						$this->withdraw($xml);
						break;
					case 'INVITE_LINK':
						$this->invite_link($xml);	//邀请好友
						break;
					default:
						return;
				}
				return true;
			}
		}
	}

	// 扫描事件 非关注事件
	private function scan($xml){
		/* START 带二维码的扫描事件 需要特殊处理 add@2015-07-07 */
		// 需要增加scan需要带上场景值
		$xml->EventKey = WxReplyKeyword::QRCODE_SUBSCRIBE_EVENTKEY_PREFIX . $xml->EventKey;
		$re = $this->wx_reply->reply_channel_subscribe($xml);
		
		// 如果渠道没有配置图文，默认推送订阅的图文
		if (!$re) {
			$xml->Event = 'subscribe';
			$re = $this->wx_reply->auto_reply($xml);
		}
		
		return $re;
	}
	
	private function subscribe($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$connect = WxConnect::get_by('wx_openid', $from);
		if($connect && $connect->wx_subscribe != WxConnect::WX_SUBSCRIBED){
			$connect->update(array(
				'wx_subscribe' => WxConnect::WX_SUBSCRIBED,
			));
		}
		
		/* START 增加逻辑，订阅后，尝试向wx_openids插入 add@2015-04-20 21:49:12 */
		try {
			$event_key = trim($xml->EventKey); // 如果是二维码 会有该值
			
			$tmp_where = "wx_openid = '{$from}' and wx_id = '{$to}'";
			$wx_account_info = WxAccount::get_by('wx_id', $to);
			$wx_openid_info = WxOpenid::find_one($tmp_where);
			if (!$wx_openid_info) {
				$attrs = array(
						'wx_openid' => $from,
						'wx_id' => $to,
						'status' => WxOpenid::STATUS_YES,
						'source' => $event_key,
				);
				$ret = WxOpenid::save($attrs);
				Logger::info("粉丝关注事件, 微信订阅服务号处理, 保存微信粉丝openid为[已关注], 微信openid : {$from} ,服务号微信id {$to}");
			} else {
				if ($wx_openid_info->status != WxOpenid::STATUS_YES) {
					$attrs = array(
							'status' => WxOpenid::STATUS_YES,
							'source' => $event_key,
					);
				} else {
					if ($wx_openid_info->source != $event_key) {
						$attrs = array(
								'source' => $event_key,
						);
					}
				}
				if($attrs){
					$ret = $wx_openid_info->update($attrs);
				}
				Logger::info("粉丝关注事件, 微信订阅服务号处理, 更新微信粉丝openid状态为[已关注], 微信openid : {$from} ,服务号微信id {$to} 来源 {$event_key}");
			}
		} catch (Exception $e) {
			$msg = $e->getMessage();
			if (false === strpos($msg, 'Duplicate entry')) {
				Logger::error("粉丝关注事件, 微信订阅服务号处理, 处理微信粉丝openid发生错误, 微信openid : {$from} ,服务号微信id {$to} .异常信息 - " . $e);
			}
		}
		/* -- END -- */
		
		/* START 带二维码的关注事件 需要特殊处理 add@2015-04-27 19:42:53 */
		$re = $this->wx_reply->reply_channel_subscribe($xml);
		if (true === $re) {
			return;
		}
		
		if($this->wx_reply->auto_reply($xml) === true){
			return;
		}
		
		/* -- END -- */
		$url = _url('weixin/bind');
		$register_url = _url('register');
		$app_down_url = _url('mobile/download');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}
		$news_arr = array(
			array(
				'title'   => '首投返现金，满额送豪礼',
				'img_url' => 'https://s1.axelahome.com/img/201506/zt/xinshoukuanghuan/900-500.png',
				'link'    => 'https://axelahome.com/zt/xinshoukuanghuan',
			),
			array(
				'title'   => '【1】绑定懒投资账号',
				'img_url' => 'https://s1.axelahome.com/weixin/zidong/subscribe_zhuan.jpg',
				'link'    => $url,
			),
			array(
				'title'   => '【2】新用户注册',
				'img_url' => 'https://s1.axelahome.com/weixin/zidong/subscribe_da.jpg',
				'link'    => $register_url,
			),
			array(
				'title'   => '【3】APP下载',
				'img_url' => 'https://s1.axelahome.com/weixin/zidong/subscribe_qian.jpg',
				'link'    => $app_down_url,
			),
		);
		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	private function unsubscribe($xml){
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;

		$connect = WxConnect::get_by('wx_openid', $from);
		if($connect){
			$connect->update(array(
				'wx_subscribe'  => WxConnect::WX_UNSUBSCRIBED,
				'prj_subscribe' => WxConnect::PRJ_UNSUBSCRIBED,
			));
		}
		
		/* START 增加逻辑，订阅后，尝试向wx_openids插入 add@2015-04-20 21:49:12 */
		try {
			$uid = isset($connect->user_id) ? $connect->user_id : '';
			$uid_txt = $uid ? "用户id {$uid}" : '';
			
			$tmp_where = "wx_openid = '{$from}' and wx_id = '{$to}'";
			$wx_account_info = WxAccount::get_by('wx_id', $to);
			$wx_openid_info = WxOpenid::find_one($tmp_where);
			
			if ($wx_openid_info && $wx_openid_info->status == WxOpenid::STATUS_YES) {
				$attrs = array(
						'status' => WxOpenid::STATUS_NO,
				);
				$ret = $wx_openid_info->update($attrs);
				Logger::info("粉丝取消关注事件, 微信订阅服务号处理, 更新微信粉丝openid状态为[未关注], 微信openid : {$from} ,服务号微信id {$to} {$uid_txt}");
			}
		} catch (Exception $e) {
			Logger::error("粉丝取消关注事件, 微信订阅服务号处理, 处理微信粉丝openid发生错误, 微信openid : {$from} ,服务号微信id {$to} {$uid_txt} 异常信息 - " . $e);
		}
		/* -- END -- */
		
		Logger::info($connect->user_id . "取消关注微信公众号");
	}

	private function get_uid($xml){
		$from = $xml->FromUserName;

		$connect = WxConnect::get_by('wx_openid', $from);
		if(!$connect){
			return null;
		}
		$uid = $connect->user_id;
		return $uid;
	}

	/*用户绑定状态*/
	private function get_bind_status($xml){
		$from = $xml->FromUserName;
		$uid  = $this->get_uid($xml);
		if(!$uid){
			return self::BIND_STATUS_NEW;
		}

		$profile = Profile::get($uid);
		$this->user_profile = $profile;
		if($profile->realname_status == Profile::REAL_NAME_VERIFIED){
			return self::BIND_STATUS_VERIFYED;
		}
		return self::BIND_STATUS_UNVERIFY;
	}

	/*绑定懒投资与微信账号关系*/
	private function bind($xml){
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;

		$bind_status = $this->get_bind_status($xml);
		if($bind_status == self::BIND_STATUS_NEW){
			$url = _url('weixin/bind');
			if(ENV == 'dev'){
				$url = preg_replace('/^https/', 'http', $url);
			}
			$news_arr = array(
				array(
					'title'   => '立即绑定懒投资账号，享受更多优质服务。',
					'img_url' => 'http://s1.axelahome.com/img/prj/wechat/bind.jpg',
					'link'    => $url,
				),
			);
			$this->wx_reply->imm_reply_news($to, $from, $news_arr);
			return;
		}
		$profile = $this->user_profile;
		$name = $profile->name;
		if($bind_status == self::BIND_STATUS_VERIFYED){
			$url = _url('project');
			$url = 'https://axelahome.com/weixin/oauth?jump=' . urlencode($url);
			$this->wx_reply->imm_reply_text($to, $from, "尊敬的" . $name . "，您已经成功绑定微信服务。\n\n<a href=\"" . $url . "\">点击开始投资</a>");
			return;
		}
		if($bind_status == self::BIND_STATUS_UNVERIFY){
			$url = 'https://axelahome.com/weixin/oauth?jump=' . urlencode($url);
			$this->wx_reply->imm_reply_text($to, $from, "尊敬的" . $name . "，您已经成功绑定微信服务。\n\n<a href=\"" . $url . "\">点击开通资金托管账户</a>");
			return;
		}
	}

	private function unbind($xml){
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
		$connect = WxConnect::get_by('wx_openid', $from);
		if($connect){
			$profile = Profile::get($connect->user_id);
			$name = $profile->name;
			WxConnect::delete($connect->id);
			$this->wx_reply->imm_reply_text($to, $from, "尊敬的" . $name . "，您已经成功取消微信账号和懒投资账号绑定。");
		}else{
			$this->wx_reply->imm_reply_text($to, $from, "尊敬的用户，您未绑定微信账号和懒投资账号。");
		}
	}

	/*绑定关系前置检查*/
	private function bind_check($xml){
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;

		$bind_status = $this->get_bind_status($xml);
		if($bind_status == self::BIND_STATUS_NEW){
			$bind_url = _url('/weixin/bind');
			$register_url = _url('/register');
			$this->wx_reply->imm_reply_text($to, $from, "很抱歉，您尚未绑定微信账号。\n\n已有懒投资账户，请<a href=\"" . $bind_url . "\">点击这里</a>绑定\n\n新用户请<a href=\"" . $register_url . "\">点击这里</a>注册。");
			return false;
		}
		$profile = $this->user_profile;
		$name = $profile->name;
		if($bind_status == self::BIND_STATUS_UNVERIFY){
			$url = _url('/user/verify/idcard');
			$this->wx_reply->imm_reply_text($to, $from, "尊敬的" . $name . "，您尚未开通资金托管账户，无法查看相关信息。\n\n<a href=\"" . $url . "\">点击开通资金托管账户</a>");
			return;
		}
		if($bind_status == self::BIND_STATUS_VERIFYED){
			return true;
		}
	}

	private function to_buy($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$news_arr = array();

		$url = 'https://axelahome.com/';
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}
		$news_arr[] = array(
			'title'   => '马上去投资',
			'desc'    => '理财就选懒投资，收益超银行活期35倍。',
			'img_url' => 'https://s1.axelahome.com/img/201502/festival/wechat20150225.jpg',
			'link'    => $url,
		);

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	private function yiyuan_hongbao($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$url = 'https://axelahome.com/zt/yiyuan';
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}
		$news_arr = array();
		$news_arr[] = array(
			'title'   => '分享抢破亿红包',
			'desc'    => '',
			'img_url' => 'https://s1.axelahome.com/img/201412/yiyuan.jpg',
			'link'    => $url,
		);

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*全部项目*/
	private function projects_all($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$prjs = Project::get_valid_project(0, 5);
		if(!$prjs || $prjs['total'] == 0){
			$this->wx_reply->imm_reply_text($to, $from, '还没有项目涅，请保持关注！');
			return;
		}
		$news_arr = array();
		foreach ($prjs['items'] as $key => $prj) {
			$url = EncryptID::get_prj_url($prj->id);
			if(ENV == 'dev'){
				$url = preg_replace('/^https/', 'http', $url);
			}
			array_push($news_arr, array(
				'title'   => $prj->title,
				'desc'    => $prj->title,
				'img_url' => $prj->img,
				'link'    => $url,
			));
		}

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*预售项目*/
	private function projects_pre($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;	

		$prjs = Project::get_valid_project(0, 8, Project::STATUS_PRE);
		if(!$prjs || $prjs['total'] == 0){
			$connect = WxConnect::get_by('wx_openid', $from);
			if($connect->prj_subscribe == WxConnect::PRJ_SUBSCRIBED){
				$this->wx_reply->imm_reply_news($to, $from, array(array(
					'title'   => '订阅懒投资项目通知，抢标so easy',
					'img_url' => 'http://s1.axelahome.com/img/prj/wechat/no_pre.jpg',
					'link'    => 'http://mp.weixin.qq.com/s?__biz=MzAxODAzNjMwMw==&mid=200694427&idx=1&sn=775d853de3519214d226b0f5ca113792#rd',
					'desc'    => '您已成功订阅项目通知，小懒会在第一时间告知预售项目和募集项目的消息。如需退订，请回复“取消项目订阅”。',
				)));
			} else {
				$this->wx_reply->imm_reply_news($to, $from, array(array(
					'title'   => '暂无预售项目，订阅项目通知，第一时间知晓！',
					'img_url' => 'http://s1.axelahome.com/img/prj/wechat/no_pre.jpg',
					'link'    => 'http://mp.weixin.qq.com/s?__biz=MzAxODAzNjMwMw==&mid=200694479&idx=1&sn=66972979f4f9d71fe32b711c5b226760#rd',
					'desc'    => '点击菜单【项目列表】，选择【订阅项目通知】',
				)));
			}
			return;
		}
		$news_arr = array();
		foreach ($prjs['items'] as $key => $prj) {
			$url = EncryptID::get_prj_url($prj->id);
			if(ENV == 'dev'){
				$url = preg_replace('/^https/', 'http', $url);
			}
			array_push($news_arr, array(
				'title'   => $prj->title,
				'desc'    => $prj->title,
				'img_url' => $prj->img,
				'link'    => $url,
			));
		}

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*募集项目*/
	private function projects_open($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$prjs = Project::get_valid_project(0, 8, Project::STATUS_OPEN);
		if(!$prjs || $prjs['total'] == 0){
			$url = _url('project');
			$this->wx_reply->imm_reply_news($to, $from, array(array(
				'title'   => '暂无募集项目，敬请期待',
				'img_url' => 'http://s1.axelahome.com/img/prj/wechat/no_open.jpg',
				'link'    => $url,
			)));
			return;
		}
		$news_arr = array();
		foreach ($prjs['items'] as $key => $prj) {
			$url = EncryptID::get_prj_url($prj->id);
			if(ENV == 'dev'){
				$url = preg_replace('/^https/', 'http', $url);
			}
			array_push($news_arr, array(
				'title'   => $prj->title,
				'desc'    => $prj->title,
				'img_url' => $prj->img,
				'link'    => $url,
			));
		}

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	// 邀请链接
	private function invite_link($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$url = _url('user/invite');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}
		$news_arr = array();
		$news_arr[] = array(
			'title'   => '邀请好友',
			'desc'    => '',
			'img_url' => 'https://s1.axelahome.com/weixin/zidong/invite.jpg',
			'link'    => $url,
		);

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*账户概览*/
	private function account_summary($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;

		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$url = _url('/user');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}

		$account = Account::get($uid);
		if(!$account){
			$account = new Account();
		}
		$unuse_coupon = Coupon::total_available($uid);
// 		$my_profit = $account->my_profit();
		$repay_sum_need = Repay::get_sum_amount_by_uid($uid, Repay::STATUS_NEW);
		$prj_count = Order::get_prj_count_by_uid($uid, array(Order::STATUS_PAYDONE, Order::STATUS_NEW, Order::STATUS_BLOCK));
// 		$platform_interest_done = PlatformInterest::get_total_amount_by_uid($uid, Trade::STATUS_DONE);
		$platform_interest_new = PlatformInterest::get_total_amount_by_uid($uid, Trade::STATUS_NEW);
// 		$stock_transfer_done = StockTransfer::get_total_amount_by_uid($uid, Trade::STATUS_DONE);
		$lqjh_account = LqjhAccount::get($uid);
// 		$qxjh_amount = ApolloOrder::get_amount_by_uid($uid, ApolloOrder::STATUS_PAYDONE);
		
		if($lqjh_account){
			$lqjh_amount = $lqjh_account->get_amount();
		}else{
			$lqjh_amount = 0;
		}
		$bxjh_repay_new = BxjhRepay::get_sum_amount_by_uid($uid, BxjhRepay::STATUS_NEW);
// 		$bxjh_platform_interest_new = BxjhPlatformInterest::get_sum_amount_by_uid($uid, BxjhPlatformInterest::STATUS_NEW);
		
		$repay_sum_already = Account::get_total_profit($uid);
		$asset = Account::get_total_asset($uid);
		$remain_amount = Account::get_remain_amount($uid);
		
		$ret_text = '';
		$ret_text .= "总资产　（元）： " . Money::show_money($asset) . "\n";
		$ret_text .= "账户余额（元）： " . Money::show_money($remain_amount) . "\n";
		$ret_text .= "累计收益（元）： " . Money::show_money($repay_sum_already) . "\n";
		$ret_text .= "\n";
// 		$ret_text .= "在投项目（个）： " . $prj_count . "\n";
		$ret_text .= "待收本金（元）： " . Money::show_money($repay_sum_need->total_amount - $repay_sum_need->total_interest+$bxjh_repay_new->total_amount-$bxjh_repay_new->total_interest) . "\n";
		$ret_text .= "零钱金额（元）： " . Money::show_money($lqjh_amount) . "\n";
// 		$ret_text .= "待收利息（元）： " . Money::show_money($repay_sum_need->total_interest+ $platform_interest_new) . "\n";
		$ret_text .= "\n";
		$ret_text .= "未用投资券总价值（元）： " . Money::show_money($unuse_coupon) . "\n\n";

		$ret_text = preg_replace('/<\/?span>/', '', $ret_text);
		$news_arr = array(
			array(
				'title'   => '账户概览',
				'desc'    => $ret_text,
				'link'    => $url,
			),
		);
		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*投资记录*/
	private function datalist_order($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;

		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$url = _url('user/order/datalist');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}

		$in = Db::build_in_string(array(Order::STATUS_NEW, Order::STATUS_BLOCK, Order::STATUS_PAYDONE));
		$where = "user_id='$uid' and status in ($in)";
		$orders = Order::find(0, 2, $where, 'time desc');
		$ret_text = '';
		if(!$orders){
			$ret_text = '很遗憾，您在懒投资平台暂无投资记录。';
		} else {
			foreach ($orders as $key => $order) {
				$prj = Project::get($order->project_id);
				$total_rate = $prj->anual_rate+$prj->platform_rate+$prj->event_rate;
				$ret_text .= "单　　号： $order->id\n";
				$ret_text .= "投资时间： $order->time\n";
				$ret_text .= "项目名称： $prj->title\n";
				$ret_text .= "预期年化： {$total_rate}%\n";
				$ret_text .= "项目期限： $prj->days 天\n";
				$ret_text .= "投资金额： " . Money::fen2yuan($order->amount) . " 元\n\n";
			}
		}
		$news_arr = array(
			array(
				'title'   => '投资记录',
				'desc'    => $ret_text,
				'link'    => $url,
			),
		);

		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*资金流水*/
	private function datalist_trade($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;

		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$url = _url('user/trade/datalist');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}

		$trade = Trade::get_list_by_uid($uid, 0, 3);
		$ret_text = '';
		if(!$trade || $trade['total'] == 0){
			$ret_text = '很遗憾，您在懒投资平台暂无资金流水。';
		} else {
			foreach ($trade['items'] as $key => $trade) {
				$ret_text .= "交易时间： $trade->time\n";
				$ret_text .= "交易类型： " . $trade->type_text() . "\n";
				$ret_text .= "交易金额： " . number_format($trade->amount/100.0, 2) . "元\n\n";
			}
			$ret_text .= '点全文可查看账单明细';
		}
		$news_arr = array(
			array(
				'title'   => '资金流水',
				'desc'    => $ret_text,
				'link'    => $url,
			),
		);
		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*个人信息*/
	private function user_info($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;

		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		$url = _url('user/account');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}

		/*
		$profile = Profile::get($uid);
		$user_desc = "用户名： $profile->name\n\n"
					. "手机号： " . substr_replace($profile->mobile, '****', 3, -4) . "\n\n"
					. "邮    箱： $profile->email\n\n"
					. "姓    名： $profile->realname\n\n"
					. "身份证： " . substr_replace($profile->id_card_no, ' *** ', 6, -4);
		*/
		$news_arr = array(
			array(
				'title'   => '我的个人信息',
				'img_url' => 'http://s1.axelahome.com/img/prj/wechat/user_info.jpg',
				'link'    => $url,
			),
		);
		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
	}

	/*订阅新项目通知*/
	private function projects_subscribe($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;

		$connect = WxConnect::get_by('wx_openid', $from);
		if($connect->prj_subscribe !== WxConnect::PRJ_SUBSCRIBED){
			try {
				$connect->update(array(
					'prj_subscribe' => WxConnect::PRJ_SUBSCRIBED
				));
				$this->wx_reply->imm_reply_text($to, $from, '您已成功订阅项目通知，小懒会在第一时间告知预售项目和募集项目的消息。');
			} catch(Exception $e) {
				$this->wx_reply->imm_reply_text($to, $from, '抱歉，订阅出错，请稍后重试。');
			}
		} else {
			$this->wx_reply->imm_reply_text($to, $from, '您已成功订阅项目通知，小懒会在第一时间告知预售项目和募集项目的消息。');
		}
	}

	/*退订新项目通知*/
	private function projects_unsubscribe($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;

		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;

		$connect = WxConnect::get_by('wx_openid', $from);
		if($connect->prj_subscribe !== WxConnect::PRJ_UNSUBSCRIBED){
			try {
				$connect->update(array(
					'prj_subscribe' => WxConnect::PRJ_UNSUBSCRIBED
				));
				$this->wx_reply->imm_reply_text($to, $from, '您已经成功退订新项目通知，如需再次订阅可点击【我的账户-订阅项目通知】。');
			} catch(Exception $e) {
				$this->wx_reply->imm_reply_text($to, $from, '抱歉，退订出错，请稍后重试。');
			}
		} else {
			$this->wx_reply->imm_reply_text($to, $from, '您已经成功退订新项目通知，如需再次订阅可点击【我的账户-订阅项目通知】。');
		}
	}

	/*双11红包*/
	private function shuang11_hongbao($xml){
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		$uid = $this->get_uid($xml);

		if($uid && Coupon::get_list_by_user($uid, '', CouponInfo::COUPON_SHUANG11_10000_11)){
			$this->wx_reply->imm_reply_text($to, $from, "你已经成功领取懒投资双十一红包，赶快去賺钱吧。\n\n<a href=\"" . _url('project') . "\">马上投资</a>");
		} else {
			$url = _url('zt/shuang11_hongbao');
			$news_arr = array(
				array(
					'title'   => '双十一红包发发发',
					'img_url' => 'https://s1.axelahome.com/img/prj/wechat/hongbao1110.jpg',
					'link'    => $url,
					'desc'    => '马上领红包，把双十一花出去的全部赚回来。',
				),
			);
			$this->wx_reply->imm_reply_news($to, $from, $news_arr);
		}
	}	

	// 接入微信时要调用的验证接口, 接入完成后, 便不再使用
	private function validate_wx($ctx){
		if(!$this->check_sign()){
			Logger::error("weixin signature error!");
			return;
		}
		echo $_GET["echostr"];
	}

	// 过节福利
	private function event_tour($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$uid = $this->user_profile->id;
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
				
		if (!$uid) {
			$content = '请你先在懒投资微信“我的账户”—“绑定账号”中绑定您的懒投资账号后再回复对应关键字领取优惠券。';
		} else {
			// TODO 领取逻辑
			$text = trim(''.$xml->Content);
			$info_id = EdaixiCoupon::INFO_TOUR;
			$arr_prize_text = EdaixiCoupon::get_infoid_prize_text($info_id);
			if (array_key_exists($text, $arr_prize_text)) {
				$prize_id = $arr_prize_text[$text];
				
				// 已经领取过
				$m = EdaixiCoupon::check_have_taken($uid, $info_id, $prize_id);
				if ($m) {
					$content = "您已经领取过啦！您的优惠券码如下：\n";
				} else {
					$m = PrizeUtil::do_taken_prize($uid, $info_id, $prize_id);
					if ($m) {
						$content = "恭喜您在“暑期狂欢季”活动中成功领取优惠券。\n";
					}
				}
				
				if ($m) {
					$prize_title = EdaixiCoupon::get_type_text($m->type);
					$prize_code = $m->code;
					$content .= "优惠券名称：{$prize_title}\n";
					$content .= "优惠码：{$prize_code}\n";
					$content .= "查看优惠券使用方法，请点击：https://axelahome.com/post/243";
				} else {
					$content = '您来晚啦，优惠券被抢光了，试试回复其它优惠券的关键字吧';
				}
			} else {
				$content = '您来晚啦，优惠券被抢光了，试试回复其它优惠券的关键字吧';
			}
		}
		
		$this->wx_reply->imm_reply_text($to, $from, $content);
	}
	
	/*充值*/
	private function recharge($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
	
		$url = _url('user/recharge');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}
		$news_arr = array(
				array(
						'title'   => '充值',
						'img_url' => 'https://s1.axelahome.com/weixin/zidong/recharge.jpg',
						'link'    => $url,
				),
		);
		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
		return;
	}
	
	/*提现*/
	private function withdraw($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		$from = $xml->FromUserName ;
		$to = $xml->ToUserName;
	
		$url = _url('user/withdraw');
		if(ENV == 'dev'){
			$url = preg_replace('/^https/', 'http', $url);
		}
		$news_arr = array(
				array(
						'title'   => '提现',
						'img_url' => 'https://s1.axelahome.com/weixin/zidong/withdraw.jpg',
						'link'    => $url,
				),
		);
		$this->wx_reply->imm_reply_news($to, $from, $news_arr);
		return;
	}
	
	// 群星净值
	private function apollo($xml){
		if (!$this->bind_check($xml)) {
			return;
		}
		
		$uid = $this->user_profile->id;
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		
		if (!$uid) {
			$content = '请你先在懒投资微信“我的账户”—“绑定账号”中，绑定您的懒投资账号后，再回复净值关键字获取最新净值。';
		} else {
			// 从持仓账户里面取
			$infos = ApolloAccount::find(0, 10000, "user_id = '{$uid}' and balance > 0");
			
			if ($infos) {
				// 1. 已购买
				$in_status = array(
						ApolloProject::STATUS_REPAY,
						ApolloProject::STATUS_CLOSE,
				);
				$now_date = date('Y-m-d H:i:s');
				$content = '小懒悄悄地告诉你~';
				
				foreach ($infos as $info) {
					$prj_id = intval($info->project_id);
					$prj_info = ApolloProject::get($prj_id);
					if (!$prj_info) {
						continue;
					}
					
					$title = trim($prj_info->title);
					$price = ApolloPrice::get_latest_price_by_prj($prj_id);
					$price = Money::hao2yuan($price);
					
					$due_time = $prj_info->due_time;
					$days = RepayUtil::days_diff($due_time, $now_date);
					$tmp_txt = "你投资的“{$title}”当前净值是{$price}，";
					if ($days > 30 && in_array($prj_info->status, $in_status)) {
						$available_amount = Money::fen2yuan($info->available_amount());
						$tmp_txt .= "可出售{$available_amount}份。";
					} else {
						$tmp_txt .= "持有30天后即可开始转让。";
					}
					$arr_text[] = $tmp_txt;
				}
				
				$text = implode("\n", $arr_text);
				if (count($arr_text) > 0) {
					$content .= "\n" . $text;
				} else {
					$content .= $text;
				}
			} else {
				// 2. 未购买 获取最大净值的项目
				$res = ApolloPrice::get_highest_price_prj();
				if ($res) {
					$price = intval($res->price);
					$prj_id = intval($res->project_id);
					$prj = ApolloProject::get($prj_id);
				} else {
					// 取不到，则
					$in_status = array(
							ApolloProject::STATUS_OPEN,
							ApolloProject::STATUS_DUE,
							ApolloProject::STATUS_REPAY,
							ApolloProject::STATUS_CLOSE,
					);
					$in_str = Db::build_in_string($in_status);
					$prj = ApolloProject::find_one("status in ({$in_str})", 'id desc');
					$price = intval($prj->price);
				}
				
				if ($prj) {
					$title = trim($prj->title);
					$price = Money::hao2yuan($price);
					$content = "小懒悄悄地告诉你~当前群星计划中，“{$title}”净值最高，目前净值{$price}。快来投资吧~";
				} else {
					$content = "小懒悄悄地告诉你~当前群星计划中，“群星1号新能源精选”净值最高，目前净值1.0013。快来投资吧~";
				}
			}
		}
		$this->wx_reply->imm_reply_text($to, $from, $content);
	}
	
	// 新年理财季
	private function event_year_end($xml) {
		if(!$this->bind_check($xml)){
			return;
		}
		
		$text = trim('' . $xml->Content);
		if ($text != '爱奇艺' && $text != '乐视') {
			return;
		}
		
		$uid = $this->user_profile->id;
		$mobile = $this->user_profile->mobile;
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		$info_id = EdaixiCoupon::INFO_EVENT_2015_YEAR_END;
		
		// 为了简化逻辑 根据邀请人和type去取
		$str = $str2 = '';
		if ($text == '爱奇艺') {
			$str = "恭喜您获得爱奇艺VIP会员，激活码如下：\n";
			$str2 = '前往爱奇艺VIP会员购买页面，选择“激活码支付”即可激活，12月31日后失效请尽快使用。';
			$prize_id = EdaixiCoupon::TYPE_EVENT_2015_YEAR_END_IQIYI_1;
		} else {
			$str = "恭喜您获得乐视网VIP会员，激活码如下：\n";
			$str2 = '在电脑上打开乐视网首页，右上角“会员VIP”－右上角“兑换码”输入即可激活，12月31日后失效请尽快使用。';
			$prize_id = EdaixiCoupon::TYPE_EVENT_2015_YEAR_END_LETV_1;
		}
		
		// 取一次
		// 是否领取过1月会员
		$m = EdaixiCoupon::check_have_taken($uid, $info_id, $prize_id);
		
		// 未领取到
		if (!$m) {
			$content = "抱歉，您还未获得会员激活码，请通过懒投资活动了解获取方法。或因系统延迟，请在1分钟后重试。";
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}
		
		// 至少领取了一个
		$content = $str . "1个月会员：{$m->code}" . "\n" . $str2;
		$this->wx_reply->imm_reply_text($to, $from, $content);
		return;
	}

	private function active_dragonpass($xml) {
		if(!$this->bind_check($xml)){
			return;
		}
		//标准格式 code,name,mobile
		$text = $xml->Content;
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		$text = str_replace('，', ',', $text);
		$info = explode(',', $text);
		if(count($info) != 3) {
			$content = "对不起，您的回复有误。请回复正确格式：兑换码，真实姓名，手机号\n";
			$content .= "如：HYGjkiYTR，张磊，1380000000\n\n";
			$content .= "小懒提示：每个手机号只能绑定3次龙腾贵宾室礼券哦~";
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}
		$code = trim($info[0]);
		$name = trim($info[1]);
		$mobile = trim($info[2]);

		if(strlen($name) < 6 || strlen($name) > 48 || !preg_match("/^1\d{10}$/", $mobile)) {
			$content = "对不起，您的回复有误。请回复正确格式：兑换码，真实姓名，手机号\n";
			$content .= "如：HYGjkiYTR，张磊，1380000000\n\n";
			$content .= "小懒提示：每个手机号只能绑定3次龙腾贵宾室礼券哦~";
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}

		$content = DragonpassCode::active_code($this->user_profile->id, $code, $name, $mobile);
		$this->wx_reply->imm_reply_text($to, $from, $content);
		return;
	}

	// 过节福利 NOTE 2015-12-01 已废弃
	private function event_20151015($xml){
		if(!$this->bind_check($xml)){
			return;
		}
		
		$text = trim('' . $xml->Content);
		if ($text != '爱奇艺' && $text != '乐视') {
			return;
		}
		
		$uid = $this->user_profile->id;
		$inviter = $this->user_profile->inviter;
		$mobile = $this->user_profile->mobile;
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		$info_id = EdaixiCoupon::INFO_EVENT_20151015_IQIYI_LETV;
		
		$iqiyi_inviters = array(
				Channel::CHANNEL_ID_IQIYI,
				Channel::CHANNEL_ID_RONG_360,
		);
		if (in_array($inviter, $iqiyi_inviters) && $text != '爱奇艺') {
			$content = '抱歉，您还未获得会员激活码，可在活动页查看获取方法。';
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}
		
		if ($inviter == Channel::CHANNEL_ID_LETV && $text != '乐视') {
			$content = '抱歉，您还未获得会员激活码，可在活动页查看获取方法。';
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}
		
		$ret_code = EventUtil::check_event_20151015($uid);
		// 不符合条件
		if ($ret_code == EventUtil::EVENT_20151015_RET_CODE_NONE) {
			$content = '抱歉，您还未获得会员激活码，可在活动页查看获取方法。';
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}
		
		$do_lqjh = false;
		$do_lrjh = false;
		if ($ret_code == EventUtil::EVENT_20151015_RET_CODE_LQJH) {
			$do_lqjh = true;
		} elseif ($ret_code == EventUtil::EVENT_20151015_RET_CODE_LRJH) {
			$do_lrjh = true;
		} elseif ($ret_code == EventUtil::EVENT_20151015_RET_CODE_BOTH) {
			$do_lqjh = true;
			$do_lrjh = true;
		}
			
		// 领取1月会员
		if ($do_lqjh) {
			EventUtil::do_event_20151015_job($this->user_profile, EventUtil::EVENT_20151015_RET_CODE_LQJH);
		}
		// 领取3月会员
		if ($do_lrjh) {
			EventUtil::do_event_20151015_job($this->user_profile, EventUtil::EVENT_20151015_RET_CODE_LRJH);
		}
		
		// 为了简化逻辑 根据邀请人和type去取
		if (in_array($inviter, $iqiyi_inviters)) {
			$str = "恭喜您获得爱奇艺VIP会员，激活码如下：\n";
			$str2 = '登录爱奇艺VIP会员购买页面，选择“激活码支付”即可激活，过期失效请尽快使用。';
			$prize_id = EdaixiCoupon::TYPE_EVENT_20151015_IQIYI_1;
			$prize_id2 = EdaixiCoupon::TYPE_EVENT_20151015_IQIYI_3;
		} else {
			$str = "恭喜您获得乐视网VIP会员，激活码如下：\n";
			$str2 = '在电脑登录乐视网首页，进入“会员VIP”－“兑换码”，输入即可激活，过期失效请尽快使用。';
			$prize_id = EdaixiCoupon::TYPE_EVENT_20151015_LETV_1;
			$prize_id2 = EdaixiCoupon::TYPE_EVENT_20151015_LETV_3;
		}
		
		// 取一次
		// 是否领取过1月会员
		$m = EdaixiCoupon::check_have_taken($uid, $info_id, $prize_id);
		// 是否领取过3月会员
		$m2 = EdaixiCoupon::check_have_taken($uid, $info_id, $prize_id2);
		
		// 未领取到
		if (!$m && !$m2) {
			$content = "会员激活码被抢光啦，小懒正加急准备下一波～请2小时候后重试或咨询客服：400-807-8000";
			$this->wx_reply->imm_reply_text($to, $from, $content);
			return;
		}
		
		// 至少领取了一个
		$text_m = $m ? "1个月会员：{$m->code}" : '1个月会员：暂未获得';
		$text_m2 = $m2 ? "3个月会员：{$m2->code}" : '3个月会员：暂未获得';
		$content = $str . $text_m . "\n" . $text_m2 . "\n" . $str2;
		$this->wx_reply->imm_reply_text($to, $from, $content);
		return;
	}
	// 内部员工关键字
	private function do_the_staff_reply($xml){
		if (!$this->bind_check($xml)){
			return;
		}
		
		$text = trim('' . $xml->Content);
		if (!in_array($text, self::$the_staff_keywords)) {
			return;
		}
		
		$uid = $this->user_profile->id;
		if (!in_array($uid, self::$the_staff_uids)) {
			return;
		}
		
		$from = $xml->FromUserName;
		$to = $xml->ToUserName;
		
		$content = '';
		switch ($text) {
			case self::STAFF_KW_RCTJ:
				$content = StaffUtil::get_richang_statis(date('Y-m-d'));
				break;
			default:
				break;
		}
		
		if (!$content) {
			return;
		}
		
		$this->wx_reply->imm_reply_text($to, $from, $content);
		return;
	}
}
