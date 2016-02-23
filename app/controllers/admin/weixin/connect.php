<?php
class ConnectController extends AdminController
{
    const PAGE_SIZE = 20;
	function index($ctx){
		$page = $_GET['page'] ? intval($_GET['page']) : 0;
		$size = $_GET['size'] ? intval($_GET['size']) : self::PAGE_SIZE;
		$ctx->page = $page;
		$ctx->size = $size;

		$user_id = $_GET['user_id'] ? intval($_GET['user_id']) : '';
		$wx_openid = $_GET['wx_openid'] ? htmlspecialchars($_GET['wx_openid']) : '';
		$prj_subscribe = (string)$_GET['prj_subscribe'] !== '' ? intval($_GET['prj_subscribe']) : '';

		$where = 1;
		if($user_id){
			$where .= " and user_id = '$user_id'";
		}
		if($wx_openid){
			$where .= " and wx_openid = '$wx_openid'";
		}
		if($prj_subscribe !== ''){
			$where .= " and prj_subscribe = '$prj_subscribe'";
		}

		$ctx->user_id = $user_id;
		$ctx->wx_openid = $wx_openid;
		$ctx->prj_subscribe = $prj_subscribe;
		$ds = WxConnect::paginate($page, $size, $where, 'id desc');
		$ctx->ds = $ds;
	}
	
	function edit($ctx){
		$id = intval($_GET['id']);
		$m = WxConnect::get($id);
		if(!$m){
			_throw("ID: $id 不存在!");
		}
		if($_POST){
			$up = array(
				'prj_subscribe' => intval($_POST['f']['prj_subscribe']),
				'wx_subscribe' => intval($_POST['f']['wx_subscribe']),
			);
			$m->update($up);
		}
		$ctx->m = $m;
	}
}
