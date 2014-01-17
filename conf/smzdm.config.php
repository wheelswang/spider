<?php
	//require php 5.3+
	$b2c_domain = array('amazon.com', 'amazon.cn', 'suning.com', 'dangdang.com', 'myhabit.com', 'yihaodian.com', 'awin1.com');
	return array(
		'domain' => 'smzdm.com',
		'merchant' => 'smzdm',
		'page_suffix' => array(),
		'exclude_args' => array(),
		'entry' => 'http://www.smzdm.com/page/1',
		'log_dir' => dirname(__FILE__) . '/../log/smzdm/',
		'data_dir' => dirname(__FILE__) . '/../data/smzdm/',
		'subdomain' => array(
			'www.smzdm.com' => array(
				'/fenlei\/.*/i' => array(
					'parser' => array(
						array('where' => ".perContentBox", 'type' => 'item_block', 'detail' => array(
								array('type' => 'name', 'where' => '.con_title a', 'attr'=>'plaintext', 'callback' => function($str) {
									return trim($str);
								}),
								array('type' => 'url', 'where' => '.bugBlock a', 'where2' => '.bugBlockMore a', 'attr' => 'href', 'callback' => function($str) {
									return SmzdmModel::getProductUrl($str);
								}),
								array('type' => 'href', 'where' => '.con_title a', 'attr' => 'href', 'callback' => function($str) {
									return $str;
								}),
								array('type' => 'pic', 'where' => '.imgBox img', 'attr' => 'src', 'callback' => function($str) {
									if(SmzdmModel::isMyImgLink($str)) {
										$file_path = Utils::savePic($str, dirname(__FILE__) . '/' . '/../data/smzdm/' . date('Y_m_d') . '/img/');
										if($file_path === false) {
											log_err('crawl product img error:' . $str);
											return 'error';
										}
										return 'img/smzdm/' . $file_path;
									}
									return $str;
								}),
								array('type' => 'content', 'where' => '.p_excerpt', 'attr' => 'plaintext', 'callback' => function($str) {
									return trim($str);
								}),
								array('type' => 'merchant', 'where' => '.mall', 'attr' => 'plaintext', 'callback' => function($str) {
									$str = str_replace('商城：', '', $str);
									return trim($str);
								}),
								array('type' => 'add_time', 'where' => '.rfloat', 'attr' => 'plaintext', 'callback' => function($str) {
									return strtotime(date('Y') . '-' . trim($str));
								}),
							),
						)
					),
					'forward' => array('.pagedown', '.con_title a'),
					'limit' => 1,
				),
				'/\/youhui\/\d+/i' => array(
					'parser' => array(
						array('where' => 'li[id^=li-comment-]', 'type' => 'comment', 'detail' => array(
								array('type' => 'name', 'where' => '.commentName a', 'attr' => 'plaintext', 'callback' => function($str) {
									return trim($str);
								}),
								array('type' => 'content', 'where' => '.hComment', 'attr' => 'plaintext', 'callback' => function($str) {
									return trim($str);
								})
							)
						)
					)
				),
			),
			'haitao.smzdm.com' => array(
				'/\/youhui\/\d+/i' => array(
					'parser' => array(
						array('where' => 'li[id^=li-comment-]', 'type' => 'comment', 'detail' => array(
								array('type' => 'name', 'where' => '.commentName a', 'attr' => 'plaintext', 'callback' => function($str) {
									return trim($str);
								}),
								array('type' => 'content', 'where' => '.hComment', 'attr' => 'plaintext', 'callback' => function($str) {
									return trim($str);
								})
							)
						)
					)
				),
			)
		),
	);

?>
