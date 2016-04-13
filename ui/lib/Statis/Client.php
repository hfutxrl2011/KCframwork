<?php
require_once(dirname(__FILE__) . '/StatisticClient.php');

class Statis_Client{
	
	public static $reportServer = "udp://192.168.180.47:55656";
	
	public static function report($module, $interface, $success = true, $code = 0, $msg = '', $report_address = ''){
		if(empty($report_address)) $report_address = self::$reportServer;
		self::CommonStatistic($module, $interface, $success, $code, $msg, $report_address);
	}
	
	public static function startTick($module, $interface){
		// ͳ�ƿ�ʼ
		StatisticClient::tick($module, $interface);
	}
	
	public static function CommonStatistic($module, $interface, $success, $code, $msg, $report_address = ''){
		// ͳ�ƵĲ������ӿڵ����Ƿ�ɹ��������롢������־
		//$success = true; $code = 0; $msg = 'success';
		// �ϱ����
		StatisticClient::report($module, $interface, $success, $code, $msg, $report_address);
		StatisticClient::report($module, 'all_pv', $success, $code, $msg, $report_address);
	}
	
}

