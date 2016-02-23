<?php
class OpenidsController extends AdminController
{
    const PAGE_SIZE = 20;
    
	function index($ctx) {
		$page = $_GET['page'] ? intval($_GET['page']) : 0;
		$size = $_GET['size'] ? intval($_GET['size']) : self::PAGE_SIZE;
		$ctx->page = $page;
		$ctx->size = $size;

		$wx_openid = $_GET['wx_openid'] ? htmlspecialchars($_GET['wx_openid']) : '';
		$status = (string)($_GET['status']) !== '' ? htmlspecialchars($_GET['status']) : '';
		$wx_id = $_GET['wx_id'] !== '' ? htmlspecialchars($_GET['wx_id']) : '';
		$source = $_GET['source'] !== '' ? htmlspecialchars($_GET['source']) : '';

		$ctx->status = $status;
		$ctx->wx_id = $wx_id;
		$ctx->source = $source;
		$ctx->wx_openid = $wx_openid;
		
		$where = 1;
		if ($status !== '') {
			$status = intval($status);
			$where .= " and status = '{$status}'";
		}
		if ($source !== '') {
			$where .= " and source = '{$source}'";
		}
		if ($wx_openid) {
			$where .= " and wx_openid = '$wx_openid'";
		}
		if ($wx_id) {
			$where .= " and wx_id = '$wx_id'";
		}
		$ds = WxOpenid::paginate($page, $size, $where, 'id desc');
		$ctx->ds = $ds;
	}
}
