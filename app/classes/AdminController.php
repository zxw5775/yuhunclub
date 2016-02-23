<?php
class AdminController extends Controller
{
	function init($ctx){
		session_start();
		$user =$_SESSION['admin_user'];
		if(!$user){
			_redirect('admin/login');
		}
	}

	static $menu =  array(
			'admin/weixin' => array(
					'name' => '微信管理',
					'sub'  => array(
							'admin/weixin/account' => '账号管理',
							'admin/weixin/reply'   => '回复管理',
							'admin/weixin/connect'   => '用户账号',
							'admin/weixin/openids'   => 'openid列表',
					),
			),
	);
}
