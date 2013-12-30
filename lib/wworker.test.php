<?php
	include 'wworker.php';
	ini_set('memory_limit', '2000M');
	class myworker extends wworker {
		protected function worker($i) {
			return str_repeat('w', 1000*1000*1);
		}
		protected function master($ret) {
			foreach($ret as $buf) {
				echo strlen($buf). "\n";
			}
		}
	}
	$worker = new myworker(100);
	$worker->start();
	echo memory_get_peak_usage() . "\n";
	
?>
