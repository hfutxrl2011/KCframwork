<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 Baidu.com, Inc. All Rights Reserved
 * $Id: controller.php 2201 2012-02-08 02:32:41Z wu_jing $ 
 * 
 **************************************************************************/

/**
 * @file dispatch.php
 * @author niulei(niulei@baidu.com)
 * @date 2011/08/31 10:50:11
 * @version $Revision: 2201 $ 
 * @brief 
 *  
 **/

class Controller {
	var $request = null;
	var $response = null;
	var $debug = null;
	var $encoding = null;
	var $pages = array ();
	
	function gets() {
		$keys = func_get_args ();
		$ret = array ();
		foreach ( $keys as $key ) {
			$ret [$key] = $this->get ( $key );
		}
		return $ret;
	}
	
	function get($key, $default = null) {
		$requestParam = $this->request->get ( $key, $default );
		//编码转换
		if(function_exists('mb_detect_encoding')){
			$encode = mb_detect_encoding($requestParam, array('UTF-8', 'GBK'));
			if("GBK" == $encode){
				$requestParam = mb_convert_encoding($requestParam,"UTF-8","GBK");
			}
		}
		return $requestParam;
	}
	
	function getraw($key, $default = null) {
		return $this->request->getraw ( $key, $default );
	}
	
	function set($key, $value) {
		return $this->response->set ( $key, $value );
	}
	
	function setArray($kv, $forceObj = false){
		foreach ((array)$kv as $k => $v){
			$this->response->set($k, $v, $forceObj);
		}
	}
	
	function setView($template, $data = array()) {
		return $this->response->setView ( $template, $data );
	}

	function registErrorPage($url)
	{
		//出现PHP错误时，正常情况会返回json串标识错误
		//可以在逻辑开始时，注册错误页面，使得程序在出错时跳到内部错误页面
		KcMvc::getKcMvc()->errPage = $url;
	}
}
?>
