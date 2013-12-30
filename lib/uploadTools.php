<?php
class uploadTools {
	public static $errCode = 0;
	public static $errMsg ='';

	public static $sql_url = 'http://www.eccbuy.net/sql/execute',
	public static $file_url = 'http://www.eccbuy.net/file/upload',

	public static function execSql($sql) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$sql_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sql);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$str = curl_exec($ch);
		$ret = json_decode($str, true);
		if($ret === null) {
			self::$errCode = 100;
			self::$errMsg = 'return:' . $str;
			return false;
		}
		if($ret['errno'] != 0) {
			self::$errCode = 101;
			self::$errMsg = $ret['msg'];
			return false;
		}

		return true;

	}

	public static function uploadFile($save_dir, $file_name, $file_data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$file_url . '?save_dir=' . $save_dir . '&file_name=' . $file_name);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$str = curl_exec($ch);
		$ret = json_decode($str, true);
		if($ret === null) {
			self::$errCode = 200;
			self::$errMsg = 'return:' . $str;
			return false;
		}
		if($ret['errno'] != 0) {
			self::$errCode = 201;
			self::$errMsg = $ret['msg'];
			return false;
		}

		return true;
	}
}