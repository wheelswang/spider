<?php
	include 'conf/config.php';
	$config = include 'conf/smzdm.config.php';
	$entrys = include 'conf/smzdm.entry.php';
	$all_domain = array();
	foreach($entrys as $entry) {
		$config['entry'] = $entry;
		$spider = new SpiderController($config);

		$dir = substr($entry, strrpos($entry, '/') + 1);
		$dir = urldecode($dir);

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
			$spider->export($dir);
		}
		else if($_SERVER['argv'][1] == 'redo') {
			$spider->redo((isset($_SERVER['argv'][2])?$_SERVER['argv'][2]:''));
			$spider->export($dir);
		}
	}

	function my_export() {
		global $spider;
		global $all_domain;
		$spider->export();
	}
?>
