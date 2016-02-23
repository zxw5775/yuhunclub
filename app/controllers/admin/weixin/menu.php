<?php
class MenuController extends AdminController
{
	const PAGE_SIZE = 20;
	const MAX_FIRST_MENU_COUNT = 3;  // 一级菜单最大数目
	const MAX_SECOND_MENU_COUNT = 5; // 二级菜单最大数目

	const MIN_LENGTH = 1;
	const FIRST_MENU_NAME_MAX_LENGTH = 16; //一级菜单名16字节
	const SECOND_MENU_NAME_MAX_LENGTH = 40; //一级菜单名16字节

	const KEY_MAX_LENGTH = 128; // key最大长度
	const URL_MAX_LENGTH = 256; // URL最大长度

	static $MENU_TYPE = array('click', 'view');

	private $model;
	private $format_type;

	function __construct() {
		$this->model = new WxAccount();
	}

	function index($ctx){
		$aid = $_GET['aid'] ? intval($_GET['aid']) : 0;
		$account = WxAccount::get($aid);
		if (count($account) <= 0) {
			_redirect('/admin/weixin/account');
			return;
		}

		$str_menu = $account->menu;
		$arr_menu = $this->format_wx_menu($str_menu);
		if (empty($arr_menu) || !isset($arr_menu['button'])) {
			$arr_menu = array();
		} else {
			$arr_menu = $arr_menu['button'];
		}
		// 需要对sub_button 进行特殊处理，javascript对object和array的处理不一致

		$ctx->aid = $aid;
		$ctx->menus = json_encode($arr_menu);
		$ctx->count = count($arr_menu);
		$ctx->account = $account;
	}

	public function check_first_menu_name($menu_name) {
		if(! $this->check_strlen($menu_name, self::MIN_LENGTH, self::FIRST_MENU_NAME_MAX_LENGTH)) {
			throw new Exception('一级菜单名不能超过16字节');
		}
	}
	public function check_strlen($string, $min, $max) {
		return strlen($string) >= $min && strlen($string) <= $max;
	}

	public function check_menu_type($type) {
		return in_array($type, self::$MENU_TYPE);
	}

	private function filter_wx_menu($menu, $level = 'second') {
		$new = array();

		$name = trim($menu['name']);
		$type = trim($menu['type']);

		$max = self::SECOND_MENU_NAME_MAX_LENGTH;;
		$msg = '二级菜单名长度错误';
		if ($level == 'first') {
			$max = self::FIRST_MENU_NAME_MAX_LENGTH;;
			$msg = '一级菜单名长度错误';
		}
		if(! $this->check_strlen($name, self::MIN_LENGTH, $max)) {
			throw new Exception($msg);
		}

		if (! $this->check_menu_type($type)) {
			throw new Exception('type 错误，应为click或view');
		}

		if ($type == 'click') {
			$key = $menu['key'];
			if (! $this->check_strlen($key, self::MIN_LENGTH, self::KEY_MAX_LENGTH)) {
				throw new Exception('KEY 超过长度');
			}
			$new['key'] = $key;
		}elseif ($type == 'view') {
			$url = $menu['url'];
			if (! $this->check_strlen($url, self::MIN_LENGTH, self::URL_MAX_LENGTH)) {
				throw new Exception('URL 长度错误');
			}
			$new['url'] = $url;
		}

		$new['name'] = $name;
		$new['type'] = $type;
		return $new;
	}

	private function format_wx_menu($menu) {
		$wx_menu = array();

		$arr_menu = json_decode($menu, true);
		if (isset($arr_menu['button'])) {
			$arr_menu = $arr_menu['button'];
		}
		if (empty($arr_menu)) {
			$wx_menu['button'] = array();
			return $wx_menu;
		}

		foreach($arr_menu as $k=>$item) {
			if(isset($item['sub_button']) && count($item['sub_button']) > 0) {
				if (count($item['sub_button']) > self::MAX_SECOND_MENU_COUNT) {
					throw new Exception('二级菜单数超过5个，不能添加');
				}

				// 一级菜单
				$res = array();

				$this->check_first_menu_name($item['name']);
				$res['name'] = $item['name'];

				foreach($item['sub_button'] as $key=>$sub) {
					$res['sub_button'][] = $this->filter_wx_menu($sub);
				}

				$wx_menu[] = $res;
			} else {
				// 二级菜单
				$wx_menu[] = $this->filter_wx_menu($item, 'first');
			}
		}
		$ret['button'] = $wx_menu;
		return $ret;
	}

	function update($ctx) {
		try {
			$aid = $_POST['id'] ? intval($_POST['id']) : 0;
			$json_menus = $_POST['menus'] ? $_POST['menus'] : '';
			$wx_menu = $this->format_wx_menu($json_menus);

			if (count($wx_menu['button']) > self::MAX_FIRST_MENU_COUNT) {
				throw new Exception('菜单数超过3个，不能添加');
			}

			$json = Text::json_encode($wx_menu);
			$update_data = array('menu'=>$json);

			$this->model->id = $aid;
			$ret = $this->model->update($update_data);
			if ($ret === false) {
				throw new Exception('系统错误');
			}
			$response = array('succ'=>1, 'msg'=>'操作成功');
		}catch (Exception $e) {
			$msg = $e->getMessage();
			$msg = !empty($msg) ? $msg : '操作失败';
			$response = array('succ'=>0, 'msg'=>$msg);
		}
		echo json_encode($response);
		$this->layout = false;
	}
	
	function sync_from_wx($ctx){
		$response = array('succ'=>1, 'msg'=>'操作成功');

		$this->layout = false;
		$aid = $_POST['aid'] ? intval($_POST['aid']) : 0;
		$account = WxAccount::get($aid);
		$wx_menu = new WxMenu($account);
		$str = $wx_menu->get();
		$resp = json_decode($str, true);
		if($resp['menu']){
			$s = Text::json_encode($resp['menu']);
			$account->update(array(
				'menu' => $s,
			));
		}else{
			$response['succ'] = 0;
			$response['msg'] = $str;
		}
			
		echo json_encode($response);
	}

	function push($ctx) {
		try {
			$aid = $_POST['aid'] ? intval($_POST['aid']) : 0;
			$account = WxAccount::get($aid);
			$wx_menu = new WxMenu($account);
			$ret = $wx_menu->create($account->menu);

			$response = array('succ'=>1, 'msg'=>'操作成功');
		}catch (Exception $e) {
			$msg = $e->getMessage();
			$msg = !empty($msg) ? $msg : '操作失败';
			$response = array('succ'=>0, 'msg'=>$msg);
		}
		echo json_encode($response);
		$this->layout = false;
	}
}
