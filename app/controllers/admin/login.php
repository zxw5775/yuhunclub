<?php
class LoginController extends Controller
{

	function init($ctx){
		php_info();
		session_start();
		$ctx->user =$_SESSION['admin_user'];
	}
	function index($ctx){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(!SafeUtil::verify_captcha($_POST['verify_code'])){
				$ctx->errmsg = '验证码错误!';
				return;
			}
			$name = htmlspecialchars(trim($_POST['name']));
			$password = htmlspecialchars(trim($_POST['password']));
			if($name === 'admin' && $password === 'da1wan2jia3'){
				$_SESSION['admin_user'] = 1;
				$url = _url('admin');
				_redirect($url);
				return;
			}else{
				$ctx->errmsg = "用户名或密码错误!";
			}
		}
	}
}
