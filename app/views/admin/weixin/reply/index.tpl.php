<h1>微信自动回复</h1>

<div style="float: left;">
	<a class="btn btn-sm btn-primary" href="<?=_url('admin/weixin/reply/add')?>">
		<i class="glyphicon glyphicon-plus"></i> 新建回复
	</a>
</div>

<div style="float: right;">
	<form method="get">
		<select name="keyword_type" style="width: 80px;">
			<option value="">全部</option>
			<option value="equal" <?=$keyword_type=='equal'?'selected="selected"':''?>>关键词 - 全匹配</option>
			<option value="contain" <?=$keyword_type=='contain'?'selected="selected"':''?>>关键词 - 模糊匹配</option>
			<option value="click" <?=$keyword_type=='click'?'selected="selected"':''?>>菜单点击</option>
			<option value="event" <?=$keyword_type=='event'?'selected="selected"':''?>>事件</option>
		</select>
		关键词:
		<input type="text" name="s" value="<?php echo htmlspecialchars($s)?>" style="width: 120px;" />
		<button type="submit" class="btn btn-xs btn-primary">搜索</button>
	</form>
</div>

<div style="clear: both; line-height: 0px; height: 0px;"></div>
<hr/>

<?php foreach($ds['items'] as $index=>$m){ ?>
<div class="wx_reply_item">
	<h3><?=$m->id?>.
		关键词:
		<?php foreach($m->keywords() as $k){ ?>
			[<code><?=$k->type?></code>]<?=$k->keyword?>
		<?php } ?>
	</h3>
	<p>
		<a class="btn btn-sm btn-primary" href="<?=_url('admin/weixin/reply/edit', array('id'=>$m->id))?>">编辑</a>
		&nbsp; &nbsp;
		<a class="btn btn-sm btn-danger" onclick="return confirm('确定要删除吗?');" href="<?=_url('admin/weixin/reply/delete', array('id'=>$m->id))?>">删除</a>
	</p>

	<?php
	$list = @json_decode($m->content);
	$text_arr = array();
	$news_arr = array();
	foreach($list as $r){
		if($r->type == 'text'){
			$text_arr[] = $r;
		}else{
			$news_arr[] = $r;
		}
	}
	?>
	
	<?php foreach($text_arr as $r){ ?>
		<div class="wx_msg">
			<div><?=nl2br(htmlspecialchars($r->content))?></div>
		</div>
	<?php } ?>

	<?php foreach($news_arr as $index=>$r){ ?>
		<?php if(count($news_arr) == 1){ ?>
			<div class="wx_msg">
				<div class="title"><?=$r->title?></div>
				<p><img src="<?=$r->img_url?>" /></p>
				<p><?=nl2br(htmlspecialchars($r->desc))?></p>
				<p style="border-top: 1px solid #ddd;"><a target="_blank" href="<?=$r->link?>">阅读全文</a></p>
			</div>
		<?php }else{ ?>
			<?php if($index == 0){ ?>
				<div class="wx_msg">
					<p><img src="<?=$r->img_url?>" /></p>
					<div class="title"><a target="_blank" href="<?=$r->link?>"><?=$r->title?></a></div>
			<?php }else{ ?>
				<div style="border-top: 1px solid #ccc; height: 40px;">
					<img src="<?=$r->img_url?>" style="float: right; width: 40px; height: 40px;" />
					<a target="_blank" href="<?=$r->link?>"><?=$r->title?></a>
				</div>
			<?php } ?>
			<?php if($index == count($news_arr) - 1){ ?>
				</div>
			<?php } ?>
		<?php } ?>
	<?php } ?>

</div>
<?php } ?>

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
		var url = <?=json_encode(_url('admin/weixin/reply'))?> + '?' + $.param(ps);
		location.href = url;
	}
	
	pager.render();
});
</script>
