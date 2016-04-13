<?php
require_once(dirname(__FILE__) . '/StatisticClient.php');

class Statis_Client{
	
	public static $reportServer = "udp://192.168.180.47:55656";
	
	public static function report($module, $interface, $success = true, $code = 0, $msg = '', $report_address = ''){
		if(empty($report_address)) $report_address = self::$reportServer;
		self::CommonStatistic($module, $interface, $success, $code, $msg, $report_address);
	}
	
	public static function startTick($module, $interface){
		// 统计开始
		StatisticClient::tick($module, $interface);
	}
	
	public static function CommonStatistic($module, $interface, $success, $code, $msg, $report_address = ''){
		// 统计的产生，接口调用是否成功、错误码、错误日志
		//$success = true; $code = 0; $msg = 'success';
		// 上报结果
		StatisticClient::report($module, $interface, $success, $code, $msg, $report_address);
		StatisticClient::report($module, 'all_pv', $success, $code, $msg, $report_address);
	}
	
}

