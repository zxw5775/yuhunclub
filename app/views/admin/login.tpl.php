<div class="panel panel-primary" style="max-width: 450px; margin: 10px auto;">
	<div class="panel-heading">
		<h3 class="panel-title">登录</h3>
	</div>
	<div class="panel-body">

	<?php if($errmsg){ ?>
	<div class="alert alert-danger">
        <strong>错误！</strong> <?=$errmsg?>
	</div>
	<?php } ?>

	<form method="post">
		<div class="form-group">
			<input autofocus="autofocus" class="form-control" name="name" placeholder="用户名" required="required" type="text" value="" />
		</div>
		<div class="form-group">
			<input autocomplete="off" class="form-control" name="password" placeholder="密码" required="required" type="password" value="" />
		</div>

		<div class="form-group">
			<img src="<?=_url('captcha')?>" onclick="change_code(this)" />
			<script>
			function change_code(img){
				img = $(img);
				var url = img.attr('src');
				url = url.replace(/\?.*$/, '') + '?' + (new Date()).getTime();
				img.attr('src', url);
			}
			</script>
			<input class="form-control" name="verify_code" type="text" placeholder="输入图片验证码" />
		</div>

		<div class="form-group">
			<input class="btn btn-lg btn-success btn-block" type="submit" value="登录" />
		</div>
	</form>
</div>
