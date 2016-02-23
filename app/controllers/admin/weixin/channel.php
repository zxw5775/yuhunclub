<?php
// 渠道相关
class ChannelController extends AdminController
{
	function index($ctx) {
		$page = $_GET['page']? intval($_GET['page']) : 0;
		$size = $_GET['size']? intval($_GET['size']) : 10;
		$status = (string)($_GET['status']) !== '' ? htmlspecialchars($_GET['status']) : '';
		$type = (string)($_GET['type']) !== '' ? htmlspecialchars($_GET['type']) : '';
		$expire_type = (string)($_GET['expire_type']) !== '' ? htmlspecialchars($_GET['expire_type']) : '';
		$id =   (string)$_GET['id'] !== '' ? intval($_GET['id']) : '';
		
		$ctx->page = $page;
		$ctx->size = $size;
		$ctx->status = $status;
		$ctx->id = $id;
		$ctx->type = $type;
		$ctx->expire_type = $expire_type;
		$ctx->channel_id = $channel_id;
		
		$where = 1;
		if ($id) {
			$id = intval($id);
			$where .= " and id  = '$id'";
		}
		if ($status !== '') {
			$status = intval($status);
			$where .= " and status = '{$status}'";
		}
		if ($type !== '') {
			$type = intval($type);
			$where .= " and type = '{$type}'";
		}
		
		if ($expire_type !== '') {
			$expire_type = intval($expire_type);
			$where .= " and expire_type = '{$expire_type}'";
		}
		$ds = WxChannelInfo::paginate($page, $size, $where, 'id desc');
		
		$arr_channel_infos = array();
		foreach ($ds['items'] as $item) {
			$channel_id = $item->id;
			if (!isset($arr_channel_infos[$channel_id])) {
				$arr_channel_infos[$channel_id] = Channel::get($channel_id);
			}
			$item->channel_info = $arr_channel_infos[$channel_id];
		}
		
		if (!$ds['items'] && $id) {
			_redirect(_action('edit', array('id' => $id)));
			return;
		}
		$ctx->ds = $ds;
	}
	function edit($ctx) {
		_render('form');
		try {
			$channel_id = (string)$_GET['id'] !== '' ? intval($_GET['id']) : '';
			$m = Channel::get($channel_id);
			if (!$m) {
				_redirect($this->_list_url());
				return;
			}
			$wx_info = WxChannelInfo::get($channel_id);
			if ($_POST) {
				$f = $_POST['f'];
				// 生成二维码 每次保存都更新一次 但是如果已经发放了 其实不能更新的 因为会过期 所以，永久的不能更新 临时的更新可以处理
				
				$expire_type = intval($f['expire_type']);
				$expire_seconds = intval($f['expire_seconds']);
				$expire_seconds = max(0, $expire_seconds);
				$status = intval($f['status']);
				
				$wx_info = WxChannelInfo::qrcode_create($channel_id, $expire_type, $expire_seconds, $status);
				_redirect($this->_list_url());
			}
			$ctx->wx_info = $wx_info;
			$ctx->m = $m;
		} catch(Exception $e) {
			$msg = $e->getMessage();
			$ctx->errmsg = $msg;
			$ctx->m = $m;
			$ctx->wx_info = $wx_info;
		}
	}
}
