<h1>合约管理</h1>
<div style="float: left;">
</div>

<p>

</p>


<table class="table table-striped table-hover">
<thead>
	<tr>
		<th>合约名</th>
		<th>strike</th>
		<th>ask</th>
		<th>10%杠杆</th>
		<th>15%杠杆</th>
		<th>20%杠杆</th>
	</tr>
</thead>
<tbody>
<?php foreach($ds as $m){ ?>
	<tr>
		<td><?=$m->name?></td>
		<td><?=Money::fen2yuan($m->strike_amount)?></td>
		<td><?=Money::fen2yuan($m->ask_amount)?></td>
		<td><?=$m->level_10?></td>
		<td><?=$m->level_15?></td>
		<td><?=$m->level_20?></td>
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
		var url = <?=json_encode(_url('admin/contract'))?> + '?' + $.param(ps);
		location.href = url;
	}
	pager.render();
});
</script>
