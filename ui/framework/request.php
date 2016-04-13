<?php
require_once dirname ( __FILE__ ) . '/kcmvc.php';

class Request {
	var $inputs = array ();
	var $cookies = array ();
	var $headers = array ();
	var $method = 'GET';
	var $host = '';
	var $userip = '127.0.0.1';
	var $clientip = '127.0.0.1';
	var $url = '';
	var $uri = '';
	var $serverEnvs = array ();
	var $now = 0;
	var $requestId = 0;
	var $of = "json";
	var $is_https = false;
	var $product = '';
	var $action = '';
	var $acl = array();
	private $app = null;
	
	function __construct(KcMvc $app) {
		$this->app = $app;
		$this->now = time ();
	}
	
	//获取原始的参数数据，不经过各种过滤处理的
	function getraw($key, $default = null) {
		if (isset ( $_GET [$key] )) {
			return $_GET [$key];
		}
		if (isset ( $_POST [$key] )) {
			return $_POST [$key];
		}
		return $default;
	}
	
	function get($key, $default = null) {
		if (is_string ( $key )) {
			if (isset ( $this->inputs [$key] )) {
				if(is_string($this->inputs[$key])){
					return strip_tags($this->inputs[$key]);
				}else{
					return $this->inputs[$key];
				}
			}
			return $default;
		} else {
			$ret = array ();
			foreach ( $key as $k ) {
				if (isset ( $this->inputs [$k] )) {
					$ret [] = strip_tags($this->inputs [$k]);
				} else {
					$ret [] = $default;
				}
			}
			return $ret;
		}
	}
	function set($key, $value) {
		$this->inputs [$key] = $value;
	}
	
	function getCookie($key, $default = null) {
		if (isset ( $this->cookies [$key] )) {
			return $this->cookies [$key];
		}
		return $default;
	}

	function getHeader($key, $default = null) {
		$name = 'HTTP_' . strtoupper ( $key );
		if (isset ( $this->serverEnvs [$name] )) {
			return $this->serverEnvs [$name];
		}
		return $default;
	}
	
	function getLogId() {
		return $this->app->requestId;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
