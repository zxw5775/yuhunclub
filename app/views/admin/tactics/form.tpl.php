<h1><?=!$m->id? '新建' : '编辑'?>站点</h1>

<?php if($errmsg){ ?>
<div class="alert alert-danger">
    <strong>错误！</strong> <?=$errmsg?>
</div>
<?php }else if($_POST){ ?>
<div class="alert alert-success">
    <strong>成功！</strong> 已经保存!
</div>
<?php } ?>
<form role="form" method="post" action="">
<table class="table">
<tbody>
	<tr>
		<td>购买合约名</td>
		<td>
			<input class="form-control" type="text" name="f[contract_name]" value="" />
		</td>
	</tr>
	<tr>
		<td>购买时ETF价格</td>
		<td>
			<input class="form-control" type="text" name="f[pay_amount]" value="" />
		</td>
	</tr>
	<tr>
		<td>阈值</td>
		<td>
			<input class="form-control" type="text" name="f[threshold]" value="1.2" />
		</td>
	</tr>
		<td></td>
		<td>
			<button class="btn btn-primary">保存</button>
			&nbsp; &nbsp;
			<a class="btn btn-default" href="<?=_list_url();?>">返回</a>
		</td>
	</tr>
</tbody>
</table>

</form>
