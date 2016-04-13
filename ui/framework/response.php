<?php
require_once dirname ( __FILE__ ) . '/kcmvc.php';

class Response {
	private $app = null;
	
	var $template = null;
	var $exception = null;
	var $error = null;
	
	var $headers = array ();
	var $outputs = array ();
	var $cookies = array ();
	var $rawData = null;
	var $formatResult = array();
	var $appendResult = array();

	function __construct(KcMvc $app) {
		$this->app = $app;
	}
	
	public function setFormatResult($keyMap){
		$this->formatResult = $keyMap;
	}
	
	public function setAppendResult($arr){
		$this->appendResult = $arr;
	}

	function setHeader($header) {
		$this->headers [] = $header;
	}
	
	function setCookie($key, $value, $expires = null, $path = '/', $domain = null, $secure = false, $httponly = false) {
		$this->cookies [] = array ($key, $value, $expires, $path, $domain, $secure, $httponly );
	}
	
	function delCookie($key, $value = '', $expires = 1, $path = '/', $domain = null, $secure = false, $httponly = false) {
		$this->cookies [] = array ($key, $value, $expires, $path, $domain, $secure, $httponly );
	}
	
	function clearOutputs()
	{
		$this->outputs = array();
	}

	function clearRawData()
	{
		$this->rawData = null;
	}
	
	function set($key, $value = null, $forceObj = false) {
		$this->outputs [$key] = $value;
		$this->_forceObj = $forceObj;
	}
	
	function setRaw($data) {
		$this->rawData = $data;
	}
	
	function setException($ex) {
		$this->exception = $ex;
	}
	
	function setError($err) {
		$this->error = $err;
	}
	
	function setView($path, $arr = array()) {
		if ($arr) {
			$this->outputs = array_merge ( $this->outputs, $arr );
		}
		$this->template = $path;
		$this->app->request->of = 'html';
	}
	
	function redirect($url, $status = 'ok') {
		$this->setHeader ( 'Location: ' . $url );
		$this->sendHeaders ();
		$this->app->endStatus = $status;
		exit ();
	}

	function buildView($template, $userData, $output = false) {
		require_once dirname(__FILE__) . '/smartyview.php';;
		$view = new SmartyView ( );
		$view->init ( $this->app );
		$view->setArray ( $userData );
		if (! $output) {
			$result = $view->build ( $template );
		} else {
			$result = $view->display ( $template );
		}
		return $result;
	}
	
	function end() {
		$this->app->filterExecutor->executeFilter ( 'output' );
		$this->app->endStatus = 'ok';
		exit ();
	}
	
	private function _buildContentType($of) {
		switch ($of) {
			case 'json' :
				$this->headers = array_merge ( array ('Content-Type: application/json; charset=' . EnvConf::$encoding ), $this->headers );
				break;
			case 'html' :
				$this->headers = array_merge ( array ('Content-Type: text/html; charset=' .	EnvConf::$encoding ), $this->headers );
				break;
			default :
				$this->headers = array_merge ( array ('Content-Type: text/plain; charset=' . EnvConf::$encoding ), $this->headers );
		}
	}
	
	private function _getResult() {
		if ($this->exception || $this->error) {
			$result = $this->outputs;
		} else {
			if(!empty($this->outputs)) {
			    $result = array(
				                   "request_id" => $this->app->requestId,
								   "error_code" => 0,
								   "error_msg" => 'SUCC',
			                       "data" => $this->outputs
					            );
			}else {
				$result = array(
					"request_id" =>	$this->app->requestId, 
					"error_code" => 0,
					"error_msg" => 'SUCC',
					"data" => array(),
				);
			}
		}
		return $result;
	}
	
	public function appendResult(&$result,$arr=array()){
		if(!empty($arr)){
			$result = array_merge($result,$arr);
		}
	}
	
	/***
	 *  格式化输出
	 *   
	 *
	*/
    private function formatResult(&$result){
		if(!empty($this->formatResult)){
			foreach($this->formatResult as $key => $value){
				if(isset($result[$key])){
					$result[$value] = $result[$key];
					unset($result[$key]);
				}
			}
		}
		if(!empty($this->appendResult)){
			foreach($this->appendResult as $appendkey => $appendvalue){
				if(!isset($result[$appendkey])){
					$result[$appendkey] = $appendvalue;
				}
			}
		}
		//兼容客户端同学版本
		if(isset($result['status']) && 0===$result['status']){
			$result['status'] = 200;
		}
	}
	
	private function _formatResponse() {
		$result = $this->_getResult ();
		$this->formatResult($result);
		$of = $this->app->request->of;
		$this->_buildContentType ( $of );
		if ($this->rawData) {
			return $this->rawData;
		} elseif ($this->template) {
			if(!isset($this->outputs['request_id'])){
				$this->outputs['request_id'] = $this->app->requestId;
			}
			if(!isset($this->outputs['error_code'])){
				$this->outputs['error_code'] = 0;
			}
			if(!isset($this->outputs['error_msg'])){
				$this->outputs['error_msg'] = 'SUCC';
			}
			
			return $this->buildView ( $this->template, $this->outputs, false );
		} else {
			if ($of == 'json') {
				if(isset($this->_forceObj) && $this->_forceObj){
					return json_encode ( $result, JSON_FORCE_OBJECT );
				}
				return json_encode($result);
			} else {
				return print_r ( $result, true );
			}
		}
	}
	
	function sendHeaders() {
		if ($this->cookies) {
			foreach ( $this->cookies as $cookie ) {
				call_user_func_array ( 'setcookie', $cookie );
			}
		}
		$headers = $this->headers;
		if ($headers) {
			foreach ( $headers as $header ) {
				header ( $header );
			}
		}
	}
	
	function send() {
		$data = $this->_formatResponse ();
		$this->sendHeaders ();
		//获取缓冲数据
		$ob = ini_get ( 'output_buffering' );
		if ($ob && strtolower ( $ob ) !== 'off') {
			$str = ob_get_clean ();
			//忽略前后空白
			$data = trim ( $str ) . $data;
		}
		if ($data) {
			if(EnvConf::$debug){
				KC_LOG_DEBUG("[RETURNED DATA]\n\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n" . 
						$data . "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
			}
			
			//统计模块
			if(true){
				$success = true;
				$code = 0;
				$msg = "succ";
				$module =  App::getModule();
				$interface =  App::getController().'_'.App::getAction();
				$dataArr = json_decode($data,true);
				if(isset($dataArr['error_code']) && $dataArr['error_code'] == 0 ){
					$msg = $dataArr['error_msg'];
				}else{
					$success = false;
					$code = isset($dataArr['error_code'])?$dataArr['error_code']:-1;
					$msg = (isset($dataArr['error_msg'])?$dataArr['error_msg']:"failed")." ,requestid:".$this->app->requestId;
					$msg .= ', ip:'. App::getClientIp();
				}
				$do_not_report_code = array('100801');
				if(!in_array($code,$do_not_report_code)){
					Statis_Client::report($module, $interface, $success, $code, $msg);
				}
			}
			
			//support for return format -- jsonp
			if('json' === $this->app->request->of){
				$cb = $this->app->request->get('callback', '');
				$cb = trim($cb);
				if(!empty($cb)){
					$data = $cb . "($data)";
				}
			}
			
			echo $data;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
