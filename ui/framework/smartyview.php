<?php
require_once LIB_ROOT . 'Smarty/Smarty.class.php';

class SmartyView {
	private $smarty = null;
	private $viewData = array ();
	private $app = null;
	private $gpc = false;
	
	function init($app) {
		$this->app = $app;
		$this->gpc = get_magic_quotes_gpc ();
		$this->smarty = new Smarty ( );
		$moduleRoot = ROOT . EnvConf::$moduleRoot . '/' . App::getModule() ;
		$this->smarty->template_dir = $moduleRoot . '/views';
		$this->smarty->force_compile = true;
		$this->smarty->compile_dir = ROOT . '/templates_c';
		$this->smarty->cache_dir = ROOT . '/cache';
		$this->smarty->config_dir = isset($this->smarty->template_dir[0])?$this->smarty->template_dir[0]:$this->smarty->template_dir . '/conf';
		$this->smarty->caching = false;
		$this->smarty->left_delimiter = '<%';
		$this->smarty->right_delimiter = '%>';
		//$this->smarty->debugging = $app ? $app->debug : false; //取消smarty的debug
	}
	
	function build($template) {
		$this->smarty->assign ( 'v', $this->viewData );
		return $this->smarty->fetch ( $template . '.tpl' );
	}
	
	function display($template) {
		$this->smarty->assign ( 'v', $this->viewData );
		$template = trim($template);
		if(false === strrpos($template, '.tpl')){
			$template .= '.tpl';
		}
		$this->smarty->display ( $template );
	}
	
	function setArray(Array $arr) {
		if ($this->viewData) {
			$this->viewData = array_merge ( $this->viewData, $arr );
		} else {
			$this->viewData = $arr;
		}
	}
}
?>
