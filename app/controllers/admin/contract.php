<?php
class ContractController extends AdminController
{
	/*
	 * 列表页
	 */
	function index($ctx){
		$page = $_GET['page']? intval($_GET['page']) : 0;
		$size = $_GET['size']? intval($_GET['size']) : 15;
		$name = $_GET['name'];
		$ctx->page = $page;
		$ctx->size = $size;
		$where  = 1;
		$where .= " and status = '".Contract::STATUS_NEW."'";
		if($name){
			$name  = htmlspecialchars($name);
			$where .= " and name like '%{$name}%'";
		}
		$ctx->ds = Contract::paginate($page, $size, $where);
		$ctx->name = $name;
	}
	
	function calculate($ctx){
		$where = 1;
		$where = "status = '".Contract::STATUS_NEW."'";
		$real_data = RealData::get_cache();
		$real_price = $real_data['amount'];
		$sql  = "SELECT name, strike_amount, ask_amount,";
		$sql .= " ({$real_price}*(1+0.1)-strike_amount)/ask_amount as  level_10,";
		$sql .= " ({$real_price}*(1+0.15)-strike_amount)/ask_amount as level_15,";
		$sql .= " ({$real_price}*(1+0.20)-strike_amount)/ask_amount as level_20";
		$sql .= " FROM ".Contract::table();
		$sql .= " WHERE status = '".Contract::STATUS_NEW."'";
		$sql .= "order by level_20 desc, level_15 desc, level_10 desc";
		$ctx->ds   = Db::find($sql);
	}

	
	function del($ctx){
		$id = intval($_GET['id']);
		$contract = Contract::get($id);
		if($contract){
			$contract->set_del();
		}
		_redirect(_list_url());
	}
	
}
