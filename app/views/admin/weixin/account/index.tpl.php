<h1>微信账号列表</h1>

<p style="color: red;">注：当类型为企业等时，微信并未提供原始ID，因而此时该字段为字面意思</p>
<div>
	<a class="btn btn-sm btn-primary" href="<?=_new_url()?>">
		<i class="glyphicon glyphicon-plus"></i> 添加
	</a>
</div>

<table class="table table-striped table-hover">
<thead>
	<tr>
		<th>ID</th>
		<th>状态</th>
		<th>类型</th>
		<th>描述</th>
		<th>原始ID</th>
		<th>Token</th>
		<th>AppId</th>
		<th>AppSecret</th>
		<th>AccessToken</th>
		<th>JsToken</th>
		<th>菜单</th>
		<th>操作</th>
	</tr>
</thead>
<tbody>
<?php foreach($ds['items'] as $m){ ?>
	<tr>
		<td><?=$m->id?></td>
		<td><?=$m->status_text()?></td>
		<td><?=$m->type_text()?></td>
		<td><?=$m->desc?></td>
		<td><?=$m->wx_id?></td>
		<td><?=$m->token?></td>
		<td><?=$m->app_id?></td>
		<td><?=$m->app_secret?></td>
		<td>
		<?php if ($m->type != WxAccount::TYPE_QIYE) {?>
			<a class="btn btn-xs btn-primary" href="#" onclick="alert($(this).next('span').html())">显示</a>
			<span style="display: none;"><?=$m->get_access_token()?></span>
			<a class="btn btn-xs btn-danger" onclick="return confirm('确认要刷新 Access Token 吗?')" href="<?=_action('refresh_access_token', $m)?>">强制刷新</a>
		<?php }?>
		</td>
		<td>
		<?php if ($m->type != WxAccount::TYPE_QIYE) {?>
			<a class="btn btn-xs btn-primary" href="#" onclick="alert($(this).next('span').html())">显示</a>
			<span style="display: none;"><?=$m->get_js_token()?></span>
			<a class="btn btn-xs btn-danger" onclick="return confirm('确认要刷新 Js Token 吗?')" href="<?=_action('refresh_js_token', $m)?>">强制刷新</a>
		<?php }?>
		</td>
		<td><a href="<?=_url('/admin/weixin/menu', array('aid'=>$m->id))?>">菜单管理</a></td>
		<td>
			<a class="btn btn-sm btn-primary" href="<?=_edit_url($m)?>">编辑</a>
		</td>
	</tr>
<?php } ?>
</tbody>
</table>



<div id="pager">
</div>

<script>
$(function(){
	var pager = new PagerView('pager');
	pager.index = <?=$page?>;
	pager.size = <?=$size?>;
	pager.itemCount = <?=$ds['total']?>;
	pager.onclick = function(index){
		var size = pager.size;
		var page = pager.index;
		var ps = {page: page, size: size};
		var url = <?=json_encode(_list_url())?> + '?' + $.param(ps);
		location.href = url;
	}
	
	pager.render();
});
</script>
