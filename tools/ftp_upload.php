<?php

	if(!isset($_SERVER['argv'][1])) {
		die("miss file\n");
	}

	if(!isset($_SERVER['argv'][2])) {
		die("miss dest\n");
	}

	$dest =  $_SERVER['argv'][2];

	if(isset($_SERVER['argv'][3])) { //文件列表
		$files = json_decode(file_get_contents(dirname(__FILE__) . '/' . $_SERVER['argv'][1]), true);
	}
	else {
		$files = array($_SERVER['argv'][1]);
	}

	$conn_id = ftp_connect('www.eccbuy.net');
	if(!$conn_id) {
		die("connect ftp error\n");
	}
	$login_result = ftp_login($conn_id, 'u798791054', 'letmego555');
	if(!$login_result) {
		echo "login ftp error\n";
		exit;
	}

	foreach($files as $file) {

		echo "uploading $file\n";
		
		$local_file = $file;

		$dest_tmp_file = $dest . '/' .  basename($file) . '.tmp';
		$dest_file = $dest . '/' . basename($file);

		if(!file_exists($local_file)) {
			echo "file not exists\n";
			continue;
		}

		ftp_pasv($conn_id, true);

		if(!ftp_put($conn_id, $dest_tmp_file, $local_file, FTP_BINARY)) {
			$err = error_get_last();
			var_export($err);
		}


		ftp_rename($conn_id, $dest_tmp_file, $dest_file);

		echo "upload $file success\n";
	}

	echo "upload all success\n";

	ftp_close($conn_id);

?>
