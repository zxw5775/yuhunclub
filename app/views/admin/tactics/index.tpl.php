<h1>策略管理</h1>
<div style="float: left;">
</div>

<p>
	<a class="btn btn-xs btn-primary" href="<?=_action('create')?>">
		<i class="glyphicon glyphicon-plus"></i> 新建策略
	</a>
</p>
<div class="query_form">
	<form method="get">
	</form>
</div>


<table class="table table-striped table-hover">
<thead>
	<tr>
		<th>购买合约名</th>
		<th>状态</th>
		<th>购买时ETF价</th>
		<th>提醒阈值</th>
		<th>创建时间</th>
		<th>操作</th>
	</tr>
</thead>
<tbody>
<?php foreach($ds['items'] as $m){ ?>
	<tr>
		<td><?=$m->contract_name?></td>
		<td><?=$m->status_text()?></td>
		<td><?=Money::fen2yuan($m->pay_amount)?></td>
		<td><?=$m->threshold?>%</td>
		<td><?=$m->create_time?></td>
		<td>
			<a onClick="if(confirm('确认删除？'))return true;return false;" href="<?=_action('del', array('id'=>$m->id))?>">删除</a>
			<a onClick="if(confirm('确认重置监控？'))return true;return false;" href="<?=_action('set_new', array('id'=>$m->id))?>">重置监控</a>
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
		ps.name = <?=json_encode($name)?>;
		if(ps.name.length == 0){
			delete ps.name;
		}
		var url = <?=json_encode(_url('admin/contract'))?> + '?' + $.param(ps);
		location.href = url;
	}
	pager.render();
});
</script>
