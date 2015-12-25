<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>管理后台<?php if($title){echo ' - ' . $title;} ?></title>
	<link href="<?php echo _url('/css/bootstrap.min.css') ?>" rel="stylesheet">
	<link href="<?php echo _url('/css/bootstrap-datetimepicker.min.css') ?>" rel="stylesheet">
	<link href="<?php echo _url('/css/menu.css') ?>" rel="stylesheet">
	<link href="<?php echo _url('/css/admin.css') ?>" rel="stylesheet">
	<script src="<?php echo _url('/js/jquery-1.9.1.min.js') ?>"></script>
	<script src="<?php echo _url('/js/bootstrap.min.js') ?>"></script>
	<script src="<?php echo _url('/js/bootstrap-datetimepicker.min.js') ?>"></script>
	<script src="<?php echo _url('/js/PagerView.js') ?>"></script>
</head>
<body>
<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
	<div class="container">
		<div class="navbar-header">
			<ul class="nav navbar-nav">
				<li>
					<a class="" href="<?=_url('admin')?>" data-toggle="">管理后台</a>
				</li>
			</ul>
		</div>
		<?php _widget('admin/_menu', array('menu' => AdminController::$menu)); ?>
		<ul class="nav navbar-nav navbar-right">
			<li>
				<a class="" href="<?=_url('admin/logout')?>" data-toggle="">退出</a>
			</li>
		</ul>
	</div>
</div>

<?php
	if($_SESSION['admin_user']){
		$real_data = RealData::get_cache();
		if($real_data){
			echo "ASHR 行情：更新时间：{$real_data['date']} 价格：".Money::fen2yuan($real_data['amount']);
		}
	}
?>

<div class="container">
	<?php _view(); ?>
		
	<div class="footer">
		Copyright&copy;2014 www.axelahome.com. All rights reserved.
		<?php printf('%.2f', 1000*(microtime(1) - APP_TIME_START)); ?> ms
	</div>

</div>
<!-- /container -->

</body>
</html>
