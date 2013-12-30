<?php
	include '../lib/SqliteDB.php';

	$db = new SqliteDB(dirname(__FILE__) . '/../../sale_info/data/data.db');

	$rows = $db->getRows('select distinct class from product');

	$c1_values = array('数码家电', '个护化妆', '食品保健', '家居生活', '图书音像', '服饰鞋包', '母婴玩具', '钟表首饰', '运动户外', '杂七杂八', '国内优惠', '海淘资讯');

	$c1_c2_values = array(
		'数码家电' => array('办公设备','外设产品','大家电','小家电','平板电脑','影音设备','数码影像','数码配件','智能手机','电子阅读','电脑硬件','网络设备'),
		'个护化妆' => array('化妆品','卫生用品','口腔护理','护肤品','洗发护发','香水'), 
		'食品保健' => array('精品名酒','日常食品','保健品',), 
		'家居生活' => array('卧室用具','卫浴用品','实用工具','厨房用品','生活家具'), 
		'图书音像' => array('图书杂志','电子读物','软件游戏','音像制品'), 
		'服饰鞋包' => array('休闲男鞋', '儿童书包', '户外运动', '数码背包', '旅行箱包', '日常穿着', '时尚女包', '时尚女鞋', '精品男包', '钱包手包'), 
		'母婴玩具' => array('儿童玩具','母婴用品'), 
		'钟表首饰' => array('名品手表','精品眼镜','珠宝饰品',), 
		'运动户外' => array('体育用品','户外装备','运动装备'), 
		'优惠券' => array('优惠券码',),
		'国内优惠' => array('国内促销',), 
		'海淘资讯' => array('海淘特价',),
		'杂七杂八' => array('乐器','办公用品','宠物用品','旅游产品','汽车用品','装修用品','运动用品','优惠券码'),
	);


	$curr_c2_values = array();
	foreach($c1_c2_values as $c2_values) {
		$curr_c2_values = array_merge($c2_values, $curr_c2_values);
	}

	$c2_values = array();

	foreach($rows as $row) {
		$classes = explode(',', trim(str_replace('分类：', '', $row['class'])));

		foreach($classes as $class) {
			$class = trim($class);

			if(in_array($class, $c1_values)) {
				echo "c1:$class  class:{$row['class']}\n";
				continue;
			}

			$c2_values[] = $class;
		}
	}

	$c2_values = array_values(array_unique($c2_values));

	echo "'" . implode("','", $c2_values) . "'\n";

	$new_c2_values = array_diff($c2_values, $curr_c2_values);

	echo "new:'" . implode("','", $new_c2_values) . "'\n";
?>