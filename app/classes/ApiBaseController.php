<?php
class ApiBaseController extends AjaxController
{
	function init($ctx){
		parent::init($ctx);
		header('P3P:CP=" OTI DSP COR IVA OUR IND COM "');
		$ref = $_SERVER['HTTP_REFERER'];
		$allow_domains = array('axelahome.com');
		$allow = false;
		foreach($allow_domains as $domain){
			if(preg_match("/^http(s)?:\/\/[^\/]*$domain\//", $ref)){
				$allow = true;
				break;
			}
		}
		if(ENV != 'dev' && !$allow){
			_throw("非法的 Referer: " . htmlspecialchars($ref));
		}
		
		$ctx->user = UC::auth();
	}
}
