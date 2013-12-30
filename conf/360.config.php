<?php
	//require php 5.3+
	return array(
		'domain' => 'jd.com',
		'page_suffix' => array('html', 'htm', 'aspx'),
		'exclude_args' => array(),
		'entry' => 'http://www.jd.com/allSort.aspx',
		'log_dir' => dirname(__FILE__) . '/../log/360buy/',
		'data_dir' => dirname(__FILE__) . '/../data/360buy/',
		'subdomain' => array(
			'www.jd.com' => array(
				'/^\/allSort\.aspx/' => array(
					'forward' => 'a'
				)
			),
			'list.jd.com' => array(
				'/.*/s' => array(
					'parser' => array(
						array('where' => "li[sku]", 'where2' => 'div[sku]', 'type' => 'item_block', 'detail' => array(
								array('type' => 'name', 'where' => 'div[class=p-name]', 'where2' => 'dt[class=p-name]', 'attr'=>'plaintext', 'callback' => function($str) {
									return trim($str);
								}),
								array('type' => 'href', 'where' => 'div[class=p-name] a', 'where2' => 'dt[class=p-name] a', 'attr' => 'href', 'callback' => function($str) {
									return $str;
								}),
								array('type' => 'price', 'dispensable' => true, 'where' => 'div[class=p-price] strong', 'where2' => 'dd[class=p-price] strong', 'attr' => 'plaintext','callback' => function($str) {
									return str_replace('￥', '', $str);
								}),
								array('type' => 'pic', 'where' => 'div[class=p-img] img', 'attr' => 'data-lazyload', 'attr2' => 'src', 'callback' => function($str) {
									return $str;
								}),
							),
						)
					),
					'forward' => '.pagin .next',
				)
			),
		),
	);
?>
