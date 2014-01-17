<?php
class SmzdmModel {
	static public $errMsg = '';

	static private $img_domain = 'zdmimg.com';
	static private $domain = 'smzdm.com';

	static public function isMyImgLink($url) {
        $domain = HttpCurl::getDomain($url);
        if($domain == self::$img_domain) {
            return true;
        }
        return false;
	}

	static public function isMylink($url) {
        $domain = HttpCurl::getDomain($url);
        if($domain == self::$domain) {
            return true;
        }
        return false;
	}

	static public function getProductUrl($url) {
		log_info('get original product url:' . $url, basename(__FILE__), __LINE__);
		$html = HttpCurl::get_http_body($url);
		if($html === false) {
			log_err('get http body error:' . HttpCurl::$errMsg);
			return $url;
		}
		if(self::isMylink($url)) {
			$patten = "/smhrefzdm = '(.*?)'/i";
			if(!preg_match($patten, $html, $matches)) {
				log_err('preg match error,patten:' . $patten . ',url:' . $url . ',html:' . $html, basename(__FILE__), __LINE__);
				return $url;
			}

			$url = $matches[1];
		}

		$url = self::getUrlFormLink($url, 'yixun.com', 'url');
		$url = self::getUrlFormLink($url, 'suning.com', 'vistURL');
		$url = self::getUrlFormLink($url, 'jd.com', 'to');
		$url = self::getUrlFormLink($url, 'viglink.com', 'out');

		log_info('get product url:' . $url, basename(__FILE__), __LINE__);
		
		return $url;
	}

	static public function getUrlFormLink($url, $domain, $key) {
		$t_domain = HttpCurl::getDomain($url);
		if($domain != $t_domain) {
			return $url;
		}

		$argu = HttpCurl::getUrlArgu($url);

		if(!isset($argu[$key])) {
			return $url;
		}

		return urldecode($argu[$key]);
	}

} 