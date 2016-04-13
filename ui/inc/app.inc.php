<?php
class App
{
	public static $subDir = null;
	public static $controller = null;
	public static $action = null;
	public static $module = null;
	public static $remoteIP = null;
	public static $path = null;
	public static $requestURI = null;
	public static $httpHost = null;
	public static $remoteIp = null;
	public static $timer = null;
	public static $rqtTime = null; 
	public static $passInfo = null;

	public static function getTimer()
	{
		if(is_null(self::$timer)){
			$timer = new MTimer();
			self::$timer = $timer;
		}

		return self::$timer;
	}

	public static function getModule()
	{
		if(is_null(self::$module)){
			self::_parseRouter();
		}
		return self::$module;
	}

	public static function getSubDir()
	{
		if(is_null(self::$subDir))	{
			self::_parseRouter();
		}
		return self::$subDir;
	} 

	public static function getController()
	{
		if(is_null(self::$controller))	{
			self::_parseRouter();
		}
		return self::$controller;
	} 
	
	public static function getAction()
	{
		if(is_null(self::$action))	{
			self::_parseRouter();
		}
		return self::$action;
	} 

	public static function getHost()
	{
		if (empty ($_SERVER ['HTTP_HOST'])) {
			KC_LOG_WARNING('Unkown http host.');
			$httpHost = 'localhost';
		}else{
			$httpHost = $_SERVER ['HTTP_HOST'];
		}
		self::$httpHost = $httpHost;
		return $httpHost;
	}

	private static function _parseRouter()
	{
        $httpHost = self::getHost();
		$urlArray = explode('.', $httpHost);
        $module = strtolower($urlArray [0]);
		$module = isset(EnvConf::$moduleMap[$module])?EnvConf::$moduleMap[$module]:$module;
		
		$requestURI = self::getPath();
        $_pos = strpos($requestURI,'&');        //过滤url后面的尾部 
        if($_pos !==  false){
            $requestURI = substr($requestURI,0,$_pos);
        }
        $_pos = strpos($requestURI,'?');
        if($_pos !==  false){
            $requestURI = substr($requestURI,0,$_pos);
        }
        $uriArray = explode('/', trim($requestURI, '/'));
        $subDir = '';
		$action = 'index';
        if(1 === count($uriArray) && !empty($uriArray[0]) ){
			$controller  = strtolower($uriArray[0]);
		}elseif( 2 === count($uriArray)){
			$controller = strtolower($uriArray[0]);
			$action = strtolower($uriArray[1]);
		}elseif( 3 === count($uriArray)){
			$module = strtolower($uriArray[0]);
			$controller = strtolower($uriArray[1]);
			$action = strtolower($uriArray[2]);
        }else{
            $controller = 'index';
        }
        
        if(isset($_GET['method']) && preg_match("/^[_a-zA-Z0-9-]+$/", $_GET['method'])){
            $action = strtolower($_GET['method']);
        }
		if(isset($_REQUEST['module']) && !empty($_REQUEST['module']) ) {//防止线上非法访问
			$_REQUEST['module'] = trim($_REQUEST['module']);
			$module = strtolower($_REQUEST['module']);
		}
		if(isset($_REQUEST['sub_dir']) && !empty($_REQUEST['sub_dir']) && preg_match("/^[_a-zA-Z0-9-]+$/", $_GET['sub_dir']) ){
			$_REQUEST['sub_dir'] = trim($_REQUEST['sub_dir']);
			$subDir = strtolower($_REQUEST['sub_dir']);
		}
		if(isset($_REQUEST['controller']) && !empty($_REQUEST['controller']) && preg_match("/^[_a-zA-Z0-9-]+$/", $_GET['controller'])){
			$_REQUEST['controller'] = trim($_REQUEST['controller']);
			$controller = strtolower($_REQUEST['controller']);
		}

		if(  !preg_match("/^[_a-zA-Z0-9-]+$/", $controller) ){
			//throw new Exception("param error in url controller:".$controller);
		}
		//hack
		if(   $controller == 'favicon.ico' ){
			//exit(0);
			//throw new Exception("param error in url controller:".$controller);
		}
		if( !preg_match("/^[_a-zA-Z0-9-]+$/", $action) ){
			//throw new Exception("param error in url action:".$action);
		}

		self::$module = $module;
		self::$subDir = $subDir;
		self::$controller = $controller;
		self::$action = $action;
	}
	
	public static function getPath()
    {
        if(!is_null(self::$path)){
            return self::$path;
        }
        $path = '/';
        if(isset($_SERVER ['REQUEST_URI'])){
            $pos = strpos($_SERVER ['REQUEST_URI'], '?');
            if(false !== $pos){
                $path = substr($_SERVER ['REQUEST_URI'], 0, $pos);
            }else{
				$path = $_SERVER ['REQUEST_URI'];
			}
        }
        self::$path = $path;
        return self::$path;
    }

    public static function getRequestURI()
    {
        if(!is_null(self::$requestURI)){
            return self::$requestURI;
        }
        if (isset ($_SERVER ['REQUEST_URI'])) {
            $requestURI = $_SERVER ['REQUEST_URI'];
        } else if (isset ($_SERVER ['QUERY_STRING'])) {
            $requestURI = $_SERVER ['QUERY_STRING'];
        } else {
            $requestURI = $_SERVER ['PHP_SELF'];
        }
        self::$requestURI = $requestURI;
		KC_LOG_INFO('RequestLog ' . $requestURI);
        return self::$requestURI;
    }

	public static function getClientIp()
	{
		if(!is_null(self::$remoteIp)){
			return self::$remoteIp;
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip  = $ips[0];
		} else if (!empty($_SERVER['HTTP_CLIENTIP'])) {
			$ip = $_SERVER['HTTP_CLIENTIP'];
		} else if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '127.0.0.1';
		}
        self::$remoteIP = $ip;
		return $ip;
	}

	public static function getLogId()
	{
		return KcMvc::getKcMvc()->requestId;
	}
}
?>
