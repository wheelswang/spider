<?php
	//require php 5.3+
	return array(
		'domain' => '51buy.com',
		'page_suffix' => array('html', 'htm'),
		'exclude_args' => array('YTAG'),
		'entry' => 'http://d.51buy.com/portal.html',
		'log_dir' => dirname(__FILE__) . '/../log/51buy/',
		'data_dir' => dirname(__FILE__) . '/../data/51buy/',
		'subdomain' => array(
			'd.51buy.com' => array(
				'/^\/portal\.html/' => array(
					'forward' => 'a'
				)
			),
			'list.51buy.com' => array(
				'/.*/s' => array(
					'parser' => array(
						array('where' => ".item_list", 'detail' => array(
								array('type' => 'name', 'where' => '.wrap_info .link_name a', 'attr'=>'plaintext', 'callback' => function($str) {
									return $str;
								}),
								array('type' => 'href', 'where' => '.wrap_info .link_name a', 'attr' => 'href', 'callback' => function($str) {
									return $str;
								}),
								array('type' => 'price', 'where' => '.price_icson .hot', 'where2' => '.price_cx .hot','attr' => 'plaintext','callback' => function($str) {
									return str_replace('&yen', '', $str);
								}),
								array('type' => 'pic', 'where' => '.link_pic img', 'attr' => 'src', 'callback' => function($str) {
									return $str;
								}),
							),
						)
					),
					'forward' => function($curr_url) {
						$url = preg_replace('/(.*?-.*?-.*?-.*?-.*?-.*?-)(.*?)(-.*?-\..*)$/e','"$1".("$2"<=1?2:$2+1)."$3"',$curr_url);
						return array($url);
					},
					'backward' => function($curr_url) {
						$url = preg_replace('/(.*?-.*?-.*?-.*?-.*?-.*?-)(.*?)(-.*?-\..*)$/e','"$1".($2-1<=1?"":$2-1)."$3"',$curr_url);
						return array($url);						
					}
				)
			),
			'search.51buy.com' => array(
				'/.*/s' => array(
					'parser' => array(
						array('where' => ".item_list", 'detail' => array(
								array('type' => 'name', 'where' => '.wrap_info .link_name a', 'attr'=>'plaintext', 'callback' => function($str) {
									return $str;
								}),
								array('type' => 'href', 'where' => '.wrap_info .link_name a', 'attr' => 'href', 'callback' => function($str) {
									return $str;
								}),
								array('type' => 'price', 'where' => '.price_icson .hot', 'where2' => '.price_cx .hot', 'attr' => 'plaintext','callback' => function($str) {
									return str_replace('&yen', '', $str);
								}),
								array('type' => 'pic', 'where' => '.link_pic img', 'attr' => 'src', 'callback' => function($str) {
									return $str;
								}),
							),
						)
					),
					'forward' => function($curr_url) {
						$url = preg_replace('/(.*?-.*?-.*?-.*?-.*?-.*?-)(.*?)(-.*?-\..*)$/e','"$1".("$2"<=1?2:$2+1)."$3"',$curr_url);
						return array($url);
					},
					'backward' => function($curr_url) {
						$url = preg_replace('/(.*?-.*?-.*?-.*?-.*?-.*?-)(.*?)(-.*?-\..*)$/e','"$1".($2-1<=1?"":$2-1)."$3"',$curr_url);
						return array($url);						
					}
				)
			)
		),
	);
?>
