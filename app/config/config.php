<?php
define('ENV', 'dev');

return array(
	'env' => ENV,
	#'magic_quotes_gpc' => 'Off', // Off|On, 改变PHP配置
	'logger' => array(
		'level' => 'debug', // none/off|(LEVEL)
		'dump' => 'file', // none|html|file, 可用'|'组合
		'files' => array( // ALL|(LEVEL)
			'ALL'	=> dirname(__FILE__) . '/../../logs/' . date('Y-m') . '.log',
		),
	),
	'db' => array(
		'host' => 'localhost',
		'dbname' => 'ashr',
		'username' => 'root',
		'password' => 'woshidazui',
		'charset' => 'utf8',
	),
	'ssdb' => array(
		'host' => '127.0.0.1',
		'port' => 8888,
	),
);
