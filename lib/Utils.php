<?php
class Utils
{
	public static $errCode = 0;
	public static $errMsg = '';

	public static function curlGet($url, $timeout = 2, $cookie = '', $headerAry = '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.tencent.com:8080/');
        $headerAry = array(
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerAry);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    public static function curlHead($url, $timeout = 2) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $res = curl_exec($ch);
        return $res;    
    }

    public static function exportArray($file_name, $data) {
        $fp = fopen($file_name , 'w');
        foreach($data as $k => $line) {
            fwrite($fp, json_encode(array($k => $line)) . "\n");
        }
        fclose($fp);
    }

    public static function loadArray($file_name) {
        $ret = array();
        $fp = fopen($file_name , 'r');
        while(!feof($fp)) {
            $line = trim(fgets($fp));
            if(!$line) {
                continue;
            }
            $line = json_decode($line, true);
            $keys = array_keys($line);
            $ret[$keys[0]] = $line[$keys[0]];
        }
        return $ret;
    }

    public static function savePic($url, $dir) {
        $struct = parse_url($url);
        $path = $struct['path'];
		$save_file = $dir . '' . $path;
		if(file_exists($save_file)) {
			return $path;
		}
        $pic_d = Utils::curlGet($url, 10);

        if(!file_exists(dirname($save_file))) {
            mkdir(dirname($save_file), 755, true);
        }

        if(file_put_contents($save_file, $pic_d)) {
            return $path;
        }

        return false;
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
} 
