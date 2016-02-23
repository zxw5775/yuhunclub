<?php
class Money
{
	/* 处理类型定义 */
	const TYPE_INVEST_NORMAL = 1;
	const TYPE_WITHDRAW = 2;
	const TYPE_INVEST_NO_CAPITAL = 3;
	
	/**
	 * 格式化金额的展示， 如
	 * 		10,000,000	：1000万；
	 * 		10,000		：1万
	 * 		1,000		：1000
	 * 
	 * @param int $money  金钱，单位为分
	 */
	public static function format_money_show($money_cents) {
		$scale = 100; // 1元，100分
		// 小于10000，直接展示金额
		if ($money_cents < 10000 * $scale) {
			$text = number_format($money_cents / $scale) . '元';
		} else {
			// 大于1万
			$scale *= 100 * 100;
			$text = number_format($money_cents / $scale) . '万';
		}
		
		return $text;
	}
	
	/**
	 * 格式化金额的展示， 如
	 * 		10,002,200	：1,000.22万；
	 * 		10,000		：1万
	 * 		1,000		：1,000
	 *
	 * @param int $money  金钱，单位为分
	 */
	public static function format_money_display($money_cents) {
		$scale = 100; // 1元，100分
		// 小于10000，直接展示金额
		if ($money_cents < 10000 * $scale) {
			$text = number_format($money_cents / $scale);
		} else {
			// 大于1万
			$scale *= 100 * 100;
			if($money_cents % $scale == 0){
				$text = number_format($money_cents / $scale) . '万';
			}else{
				$text = number_format($money_cents / $scale, 2) . '万';
			}
		}
	
		return $text;
	}


	static function show_money($money){
		$scale = 100;
		return str_replace('.', '.<span>', number_format(intval($money)/$scale,2, '.', ',') . '</span>');
	}
	
	static function show_money_new($amount, $num_class='', $punc_class=''){
		$str_money = number_format(self::fen2yuan($amount), 2, '.', ',');
		$format_money = '';
		for($i=0; $i<strlen($str_money); $i++){
			if(is_numeric($str_money[$i])){
				$class = $num_class;
			}else{
				$class = $punc_class;
			}
			$format_money .= '<span class="'.$class.'">'.$str_money[$i]."</span>\n";
		}
		return $format_money;
	}

	static function display_money($money){
		return number_format(self::fen2yuan($money), 2);
	}
	
	static function fen2yuan_float4($money){
		return sprintf("%.6f", (double)(intval($money*10000) / 10000 / 100));
	}
	static function display_float_money($money){
		return number_format(self::fen2yuan_float4($money), 6);
	}
	/**
	 * 元转换成分
	 * @param float $money  金钱，单位为元
	 */
	static function yuan2fen($m){
		if(is_float($m) || (is_string($m) && is_numeric($m))){
			$m = number_format($m, 2, '', '');
			return intval($m);
		}
		return intval($m * 100);
	}

	/**
	 * 元转换成分
	 * @param int $money  金钱，单位为分
	 */
	static function fen2yuan($money){
		return sprintf("%.2f", (double)(intval($money) / 100));
	}

	static function format_money($money){
		return ((int)($money * 100)) / 100;
	}
	/**
	 * 
	 * 
	 * 计算金钱的成分的
	 * TODO 增加零钱计划后 各成分使用情况
	 * 	投资的时候： 新充值资金 > 到期本金 > 零钱计划赎回 > 到期利息
	 * 	提现的时候： 利息 > 本金 > 零钱计划赎回 > 充值
	 * 	无本金投资： 新充值资金 > 零钱计划赎回 > 到期利息
	 * 
	 * @param $price 本次要花的钱
	 * @param $account 当前资金帐户的对象
	 * @param $type 处理类型
	 * @return array money_detail
	 * 
	 */
	public static function make_money_detail($price, $account=null, $type = self::TYPE_INVEST_NORMAL){
		$ret = array(
			'amount'=>$price,
			'fresh_amount'=>0,
			'invested_amount'=>0,
			'interest_amount'=>0,
			'lqjh_amount'=>0, // 从零钱计划赎回金额
		);
		if(null === $account){
			$ret['fresh_amount'] = $price;
			return $ret;
		}
		switch($type){
			case self::TYPE_INVEST_NORMAL:
				return self::make_money_detail_invest($price, $account);
			case self::TYPE_WITHDRAW:
				return self::make_money_detail_withdraw($price, $account);
			case self::TYPE_INVEST_NO_CAPITAL:
				return self::make_money_detail_invest_no_capital($price, $account);
			default:
				$ret['fresh_amount'] = $price;
				return $ret;
		}
	}
	// 投资：新充值资金 > 到期本金 > 零钱计划赎回 > 到期利息
	private static function make_money_detail_invest($price, $account){
		$ret = array(
			'amount'=>$price,
		);
		$fresh_amount = 0;
		$invested_amount = 0;
		$lqjh_amount = 0;
		$interest_amount = 0;
		
		$use_momey = $account->balance - $account->locked_amount;
		if($price <= $account->fresh_amount){
			$fresh_amount = min($price, $use_momey);
		}else{
			$fresh_amount = $account->fresh_amount;
			$invested_amount = min($account->invested_amount, $price - $fresh_amount);
			$lqjh_amount = min($account->lqjh_amount, $price - $fresh_amount - $invested_amount);
			$interest_amount = max(0,$price - $invested_amount - $fresh_amount - $lqjh_amount);
		}
		$ret['fresh_amount'] = $fresh_amount;
		$ret['invested_amount'] = $invested_amount;
		$ret['lqjh_amount'] = $lqjh_amount;
		$ret['interest_amount'] = $interest_amount;
		
		return $ret;
	}
	// 无本金投资：新充值资金 > 零钱计划赎回 > 到期利息
	private static function make_money_detail_invest_no_capital($price, $account){
		$ret = array(
			'amount'=>$price,
		);
		$fresh_amount = 0;
		$invested_amount = 0;
		$lqjh_amount = 0;
		$interest_amount = 0;
		
		$use_momey_no_capital = $account->balance - $account->locked_amount - $account->invested_amount;
		if($price <= $use_momey_no_capital){
			$fresh_amount = min($price, $account->fresh_amount);
			$lqjh_amount = min($account->lqjh_amount, $price - $fresh_amount);
			$interest_amount = $price - $fresh_amount - $lqjh_amount;
		}
		$ret['fresh_amount'] = $fresh_amount;
		$ret['invested_amount'] = $invested_amount;
		$ret['lqjh_amount'] = $lqjh_amount;
		$ret['interest_amount'] = $interest_amount;
		
		return $ret;
	}
	// 提现：利息 > 本金 > 零钱计划赎回 > 充值
	private static function make_money_detail_withdraw($price, $account){
		$ret = array(
			'amount'=>$price,
		);
		$fresh_amount = 0;
		$invested_amount = 0;
		$lqjh_amount = 0;
		$interest_amount = 0;
		$use_momey = $account->balance - $account->locked_amount;
		if($price <= $account->interest_amount+$account->invested_amount){
			$interest_amount = min($price, $account->interest_amount);
			$invested_amount = $price - $interest_amount; 
		}else{
			$interest_amount = $account->interest_amount;
			$invested_amount = min($price-$interest_amount, $account->invested_amount);
			$lqjh_amount = min($price - $interest_amount - $invested_amount, $account->lqjh_amount);
			$fresh_amount    = $price - $interest_amount - $invested_amount - $lqjh_amount;
		}
		$ret['fresh_amount'] = $fresh_amount;
		$ret['invested_amount'] = $invested_amount;
		$ret['lqjh_amount'] = $lqjh_amount;
		$ret['interest_amount'] = $interest_amount;
		return $ret;
	}
}
