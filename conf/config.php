<?php
define('ROOT', dirname(__FILE__) . '/../');
ini_set('display_errors', 'On');
ini_set('memory_limit', '2000M');
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);
umask(0);

$autoLoadDirs = array('lib', 'model');

HttpCurl::set_timeout(5);
HttpCurl::set_proxy('proxy.tencent.com', 8080);

function __autoload($class_name) {
	global $autoLoadDirs;
	foreach($autoLoadDirs as $dir) {
		$file = ROOT . $dir . '/' . $class_name . '.php';
		if(file_exists($file)) {
			include($file);
			return;
		}
	}
}
