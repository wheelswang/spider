<?php
/**
 * @author wheelswang
 * @date 2013-07-16
 * @version 1.0
 */

class HttpCurl {

	static private $proxy_host = null;
	static private $proxy_port = 80;
	static private $http_header = null;
	static private $timeout = 0;

	static public $errMsg = '';

	static public function set_proxy($proxy_host, $proxy_port) {
		self::$proxy_host = $proxy_host;
		self::$proxy_port = $proxy_port;
	}

	static public function set_timeout($sec) {
		self::$timeout = $sec;
	}

	static public function set_header($http_header) {
		self::$http_header = $http_header;
	}

	static public function exec($url, $method = 'GET', $post_argv = null ,$port = 80) {
		$url_struct = self::getUrlStruct($url);
		if(!$url_struct) {
			return false;
		}
		$host = $url_struct['host'];
		$uri = $url_struct['path'] . ($url_struct['query'] ? '?' . $url_struct['query'] : '');

		if(self::$proxy_host) {
			$fp = @fsockopen('tcp://' . self::$proxy_host, self::$proxy_port, $errno, $errstr, self::$timeout ? self::$timeout : ini_get("default_socket_timeout"));
		}
		else {
			$fp = @fsockopen($host, $port, $errno, $errstr, self::$timeout ? self::$timeout : ini_get("default_socket_timeout"));
		}
		if(!$fp) {
			self::$errMsg = $errno . ':' . $errstr;
			return false;
		}
		
		if(self::$timeout) {
			stream_set_timeout($fp, self::$timeout);
		}
	
		if(self::$proxy_host) {
			$http_req = "$method http://$host:$port$uri HTTP/1.1\r\nHOST:$host:$port\r\nConnection:Close\r\n";
		}
		else {
			$http_req = "$method $uri HTTP/1.1\r\nHOST:$host:$port\r\nConnection:Close\r\n";
		}

		if(self::$http_header) {
			foreach(self::$http_header as $key => $value) {
				$http_req .= "$key:$value\r\n";
			}
		}

		if($post_argv) {
			$http_req .= "Content-Type: application/x-www-form-urlencoded;\r\n";
			$post_req = '';
			foreach($post_argv as $post_key => $post_data) {
				$post_req .= $post_key . '=' . $post_data;
			}
			$http_req .= "Content-Length: " . strlen($post_req) . "\r\n";
		}
	
		$http_req .= "\r\n";
		
		if(isset($post_req)) {
			$http_req .= $post_req;
		}

		$rw = fwrite($fp, $http_req);

		if($rw != strlen($http_req)) {
			self::$errMsg = 'send http error';
			return false;
		}

		$http_rsp_header = array();
		$http_rsp_body = '';
		$recv_head = true;

		$line = self::http_gets($fp);
		if($line === false) {
			return false;
		}

		$http_rsp_header[0] = trim($line);

		while(!feof($fp)) {
			$line = self::http_gets($fp);
			if($line === false) {
				return false;
			}
			if($line == "\r\n") {
				$recv_head = false;
				continue;
			}
			if($recv_head) {
				$line = trim($line);
				$colon_pos = strpos($line, ':');
				$http_rsp_header[substr($line, 0, $colon_pos)] = trim(substr($line, $colon_pos + 1));
			}
			else {
				$http_rsp_body .= $line;
			}
		}

		return array('http_head' => $http_rsp_header, 'http_body' => $http_rsp_body);
	}

	static public function get_http_body($url) {
		$ret = self::exec($url);
		if($ret === false) {
			return false;
		}
		return $ret['http_body'];
	}

	static private function http_gets($fp) {
		$line = fgets($fp, 1024*100);
		$info = stream_get_meta_data($fp);
		if($info['timed_out']) {
			self::$errMsg = 'fgets timeout';
			return false;
		}
		return $line;
	}

	static public function get_final_http_body($url) {
		$ret = self::exec($url);
		$http_head = $ret['http_head'];
		if(isset($http_head['Location'])) {
			//相对路径
			if(strpos($http_head['Location'], 'http') !== 0) {
				$http_head['Location'] = $url_struct['host'] . $http_head['Location'];
			}

			return self::get_final_http_body($http_head['Location']);
		}
		return $ret['http_body'];
	}

	static public function get_final_url($url, $exclude = array()) {
		$ret = self::exec($url);
		if($ret === false) {
			return false;
		}
		$http_head = $ret['http_head'];
		if(isset($http_head['Location'])) {
			//相对路径
			if(strpos($http_head['Location'], 'http') !== 0) {
				$http_head['Location'] = $url_struct['host'] . $http_head['Location'];
			}

			$domain = self::getDomain($http_head['Location']);
			if(in_array($domain, $exclude)) {
				return $http_head['Location'];
			}

			return self::get_final_url($http_head['Location'], $exclude);
		}
		return $url;
	}

   static public function getUrlStruct($url) {
        $struct = parse_url($url);
        if($struct && isset($struct['host']) && isset($struct['scheme'])) {
            if(!isset($struct['path'])) {
                $struct['path'] = '/';
            }
             if(!isset($struct['query'])) {
                $struct['query'] = '';
            }           
            return  $struct;
        }
        return false;
    }

	static public function getDomain($url) {
		$struct = parse_url($url);
		$host = $struct['host'];
		preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
		return $matches[0];
	}

	static public function getUrlArgu($url) {
		$struct = parse_url($url);
		if(!isset($struct['query'])) {
			return array();
		}

		$ret = array();
		$items = explode('&', $struct['query']);
		foreach($items as $item) {
			$arr = explode('=', $item);
			$ret[$arr[0]] = isset($arr[1]) ? $arr[1] : '';
		}

		return $ret;
	}
}

?>
