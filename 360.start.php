<?php
	ini_set('display_errors', 'On');
	ini_set('memory_limit', '2000M');
	date_default_timezone_set('Asia/Shanghai');
	error_reporting(E_ALL);
	include 'lib/SpiderController.php';
	$config = include 'conf/360.config.php';
	$spider = new SpiderController($config);
	if(PHP_OS == 'Linux') {
		declare(ticks = 1);
		if(pcntl_signal(SIGUSR1, "my_export") === false) {
			echo "pcntl_signal error\n";
		}
	}
	if(!isset($_SERVER['argv'][1])) {
		die("miss argv start|redo");
	}
	if($_SERVER['argv'][1] == 'start') {
		$spider->start();
		$spider->export();
	}
	else if($_SERVER['argv'][1] == 'redo') {
		$spider->redo((isset($_SERVER['argv'][2])?$_SERVER['argv'][2]:''));
		$spider->export();
	}

	function my_export() {
		global $spider;
		$spider->export();
	}
?>
