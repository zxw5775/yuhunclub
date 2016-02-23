<h1>合约管理</h1>
<div style="float: left;">
</div>


<div class="query_form">
	<form method="get">
		合约名（支持模糊匹配）:
		<input name="name" type="text" value="<?=$name?>" size="8" />
		<button type="submit" class="btn btn-xs btn-primary">搜索</button>
	</form>
</div>


<table class="table table-striped table-hover">
<thead>
	<tr>
		<th>合约名</th>
		<th>状态</th>
		<th>strike</th>
		<th>last</th>
		<th>bid</th>
		<th>ask</th>
		<th>change</th>
		<th>change%</th>
		<th>volume</th>
		<th>open_interest</th>
		<th>lmplied_rate</th>
		<th>最后更新时间</th>
		<th>操作</th>
	</tr>
</thead>
<tbody>
<?php foreach($ds['items'] as $m){ ?>
	<tr>
		<td><?=$m->name?></td>
		<td><?=$m->status_text()?></td>
		<td><?=Money::fen2yuan($m->strike_amount)?></td>
		<td><?=Money::fen2yuan($m->last_amount)?></td>
		<td><?=Money::fen2yuan($m->bid_amount)?></td>
		<td><?=Money::fen2yuan($m->ask_amount)?></td>
		<td><?=Money::fen2yuan($m->change_amount)?></td>
		<td><?=$m->change_rate?>%</td>
		<td><?=$m->volume?></td>
		<td><?=$m->open_interest?></td>
		<td><?=$m->lmplied_rate?>%</td>
		<td><?=$m->modify_time?></td>
		<td><a onClick="if(confirm('确认删除？'))return true;return false;" href="<?=_action('del', array('id'=>$m->id))?>">删除</a></td>
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
