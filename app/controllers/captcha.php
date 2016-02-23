<?php
class CaptchaController extends ApiBaseController
{
	function index($ctx){
		$this->is_ajax = false;
		$this->layout = false;
		require_once(APP_PATH . '/classes/captcha/SimpleCaptcha.php');
		$captcha = new SimpleCaptcha();
		$captcha->width = 140;
		$captcha->height = 60;
		$captcha->scale = 4;
		$captcha->blur = true;

		// OPTIONAL Change configuration...
		//$captcha->imageFormat = 'png';
		//$captcha->resourcesPath = "/var/cool-php-captcha/resources";

		if(isset($_GET['token']) && $_GET['token']){
			$code = SafeUtil::get_captcha($_GET['token']);
			$captcha->setText($code);
		}else{
			$code = $captcha->getText();
		}
		if(strlen($code)){
			SafeUtil::set_captcha($code, 300);
			$captcha->CreateImage();
		}
	}
	
	function access($ctx){
		$this->layout = false;
		require_once(APP_PATH . '/classes/captcha/SimpleCaptcha.php');
		$captcha = new SimpleCaptcha();
		$code = $captcha->getText();
		$token = SafeUtil::set_captcha($code, 300);
		$ret = array(
			'img_url' => _action('', array('token'=>$token)),
			'field_name' => SafeUtil::CAPTCHA_FIELD_NAME,
			'field_value' => $token,
		);
		return $ret;
	}
}
