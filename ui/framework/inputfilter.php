<?php
class InputFilter {
	private function _processLogin($app) {
	}

	private function _checkStrict($app) {
		
	}

	private function _parseParams($app) {
		$app->request->method = App::getAction();
		$app->request->requestId = $app->requestId;
		$arr = array_merge ( $_GET, $_POST );
		$app->request->inputs = $arr;
		if (get_magic_quotes_gpc ()) {
			$_COOKIE = array_map ( 'stripslashes', $_COOKIE );
		}
		$app->request->cookies = $_COOKIE;
		$app->request->userip = App::getClientIp();
		if(isset($_SERVER ['REMOTE_ADDR'])){
			$app->request->clientip = $_SERVER ['REMOTE_ADDR'];
		}else{
			$app->request->clientip = '127.0.0.1';
		}
		$app->request->url = App::getPath();
		$app->request->url = strtolower($app->request->url);
		$app->request_uri = App::getRequestURI();
		$app->request->host = strtolower (App::getHost());
		$app->request->serverEnvs = $_SERVER;
	}

	function process(KcMvc $app) {
		$this->_parseParams ( $app );
		$this->_checkStrict ($app);
		$this->_processLogin ($app);
	}
}

?>
