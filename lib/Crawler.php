<?php
	class Crawler {
		public $errCode;
		public $errMsg;
		private $time_out;
		public function __construct() {
			$this->time_out = 10;
		}
		public function crawl($url) {
			$page = Utils::curlGet($url, $this->time_out);
			return $page;
		}
	}
?>