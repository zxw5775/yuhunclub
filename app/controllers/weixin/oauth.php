<?php
class OauthController extends Controller
{
	private $appid = '';
	private $secret = '';

	function init($ctx){
		parent::init($ctx);
		$account = WxAccount::get(1); // 1 是"懒投资"服务号
		$this->appid = $account->app_id;
		$this->secret = $account->app_secret;
	}
	
	static private function validate_url($url){
		$allow = Util::is_allow_domain($url);
		if(!$allow){
			_redirect('/');
		}
	}
	
	function index($ctx){
		return $this->open_url($ctx);
	}
	
	function open_url($ctx){
		$jump = htmlspecialchars(trim($_GET['jump']));
		self::validate_url($jump);
		if(!$this->appid || !$this->secret){
			_redirect($jump);
		}
		// 如果已经登录, 则不需要和weixin交互
		$user = UC::auth();
		if($user){
			_redirect($jump);
		}
		
		// 使用域名 axelahome.com, 避免因为跨域导致 session 获取不到问题
		$callback = _url('https://axelahome.com/weixin/oauth/callback', array(
			'jump' => $jump,
		));
		$wx_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
		$wx_url = "$wx_url?appid={$this->appid}&redirect_uri=$callback&response_type=code&scope=snsapi_base&state=1#wechat_redirect";

		_redirect($wx_url);
	}
	
	function callback($ctx){
		$jump = htmlspecialchars(trim($_GET['jump']));
		self::validate_url($jump);
		if(!$this->appid || !$this->secret){
			_redirect($jump);
		}

		$code = urlencode(htmlspecialchars(trim($_GET['code'])));
		if(!$code){
			_redirect($jump);
		}
		$wx_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		$wx_url = "$wx_url?appid={$this->appid}&secret={$this->secret}&code=$code&grant_type=authorization_code";
		$resp = Http::get($wx_url);
		$ret = @json_decode($resp, true);
		if(is_array($ret) && $ret['openid']){
			$connect = WxConnect::get_by('wx_openid', $ret['openid']);
			if($connect){
				Logger::info("wx_openid[{$ret['openid']}] oauth login, uid: {$connect->user_id}");
				$profile = Profile::get($connect->user_id);
				if($profile && $profile->status != Profile::STATUS_LOCK){
					UC::force_login($profile);
				}
			}else{
				// 兼容 /weixin/bind, 因为它依赖 session 中的 openid, 所以这里设置
				session_start();
				$_SESSION['wx_openid'] = $ret['openid'];
			}
		}else{
			Logger::info("weixin oauth, code: $code, resp: $resp, " . Http::$error);
		}
		
		_redirect($jump);
	}
}

