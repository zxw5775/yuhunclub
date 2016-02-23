<?php
class UserBaseController extends Controller
{
	function init($ctx){
		parent::init($ctx);
		$ctx->user = UC::auth();
		if(!$ctx->user){
			$url = $_SERVER['REQUEST_URI'];
			_redirect('login', array('jump'=>$url));
			return;
		}
	}
}
