<h1><?=!$m->id? '新建' : '编辑'?>微信账号</h1>

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
	<input type="hidden" name="id" value="<?=$m->id?>" />
<table class="table">
<tbody>
	<tr>
		<td width="120">ID</td>
		<td><?=$m->id?></td>
	</tr>
	<tr>
		<td>状态</td>
		<td>
			<?=Html::select('f[status]', array(''=>'')+WxAccount::status_table(), $m->status)?>
		</td>
	</tr>
	<tr>
		<td>类型</td>
		<td>
			<?=Html::select('f[type]', array(''=>'')+WxAccount::type_table(), $m->type)?>
		</td>
	</tr>
	<tr>
		<td>原始ID(*)</td>
		<td>
			<input class="form-control" type="text" name="f[wx_id]" value="<?=$m->wx_id?>" />
		</td>
	</tr>
	<tr>
		<td>Token(*)</td>
		<td>
			<input class="form-control" type="text" name="f[token]" value="<?=$m->token?>" />
		</td>
	</tr>
	<tr>
		<td>AppId(*)</td>
		<td>
			<input class="form-control" type="text" name="f[app_id]" value="<?=$m->app_id?>" />
		</td>
	</tr>
	<tr>
		<td>AppSecret(*)</td>
		<td>
			<input class="form-control" type="text" name="f[app_secret]" value="<?=$m->app_secret?>" />
		</td>
	</tr>
	<tr>
		<td>描述</td>
		<td>
			<textarea name="f[desc]" class="form-control"><?=htmlspecialchars($m->desc)?></textarea>
		</td>
	</tr>
	<tr>
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
