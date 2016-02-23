<?php
class BindController extends AppController
{
	private $openid;

	function init($ctx){
		parent::init($ctx);
		array_unshift($this->view_path, 'views_mobile');
		session_start();
		$this->openid = $_SESSION['wx_openid'];
		$this->layout = 'v2_layout';
	}

	function index($ctx) {
		$ctx->title = '绑定微信账号';
		setcookie('no_subscribe_prj', '', time()+3600);

		$user = UC::auth();
		if($user){
			$connect = WxConnect::get_by('user_id', $user['id']);
			if($connect){
				_redirect('/');
			}
		}
		
		if(!$this->openid){
			if(!$_GET['redirect']){ // 避免循环跳转
				UC::logout();
				// 使用域名 axelahome.com, 避免因为跨域导致 session 获取不到问题
				$jump = _url('https://axelahome.com/weixin/bind', array('redirect'=>1));
				$url = _url('https://axelahome.com/weixin/oauth', array('jump' => $jump));
				_redirect($url);
			}
			_throw("链接已经过期, 请重新获取微信消息!", 200);
		}else{
			$connect = WxConnect::get_by('wx_openid', $this->openid);
			if($connect){
				_throw('此微信号已经绑定过懒投资账号, 请先解绑!');
			}
		}
	}
	
	function success($ctx){
		$ctx->title = '绑定成功';
		
		$prj_subscribe_status = $_COOKIE['no_subscribe_prj'] ? WxConnect::PRJ_UNSUBSCRIBED : WxConnect::PRJ_SUBSCRIBED;
		setcookie('no_subscribe_prj', '', time()+3600);
		
		if (!$ctx->user) {
			_redirect(_action('/'));
		}
		
		$uid = $ctx->user['id'];
		$wx_openid = $this->openid;
		
		if($uid && $wx_openid){
			WxConnect::bind($uid, $wx_openid);
		}
		
		setcookie(WxTmpLogin::COOKIE_KEY_AUTO_BIND_WX, '', time() - 1, '/');
		unset($_SESSION['wx_openid']);
	}
}
