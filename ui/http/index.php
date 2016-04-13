<?php
define ( 'ROOT', dirname(dirname(__FILE__)) . '/');
define ( 'FRAMEWORK_ROOT', ROOT . 'framework/' );
define ( 'CONF_ROOT', ROOT . 'conf/' );
define ( 'LIB_ROOT', ROOT . 'lib/' );
define ( 'HTTP_ROOT', ROOT . 'http/' );
define ( 'TMP_ROOT', ROOT . 'tmp/' );
define ( 'LOG_ROOT', ROOT . 'log/' );
define ( 'INC_ROOT', ROOT . 'inc/' );
require_once FRAMEWORK_ROOT . 'kcmvc.php';

if( isset($_GET['xhprof_enable']) && 1 == $_GET['xhprof_enable'] ){
	KcMvc::$xhprof_enable = true;
}

if(KcMvc::$xhprof_enable){
	//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
}

$kcmvc = KcMvc::getKcMvc();
$kcmvc->run();

if(KcMvc::$xhprof_enable){
	$xhprofData = xhprof_disable();
	include_once LIB_ROOT . "xhprof_lib/utils/xhprof_lib.php";
	include_once LIB_ROOT . "xhprof_lib/utils/xhprof_runs.php";
	$xhprofRuns = new XHProfRuns_Default();
	$runId = $xhprofRuns->save_run($xhprofData, "xhprof");
	$url = "/xhprof_html/index.php?run=$runId&source=xhprof";
    echo '<a href="'.$url.'">'.$url.'</a>';
}

?>
