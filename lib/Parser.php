<?php
	class Parser {
		private $subdomain_config;
		public $errCode;
		public $errMsg;
		private $controller;
		public function __construct($subdomain_config, $controller) {
			$this->subdomain_config = $subdomain_config;
			$this->controller = $controller;
		}
		public function parse($html, $url) {
			$url_struct = parse_url($url);
			$data = array();
			$urls = array();
			$dom = str_get_html($html);
			if($dom === false) {
				$this->errCode = 100;
				$this->errMsg = 'get dom error url:' . $url;
				return false;
			}
			$config = $this->subdomain_config[$url_struct['host']];
			if($config) {
				foreach($config as $preg => $page_config) {
					if(preg_match($preg, $url_struct['path'] . (isset($url_struct['query'])?$url_struct['query']:''))) {
						$items = array();
						if(isset($page_config['parser'])) {
							$pos = 0;
							foreach($page_config['parser'] as $block_config) {
								$dom_items = $this->find($dom, $block_config);
								if(!$dom_items) {
									$this->errCode = 101;
									$this->errMsg = 'get block error:' . $block_config['where'] . ' url:' . $url;
									return false;
								}

								foreach($dom_items as $item_pos => $dom_item) {
									foreach($block_config['detail'] as $detail_config) {
										$o = $this->find($dom_item, $detail_config, 0);
										if(!$o && (!isset($detail_config['dispensable']) || !$detail_config['dispensable'])) {
											log_err('get ' . $detail_config['type'] . ' error|pos:' . $item_pos  . ' url:' . $url . ' dom:' . $dom_item, basename(__FILE__), __LINE__);
											break;
										}
										$v = @$o->$detail_config['attr'];
										if(!$v && isset($detail_config['attr2'])) {
											$v = $o->$detail_config['attr2'];
										}
										if(!$v && (!isset($detail_config['dispensable']) || !$detail_config['dispensable'])) {
											log_err('get ' . $detail_config['type'] . '->attr error|pos:' . $item_pos . ' url:' . $url . ' dom:' . $dom_item, basename(__FILE__), __LINE__);
											break;
										}
										if(isset($detail_config['callback'])) {
											$v = $detail_config['callback']($v);
										}

										$items[$pos][$detail_config['type']] = $v;
									}
									$pos ++;
								}
							}
							$data = array($url => $items);
						}
						//获取锚
						if(isset($page_config['forward'])) {
							if(is_string($page_config['forward'])) {
								foreach($dom->find($page_config['forward']) as $anchor) {
									$forward_url = $anchor->href;
									$forward_url = $this->get_url($forward_url, $url_struct);
									$urls[] = $forward_url;
								}
								
							}
							else if(is_array($page_config['forward'])) {
								foreach($page_config['forward'] as $forward) {
									foreach($dom->find($forward) as $anchor) {
										$forward_url = $anchor->href;
										$forward_url = $this->get_url($forward_url, $url_struct);
										$urls[] = $forward_url;
									}								
								}
							}
							else if(is_object($page_config['forward']) && count($items) > 0) {
								$forward_urls = $page_config['forward']($url);
								$back_urls = array();//为防止下一页与上一页相同而进入死循环
								if(isset($page_config['backward']) && is_object($page_config['forward'])) {
									$back_urls = $page_config['backward']($url);
								}
								foreach($forward_urls as $key => $url) {
									if(isset($back_urls[$key]) && isset($this->controller->data[$back_urls[$key]]) && $this->controller->data[$back_urls[$key]] == $items) {
										continue;
									}
									$urls[] = $url;
								}
							}
						}
						break;
					}
				}
			}
			$ret['data'] = $data;
			$ret['urls'] = $urls;
			return $ret;
		}

		private function find($dom,  $config, $i = null) {
			if($i === null) {
				$ret = $dom->find($config['where']);
				if(!$ret && isset($config['where2'])) {
					$ret = $dom->find($config['where2']);
				}
				return $ret;
			}
			else {
				$ret = $dom->find($config['where'], $i);
				if(!$ret && isset($config['where2'])) {
					$ret = $dom->find($config['where2'], $i);
				}
				return $ret;
			}
		}

		private function get_url($url, $url_struct) {
			if(strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
				$url = $url_struct['scheme'] . '://' . $url_struct['host'] . str_replace('\\', '/', dirname($url_struct['path'])) . '/' . $url;//str_replace for win
			}
			return $url;		
		}
	}
?>
