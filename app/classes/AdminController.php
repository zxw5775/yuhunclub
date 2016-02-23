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
		'admin/bao' => array(
			'name'=> '车友管理',
			'sub' => array(
				'admin/' => '车友管理',
				'admin/' => '车标申请',
				'admin/' => '微信管理',	
			),
		),
	);
}
