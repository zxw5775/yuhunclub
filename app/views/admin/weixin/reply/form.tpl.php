<h1><?=!$m->id? '新建' : '编辑'?>自动回复</h1>

<?php
	$xml = @simplexml_load_string($m->content, 'SimpleXMLElement', LIBXML_NOCDATA);
?>

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
		<td>回复类型</td>
		<td>多条混合</td>
	</tr>
	<tr>
		<td width="80">规则类型</td>
		<td>
			<?=Html::select('keyword_type', array(''=>'') + WxReplyKeyword::$keyword_type_table, $keyword_type)?>
		</td>
	</tr>
	<tr>
		<td>关键词</td>
		<td>
			<input class="form-control" id="keywords" name="keywords" value="<?=$m->keywords?>" />
			包括: 关键词/菜单Key/事件名. 用逗号分隔
			<div class="hook-keyword-type" style="margin-top: 16px;">
			如果是渠道关注事件，请填写渠道用户UID，填写渠道用户UID，或者选择渠道用户
			<select onchange="$('#keywords').val($(this).val())">
				<option value="0">请选择</option>
				<?php $channel_list = $channel_list ? $channel_list : array()?>
				<?php foreach($channel_list as $v){
					$channel_subscribe_keywords = WxReplyKeyword::CHANNEL_SUBSCRIBE_PREFIE . $v->id;
				?>
					<option value="<?=$channel_subscribe_keywords?>" <?=($channel_subscribe_keywords == $m->keywords)?'selected="selected"':''?>><?=$v->name?></option>
				<?php }?>
			</select>
			</div>
		</td>
	</tr>

	<tr>
		<td colspan="2" class="items">
			<hr/>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<a class="btn btn-sm btn-default" onclick="add_row('news')">
				<i class="glyphicon glyphicon-plus"></i> 图文消息
			</a>
			&nbsp;
			<a class="btn btn-sm btn-default" onclick="add_row('text')">
				<i class="glyphicon glyphicon-plus"></i> 文本消息
			</a>
			<hr/>
		</td>
	</tr>

	<tr>
		<td></td>
		<td>
			<button class="btn btn-primary">保存</button>
			&nbsp; &nbsp;
			<a class="btn btn-default" onclick="history.go(-1)">返回</a>
		</td>
	</tr>
</tbody>
</table>

</form>


<script>

function hook_keyword_type(keyword_type) {
	switch (keyword_type) {
		case 'event':
			$(".hook-keyword-type").show();
			break;
		default:
			$(".hook-keyword-type").hide();
			break;
	}
}

$(function(){
	var keyword_type = '<?=$keyword_type?>';
	hook_keyword_type(keyword_type);
	
	$(document).on('change', '[name="keyword_type"]', function (e) {
		hook_keyword_type($(this).val());
	});
	
	<?php
	$list = @json_decode($m->content, true);
	if($list){
		foreach($list as $r){
	?>
	add_row(<?=json_encode($r['type'])?>, <?=json_encode($r)?>);
	<?php
		}
	}else{
	?>
	<?php } ?>
});

function add_row(type, data){
	var t = $('#item_template div.' + type).clone();
	var del = $('#item_template table.del').clone();
	t.find('input, texterea').val('');
	t.find('*[name*=type]').val(type);
	if(typeof(data) == 'object'){
		for(var k in data){
			var v = data[k];
			t.find('*[name*=' + k + ']').val(v);
		}
	}
	t.append(del).append('<hr/>');
	$('td.items').append(t);
}

function del_row(a){
	var t = $(a).parents('div:first').remove();
}
</script>



<div id="item_template" style="display: none;">
	<table width="100%" class="del">
		<tr>
			<td width="80"></td>
			<td style="text-align: right;">
				<a class="btn btn-sm btn-danger" onclick="del_row(this)">
					<i class="glyphicon glyphicon-remove"></i> 删除
				</a>
			</td>
		</tr>
	</table>
		
	<div class="text">
		<input type="hidden" name="type[]" value="text" />
		<input type="hidden" name="title[]" value="text" />
		<input type="hidden" name="desc[]" value="text" />
		<input type="hidden" name="img_url[]" value="text" />
		<input type="hidden" name="link[]" value="text" />
		<table width="100%">
			<tr>
				<td width="80"><b>内容</b></td>
				<td>
					<textarea class="form-control" name="content[]" style="height: 80px;"></textarea>
				</td>
			</tr>
		</table>
	</div>
	
	<div class="news">
		<input type="hidden" name="type[]" value="news" />
		<input type="hidden" name="content[]" value="text" />
		<table width="100%">
			<tr>
				<td width="80"><b>标题:</b></td>
				<td>
					<input class="form-control" name="title[]" value="" />
				</td>
			</tr>
			<tr>
				<td><b>简介:</b></td>
				<td>
					<textarea class="form-control" name="desc[]" style="height: 80px;"></textarea>
				</td>
			</tr>
			<tr>
				<td><b>图片:</b></td>
				<td><input class="form-control" name="img_url[]" value="" /></td>
			</tr>
			<tr>
				<td><b>链接:</b></td>
				<td><input class="form-control" name="link[]" value="" /></td>
			</tr>
		</table>
	</div>
</div>



