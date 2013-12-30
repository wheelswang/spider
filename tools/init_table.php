<?php

	include '../lib/Sqlite3DB.php';

	$web_root = '/Library/WebServer/Documents/www_eccbuy_net/';

	$db = new Sqlite3DB($web_root . 'data/data.db');

	$db->execSql('drop table if exists category_1');
	$db->execSql('drop table if exists category_2');
	$db->execSql('drop table if exists product_category_1');
	$db->execSql('drop table if exists product_category_2');	
	$db->execSql('drop table if exists product');
	$db->execSql('drop table if exists comment');
	$db->execSql('drop table if exists user');


	if(!$db->execSql('create table category_1(id integer primary key,name varchar(255))')) {
		die();
	}

	if(!$db->execSql('create table category_2(id integer primary key,name varchar(255),c1_id integer)')) {
		die();
	}

	if(!$db->execSql('create table product_category_1(pid integer,c1_id integer,primary key(pid,c1_id))')) {
		die();
	}

	if(!$db->execSql('create table product_category_2(pid integer,c2_id integer,primary key(pid,c2_id))')) {
		die();
	}

	$c1_c2_values = array(
	'电脑数码' => array(),
	'家用电器' => array(), 
	'运动户外' => array(), 
	'服饰鞋包' => array(), 
	'个护彩妆' => array(), 
	'母婴用品' => array(), 
	'日用百货' => array(), 
	'食品保健' => array(), 
	'图书音像' => array(), 
	'玩模乐器' => array(),
	'礼品钟表' => array(),
	'办公设备' => array(),
	'家居家装' => array(),
	'汽车用品' => array(),
	'其他分类' => array(),
	);

	$c2_map = array();

	foreach($c1_c2_values as $c1 => $c2s) {
		$db->insert('category_1', array('name' => $c1));
		$c1_id = $db->getInsertId();
		foreach($c2s as $c2) {
			$db->insert('category_2', array('name' => $c2, 'c1_id' => $c1_id));
			$c2_id = $db->getInsertId();
			$c2_map[$c2] = $c2_id;
		}
	}

	$db->execSql('create table product(id integer primary key, merchant varchar(20), source varchar(20), title varchar(255), content text, url varchar(255), pic varchar(255), add_time integer, class integer)');

	$db->execSql('create table comment(id integer primary key, product_id integer, name varchar(100), user_id integer, content text, source integer)');

	$db->execSql('create table user(id integer primary key,nickname varchar(255),gender varchar(255),head_pic varchar(255),qq_open_id varchar(255) unique,add_time integer default 0,last_time integer default 0,login_count integer default 0,last_comment_time integer default 0);
');
?>
