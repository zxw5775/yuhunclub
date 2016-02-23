<ul class="nav navbar-nav">

<?php
$ret = '';
foreach($menu as $key => $config){
	if(is_string($config)){
		$url = _url($key);
		$cls = ($key == App::$controller->module)? ' class="active"' : '';
		$ret .= "<li{$cls}><a href=\"$url\">$config</a></li>\n";
		continue;
	}

	$list = '';
	$active = '';
	foreach($config['sub'] as $kkey=>$name){
		$url = _url($kkey);
		if($kkey == App::$controller->module){
			$active = ' active';
			$list .= "<li class=\"active\"><a href=\"$url\">$name</a></li>\n";
		}else{
			$list .= "<li><a href=\"$url\">$name</a></li>\n";
		}
	}
	$ret .= "<li class=\"dropdown-toggle{$active}\">";
	$ret .= '<a data-toggle="dropdown" href="#">' . $config['name'] . '<b class="caret"></b></a>';
	$ret .= '<ul class="dropdown-menu" role="menu">';
	$ret .= $list;
	$ret .= '</ul></li>';
}

echo $ret;
?>

</ul>
