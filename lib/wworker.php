<?php
	/*  多进程并发工作类
	 *  version : 1.0
	 *  write by wheelswang @ 2013-04-08
	 *  modified by wheelswang @ 2013-04-09 使用register_shutdown_function, 解决子进程异常退出造成父进程select卡死的问题
	 *  modified by wheelswang @ 2013-05-08 使用rw_buf读写数据
	 */
	define('WAIT_READ_EVENT', 1);
	define('WAIT_WRITE_EVENT', 2);

	define('RW_CONTINUE_FLAG', 0);
	define('READ_END_FLAG', 1);
	define('READ_ERR_FLAG', 2);
	define('WRITE_END_FLAG', 3);
	define('WRITE_ERR_FLAG', 4);
	define('RW_CLOSE_FLAG', 5);
	define('BAD_REQUEST', 6);
	abstract class wworker {
		private $worker_num;
		private $p_sock;//父进程读socket
		private $s_sock;//子进程写socket
		private $select_timeout = 2;
		private $report_socket;
		private $report_str = '';
		private $rw_buf = array();
		private $err_msg = '';
		private $err_code = 0;
		public function __construct($worker_num = 10){
			$this->worker_num = $worker_num;
		}

		public function start() {
			if($this->worker_num <= 0) {
				return;
			}
			$this->dispatch();
			$this->init_buf($this->p_sock);
			$ret = $this->collect($this->p_sock);
			$data = array();
			foreach($this->rw_buf as $s => $v) {
				$data[$s] = substr($v['rbuf'], 4);
			}
			$this->rw_buf = array();
			$this->master($data);
		}

		private function dispatch() {
			for($i = 0; $i < $this->worker_num; $i++) {
				$ret = socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sck_pair);
				socket_set_nonblock($sck_pair[0]);
				socket_set_nonblock($sck_pair[1]);
				$this->p_sock[] = $sck_pair[1];
				$this->s_sock[] = $sck_pair[0];
				$npid = pcntl_fork();

				if ($npid == -1) 
				{
					echo "create child processs failed!\n";
					exit(0);
				}
				else if($npid == 0){ //子进程
					register_shutdown_function(array($this,'report'));
					echo 'create children worker ' . $i . "\n";
					socket_close($sck_pair[1]);
					$this->report_socket = $sck_pair[0];
					$str = $this->worker($i);
					$this->report_str = $str;
					exit;
				}
			}
		}
		private function init_buf($socks) {
			foreach($socks as $sock) {
				$this->rw_buf[(int)$sock] = array( 
					'event' 	=> 0,
					'sock'  	=> $sock, 
					'r_all_len'	=> 0, 
					'rbuf'		=> '',
					'rlen'		=> 0,
					'w_all_len'	=> 0, 
					'wbuf'		=> '',
					'wlen'		=> 0
				);
			}
		}


		private function read(&$buf) {
			if($buf['rlen'] === 0) {
				$tmp = socket_read($buf['sock'], 4, PHP_BINARY_READ);
				if ($tmp === FALSE)
				{
					$this->err_msg = "read failed from fd[".$buf['sock']."], ";
					$this->err_code = 100;
					return READ_ERR_FLAG;
				}
				if (strlen($tmp) == 0 && strlen($buf['rbuf']) != 4)
				{
					$this->err_msg = "read zero bytes from fd[".$buf['sock']."]";
					$this->err_code = 101;
					return RW_CLOSE_FLAG; 
				}

				$buf['rbuf'] .= $tmp;
				$buf['rlen'] = 4;
			}
			$header = unpack('Ilength', $buf['rbuf']);
			$buf['r_all_len'] = $header['length'];
			$tmp = socket_read($buf['sock'], $buf['r_all_len'] - $buf['rlen'] , PHP_BINARY_READ);
			if($tmp === false) {
				return RW_CONTINUE_FLAG;
			}
			$buf['rbuf'] .= $tmp;
			$buf['rlen'] += strlen($tmp);
			if($buf['rlen'] >= $buf['r_all_len']) {
				return READ_END_FLAG;
			}
			return RW_CONTINUE_FLAG;

		}
		abstract protected function worker($i);
		abstract protected function master($ret);
		private function collect($sockets) {
			$num = 0;
			$will_stop = 0;
			foreach($sockets as $sock) {
				$cli_sock_r[(int)$sock] = $sock;
			}
			while(!$will_stop) {
				$r = $cli_sock_r;
				$w = array();
				$e = array();
				socket_select($r, $w, $e, NULL);
				foreach($r as $s) {
					$read_ret = $this->read($this->rw_buf[(int)$s]);
					if($read_ret != RW_CONTINUE_FLAG) {
						unset($cli_sock_r[(int)$s]);
						$num ++;
					}
				}
				if($num == $this->worker_num)
					$will_stop = 1;
			}
		}
		public function report() {
			$this->init_buf(array($this->report_socket));
			$this->rw_buf[(int)$this->report_socket]['wbuf'] = $this->packData($this->report_str);
			$this->rw_buf[(int)$this->report_socket]['w_all_len'] = strlen($this->report_str) + 4;
			$will_stop = 0;
			while(!$will_stop) {
				$r = array();
				$w = array($this->report_socket);
				$e = array();
				socket_select($r, $w, $e, NULL);
				foreach($w as $s) {
					$write_ret = $this->write($this->rw_buf[(int)$s]);
					if($write_ret != RW_CONTINUE_FLAG) {
						$will_stop = 1;
					}
				}
			}
		}
		private function write(&$buf) {
			$wlen = socket_write($buf['sock'], substr($buf['wbuf'], $buf['wlen']), $buf['w_all_len'] - $buf['wlen']);
			if($wlen === false) {
				$this->err_msg = "write failed to fd[".$buf['sock']."], ";
				$this->err_code = 200;
				return WRITE_ERR_FLAG;
			}
			$buf['wlen'] += $wlen;
			if($buf['wlen'] >= $buf['w_all_len']) {
				return WRITE_END_FLAG;
			}
			return RW_CONTINUE_FLAG;
		}
		private function packData($str) {
			$head = pack("I*",strlen($str)+4);
			return $head.$str;
		}
	}
?>