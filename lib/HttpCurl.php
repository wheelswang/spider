<?php
/**
 * @author wheelswang
 * @date 2013-07-16
 * @version 1.0
 */

class HttpCurl {

	private $proxy_host = null;
	private $proxy_port = 80;
	private $http_header = null;
	private $timeout = 0;

	public $errMsg = '';

	public function set_proxy($proxy_host, $proxy_port) {
		$this->proxy_host = $proxy_host;
		$this->proxy_port = $proxy_port;
	}

	public function set_timeout($sec) {
		$this->timeout = $sec;
	}

	public function set_header($http_header) {
		$this->http_header = $http_header;
	}

	public function exec($url, $method = 'GET', $post_argv = null ,$port = 80) {
		$url_struct = $this->getUrlStruct($url);
		if(!$url_struct) {
			return false;
		}
		$host = $url_struct['host'];
		$uri = $url_struct['path'] . ($url_struct['query'] ? '?' . $url_struct['query'] : '');

		if($this->proxy_host) {
			$fp = @fsockopen('tcp://' . $this->proxy_host, $this->proxy_port, $errno, $errstr, $this->timeout ? $this->timeout : ini_get("default_socket_timeout"));
		}
		else {
			$fp = @fsockopen($host, $port, $errno, $errstr, $this->timeout ? $this->timeout : ini_get("default_socket_timeout"));
		}
		if(!$fp) {
			$this->errMsg = $errno . ':' . $errstr;
			return false;
		}
		
		if($this->timeout) {
			stream_set_timeout($fp, $this->timeout);
		}
	
		if($this->proxy_host) {
			$http_req = "$method http://$host:$port$uri HTTP/1.0\r\nHOST:$host:$port\r\nConnection:Close\r\n";
		}
		else {
			$http_req = "$method $uri HTTP/1.0\r\nHOST:$host:$port\r\nConnection:Close\r\n";
		}

		if($this->http_header) {
			foreach($this->http_header as $key => $value) {
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
			$this->errMsg = 'send http error';
			return false;
		}

		$http_rsp_header = array();
		$http_rsp_body = '';
		$recv_head = true;

		$line = $this->http_gets($fp);
		if($line === false) {
			return false;
		}

		$http_rsp_header[0] = trim($line);

		while(!feof($fp)) {
			$line = $this->http_gets($fp);
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

	public function get_http_body($url) {
		$ret = $this->exec($url);
		return $ret['http_body'];
	}

	private function http_gets($fp) {
		$line = fgets($fp, 1024*100);
		$info = stream_get_meta_data($fp);
		if($info['timed_out']) {
			$this->errMsg = 'fgets timeout';
			return false;
		}
		return $line;
	}

	public function get_final_http_body($url) {
		$ret = $this->exec($url);
		$http_head = $ret['http_head'];
		if(isset($http_head['Location'])) {
			//相对路径
			if(strpos($http_head['Location'], 'http') !== 0) {
				$http_head['Location'] = $url_struct['host'] . $http_head['Location'];
			}

			return $this->get_final_http_body($http_head['Location']);
		}
		return $ret['http_body'];
	}

	public function get_final_url($url, $exclude = array()) {
		$ret = $this->exec($url);
		$http_head = $ret['http_head'];
		if(isset($http_head['Location'])) {
			//相对路径
			if(strpos($http_head['Location'], 'http') !== 0) {
				$http_head['Location'] = $url_struct['host'] . $http_head['Location'];
			}

			$domain = $this->getDomain($http_head['Location']);
			if(in_array($domain, $exclude)) {
				return $http_head['Location'];
			}

			return $this->get_final_url($http_head['Location'], $exclude);
		}
		return $url;
	}

   public static function getUrlStruct($url) {
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

    public static function getDomain($url) {
        $struct = parse_url($url);
        $host = $struct['host'];
        preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
        return $matches[0];
    }


}

?>