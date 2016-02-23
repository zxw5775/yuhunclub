<?php
class JumpController extends AppController
{
	// 微信用户回复消息后, 会给他生成一个临时标识和其微信账号绑定,
	// 然后他点击带有这个标识的链接过来, 就可以识别出其微信身份了.
	function index($ctx){
		$jump = htmlspecialchars(trim($_GET['jump']));
		$host = Html::host();
		if(!preg_match("/http(s)?:\/\/[^\/]*$host\//", $jump)){
			$jump = '';
		}

		// 验证 token
		$token = htmlspecialchars(trim($_GET['token']));
		if(strlen($token) == 32){
			$sess = WxTmpLogin::get_session($token);
			if($sess){
				WxTmpLogin::del_session($token);
			}
		}
		if(!$sess){
			#if($token && !$_SESSION['wx_openid']){
			#	_throw("链接已经过期, 请重新获取微信消息!", 200);
			#}
			_redirect($jump);
		}
		
		session_start();
		$_SESSION['wx_openid'] = $sess['openid'];
		
		$connect = WxConnect::get_by('wx_openid', $sess['openid']);
		if(!$connect){
			setcookie(WxTmpLogin::COOKIE_KEY_AUTO_BIND_WX, 1, time() + 3600*24, '/');
			Logger::info("not connected wx_openid: {$sess['openid']}");
			UC::logout();
		}else{
			$uid = $connect->user_id;
			$profile = Profile::get($uid);
			setcookie('ltz_wx_binded', 1, time() + 3600*24*365, "/");
			// 已经绑定了，直接删除该cookie
			if (isset($_COOKIE[WxTmpLogin::COOKIE_KEY_AUTO_BIND_WX])) {
				setcookie(WxTmpLogin::COOKIE_KEY_AUTO_BIND_WX, '', time() - 1, '/');
			}
			Logger::info("wx_openid[{$sess['openid']}] login, uid: {$uid}, {$profile->name}");
			UC::force_login($profile);
		}

		_redirect($jump);
	}
}
