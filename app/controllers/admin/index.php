<?php
class IndexController extends AdminController
{
	function index($ctx){

	}
	
	function logout($ctx){
		unset($_SESSION['admin_user']);
		_redirect('admin/login');
	}
}
