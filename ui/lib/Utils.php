<?php
class Utils
{
	public static function packIp($ip)
	{
		$ip_origin  = $ip;
		$ip_format  = self::_ipstr_to_int($ip_origin);
		return $ip_format;	
	}

	protected static function _ipstr_to_int($ip)
	{
		$ip_uint = ip2long($ip);
		$userIP_int_raw  = unpack('I', pack('I', $ip_uint));
		$ip_int = $userIP_int_raw[1];
		return $ip_int;	
	}

	public static function addUrlQueryString($tpl, $inputUrl, $arrParam = array()){
		if(empty($arrParam)){
			return $inputUrl;
		}
		
		if(strpos($inputUrl,'/') === 0){
			$inputUrl = 'http://'.$_SERVER['HTTP_HOST'].$inputUrl;
		}
		
		$arrUrl = parse_url($inputUrl);
		if(!$arrUrl || !isset($arrUrl['scheme']) || !isset($arrUrl['host'])){
			if(!is_null($tpl)){
				throw new Exception('internal ' . $tpl . ' invalid param url has been received  [ url: ' . $inputUrl . ' ].', 1);
			}
			throw new Exception('internal invalid param url has been received [ url: ' . $inputUrl . ' ].');
		}
		$url = $arrUrl['scheme'] . '://';
		if(isset($arrUrl['user'])){
			$url .= $arrUrl['user'];
			if(isset($arrUrl['pass'])){
				$url .= ':' . $arrUrl['pass'];
			}
			$url .= '@';
		}
		$url .= $arrUrl['host'];
		if(isset($arrUrl['port'])){
			$url .= ':' . $arrUrl['port'];
		}
		$split = '/?';
		if(isset($arrUrl['path'])){
			$url .= $arrUrl['path'];
			$split = '?';
		}
		$qs = http_build_query($arrParam);
		if(isset($arrUrl['query'])){
			$url .= $split . $arrUrl['query'];
			$split = '&';
		}
		$url .= $split . $qs;
		if(isset($arrUrl['fragment'])){
			$url .= '#' . $arrUrl['fragment'];
		}
		return $url;
	}

	/**
	 * 匹配回调函数
	 *
	 * @param array $arrInput 输入参数
	 */
	public static function matchCallback($arrInput){
		if(isset($arrInput[0])){
			return '<em>' . $arrInput[0] . '</em>';
		}
		return ;
	}

	/**
	 * 为字符串中的数字加上em飘红
	 *
	 * @param string $str 要飘红的字符串
	 */
	public static function emNumberInString($str){
		return preg_replace_callback('/[0-9\.\-]+/', 'Utils::matchCallback', $str);
	}

	/**
	 * 删除不需要的输出数据，这在手机端比较重要，传递尽量少的数据
	 * 
	 * @param array  $list 可供迭代的数据
	 * @param array $keys 保留哪些key
	 */
	public static function trimItem($list, $keys)
	{
		$ret = array();
		$keys = (array)$keys;
		foreach ((array)$list as $item){
			$tmp = array();
			foreach ($item as $k => $v){
				if(in_array($k, $keys)){
					$tmp[$k] = $v;
				}
			}
			$ret[] = $tmp;
		}
		return $ret;
	}

	/**
	 * 自动补足版本信息
	 * 
	 * @param array  $version 原始版本
	 */
	public static function formatVersion($version)
	{
		$versionArr = explode(".",$version);
		foreach($versionArr as &$v){
			if(!is_numeric($v)){
				$v = 0;
			}
		}
		if(count($versionArr) === 3){
			array_unshift($versionArr,0);
		}else if(count($versionArr) === 2){
			array_unshift($versionArr,0,0);
		}else if(count($versionArr) === 1){
			array_unshift($versionArr,0,0,0);
		}
		$version = implode(".",$versionArr);
		return $version;
	}

	public static function checkPost($tpl = null)
	{
		if('POST' !== $_SERVER['REQUEST_METHOD']){
			if(is_null($tpl)){
				throw new Exception('submit such method require HTTP method POST.');
			}
			throw new Exception("submit $tpl such method require HTTP method POST.", 1);
		}	
	}

	public static function checkFormHash($tpl, $ctrl)
	{
		$formhash = $ctrl->get('formhash', null);
		if(!isset($ctrl->request->cookies[AppConf::$cookiePrefix . '_formhash']) || empty($formhash) || 
				$formhash !== $ctrl->request->cookies[AppConf::$cookiePrefix . '_formhash']){
			$str = '[ form: ' . $formhash . ', cookie: ' . (isset($ctrl->request->cookies[AppConf::$cookiePrefix . '_formhash']) ? $ctrl->request->cookies[AppConf::$cookiePrefix . '_formhash'] : 'null') . ' ].';
			if(is_null($tpl)){
				throw new Exception("formhash check formhash failed $str .");
			}
			throw new Exception('formhash ' . $tpl . " check formhash failed $str.", 1);
		}
	}
	
	public static function httpRequest($url ,$method = 'GET',$params = null){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if('POST' == $method){
			curl_setopt($ch, CURLOPT_POST, true);
			if(!empty($params)){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			}
		}else{
			curl_setopt($ch, CURLOPT_HEADER, false);
		}
		$result = curl_exec($ch);
	    curl_close($ch);	
		return $result;	
	}
	
	public static function createShortUrl($url, $retry = 3){
		if(empty($url)){
			return $url;
		}
		
		while($retry > 0) {
			#$dwz = "http://dwz.cn/create.php";
            $dwz = "http://s.youzu.com/gen.php";
			$data=array('url'=>$url);
			$res = self::httpRequest($dwz , 'POST' ,$data);
			KC_LOG_DEBUG("create short url ,source url : $url, res: $res");
			$result =json_decode($res,true);
			$shortUrl = $url;
			if(isset($result['tinyurl'])){
				$shortUrl = $result['tinyurl'];
				break;
			}
			$retry--;
		}
		
		return $shortUrl;
	}
	
	public static function getCurrentUrl(){
		$url = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'];
		$url .= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : urlencode($_SERVER['PHP_SELF']) . '?' . urlencode($_SERVER['QUERY_STRING']);
		return $url;
	}
	
	public static function getHost(){
		$url = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'];
		return $url;
	}
	
	public static function parseUrlBase($apiUrl){
		$params = array();
		$port = parse_url($apiUrl, PHP_URL_PORT);
		if(!empty($port)){
			$port = ':'.$port.'';
		}else{
			$port = '';
		}
		$params['baseurl'] = parse_url($apiUrl, PHP_URL_SCHEME) . '://' . parse_url($apiUrl, PHP_URL_HOST) . $port ;
		$query = parse_url($apiUrl, PHP_URL_QUERY);
		if(!empty($query)){
			$query = '?'.$query;
		}else{
			$query = '';
		}
		$params['path'] = parse_url($apiUrl, PHP_URL_PATH) . $query;
		$params['path'] = trim($params['path'],'/');
		return $params;
	}

}
?>
