<?php
	
	include '../conf/config.php';

	if(!isset($_SERVER['argv'][1])) {
		$_SERVER['argv'][1] = date('Y-m-d');
	}
	$time = strtotime($_SERVER['argv'][1]);
	$all = array();
	$info_data = array();
	$commemt_data = array();

	$dir_name_map = array(
 		'电脑数码' => 'diannaoshuma',
		'家用电器' => 'jiayongdianqi',
		'运动户外' => 'yundonghuwai',
		'服饰鞋包' => 'fushixiebao',
		'个护彩妆' => 'gehucaizhuang',
		'母婴用品' => 'muyingyongpin',
		'日用百货' => 'riyongbaihuo',
		'食品保健' => 'shipinbaojian',
		'图书音像' => 'tushuyinxiang',
		'玩模乐器' => 'wanmoyueqi',
		'礼品钟表' => 'lipinzhongbiao',
		'办公设备' => 'bangongshebei',
		'家居家装' => 'jiajujiazhuang',
		'汽车用品' => 'qicheyongpin',
		'其他分类' => 'qitafenlei',
	);

	//获取分类
	$db = new Sqlite3DB(ROOT . 'data/data.db');
	$categories = $db->getRows('select * from category_1');
	foreach($categories as $category) {
		$c1_id = $category['id'];
		$c1_name = $category['name'];

		if(!file_exists(dirname(__FILE__) . '/../data/smzdm/' . date('Y_m_d', $time) . '/' . $dir_name_map[$c1_name] . '/data.dat')) {
			echo $c1_name . " file not exists\n";
			continue;
		}

		foreach(file((dirname(__FILE__) . '/../data/smzdm/' . date('Y_m_d', $time) . '/' . $dir_name_map[$c1_name] . '/data.dat')) as $line) {
			$l = json_decode($line, true);
			$keys = array_keys($l);
			$url = $keys[0];
			if(preg_match('/fenlei\/.*$/i', $url)) {
				foreach($l[$url] as &$t) {
					$t['c1_id'] = (int)$c1_id;
				}
				$info_data = array_merge($info_data, $l[$url]);
			}
			else {
				$comment_data[$url] = $l[$url];
			}
		}
	}

	echo 'total product:' . count($info_data) . "\n";

	foreach($info_data as $record) {

		$ret = checkData($record, array('name', 'content', 'merchant', 'add_time', 'href', 'url', 'pic'));

		if($ret === false) {

			echo json_encode($record) . "\n";
			continue;

		}

		$c1_id = $record['c1_id'];

 		$href = $record['href'];
		$comments = isset($comment_data[$href]) ? $comment_data[$href] : array();

		foreach($comments as &$comment) {
			$comment['source'] = 1;
		}

		$products = $db->getRows("select id from product where source='$href'");

		if($products) { //update
			
			echo "update product:$href\n";

			$ret = $db->update('product', $record, 'id=' . $products[0]['id'],
				array('name', 'content', 'merchant', 'add_time', 'href', 'url', 'pic'), 
				array(
				'name' => 'title',
				'href' => 'source',
				)
			);

			if($ret === false) {
				echo "update error:$href\n";
				continue;
			}

			//更新评论
			$product_id = $products[0]['id'];
			//清除之前导入的评论
			$db->remove('comment',"product_id=$product_id and source=1");

			foreach($comments as $comment) {
				$comment['product_id'] = $product_id;
				$db->insert('comment', $comment);
			}
			
		}
		else { //insert

			echo "insert product:$href\n";

			$ret = $db->insert('product', $record,
				array('name', 'content', 'merchant', 'add_time', 'href', 'url', 'pic'), 
				array(
				'name' => 'title',
				'href' => 'source',
				)
			);

			if($ret === false) {
				echo $db->errMsg . "\n";
				continue;
			}

			$product_id = $db->getInsertId();

			foreach($comments as $comment) {
				$comment['product_id'] = $product_id;
				$db->insert('comment', $comment);
			}
		}

		//商品分类
		$ret = $db->execSql('replace into product_category_1 values(' . $product_id . ', ' . $c1_id . ')');
		if($ret === false) {
			echo "replace product_category_1 error:" . $db->errMsg;
			continue;
		}
		
	}

	cope_dir(dirname(__FILE__) . '/../data/smzdm/' . date('Y_m_d', $time) . '/img/', ROOT . 'img/smzdm');

	function cope_dir($source,$target ){
		if(is_dir($source)){
			if(!file_exists($target))
				mkdir($target, 755, true);
			$dir = dir($source);
			while ( ($f=$dir->read()) !== false){
				if($f ==  '.' || $f == '..')
					continue;
				if (is_dir($source.'/'.$f)){
					cope_dir($source.'/'.$f,$target.'/'.$f);
				}else{
					copy($source.'/'.$f,$target.'/'.$f);
				}
			}
		}else{
			copy($source,$target);
		}
	}


	function checkData($record, $fields) {
		$ret = array_diff($fields,
			array_keys($record)
		);

		if($ret) {
			echo "miss col:" . json_encode($ret) . "\n";
			return false;
		}

		return true;

	}

?>
