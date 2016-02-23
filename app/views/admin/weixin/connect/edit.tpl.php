<h1><?=!$m->id? '新建' : '编辑'?>微信关联</h1>

<?php if($errmsg){ ?>
<div class="alert alert-danger">
	<strong>错误！</strong> <?=htmlspecialchars($errmsg)?>
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
		<td width="130">ID</td>
		<td><?=$m->id?></td>
	</tr>
	<tr>
		<td>UID</td>
		<td><?=$m->user_id?></td>
	</tr>
	<tr>
		<td>关注项目通知</td>
		<td>
			<?=Html::select('f[prj_subscribe]', WxConnect::prj_sub_table(), $m->prj_subscribe)?>
		</td>
	</tr>
	<tr>
		<td>是否粉丝</td>
		<td>
			<?=Html::select('f[wx_subscribe]', WxConnect::wx_sub_table(), $m->wx_subscribe)?>
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
