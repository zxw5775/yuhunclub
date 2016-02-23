<h1>微信账号 <a href="<?=_url('/admin/weixin/account')?>" class="btn btn-default" role="button"><?=$account->desc?></a> 的菜单列表</h1>

<div name="add-buttons" class="btn-toolbar" style="margin-bottom:2px;">
	<button id="sync_from_wx" class="btn btn-default btn-primary">从微信同步</button>
	<button class="btn btn-primary" style="margin-left:5px;<?=($count < 3) ? '': 'display:none"'?>" name="add-normal-first">添加普通一级菜单</button>
	<button class="btn btn-primary" style="margin-left:5px;<?=($count < 3) ? '': 'display:none"'?>" name="add-multi-first">添加多维二级菜单</button>
	<button class="btn btn-danger" style="margin-left:5px" name="add-to-weixin">同步至微信</button>
	请先保存后再同步
</div>

<div name="menus" style="margin-top:22px">
</div>

<div style="margin-top:22px">
<button type="submit" id="submit" class="btn btn-primary">保存</button>
</div>

<ul id="ul_hidden" style="display:none">
	<li>
		<span><i class="icon-minus-sign"></i>二级菜单</span>
		<button type="button" class="btn btn-link" name="del-menu-second">删除该二级菜单</button>
		<ul>
			<li>
				<span><i class="icon-leaf"></i>名称</span> :
				<input data-tip="二级菜单名称长度最大为7个汉字" data-max="40" data-min="1" type="text" name="name" value="" />
			</li>
			<li>
				<span><i class="icon-leaf"></i>类型</span> :
				<input type="radio" name="type" value="click" /> 菜单点击
				<input type="radio" name="type" value="view" /> 链接跳转
			</li>
			<li>
				<span><i class="icon-leaf"></i>内容</span> : 
				<input type="text" data-tip="不能为空" name="content" value="" style="width:500px;" />
			</li>
		</ul>
	</li>
</ul>

<ul style="display:none" id="multi-ul-hidden">
		<li>
			<span><i class="icon-folder-open"></i> 一级菜单</span>
			<button type="button" class="btn btn-link" name="add-normal-second">添加二级菜单</button>
			<button type="button" class="btn btn-link" name="del-menu-first">删除该一级菜单</button>
			<ul id="multi-menu-ul">
				<li>
				   <span><i class="icon-minus-sign"></i>名称</span> :
				   <input type="text" data-tip="一级菜单名称最大长度为4个汉字" data-max="16" data-max="1" name="menu-name" value="" />
				</li>
			</ul>
		</li>
</ul>

<script>
$(function(){
	$('#sync_from_wx').click(function(){
		if(!confirm('从微信同步菜单配置, 将替换掉本地的菜单配置, 是否继续?')){
			return false;
		}
		var data = {};
		data.aid = <?=json_encode($aid)?>;
		var url = <?=json_encode(_action('sync_from_wx'))?>;
		doCommit(url, data);
		return false;
	});

	$('[name="add-to-weixin"]').click(function(e) { 
		if(!confirm('将更新微信账号的自定义菜单, 是否继续?')){
			return false;
		}
		var data = new Object();
		data.aid = aid;
		var json_post = JSON.parse(JSON.stringify(data));
		var url = "<?=_action('push')?>";
		doCommit(url, json_post);
	});
});

// 检测长度 中英文
String.prototype.len = function() {
	return this.replace(/[^\x00-\xff]/g,"rr").length;
}

var aid = <?=$aid?>;
// 动态添加 menu_leve = 1 or 2,分别表示一级菜单 or 二级菜单
var _createMenu = function (str) {
	var menu_str = (typeof(str) == 'undefined') ? '{}' : str;
	var menuDiv = $('#ul_hidden').clone(true).removeAttr("id").show();

	var _t = new Date().getTime() + '_' + Math.round(Math.random() * 100);
	menuDiv.find('[name="name"]').val(menu_str.name);	
	menuDiv.find('[value="' + menu_str.type + '"]').attr("checked", "checked");
	menuDiv.find('[name="type"]').attr("name", "type_" + _t);
	if (menu_str.type == 'click') {
		menuDiv.find('[name="content"]').attr('data-max', 128).attr('data-min', 1).val(menu_str.key);
	} else {
		menuDiv.find('[name="content"]').attr('data-max', 256).attr('data-min', 1).val(menu_str.url);	
	}
	return menuDiv;
}

var _tojson = function() {
	var post_json = '{}';
	var post_data = new Array();
	// 检测结果
	var check_res = 1;

	$('div.tree').not(function(){$(this).is(":hidden")}).each(function(key) { 
		if($(this).is(":hidden")) {
			return;
		}

		var level = $(this).data('level');;
		var patten = new RegExp('type_*');
		var data = new Array();

		if (level == 'multi') {
			var multi_data = new Object();
			var seconds = new Array();
			var dom_menu_name = $(this).find('[name="menu-name"]').eq(0);
			var menu_name = dom_menu_name.val();

			dom_menu_name.parent('li').siblings('li').each(function(k) {
				var tmp = new Object();

				var dom_input_name = $(this).find('[name="name"]').eq(0);
				var name = dom_input_name.val();
				var max_name = dom_input_name.data('max');
				var min_name = dom_input_name.data('min');
				if (name.len() < min_name || name.len() > max_name) {
					var tip = dom_input_name.data('tip');
					dom_input_name.siblings('label').remove();
					dom_input_name.after('<label class="tip">' + tip + '</label>');
					check_res = -1;
					return;
				}

				var dom_input_content = $(this).find('[name="content"]').eq(0);
				var content = dom_input_content.val();
				var max_content = dom_input_content.data('max');
				var min_content = dom_input_content.data('min');
				if (content.len() < min_content || content.len() > max_content) {
					var tip = dom_input_content.data('tip');
					dom_input_content.siblings('label').remove();
					dom_input_content.after('<label class="tip">' + tip + '</label>');
					check_res = -1;
					return;
				}

				tmp.name = name;
				tmp.type = $(this).find('[name^="type"]:checked').eq(0).val();
				if (tmp.type == 'click') {
					tmp.key = $(this).find('[name="content"]').eq(0).val();
				} else {
					tmp.url = $(this).find('[name="content"]').eq(0).val();
				}

				seconds.push(tmp);
			});
			var seconds_json = JSON.parse(JSON.stringify(seconds));
			multi_data.name = menu_name;
			multi_data.sub_button = seconds_json;

			post_data.push(multi_data);
		} else {
			var tmp = new Object();

			var dom_input_name = $(this).find('[name="name"]').eq(0);
			var name = dom_input_name.val();
			var max_name = dom_input_name.data('max');
			var min_name = dom_input_name.data('min');
			if (name.len() < min_name || name.len() > max_name) {
				var tip = dom_input_name.data('tip');
				dom_input_name.siblings('label').remove();
				dom_input_name.after('<label class="tip">' + tip + '</label>');
				check_res = -1;
				return;
			}

			var dom_input_content = $(this).find('[name="content"]').eq(0);
			var content = dom_input_content.val();
			var max_content = dom_input_content.data('max');
			var min_content = dom_input_content.data('min');
			if (content.len() < min_content || content.len() > max_content) {
				var tip = dom_input_content.data('tip');
				dom_input_content.siblings('label').remove();
				dom_input_content.after('<label class="tip">' + tip + '</label>');
				check_res = -1;
				return;
			}

			tmp.name = name;
			tmp.type = $(this).find('[name^="type"]:checked').eq(0).val();
			if (tmp.type == 'click') {
				tmp.key = $(this).find('[name="content"]').eq(0).val();
				if(tmp.key.length == 0){
					tmp.key = tmp.name;
				}
			} else {
				tmp.url = $(this).find('[name="content"]').eq(0).val();
			}
			post_data.push(tmp);
		}
	});

	// 拼接成json
	var post_json = JSON.stringify(post_data);
	if (check_res == -1) {
		return -1;
	}
	return post_json;
}

var add_div_move_buttons = function() {
	var divTrees = $('div.tree'); 
	var div_tree_count = divTrees.length;
	divTrees.not(function(){$(this).is(":hidden")}).each(function(key) { 
		if($(this).is(":hidden")) {
			return;
		}
		if(key == 0 && key == div_tree_count - 1) {
			html = '';
		} else if(key == 0) {
			html = '<button class="btn btn-primary" style="margin-left:5px;" name="moveDown">下移</button>';
		} else if(key == div_tree_count - 1) {
			html = '<button class="btn btn-primary" style="margin-left:5px;" name="moveUp">上移</button>';
		} else {
			html = '<button class="btn btn-primary" style="margin-left:5px;" name="moveUp">上移</button>';
			html += '<button class="btn btn-primary" style="margin-left:5px;" name="moveDown">下移</button>';
		}
		$(this).find('[name="moveUp"]').remove();
		$(this).find('[name="moveDown"]').remove();
		$(this).find('ul').eq(0).before(html);
	});
}

var add_div_html = function(name) {
	var divHtml = '<div class="tree" data-level="' + name + '"></div>';
	var divTree = $(divHtml);
	return divTree;
}

var show_normal_first = function(str) {
	var html = _createMenu(str);
	html.find('span').eq(0).html('<i class="icon-minus-sign"></i>一级菜单');
	html.find('button').eq(0).text('删除该一级菜单');
	html.find('button').eq(0).attr('name', 'del-menu-first');
	html.find('[name$="name"]').eq(0).data('tip', '一级菜单名称最大长度为4个汉字').attr('data-max', 14).attr('data-min', 1);


	var divTree = add_div_html('normal');
	divTree.append(html);
	$('[name="menus"]').append(divTree);
}

var show_second = function(str) {
	var html = _createMenu(str);
	var t = $(html.children());

	return t;
}

var show_multi_first = function(str) {
	var menu_str = (typeof(str) == 'undefined') ? '{}' : str;
	var menuDiv = $('#multi-ul-hidden').clone(true).removeAttr("id").show();
	menuDiv.find('[name="menu-name"]').val(menu_str.name);	

	if (menu_str.hasOwnProperty('sub_button') && menu_str.sub_button.length > 0) {
		jQuery.each(menu_str.sub_button, function(i, field) {
			var html = show_second(field);
			menuDiv.find('#multi-menu-ul').eq(0).append(html);
		});
	} else {
		var html = show_second();
		menuDiv.find('#multi-menu-ul').eq(0).append(html);
	}

	menuDiv.find('#multi-menu-ul').eq(0).removeAttr("id");;

	var divTree = add_div_html('multi');
	divTree.append(menuDiv);
	$('[name="menus"]').append(divTree);
}

var doCommit = function(url, data) {
	$.post(url, data, function (result) {
		alert(result.msg);
		if (result.succ == 1) {
			if (result.hasOwnProperty("uri")) {
				location.replace(result.uri);
			} else {
				location.reload();
			}
		}
	}, 'JSON');
};

var doAddButtonShow = function () {
	if($('div.tree').length >= 3) {
		$('[name="add-multi-first"]').eq(0).hide();
		$('[name="add-normal-first"]').eq(0).hide();
	}else {
		$('[name="add-multi-first"]').eq(0).show();
		$('[name="add-normal-first"]').eq(0).show();
	}
}

var checkFirstMenuCount = function () {
	if($('div.tree').length >= 3) {
		alert('最多只能有3个一级菜单');
		return false;
	}
	return true;
}

// 菜单类型适配
$(document).on('change', '[name^="type"]', function (e) {
	var menu_type = $(this).val();
	if(menu_type == 'click') {
		var n = $(this).parent('li').eq(0).prev('li').find('input').val();
		$(this).parent('li').eq(0).next('li').find('input').val(n);
		$(this).parent('li').eq(0).next('li').find('input').attr('placeholder', '请填写事件KEY').data('tip', '请填写事件KEY').attr('data-max', 128).attr('data-min', 1);
	}else {
		$(this).parent('li').eq(0).next('li').find('input').attr('placeholder', '请填写跳转URL').data('tip', '请填写跳转URL').attr('data-max', 256).attr('data-min', 1);
	}
});

// 移动
$(document).on('click', '[name="moveUp"]', function(e) {
	var thisDiv = $(this).closest('div.tree');
	var nextDiv = thisDiv.prev('div.tree').eq(0);
	var cloneDiv = thisDiv.clone(true);
	thisDiv.remove();
	nextDiv.before(cloneDiv);
	add_div_move_buttons();
});
$(document).on('click', '[name="moveDown"]', function(e) {
	var thisDiv = $(this).closest('div.tree');
	var beforeDiv = thisDiv.next('div.tree').eq(0);
	var cloneDiv = thisDiv.clone(true);
	thisDiv.remove();
	beforeDiv.after(cloneDiv);
	add_div_move_buttons();
});

$(document).on('focus', '[type="text"]', function(e) {
	var tip = $(this).data('tip');
	$(this).after('<label class="tip">' + tip + '</label>');
});
$(document).on('blur', '[type="text"]', function(e) {
	$(this).siblings('label').remove();
});

$(document).on('click', '[name="add-multi-first"]', function(e) {
	if (checkFirstMenuCount()) {
		show_multi_first();
	}
	doAddButtonShow();
	add_div_move_buttons();
});

$(document).on('click', '[name="add-normal-first"]', function(e) {
	if (checkFirstMenuCount()) {
		show_normal_first();
	}
	doAddButtonShow();
	add_div_move_buttons();
});

$(document).on('click', '[name="del-menu-first"]', function (e) {
	$(this).closest('div').remove();
	doAddButtonShow();
	add_div_move_buttons();

});

$(document).on('click', '[name="add-normal-second"]', function (e) {
	if($(this).closest('ul').find('[name="del-menu-second"]').length >= 5) {
		$(this).hide();
		alert('一级菜单最多添加5个二级菜单');
		return;
	}
	var html = show_second();
	$(this).siblings('ul').eq(0).append(html);

	if($(this).closest('ul').find('[name="del-menu-second"]').length >= 5) {
		$(this).hide();
	}
});

$(document).on('click', '[name="del-menu-second"]', function (e) {
	if($(this).closest('ul').find('[name="del-menu-second"]').length <= 5) {
		$(this).closest('div').find('[name="add-normal-second"]').eq(0).show();
	}
	$(this).closest('li').remove();
});

$('#submit').click(function(e) {
	var url = "<?=_action('update')?>";
	var menus = _tojson();
	if (menus == -1) {
		alert('数据填写错误，请检查');
		return;
	}
	var data = {id:aid, menus: menus};

	doCommit(url, data);
});

var menus = <?=$menus?>;
jQuery.each(menus, function(i, field) {
	if (field.hasOwnProperty('sub_button') && field.sub_button.length > 0) {
		show_multi_first(field);
	} else {
		show_normal_first(field);
	}
	add_div_move_buttons();
});

</script>
