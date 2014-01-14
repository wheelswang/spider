<?php
	include dirname(__FILE__).'/Utils.php';
	include dirname(__FILE__).'/log.php';
	include dirname(__FILE__).'/simple_html_dom.php';
	include dirname(__FILE__).'/wworker.php';
	include dirname(__FILE__).'/Crawler.php';
	include dirname(__FILE__).'/Parser.php';
	class SpiderController {
		protected $domain;
		protected $timeout = 2;
		protected $page_suffix;
		protected $exclude_args;
		protected $subdomain;
		protected $curr_url_struct;
		protected $subdomain_page;
		protected $page_limit;
		protected $page_counter;
		public $config;
		public $crawled_url;
		public $task_url;
		public $dead_url;
		public $crawler;
		public $parser;
		public $data;
		public function __construct($config) {
			$this->config = $config;
			$this->domain = $config['domain'];
			$this->page_suffix = $config['page_suffix'];
			$this->exclude_args = $config['exclude_args'];
			$this->subdomain = array_keys($config['subdomain']);
			$this->crawled_url = array();
			$this->failed_url = array();
			$this->dead_url = array();
			$this->task_url = array($config['entry'] => $config['entry']);
			$this->data_dir = $config['data_dir'];
			$this->data = array();
			$this->crawler = new Crawler;
			$this->parser = new Parser($config['subdomain'], $this);
			foreach($config['subdomain'] as $subdomain => $sub_configs) {
				foreach($sub_configs as $page_format => $page_config) {
					$this->subdomain_page[$subdomain][] = $page_format; 
					if(isset($page_config['limit'])) {
						$this->page_limit[$subdomain][$page_format] = $page_config['limit'];
					}
				}
			}
			if(!file_exists($config['log_dir'])) {
				mkdir($config['log_dir'], 755, true);
			}
			log_init($config['log_dir'], LOG_LEVEL_DEBUG);

			$this->init_counter();
		}
		
		public function start() {
			while($this->task_url) {
				log_info('task_url:' . count($this->task_url), basename(__FILE__), __LINE__);
				$urls = array_splice($this->task_url, 0, 1);
				$my_urls = array_values($urls);
				$this->work($my_urls[0]);
			}
		}


		public function init_counter() {
			$this->page_counter = array();
			foreach($this->task_url as $url) {
				$struct = parse_url($url);
				if(isset($this->page_limit[$struct['host']])) {
					foreach($this->page_limit[$struct['host']] as $format => $limit) {
						if(preg_match($format, $url)) {
							$this->page_counter[$struct['host']][$format] = 1;
							return; 
						}
					}
				}
			}
		}

		protected function work($url) {
			log_info('begin url:' . $url, basename(__FILE__), __LINE__);
			log_info('begin crawl', basename(__FILE__), __LINE__);
			$page = $this->crawler->crawl($url);
			log_info('finish crawl', basename(__FILE__), __LINE__);
			if(!trim($page)) {
				log_err('crawl error url:' . $url, basename(__FILE__), __LINE__);
				if(!isset($this->failed_url[$url])) {
					$this->failed_url[$url] = 1;
				}
				else {
					$this->failed_url[$url] ++;
				}
				if($this->failed_url[$url] == 3) {
					$this->dead_url[] = $url;
				}
				else {
					$this->task_url[$url] = $url;
				}
				return;
			}
			$this->crawled_url[] = $url;
			log_info('begin parse', basename(__FILE__), __LINE__);
			$data = $this->parser->parse($page, $url);
			if($data === false) {
				log_err('parse error:' . $this->parser->errMsg, basename(__FILE__), __LINE__);
				return;
			}
			log_info('finish parse', basename(__FILE__), __LINE__);
			$forward_urls = $data['urls'];
			log_info('get url num:' . count($forward_urls), basename(__FILE__), __LINE__);
			$forward_urls = $this->filter_url($forward_urls);
			log_info('url num after filter:' . count($forward_urls), basename(__FILE__), __LINE__);
			if(!is_array($data['data'])) {
				var_export($data);
			}
			$this->data = array_merge($this->data, $data['data']);
			foreach($forward_urls as $forward_url) {
				$this->task_url[$forward_url] = $forward_url;
			}
			return;
		}

		protected function filter_url($urls){
			$ret_urls = array();
			foreach($urls as $url) {
				$url_struct = parse_url($url);
				$this->curr_url_struct = $url_struct;
				if($url_struct === false || !isset($url_struct['scheme']) || !isset($url_struct['host'])) {
					log_err('err url:' . $url, basename(__FILE__), __LINE__);
					continue;
				}
				$url = $this->clean_url($url);
				$this->curr_url_struct = parse_url($url);
				if(in_array($url, $this->crawled_url)) {
					continue;
				}
				if(in_array($url, $this->dead_url)) {
					continue;
				}
				$ret = $this->check_url($url);
				if($ret === false) {
					continue;
				}

				//页面抓取个数限制
				if(isset($this->page_limit[$url_struct['host']])) {
					foreach($this->page_limit[$url_struct['host']] as $page_format => $limit) {
						if(preg_match($page_format, $url_struct['path'])) {
							if($this->page_counter[$url_struct['host']][$page_format] >= $limit)
								continue 2;
							else {
								$this->page_counter[$url_struct['host']][$page_format] ++;
								break;
							}
						}
					}
				}

				$ret_urls[$url] = $url;				
			}
			return array_values($ret_urls);
		}

		protected function check_url($url) {
			$ret = $this->curr_url_struct;
			if(!in_array($ret['host'], $this->subdomain)) {
				return false;
			}
			if(!$this->page_suffix) {
				return true;
			}
			foreach($this->page_suffix as $suffix) {
				if(strpos($ret['path'], $suffix) === strlen($ret['path']) - strlen($suffix)) {
					return true;
				}
			}
			return false;
		}

		protected function clean_url($url) {
			$ret = $this->curr_url_struct;
			$ret_url = $ret['scheme'] . '://' . $ret['host'] . (isset($ret['path']) ? $ret['path'] : '/');
			if(!isset($ret['query']) || !$ret['query']) {
				return $ret_url;
			}
			$querys = explode('&',$ret['query']);
			$args = array();
			foreach($querys as $query) {
				$q = explode('=', $query);
				if($this->exclude_args && in_array($q[0], $this->exclude_args)) {
					continue;
				}
				$args[$q[0]] = $q[1];
			}
			if($args) {
				ksort($args);
				$ret_url .= '?';
				foreach($args as $k => $v) {
					$ret_url .= $k . '=' . $v . '&';
				}
				$ret_url = substr($ret_url, 0, -1);
			}
			return $ret_url;
		}

		public function export($sub_dir = '') {
			echo "exporting\n";
			$dir = $this->data_dir . '/' . date('Y_m_d') . '/' . $sub_dir . '/';
			if(!file_exists($dir)) {
				mkdir($dir, 755, true);
			}
			Utils::exportArray($dir . 'task_url.dat', $this->task_url);
			Utils::exportArray($dir . 'data.dat', $this->data);
			Utils::exportArray($dir . 'crawled.dat', $this->crawled_url);
			Utils::exportArray($dir . 'dead.dat', $this->dead_url);
			Utils::exportArray($dir . 'counter.dat', $this->page_counter);
			if($this->dead_url) {
				echo "has dead url:" . count($this->dead_url) . "\n";
				return false;
			}
			return true;			
		}

		public function redo($date=null, $prefix) {
			if($date) {
				$dir = $this->config['data_dir'] . '/' . $date . '/';
			}
			else {
				$dir = $this->data_dir;
			}
			$this->task_url = Utils::loadArray($dir . 'task_url.dat');
			$this->data = Utils::loadArray($dir . 'data.dat', true);
			$this->crawled_url = Utils::loadArray($dir . 'crawled.dat');
			$this->dead_url = array();
			$this->task_url = array_merge($this->task_url, Utils::loadArray($dir . 'dead.dat'));
			$this->page_counter = Utils::loadArray($dir . 'counter.dat');
			$this->start();
		}
	}
?>
