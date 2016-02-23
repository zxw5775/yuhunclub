<?php
class AccountController extends AdminController
{
    const PAGE_SIZE = 20;
	function index($ctx){
		$page = $_GET['page'] ? intval($_GET['page']) : 0;
		$size = $_GET['size'] ? intval($_GET['size']) : self::PAGE_SIZE;
		$ctx->page = $page;
		$ctx->size = $size;

		$ds = WxAccount::paginate($page, $size, '', 'id desc');
		$ctx->ds = $ds;
	}
	
	function create($ctx){
		_render('form');

		if($_POST){
			return $this->save($ctx);
		}
	}
	
	function edit($ctx){
		_render('form');

		if($_POST){
			return $this->save($ctx);
		}

		$id = intval($_GET['id']);
		$m = WxAccount::get($id);
		if(!$m){
			_redirect($this->_list_url());
			return;
		}
		
		$ctx->m = $m;
	}
	
	function save($ctx){
		$id = intval($_POST['id']);
		$m = WxAccount::get($id);
		if($id && !$m){
			_redirect($this->_list_url());
			return;
		}
		
		if($m){
			$m->update($_POST['f']);
		}else{
			$m = WxAccount::save($_POST['f']);
		}
		
		$ctx->m = $m;
	}
	
	function refresh_access_token($ctx){
		$id = intval($_GET['id']);
		$account = WxAccount::get($id);
		$account->refresh_access_token();
		_redirect(_list_url());
	}
	
	function refresh_js_token($ctx){
		$id = intval($_GET['id']);
		$account = WxAccount::get($id);
		$account->refresh_js_token();
		_redirect(_list_url());
	}
}
