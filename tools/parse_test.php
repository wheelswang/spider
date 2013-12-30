<?php
	ini_set('display_errors', 'On');
	include '../lib/simple_html_dom.php';
	$config = include('../conf/smzdm.config.php');

	$page = file_get_html('http://www.smzdm.com/category/%E4%B8%AA%E6%8A%A4%E5%8C%96%E5%A6%86');

	$blocks = $page->find('.perContentBox');
	foreach($blocks  as $block) {
		foreach($config['subdomain']['www.smzdm.com']['/category\/.*/i']['parser'][0]['detail'] as $c) {
			if(!$block->find($c['where'])) {
				echo $c['where'] . " empty \n";
			}
		}
	}
//end of script