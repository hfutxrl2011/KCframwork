<?php
/**
 * Thrown when an http API call returns an exception.
 */
class HttpException extends Exception
{
	/**
	 * The result from the API server that represents the exception information.
	 */
	protected $result;

	/**
	 * Make a new API Exception with the given result.
	 *
	 * @param array $result The result from the API server
	 */
	public function __construct($result) {
		$this->result = is_array($result) ? $result : (array)$result;
		$code = array_key_exists('error_code', $result) && is_int($result['error_msg']) ? $result['error_code'] : 0;
		if (array_key_exists('error_msg', $result) && !empty($result['error_msg'])) {
			$msg = $result['error_msg'];
		} else {
			$msg = 'Unknown Error. Check getResult()';
		}
		parent::__construct($msg, $code);
	}

	/**
	 * Return the associated result object returned by the API server.
	 *
	 * @return array The result from the API server
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * To make debugging easier.
	 *
	 * @return string The string representation of the error
	 */
	public function __toString() {
		$str = __CLASS__ . ': {';
		if ($this->code != 0) {
			$str .= $this->code . ': ';
		}
		$str .= $this->message;
		$str .= ' in '.$this->getFile().':'.$this->getLine().'}';
		$str .= ' call stack: {'.$this->getTraceLog($this->getTraceAsString()).'}';
		return $str;
	}
	
	/**
	 * get trace string for useing log output
	 * @param string $trace
	 * @return multitype:NULL mixed
	 */
	public function getTraceLog($trace) {
		$matches = preg_split ( '/[\r\n]+/is', $trace );
		$traceArray = array ();
		isset ( $matches [0] ) && $traceArray [] = $matches [0];
		isset ( $matches [1] ) && $traceArray [] = $matches [1];
		isset ( $matches [2] ) && $traceArray [] = $matches [2];
		isset ( $matches [3] ) && $traceArray [] = $matches [3];
		return implode('<<<---',$traceArray);
	}
	
	/**
	 * get trace string for useing log output
	 * @param string $trace
	 * @return multitype:NULL mixed
	 */
	public function getTraceStringForLog($trace) {
		preg_match_all ( '/([^:\r\n]+):{1}([^:\r\n]+)[\r\n]+([^:\r\n]+):{1}[\r\n]{1}(.*?)(Variables in local scope \(#\d+\):)[\r\n]+(.*)/is', $trace, $matches);
		$traceArray = array();
		if (!empty($matches[1]) && !empty($matches[2])) {
			$traceArray[$matches[1][0]] = $matches[2][0];
		}
	
		if (!empty($matches[3]) && !empty($matches[4])) {
			$callstack = array_filter(preg_split('/[\r\n]/',$matches[4][0]));
			$traceArray[$matches[3][0]] = array_pop($callstack);
		}
	
		if (!empty($matches[5]) && !empty($matches[6])) {
			$traceArray[$matches[5][0]] = $matches[6][0];
		}
		return $traceArray;
	}
}

/**
 * A REST client to make requests to 3rd party RESTful web services.
 *
 * <p>You can send GET, POST, PUT and DELETE requests with HttpClient.
 * Method chaining is also supported by this class.</p>
 * Example usage:
 * <code>
 * //Example usage in a bingo Controller
 * //import the class and create the REST client object
 * $client = new HttpClient;
 * $client->connect("http://api.baidu.com/messages.xml")
 *        ->auth('user', 'password', true)
 *        ->get()
 *
 * if($client->isSuccess()){
 *      echo "<pre>Received content-type: {$client->getContentType()}<br/>";
 *      print_r( $client->result() );
 * }else{
 *      echo "<pre>Request unsuccessful, error {$client->getHttpCode()} return";
 * }
 * </code>
 */
class HttpClient {
	
    protected $serverUrl;//post or get url
    protected $curlOpt;//curl_setopt_array() param array
    protected $authUser;//The HTTP authentication user
    protected $authPwd;//The HTTP authentication password
    protected $args;//post or get request params
    protected $cookies=array();//curl witch cookies
    
    protected $result;//return response result
    protected $resultLen;//return response result length
    protected $httpCode;//return response code
    protected $httpCodeInfo;// describe response code msg
    protected $contentType;//response header
    
    private static $curlDebug = false;//for verbose
    private static $maxRedirect = 0;//maximum amount of HTTP redirections
    private static $timeout = 12;//maximum number of seconds
    private static $connectTimeout = 3;//maximum number of seconds for connect
    
    private static $retryTimes = 2;//fail error for retry times
    private static $sleepTime = 100000;//100ms(macro seconds)

    /**
     * for sending Accept header, this is to be used with requests to REST server
     * @var const
     */
    const HTML = 'text/html';
    const XML = 'application/xml';
    const JSON = 'application/json';
    const JS = 'application/javascript';
    const CSS = 'text/css';
    const RSS = 'application/rss+xml';
    const YAML = 'text/yaml';
    const ATOM = 'application/atom+xml';
    const PDF = 'application/pdf';
    const TEXT = 'text/plain';
    const PNG = 'image/png';
    const JPG = 'image/jpeg';
    const GIF = 'image/gif';
    const CSV = 'text/csv';
    
    /**
     * request method
     * @var const string
     */
    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const DELETE = 'delete';
    const HEAD = 'head';
    
    /**
     * the last error number
     * @var int
     */
    private $errno;
    /**
     * a string containing the last error for the current session 
     * @var string
     */
	private $errmsg;
	
	/**
	 * 
	 * @var string
	 */
	private $curlInfo;
	
	/**
	 * 
	 * @var string
	 */
	private $responseHeader = '';
	
	/**
	 * the handle's request string
	 * @var mixed
	 */
	private $headerOut = '';
	
	/**
	 * list errno values
	 * @var const
	 */
	const SUCCESS = 0;				//成功
	const errUrlInvalid = 1;		//非法url
	const errServiceInvalid = 2;	//对端服务不正常
	const errHttpTimeout = 3;		//交互超时，包括连接超时
	const errTooManyRedirects = 4;	//重定向次数过多
	const errTooLargeResponse = 5;	//响应内容过大
	const errResponseErrorPage = 6;	//返回错误页面
	const errNoResponse = 7;		//没有响应包
	const errNoResponseBody = 8;	//响应包中没有body内容
	const errOtherEror = 9;			//其余错误
	const errThrowException = 10;	//抛出未知异常
	
	/**
	 * HTTP/1.1: Status Code Definitions
	 * @var string
	 */
	private static $statusCode = array(
			//[Informational 1xx]
			100=>"Continue",
			101=>'Switching Protocols',
			
			//[Successful 2xx]
			200=>'OK',
			201=>'Created',
			202=>'Accepted',
			203=>'Non-Authoritative Information',
			204=>'No Content',
			205=>'Reset Content',
			206=>'Partial Content',
			
			//[Redirection 3xx]
			300=>'Multiple Choices',
			301=>'Moved Permanently',
			302=>'Found',
			303=>'See Other',
			304=>'Not Modified',
			305=>'Use Proxy',
			306=>'(Unused)',
			307=>'Temporary Redirect',
			
			//[Client Error 4xx]
			400=>'Bad Request',
			401=>'Unauthorized',
			402=>'Payment Required',
			403=>'Forbidden',
			404=>'Not Found',
			405=>'Method Not Allowed',
			406=>'Not Acceptable',
			407=>'Proxy Authentication Required',
			408=>'Request Timeout',
			409=>'Conflict',
			410=>'Gone',
			411=>'Length Required',
			412=>'Precondition Failed',
			413=>'Request Entity Too Large',
			414=>'Request-URI Too Long',
			415=>'Unsupported Media Type',
			416=>'Requested Range Not Satisfiable',
			417=>'Expectation Failed',
			
			//[Server Error 5xx]
			500=>'Internal Server Error',
			501=>'Not Implemented',
			502=>'Bad Gateway',
			503=>'Service Unavailable',
			504=>'Gateway Timeout',
			505=>'HTTP Version Not Supported',
			);
	
    /**
     * 构造函数
     * @param string $serverUrl
     */
    public function  __construct($serverUrl=NULL) {
        if($serverUrl != NULL){
            $this->setServerUrl($serverUrl);
        }
        $this->curlOpt = array();
        $this->curlOpt['RETURNTRANSFER'] = true;//return the transfer as a string of the return value of curl_exec()
        $this->curlOpt['HEADER'] = false;//include the header in the output. please notice...
        $this->curlOpt['FRESH_CONNECT'] = false;//force the use of a new connection instead of a cached one.
//         ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off'
	    $this->curlOpt['FOLLOWLOCATION'] = true;//follow any 'Location: ' header that the server sends as part of the HTTP header
        $this->curlOpt['MAXREDIRS'] = self::$maxRedirect;//The maximum amount of HTTP redirections to follow
        $this->curlOpt['CONNECTTIMEOUT'] = self::$connectTimeout;//The number of seconds to wait while trying to connect.
        $this->curlOpt['TIMEOUT'] = self::$timeout;//The maximum number of seconds to allow cURL functions to execute. 
        $this->curlOpt['USERAGENT'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $this->curlOpt['REFERER'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        $this->curlOpt['ENCODING'] = '';//'' automaticly enables all supported encodings("identity", "deflate", and "gzip")
        $this->curlOpt['AUTOREFERER'] = true;//automatically set the Referer: field in requests where it follows a Location: redirect. 
        $this->curlOpt['HEADERFUNCTION'] = array($this, 'getHeader');//__CLASS__.'::getHeader';//The header data must be written when using this callback function
        $this->curlOpt['WRITEFUNCTION'] =  array($this, 'getResult');//__CLASS__.'::getResult';//The data must be saved by using this callback function.
        $this->curlOpt['FILETIME'] = true;//attempt to retrieve the modification date of the remote document.
        
        $this->curlOpt['NOSIGNAL'] = true;//ignore any cURL function that causes a signal to be sent to the PHP process. for timeout
        $this->curlOpt['SSL_VERIFYPEER'] = false;//false to stop cURL from verifying the peer's certificate. 
        $this->curlOpt['SSL_VERIFYHOST'] = 2;//In production environments the value of this option should be kept at 2 (default value).
        $this->curlOpt['NOSIGNAL'] = true;//TRUE to ignore any cURL function that causes a signal to be sent to the PHP process. 
        
        $this->curlOpt['HTTPHEADER'] = array('Expect:','SOAPAction: ""','Cache-Control: max-age=0','Pragma: no-cache');//set default httpheader
        
        $this->curlOpt['VERBOSE'] = self::$curlDebug;//output verbose information
    }
    
    /**
     * get header
     * @param resource $ch
     * @param string $header
     */
    public function getHeader($ch, $header)
    {
    	$this->responseHeader .= $header;
    	return strlen($header);
    }
    
    /**
     * get body of http request
     * @param resource $ch
     * @param resource $data
     * @return unknown
     */
    public function getResult($ch, $data)
    {
    	$this->result .= $data;
    	$len = strlen($data);
    	$this->resultLen += $len;
    	return $len;
    }

    /**
     * Get/set the REST server URL
     * @param string $serverUrl
     * @return mixed
     */
    public function connect($serverUrl=NULL){
        if($serverUrl == NULL){
        	return $this->serverUrl;
        }
        $this->setServerUrl($serverUrl);
        return $this;
    }

    /**
     * Check if a given URL exist.
     *
     * The url exists if the return HTTP code is 200
     * @param string $url Url of the page
     * @return boolean True if exists (200)
     */
    public static function checkUrlExist($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // set to HEAD request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // don't output the response
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // don't use a cached version of the url
        curl_exec($ch);// don't care return value
        $valid = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200;
        curl_close($ch);
        return $valid;
    }

    /**
     * Send request to a URL and returns the HEAD request HTTP code.
     *
     * @param string $url Url of the page
     * @return int returns the HTTP code
     */
    public static function retrieveHeaderCode($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // set to HEAD request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // don't output the response
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // don't use a cached version of the url
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    /**
     * Get/set the connection timeout duration (seconds)
     * @param int $sec Timeout duration in seconds
     * @return mixed
     */
    public function setConnectTimeout($sec=NULL){
        if($sec === NULL){
            return $this;
        }else{
            $this->curlOpt['CONNECTTIMEOUT'] = $sec;
        }
        return $this;
    }
    
    /**
     * Get/set the timeout duration (seconds)
     * @param int $sec Timeout duration in seconds
     * @return mixed
     */
    public function setTimeout($sec=NULL){
    	if($sec === NULL){
    		return $this;
    	}else{
    		$this->curlOpt['TIMEOUT'] = $sec;
    	}
    	return $this;
    }

    /**
     * Get/set data for the REST request.
     *
     * The data can either be a string of params <b>id=19&year=2009&filter=true</b>
     * or an assoc array <b>array('id'=>19, 'year'=>2009, 'filter'=>true)</b>
     *
     * <p>The data is returned when no data value is passed into the method.</p>
     *
     * @param string|array $data
     * @return mixed
     */
    public function data($data = NULL){
        if($data == NULL)
            return $this->args;

        if(is_string($data)){
            $this->args = $data;
        } else if(is_array($data)){
            $this->args = http_build_query($data);
        } else {
        	$this->args = strval($data);
        }
        return $this;
    }

    /**
     * Get/Set options for executing the REST connection.
     *
     * This method prepares options for Curl to work.
     * Instead of setting CURLOPT_URL = value, you should use URL = value while setting various options
     *
     * The options are returned when no option array is passed into the method.
     *
     * <p>See http://www.php.net/manual/en/function.curl-setopt.php for the list of Curl options.</p>
     * Option keys are case insensitive. Example option input:
     * <code>
     * $client = new HttpClient('http://somewebsite.com/api/rest');
     * $client->options(array(
     *                      'returnTransfer'=>true,
     *                      'header'=>true,
     *                      'SSL_VERIFYPEER'=>false,
     *                      'timeout'=>10
     *                  ));
     *
     * $client->execute('get');
     * //or $client->get();
     * </code>
     * @param array $optArr
     * @return mixed
     */
    public function options($optArr=NULL){
        if($optArr == NULL)
            return $this->curlOpt;
        $this->curlOpt = array_merge($this->curlOpt, $optArr);
        return $this;
    }

    /**
     * Get/set authentication details for the RESTful call
     *
     * Authentication can be done with HTTP Basic or Digest. HTTP Auth Digest will be used by default.
     * If no values are passed into the method,
     * the auth details with be returned in an array consist of Username and Password.
     *
     * <p>If you are implementing your own RESTful api, you can handle authentication with DooDigestAuth::http_auth()
     * or setup authentication in your routes.</p>
     *
     * @param string $username Username
     * @param string $password Password
     * @param bool $basic to switch between HTTP Basic or Digest authentication
     * @return mixed
     */
    public function auth($username=NULL, $password=NULL, $basic=FALSE){
        if($username === NULL && $password === NULL)
            return array($this->authUser, $this->authPwd);

        $this->authUser = $username;
        $this->authPwd = $password;

        $this->curlOpt['HTTPAUTH'] = ($basic)?CURLAUTH_BASIC : CURLAUTH_DIGEST;

        return $this;
    }

    /**
     * Get/set desired accept type.
     * <p>This should be used if the REST server analyze Accept header to parse what format
     * you're seeking for the result content. eg. json, xml, rss, atom.
     * HttpClient provides a list of common used format to be used in your code.</p>
     * Example to retrieve result in JSON:
     * <code>
     *   $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,"; 
  	 *	 $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*\/*;q=0.5"; 
     * $client = new HttpClient;
     * $client->connect("http://api.baidu.com/direct_messages")
     *        ->auth('username', 'password', true)
     *        ->accept(HttpClient::JSON)
     *        ->get();
     * </code>
     * @param string $type
     * @return mixed
     */
    public function accept($type=NULL){
        if($type==NULL)
            if(isset($this->curlOpt['HTTPHEADER']) && $this->curlOpt['HTTPHEADER'][0])
                return str_replace('Accept: ', '', $this->curlOpt['HTTPHEADER'][0]);
            else
                return;
        $lastHeader = $this->curlOpt['HTTPHEADER'];
		if (is_array($lastHeader) && !empty($lastHeader)) {
			if (is_string($type)) {
				$lastHeader[] = "Accept: $type";
			}
		}
        $this->curlOpt['HTTPHEADER'] = $lastHeader;
        return $this;
    }

    /**
     * Get/set desired content type to be post to the server
     * <p>This should be used if the REST server analyze Content-Type header to parse what format
     * you're posting to the API. eg. json, xml, rss, atom.
     * HttpClient provides a list of common used format to be used in your code.</p>
     * Example to retrieve result in JSON:
     * <code>
     * $client = new HttpClient;
     * $client->connect("http://api.baidu.com/post_status")
     *        ->auth('username', 'password', true)
     *        ->contentType(HttpClient::JSON)
     *        ->post();
     * </code>
     * @param string $type
     * @return mixed
     */
    public function contentType($type=NULL){
        if($type == NULL){
            if(isset($this->curlOpt['HTTPHEADER']) && $this->curlOpt['HTTPHEADER'][0])
                return str_replace('Content-Type: ', '', $this->curlOpt['HTTPHEADER'][0]);
            else
                return;
        }
        
        $lastHeader = $this->curlOpt['HTTPHEADER'];
        if (is_array($lastHeader) && !empty($lastHeader)) {
        	if (is_string($type)) {
        		$lastHeader[] = "Content-Type: $type";
        	}
        }
        $this->curlOpt['HTTPHEADER'] = $lastHeader;
        return $this;
    }

    /**
     * Execute the RESTful request through either GET, POST, PUT or DELETE request method
     * @param string $method Method string is case insensitive.
     */
    public function execute($method){
        $method = strtolower($method);
        if($method=='get')
            $this->get();
        elseif($method=='post')
            $this->post();
        elseif($method=='put')
            $this->post();
        elseif($method=='delete')
            $this->post();
    }
    
    /**
     * not singleton pattern,please notice!
     * @param array $options
     * @return object
     */
    public static function getInstance(array $options = NULL)
    {
    	return new Httpclient($options);
    }

    /**
     * Execute the request with HTTP GET request method
     * @return HttpClient
     * @throws HttpException
     */
    public function get(){
        try {
//             $exception = array('error_code' => 1, 'error_msg' => 'dddddddddd');
//             throw new HttpException($exception); //由接口逻辑决定如何处理异常
//             throw new Exception('exception string notice....',1); //由接口逻辑决定如何处理异常
        	$this->curlExec ( self::GET );
        	if($this->checkHttpResponse($this->serverUrl, $this->errno, $this->errmsg))
        	{
        		return $this->result;
        	}
        } catch (HttpException $e) {
        	$exception = array('error_code'=> self::errThrowException,'error_msg' => $e->getMessage().' method: '.__function__);
        	$this->throwHttpException($exception);//向外层抛出
        }
        return false;
    }

    /**
     * Execute the request with HTTP POST request method
     * @return HttpClient
     * @throws HttpException
     */
    public function post(){
        try {
        	//POST request makes only once.
        	self::setRetryTimes(1);
        	$this->curlExec ( self::POST );
        	if($this->checkHttpResponse($this->serverUrl, $this->errno, $this->errmsg))
        	{
        		return $this->result;
        	}
        } catch (HttpException $e) {
        	$exception = array('error_code'=> self::errThrowException,'error_msg' => $e->getMessage().' method: '.__function__);
        	$this->throwHttpException($exception);//向外层抛出
        }
        return false;
    }
    
	/**
	 * exec curl request for retrying xx times if failed
	 * @param ch resource
	 * @throws HttpException
	 */
	private function curlExec($method = self::GET) {
		//set opt_array
		$arr = array();
		foreach($this->curlOpt as $k=>$v){
			$arr[constant('CURLOPT_'.strtoupper($k))] = $v;
		}
		//config param
		switch ($method){
			case self::GET:
				if($this->args != NULL){
					$this->serverUrl = $this->serverUrl .'?'. $this->args ;
				}else{
					$this->serverUrl = $this->serverUrl;
				}
				//set GET method
				$arr[CURLOPT_HTTPGET] = true;
				break;
			case self::POST:
				//set POST method and fields
				$arr[CURLOPT_POST] = true;
				$arr[CURLOPT_POSTFIELDS] = $this->args;
				break;
			case self::PUT:
				//set PUT method and fields
				$arr[CURLOPT_CUSTOMREQUEST] = 'PUT';
				$arr[CURLOPT_POSTFIELDS] = $this->args;
				break;
			case self::DELETE:
				//set DELETE method, delete methods don't have fields,ids should be set in server url
				$arr[CURLOPT_CUSTOMREQUEST] = 'DELETE';
				break;
			case self::HEAD:
				//set HEAD method, delete methods don't have fields,ids should be set in server url
				$arr[CURLOPT_CUSTOMREQUEST] = 'HEAD';
				break;				
			default:
				//set POST method and fields
				$arr[CURLOPT_POST] = true;
				$arr[CURLOPT_POSTFIELDS] = $this->args;
		}
		
		//init curl
		if (($ch = curl_init($this->serverUrl)) == false) {
			$exception = array('error_code'=> self::errServiceInvalid,'error_msg' => "curl_init error for url {$this->serverUrl}.");
			$this->throwHttpException($exception);
		}
		
		//set HTTP auth username and password is found
		if(isset($this->authUser) || isset($this->authPwd)){
			$arr[CURLOPT_USERPWD] = $this->authUser .':'. $this->authPwd;
		}
		
		//header out
		$arr[CURLINFO_HEADER_OUT] = true;
		
		//setCookie
		if(is_array($this->cookies) && count($this->cookies) > 0 )
		{
			$cookieStr = '';
			foreach( $this->cookies as $key => $value )
			{
				$cookieStr .= $key.'='.rawurlencode($value).'; ';
			}
			$arr[CURLOPT_COOKIE] = $cookieStr;
		}
		
		//Set multiple options for a cURL transfer 
		curl_setopt_array($ch, $arr);
		
        $times = self::$retryTimes;
        do {
        	//reset msg
        	$this->resetResult();
        	curl_exec($ch);
        	
        	$this->curlInfo = curl_getinfo($ch);
        	$this->httpCode = isset($this->curlInfo['http_code'])?$this->curlInfo['http_code']:-1;
        	$this->httpCodeInfo = self::getStatusCodeMsg($this->httpCode);
        	$this->contentType = isset($this->curlInfo['content_type'])?$this->curlInfo['content_type']:'';
        	$this->headerOut = isset($this->curlInfo['request_header'])?$this->curlInfo['request_header']:'';
        	
        	$this->errno = curl_errno($ch);
        	$this->errmsg = curl_error($ch);
			
			if ($this->errno > 0) {//error for retry
				usleep(self::$sleepTime);//pause 100ms for later retry
				
				if (($this->httpCode == 301 || $this->httpCode == 302 || $this->httpCode == 307) && preg_match ('/Location: ([^\r\n]+)[\r\n]*/si', $this->responseHeader, $matches ) > 0) {
					$nextUrl = $matches[1];
					//$nextUrl is relative path, prefix with domain name
					if (preg_match ('/^http(?:|s):\/\//', $nextUrl) <= 0 && preg_match ('/^(http(?:|s):\/\/[^\/]+)/', $this->serverUrl, $matches) > 0) {
						$nextUrl = $matches[1].'/'.$nextUrl;
					}
					$this->setServerUrl($nextUrl);
					curl_setopt($ch, CURLOPT_URL, $nextUrl);
				}
			}
        } while ($this->errno && --$times);
        
        //get curl info failed,throw exception
//         if (!$this->isSuccess()) {
//         	$exception = array('error_code'=> $this->errno,'error_msg' => $this->errmsg);
// 			$this->throwHttpException($exception);
//         }
        //close the curl handle
        curl_close($ch);
	}


    /**
     * Execute the request with HTTP PUT request method
     * @return HttpClient
     * @throws HttpException
     */
    public function put(){
        try {
        	$this->curlExec ( self::PUT );
        	if($this->checkHttpResponse($this->serverUrl, $this->errno, $this->errmsg))
        	{
        		return $this->result;
        	}
        } catch (HttpException $e) {
        	$exception = array('error_code'=> self::errThrowException,'error_msg' => $e->getMessage().' method: '.__function__);
        	$this->throwHttpException($exception);//向外层抛出
        }
        return false;
    }
    
    /**
     * Execute the request with HTTP head request method
     * @return HttpClient
     * @throws HttpException
     */
    public function head(){
    	try {
    		$this->options(array('NOBODY' => true));
    		$this->curlExec ( self::HEAD );
    		if($this->checkHttpResponse($this->serverUrl, $this->errno, $this->errmsg))
    		{
    			return $this->result;
    		}
    	} catch (HttpException $e) {
    		$exception = array('error_code'=> self::errThrowException,'error_msg' => $e->getMessage().' method: '.__function__);
    		$this->throwHttpException($exception);//向外层抛出
    	}
    	return false;
    }
    
    /**
     * throw HttpException
     * @param array $result
     * @throws HttpException
     */
    protected function throwHttpException(array $result = array()) {
    	throw new HttpException($result);
    }
    
    /**
     * reset curl response
     */
    public function resetResult(){
    	$this->responseHeader = '';
    	$this->result = '';
    	$this->errno = 0;
    	$this->errmsg = '';
    }

    /**
     * Execute the request with HTTP DELETE request method
     * @return HttpClient
     * @throws HttpException
     */
    public function delete(){
        try {
        	$this->curlExec ( self::DELETE );
        	if($this->checkHttpResponse($this->serverUrl, $this->errno, $this->errmsg))
        	{
        		return $this->result;
        	}
        } catch (HttpException $e) {
        	$exception = array('error_code'=> self::errThrowException,'error_msg' => $e->getMessage().' method: '.__function__);
        	$this->throwHttpException($exception);//向外层抛出
        }
        return false;
    }

	private function checkHttpResponse($url, $errno, $errmsg)
	{
		$url = htmlspecialchars ( $url, ENT_QUOTES );
		if ($errno == CURLE_OK){
			$this->errno = self::SUCCESS;
			$this->errmsg = '';
			return true;
		} elseif ($errno == CURLE_URL_MALFORMAT || $errno == CURLE_COULDNT_RESOLVE_HOST) {
			$this->errno = self::errUrlInvalid;
			$this->errmsg = "The URL $url is not valid.";
		} elseif ($errno == CURLE_COULDNT_CONNECT) {
			$this->errno = self::errServiceInvalid;
			$this->errmsg = "Service for URL[$url] is invalid now, errno[$errno] errmsg[$errmsg]";
		} elseif ($errno == 28) {
			$this->errno = self::errHttpTimeout;
			$this->errmsg = "Request for $url timeout: $errmsg";
		} elseif ($errno == CURLE_TOO_MANY_REDIRECTS) {
			$this->errno = self::errTooManyRedirects;
			$this->errmsg = "Request for $url caused too many redirections.";
		} else {
			$this->errno = self::errOtherEror;
			$this->errmsg = "Request for $url failed, errno[$errno] errmsg[$errmsg]";
		}
		
		return false;
	}
    /**
     * Get result of the executed request
     * @return string
     */
    public function result(){
        return $this->result;
    }

    /**
     * Determined if it's a successful request
     * @return bool
     */
    public function isSuccess(){
        return ($this->httpCode>=200 && $this->httpCode<300);
    }

    /**
     * Convert the REST result to XML object
     *
     * Returns a SimpleXMLElement object by default which consumed less memory than DOMDocument.
     * However if you need the result to be DOMDocument which is more flexible and powerful in modifying XML,
     * just passed in True to the function.
     *
     * @param bool $domObject convert result in to DOMDOcument if True
     * @return SimpleXMLElement|DOMDocument
     */
    public function xml_result($domObject=FALSE){
        if($domObject){
            $d = new DOMDocument('1.0','UTF-8');
            $d->loadXML($this->result);
            return $d;
        }else
            return simplexml_load_string($this->result);
    }

    /**
     * Convert the REST result to JSON object
     * @param bool $toArray convert result into assoc array if True.
     * @return object
     */
    public function json_result($toArray=FALSE){
        return json_decode($this->result,$toArray);
    }
    
	/**
	 * for debug curl return value
	 * @param boolean $curlDebug
	 */
	public static function setCurlDebug($curlDebug) {
		HttpClient::$curlDebug = $curlDebug;
	}
	
	/**
	 * Get result's HTTP status code of the executed request
	 * @return the $httpCode
	 */
	public function getHttpCode() {
		return $this->httpCode;
	}

	/**
	 * Get result's content type of the executed request
	 * @return the $contentType
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * @return the $cookies
	 */
	public function getCookies() {
		return $this->cookies;
	}

	/**
	 * @param multitype: $cookies
	 */
	public function setCookies(array $cookies) {
		$this->cookies = $cookies;
		return $this;
	}
	/**
	 * @return the $statusCode
	 */
	public static function getStatusCode() {
		return HttpClient::$statusCode;
	}

	/**
	 * get status code msg
	 * @param int $code
	 */
	public static function getStatusCodeMsg($code) {
		if (isset(HttpClient::$statusCode[$code])) {
			return HttpClient::$statusCode[$code];
		}
		return 'getStatusCodeMsg error';
	}
	/**
	 * @return the $errno
	 */
	public function getErrno() {
		return $this->errno;
	}

	/**
	 * @return the $errmsg
	 */
	public function getErrmsg() {
		return $this->errmsg;
	}
	/**
	 * @return the $curlInfo
	 */
	public function getCurlInfo() {
		return $this->curlInfo;
	}
	/**
	 * @return the $serverUrl
	 */
	public function getServerUrl() {
		return $this->serverUrl;
	}

	/**
	 * @param string $serverUrl
	 */
	public function setServerUrl($serverUrl) {
		$this->serverUrl = $serverUrl;
	}
	/**
	 * @param number $retryTimes
	 */
	public static function setRetryTimes($retryTimes) {
		HttpClient::$retryTimes = $retryTimes;
	}
	/**
	 * @return the $responseHeader
	 */
	public function getResponseHeader() {
		return $this->responseHeader;
	}



}
