<h1>微信用户列表</h1>

<div class="query_form">
	<form method="get">		
		UID:
		<input name="user_id" type="text" value="<?=$user_id?>" size="6" />
		OPEN_ID:
		<input name="wx_openid" type="text" value="<?=$wx_openid?>" >
		订阅状态：
		<select name="prj_subscribe">
			<option value="" <?=($prj_subscribe  === '' ? 'selected="selected"' : '')?>>全部</option>
			<option value="1" <?=($prj_subscribe == '1' ? 'selected="selected"' : '')?>>已订阅</option>
			<option value="0" <?=($prj_subscribe == '0' ? 'selected="selected"' : '')?>>未订阅</option>
		</select>
		<button type="submit" class="btn btn-xs btn-primary">搜索</button>
	</form>
</div>


<table class="table table-striped table-hover">
<thead>
	<tr>
		<th>ID</th>
		<th>绑定时间</th>
		<th>UID</th>
		<th>openid</th>
		<th>是否订阅项目通知</th>
		<th>是否微信粉丝</th>
		<th>操作</th>
	</tr>
</thead>
<tbody>
<?php foreach($ds['items'] as $m){ ?>
	<tr>
		<td><?=$m->id?></td>
		<td><?=$m->time?></td>
		<td><?=AdminUtil::show_user($m->user_id)?></td>
		<td><?=$m->wx_openid?></td>
		<td><?=$m->prj_subscribe ? '已订阅' : '未订阅'?></td>
		<td><?=$m->wx_subscribe ? '是' : '否'?></td>
		<td>
			<a href="<?=_action('edit', $m)?>">编辑</a>
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
