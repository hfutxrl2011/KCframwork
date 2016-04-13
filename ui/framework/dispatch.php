<?php
class Dispatch {
	private $app = null;
	
	function __construct(KcMvc $app) {
		$this->app = $app;
	}
	
	function dispatch_url($url) {
		$moduleRoot = ROOT . EnvConf::$moduleRoot . "/" . App::getModule();
		$classFile = "$moduleRoot/controllers/";
		$subDir = App::getSubDir();
		if(!empty($subDir)){
			$classFile .= $subDir . '/';
		}
		$className = ucfirst(App::getController()) . 'Controller';
		$func = ucfirst(App::getAction()) . 'Action';
		KC_LOG_DEBUG("hit controller: $className, action: $func.");
		$classFile .= strtolower(substr($className, 0, -10)) . '.php';
		if(!is_readable($classFile)){
			KC_LOG_DEBUG("not_found invalid controller which is unavailable [ file: $classFile ]");
			throw new Exception("not_found invalid controller which is unavailable.");
		}
		require_once($classFile);
		if(!class_exists($className)) {
			KC_LOG_DEBUG("not_found invalid controller which is unavailable [ file: $classFile, class: $className ]");
			throw new Exception("not_found invalid controller which is unavailable.");
		}
		$obj = new $className();
		$obj->request = $this->app->request;
		$obj->response = $this->app->response;
		$obj->encoding = $this->app->encoding;
		$obj->debug = $this->app->debug;
		try {
			$this->_callmethod ( $obj, '_before' );
			if (! $this->_callmethod ( $obj, $func, array() )) {
				throw new Exception ( "not_found call method failed [ method name: $func ]" );
			}
		} catch ( Exception $ex ) {
			$this->_callmethod ( $obj, '_after' );
			throw $ex;
		}
		$this->_callmethod ( $obj, '_after' );
	}
	
	private function _callmethod($controller, $method, $args = array()) {
		if (is_callable ( array ($controller, $method ) )) {
			$reflection = new ReflectionMethod ( $controller, $method );
			$argnum = $reflection->getNumberOfParameters ();
			if ($argnum > count ( $args ) + 1 ) {
				throw new Exception ( "not_found call method failed [ method name: $method ]." );
			}
			$reflection->invokeArgs ( $controller, $args );
			return true;
		}
		return false;
	}

}

?>
