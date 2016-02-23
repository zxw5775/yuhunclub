<h1>微信粉丝openid列表</h1>

<div class="query_form">
	<form method="get">
		状态:
		<?=Html::select('status', array(''=>'全部')+WxOpenid::$status_table, $status)?>
		服务号微信id:
		<input name="wx_id" type="text" value="<?=$wx_id?>" />
		openid:
		<input name="wx_openid" type="text" value="<?=$wx_openid?>" />
		关注来源:
		<input name="source" type="text" value="<?=$source?>" />
		<button type="submit" class="btn btn-xs btn-primary">搜索</button>
	</form>
</div>


<table class="table table-striped table-hover">
<thead>
	<tr>
		<th>ID</th>
		<th>状态</th>
		<th>openid</th>
		<th>服务号微信id</th>
		<th>关注来源</th>
	</tr>
</thead>
<tbody>
<?php foreach($ds['items'] as $m){ ?>
	<tr>
		<td><?=$m->id?></td>
		<td><?=$m->status_text()?></td>
		<td><?=$m->wx_openid?></td>
		<td><?=$m->wx_id?></td>
		<td><?=$m->source?></td>
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

		ps.status = <?=json_encode($status)?>;
		if(ps.status.length == 0){
			delete ps.status;
		}
		ps.wx_id = <?=json_encode($wx_id)?>;
		if(ps.wx_id.length == 0){
			delete ps.wx_id;
		}
		ps.source = <?=json_encode($source)?>;
		if(ps.source.length == ''){
			delete ps.source;
		}
		ps.wx_openid = <?=json_encode($wx_openid)?>;
		if(ps.wx_openid.length == 0){
			delete ps.wx_openid;
		}
		
		var url = <?=json_encode(_list_url())?> + '?' + $.param(ps);
		location.href = url;
	}
	
	pager.render();
});
</script>
