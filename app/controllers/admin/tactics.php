<?php
class TacticsController extends AdminController
{
	/*
	 * 列表页
	 */
	function index($ctx){
		$page = $_GET['page']? intval($_GET['page']) : 0;
		$size = $_GET['size']? intval($_GET['size']) : 15;
		$where  = 1;
		$where .= " and status <> '".  Tactics::STATUS_DELETED."'";
		$ctx->page = $page;
		$ctx->size = $size;
		$ctx->ds = Tactics::paginate($page, $size, $where);
	}

	function create($ctx){
		_render('form');
		if(isset($_POST['f']) && $_POST['f']){
			$_POST['f']['create_time'] = date('Y-m-d H:i:s');
			$_POST['f']['pay_amount']  = Money::yuan2fen($_POST['f']['pay_amount']);
			Tactics::save($_POST['f']);
			_redirect($this->_list_url());
		}
	}
	
	function del($ctx){
		$id = intval($_GET['id']);
		$tactics = Tactics::get($id);
		if($tactics){
			$tactics->set_del();
		}
		_redirect(_list_url());
	}
	
	function set_send($ctx){
		$id = intval($_GET['id']);
		$tactics = Tactics::get($id);
		if($tactics){
			$tactics->set_send();
		}
		_redirect(_list_url());
	}
	
	function set_new($ctx){
		$id = intval($_GET['id']);
		$tactics = Tactics::get($id);
		if($tactics){
			$tactics->set_new();
		}
		_redirect(_list_url());
	}
}
