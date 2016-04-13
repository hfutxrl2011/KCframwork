<?php
require_once INC_ROOT . '/env.inc.php';
require_once INC_ROOT . '/app.inc.php';
require_once LIB_ROOT . '/MTimer.php';
class KcMvc {
	public $debug = true;
	public $encoding = 'UTF-8';
	public $endStatus = 'error';
	public $requestId = 0;
	public $php = null;
	public $request = null;
	public $response = null;
	public $errPage = null;
	public static $xhprof_enable = false;
	protected static $_appObj = null;

	protected function __construct() {
		App::getTimer();	//set start timer
	}

	public static function getKcMvc()
	{
		if(is_null(self::$_appObj)){
			self::$_appObj = new KcMvc();
		}
		return self::$_appObj;
	}
	
	function run() {
		$this->init();
		$this->process();
		$this->endStatus = 'ok';
	}

	function init() {
		$this->_initConf ();
		$this->_initWebObject();
		$this->_initEnv ();
		$this->_initLogger();
		$this->requestId = $this->genRequestId ();
		//do self-define module initial process
		$mClassName = ucfirst(App::getModule()) . 'Module';
		if(class_exists($mClassName)){
			$notUsed = new $mClassName;
			call_user_func($mClassName . '::initModule');
		}else{
			throw new Exception("initModule class not exists");
		}
		//统计执行时间
		$module =  App::getModule();
		$interface =  App::getController().'_'.App::getAction();
		
		Statis_Client::startTick($module,$interface);
		App::getTimer()->set('framework init');
	}
	
    function process() {
		$input_filter = new InputFilter();
		$input_filter->process($this);
        if(!is_null($this->request->get("method"))) {
		    $basic = array(
						'reqip'=>$this->request->userip.':'.$this->request->clientip,
						'uri'=>$this->request->url,            
		                'method'=>$this->request->get("method"),
						'logid'=>$this->requestId
					  );
		}else {
			$basic = array(
						'reqip'=>$this->request->userip.':'.$this->request->clientip,
						'uri'=>$this->request->url, 
						'logid'=>$this->requestId
					  );
		}
		kc_log_addbasic($basic);
		$dispatch = new Dispatch($this);
		App::getTimer()->set('framework prepare');
		$dispatch->dispatch_url($this->request->url);
		$this->response->send();
		KC_LOG_TRACE('[TIME COST STATISTIC] [ ' . App::getTimer()->getString() . ' ].');
	}

	//generate an unique request id for every request
	private function genRequestId() {
		if (isset ( $_SERVER ['HTTP_CLIENTAPPID'] )) {
			return intval ( $_SERVER ['HTTP_CLIENTAPPID'] );
		}
		$reqip = App::getClientIp();
		$time = gettimeofday ();
		$time = $time ['sec'] * 100 + $time ['usec'];
		$ip = ip2long ( $reqip );
		$id = ($time ^ $ip) & 0xFFFFFFFF;
		$id = Sign::sign64($id . '_' . rand(1, 800000000));
		return $id;
	}
	
	//try to include all web object file and initialize request and response object
	private function _initWebObject() {
		require_once FRAMEWORK_ROOT . 'request.php';
		require_once FRAMEWORK_ROOT . 'response.php';
		require_once FRAMEWORK_ROOT . 'smartyview.php';
		require_once FRAMEWORK_ROOT . 'dispatch.php';
		require_once FRAMEWORK_ROOT . 'controller.php';
		require_once FRAMEWORK_ROOT . 'inputfilter.php';
		$this->request = new Request($this);
		$this->response = new Response($this);
	}
	
	//load config according to current environment
	//if debug switch is on, print all error infomation to browers
	private function _initConf() {
		$uiEnv = Env::getEnvironment();
		$confName = $uiEnv . '.conf.php';
		require_once CONF_ROOT. $confName;
		$this->debug = EnvConf::$debug;
		$this->encoding = EnvConf::$encoding;
		if ($this->debug) {
			ini_set('display_errors',1);
		}
		date_default_timezone_set("Asia/Shanghai");
	}
	
	//try to set encoding to config value
	//try to hook error and exception handler
	private function _initEnv() {
		mb_internal_encoding($this->encoding);
		iconv_set_encoding("internal_encoding", $this->encoding);
		error_reporting(E_ALL|E_STRICT);
		set_error_handler(array($this,'errorHandler'));
		set_exception_handler(array($this,'exceptionHandler'));
		register_shutdown_function(array($this,'shutdownHandler'));
	}
	
	//try to init logger according to current module
	//this logger support format printing of all type, including array, such as KC_LOG_TRACE("array %s", array('a' => 'b'));
    private function _initLogger() {
    	require_once LIB_ROOT . 'KcLog.php';
		kc_log_init('LOCAL_LOG', EnvConf::$log_level, array(), LOG_ROOT, 'mui.' . App::getModule());
	}
	
	private function _errorHandler() {
		restore_error_handler();
		$error = func_get_args();
		if (!($error[0] & error_reporting())) {
			KC_LOG_DEBUG('caught info, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
			set_error_handler(array($this,'errorHandler'));
			return false;
		} elseif ($error[0] === E_USER_NOTICE) {
			KC_LOG_TRACE('caught trace, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
			set_error_handler(array($this,'errorHandler'));
			return false;
		} elseif($error[0] === E_STRICT) {
		    set_error_handler(array($this,'errorHandler'));
		    return false;	
		} else {
			KC_LOG_FATAL('caught error, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
			$this->endStatus = 'error';
			return true;
		}
	}

	function errorHandler()	{
		$error = func_get_args();
		if (false === $this->_errorHandler($error[0],$error[1],$error[2],$error[3])) { 
			return;
		}
		if (true === $this->debug) {
			unset($error[4]);
			echo "<pre>\n";
			print_r($error);
			echo "\n</pre>";
		}
		$errcode = 'internal';
		if(is_null($this->errPage)){
			$this->response->setHeader("HTTP/1.1 " .
			EnvConf::$errorMap[$errcode]['http_code'] . ' ' . EnvConf::$errorMap[$errcode]['error_msg']);
			$this->response->clearOutputs();
			$this->response->clearRawData();
			$this->response->set("request_id", $this->requestId);
			$this->response->set("error_code", EnvConf::$errorMap[$errcode]['error_code']);
			$this->response->set("error_msg", EnvConf::$errorMap[$errcode]['error_msg']);
			$this->response->setError($error);
			$this->response->send();
			exit();
		}
		$arrUrl = parse_url($this->errPage);
		if(false === $arrUrl || !isset($arrUrl['scheme']) || !isset($arrUrl['host'])){
			$this->response->clearOutputs();
			$this->response->clearRawData();
			$this->response->set("request_id", $this->requestId);
			$this->response->set("error_code", EnvConf::$errorMap[$errcode]['error_code']);
			$this->response->set("error_msg", EnvConf::$errorMap[$errcode]['error_msg']);
			$this->response->setError($error);
			$this->response->setView($this->errPage);
			$this->response->send();
			exit(0);
		}
		$this->response->redirect($this->errPage);
	}

    private function _exceptionHandler($ex)	{
		restore_exception_handler();
		$errMsg = $ex->getMessage();
		$redirect = !!$ex->getCode();
		$tmp = explode(' ', $errMsg);
		$err_msg = '';
		if(count($tmp) <= 0){
			$errcode = 'internal';
		}else{
			$errcode = trim($tmp[0]);
			if(!array_key_exists($errcode, EnvConf::$errorMap)){
				$errcode = 'internal';
			}
			$err_msg = substr($errMsg,strlen($errcode));
			if($err_msg){
				$err_msg = " 详细信息:".$err_msg;
			}
		}
		if($redirect){
			if(count($tmp) <= 1){
				$redirect = false;
			}else{
				$tpl = trim($tmp[1]);
				$tplFile = ROOT . EnvConf::$moduleRoot . '/' . App::getModule() . '/views/errtpl/' . $tpl . '.tpl';
				if(!file_exists($tplFile)){
					$redirect = false;
				}
			}
		}
		$this->response->clearOutputs();
		$this->response->clearRawData();
		$errmsg = sprintf('Caught exception, errcode:%s, trace: %s', $errcode, $ex->__toString());
		KC_LOG_FATAL($errmsg);
		$this->response->set("request_id", $this->requestId);
		$this->response->set("error_code", EnvConf::$errorMap[$errcode]['error_code']);
		$this->response->set("error_msg", EnvConf::$errorMap[$errcode]['error_msg'].$err_msg);
		if(!$redirect){
			$this->response->setHeader("HTTP/1.1 " .
			EnvConf::$errorMap[$errcode]['http_code'] . ' ' . EnvConf::$errorMap[$errcode]['error_msg']);
		}else{
			$this->response->setView($tpl);
		}
		$this->endStatus = $errcode;
	}

	function exceptionHandler($ex) {
		$this->_exceptionHandler($ex);
		if (true === $this->debug) {
			echo "<pre>\n";
			print_r($ex->__toString());
			echo "\n</pre>";
		}
		$this->response->setException($ex);
		$this->response->send();
		exit;
	}

	function shutdownHandler() {
		if(!is_null($this->request->get("method"))) {
		    $basic = array(
						'ip'=>$this->request->userip.':'.$this->request->clientip,
						'uri'=>$this->request->url,            
		                'method'=>$this->request->get("method"),
						'logid'=>$this->requestId
					  );
		}else {
			$basic = array(
						'ip'=>$this->request->userip.':'.$this->request->clientip,
						'uri'=>$this->request->url, 
						'logid'=>$this->requestId
					  );
		}
		kc_log_addbasic($basic);
		kc_log_flush();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
