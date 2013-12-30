<?php
define('LOG_LEVEL_NONE', 0);
define('LOG_LEVEL_ERROR', 1);
define('LOG_LEVEL_NOTICE', 2);
define('LOG_LEVEL_INFO', 3);
define('LOG_LEVEL_DEBUG', 4);

define('LOG_FILE_SIZE', 1024 * 1024 * 100);
define('LOG_TMEP_FILE', "_tmp.log");

$log_fd 	= 0;
$log_level 	= 0;
$log_path  	= '';
$log_ready	= 0;

function log_init($path, $level)
{
	global $log_fd, $log_level, $log_path, $log_ready;

	$log_path 	= $path;
	$log_fd 	= fopen($log_path . "/" . LOG_TMEP_FILE, "a+");	
	if (!$log_fd)
	{
		$log_ready = 0;
		return -1;
	}

	$log_level = $level;
	$log_ready = 1;
	return 0;
}

function check_full()
{
	global $log_fd, $log_path, $log_ready;
	
	$tmp_log = $log_path . "/" . LOG_TMEP_FILE;
	clearstatcache();
	if ($log_ready && filesize($tmp_log) >= LOG_FILE_SIZE)
	{
		fclose($log_fd);

		$new_file = $log_path . "/" . date("YmdHisu") . ".log"; 
		rename($tmp_log, $new_file);

		$log_fd = fopen($tmp_log, "w+");
		if (!$log_fd)
		{
			$log_ready = 0;
		}
	}
}

function log_write($msg, $type, $func, $line)
{
	global $log_fd, $log_level, $log_path, $log_ready;

	if (flock($log_fd, LOCK_EX))
	{
		check_full();

		
		list($usec, $sec) = explode(" ", microtime());
		$time = date("Y-m-d H:i:s",$sec);
		$time .= ":".((float)$usec*1000000);
		fwrite($log_fd, "[$time][$type][$func][$line]".$msg."\n");
		fflush($log_fd);

		flock($log_fd, LOCK_UN);
	}
}

function log_err($msg, $func='', $line='')
{
	global $log_fd, $log_level, $log_path, $log_ready;

	if ($log_ready && (LOG_LEVEL_ERROR <= $log_level))
	{
		log_write($msg, "ERROR", $func, $line);
	}
}

function log_notice($msg, $func='', $line='')
{
	global $log_fd, $log_level, $log_path, $log_ready;

	if ($log_ready && (LOG_LEVEL_NOTICE <= $log_level))
	{
		log_write($msg, "NOTICE", $func, $line);
	}
}

function log_info($msg, $func='', $line='')
{
	global $log_fd, $log_level, $log_path, $log_ready;

	if ($log_ready && LOG_LEVEL_INFO <= $log_level)
	{
		log_write($msg, "INFO", $func, $line);
	}
}

function log_debug($msg, $func='', $line='')
{
	global $log_fd, $log_level, $log_path, $log_ready;

	if ($log_ready && LOG_LEVEL_DEBUG <= $log_level)
	{
		log_write($msg, "DEBUG", $func, $line);
	}
}

function log_uninit()
{
	global $log_fd, $log_ready;

	if ($log_ready)
	{
		fclose($log_fd);
		fflush($log_fd);

		$log_ready = 0;
	}
}

?>
