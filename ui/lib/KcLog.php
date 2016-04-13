<?php

function kc_posix_getpid() {
	if ( function_exists('posix_getpid'))
		return posix_getpid();
	return 'unkown-posix-id';
}

final class __mc_log__ {
	const LOG_INFO = 0;
	const LOG_FATAL = 1;
	const LOG_WARNING = 2;
	const LOG_MONITOR = 3;
	const LOG_NOTICE = 4;
	const LOG_TRACE = 8;
	const LOG_DEBUG = 16;
	const PAGE_SIZE = 0;
	const LOG_SPACE = "";
	const MONTIR_STR = ' ---LOG_MONITOR---';
	const LOCAL_LOG = 'LOCAL_LOG';
	const KC_LOG = 'KC_LOG';

	private $request_log_level = self::LOG_DEBUG;
	private $kc_log_handler = NULL;
	private $VALID_LOG_TYPE = array (self::LOCAL_LOG, self::KC_LOG );
	private $LOG_TYPE = self::KC_LOG;

	static $LOG_NAME = array (
			self::LOG_INFO => 'INFO',
			self::LOG_FATAL => 'FATAL', 
			self::LOG_WARNING => 'WARNING', 
			self::LOG_MONITOR => 'MONITOR', 
			self::LOG_NOTICE => 'NOTICE', 
			self::LOG_TRACE => 'TRACE', 
			self::LOG_DEBUG => 'DEBUG' 
			);
	static $BASIC_FIELD = array (
			'logid', 
			'reqip', 
			'uid', 
			'uname', 
			'baiduid', 
			'method', 
			'uri' 
			);

	private $log_name = '';
	private $log_path = '';
	private $wflog_path = '';
	private $log_str = '';
	private $wflog_str = '';
	private $basic_info = '';
	private $notice_str = '';
	private $log_level = self::LOG_DEBUG;
	private $arr_basic = NULL;
	private $force_flush = false;
	private $init_pid = 0;

	function __construct($logtype) {
		if (! in_array ( $logtype, $this->VALID_LOG_TYPE )) {
			throw new Exception ( 'invalid log type :' . print_r ( $this->VALID_LOG_TYPE, true ) );
		}
		$this->LOG_TYPE = $logtype;
	}

	function __destruct() {
		$this->check_flush_log ( true );
		if ($this->init_pid == kc_posix_getpid ()) {
			$this->check_flush_log ( true );
		}
	}

	function init($dir, $name, $level, $arr_basic_info, $flush = false) {
		$this->force_flush = $flush;
		if (empty ( $dir ) || empty ( $name )) {
			return false;
		}
		if ('/' != $dir {0}) {
			$dir = realpath ( $dir );
		}
		$dir = rtrim ( $dir, "." );
		$name = rtrim ( $name, "/" );
		$this->log_path = $dir . "/" . $name . ".log";
		$this->wflog_path = $dir . "/" . $name . ".log.wf";
		$this->log_name = $name;
		$this->log_level = $level;
		/* set basic info */
		$this->arr_basic = $arr_basic_info;
		/* ���basic info���ַ� */
		$this->gen_basicinfo ();
		/* ��¼��ʹ����̵�id */
		$this->init_pid = kc_posix_getpid ();
		return true;
	}

	private function gen_log_part($str) {
		return "[ " . self::LOG_SPACE . $str . " " . self::LOG_SPACE . "]";
	}

	private function gen_basicinfo() {
		$this->basic_info = '';
		foreach ( self::$BASIC_FIELD as $key ) {
			if (! empty ( $this->arr_basic [$key] )) {
				$this->basic_info .= $this->gen_log_part ( "$key:" . $this->arr_basic [$key] ) . " ";
			}
		}
	}

	public function check_flush_log($force_flush) {
		if (strlen ( $this->log_str ) > self::PAGE_SIZE || strlen ( $this->wflog_str ) > self::PAGE_SIZE) {
			$force_flush = true;
		}

		if ($force_flush) {
			/* first write warning log */
			if (! empty ( $this->wflog_str )) {
				$this->write_file ( $this->wflog_path, $this->wflog_str );
			}
			/* then common log */
			if (! empty ( $this->log_str )) {
				$this->write_file ( $this->log_path, $this->log_str );
			}
			/* clear the printed log*/
			$this->wflog_str = '';
			$this->log_str = '';
		} /* force_flush */
	}

	private function write_file($path, $str) {
		if ($this->LOG_TYPE === self::LOCAL_LOG) {
			$fd = @fopen ( $path, "a+" );
			if (is_resource ( $fd )) {
				fputs ( $fd, $str );
				fclose ( $fd );
			} else {
				trigger_error ( "cant open log path:$path", E_USER_WARNING );
			}
		} else if ($this->LOG_TYPE === self::KC_LOG) {
			switch ($this->request_log_level) {
				case self::LOG_FATAL :
					{
						$this->kc_log_handler->logFatal ( $str );
						break;
					}
				case self::LOG_DEBUG :
					{
						$this->kc_log_handler->logDebug ( $str );
						break;
					}
				case self::LOG_TRACE :
					{
						$this->kc_log_handler->logTrace ( $str );
						break;
					}
				case self::LOG_WARNING :
					{
						$this->kc_log_handler->logWarning ( $str );
						break;
					}
				case self::LOG_NOTICE :
					{
						$this->kc_log_handler->logNotice ( $str );
						break;
					}
				default :
					{
						trigger_error ( "unknown log level", E_USER_WARNING );
					}

			}
		} else {
			trigger_error ( "invalid log type", E_USER_WARNING );
		}
	}

	public function add_basicinfo($arr_basic_info) {
		$this->arr_basic = array_merge ( $this->arr_basic, $arr_basic_info );
		$this->gen_basicinfo ();
	}

	public function push_notice($format, $arr_data) {
		$this->notice_str .= " " . $this->gen_log_part ( vsprintf ( $format, $arr_data ) );
	}

	public function clear_notice() {
		$this->notice_str = '';
	}

	public function write_log($type, $format, $line_no, $arr_data) {
		if ($this->log_level < $type)
			return;		
		$this->request_log_level = $type;

		if ($this->LOG_TYPE === self::LOCAL_LOG) {
			$str = sprintf ( "%s: %s: %s * %d %s", self::$LOG_NAME [$type], date ( "m-d H:i:s" ), $this->log_name, kc_posix_getpid (), $line_no );
		} else if ($this->LOG_TYPE === self::KC_LOG) {
			$str = sprintf ( " %s: %s * %d %s", date ( "m-d H:i:s" ), $this->log_name, kc_posix_getpid (), $line_no );
		}

		/* add monitor tag?	*/
		if ($type == self::LOG_MONITOR || $type == self::LOG_FATAL) {
			$str .= self::MONTIR_STR;
		}
		/* add basic log */
		$str .= " " . $this->basic_info;

		/* add detail log */
		if (empty ( $arr_data )) {
			$str .= $format;
		} else {
			$str .= " " . vsprintf ( $format, $arr_data );
		}

		switch ($type) {
			case self::LOG_MONITOR :
			case self::LOG_FATAL :
			case self::LOG_WARNING :
			case self::LOG_FATAL :
				$this->wflog_str .= $str . "\n";
				break;
			case self::LOG_INFO  :
			case self::LOG_DEBUG :
			case self::LOG_TRACE :
				$this->log_str .= $str . "\n";
				break;
			case self::LOG_NOTICE :
				$this->log_str .= $str . $this->notice_str . "\n";
				$this->clear_notice ();
				break;
			default :
				break;
		}
		$this->check_flush_log ( $this->force_flush );
	}
}

function _json_encode($arrLog)
{
	if(!is_array($arrLog))
	{
		return strval($arrLog);
	}
	$strLog = '{';
	foreach ($arrLog as $k => $v)
	{
		$strLog .= '"' . $k . '":';
		if(is_array($v))
		{
			if(empty($v)){
				$strLog .= '[],'	;
				continue;
			}else{
				$strLog .= _json_encode($v) . ',';
				continue;
			}
		}
		if(is_string($v))
		{
			$strLog .= '"' . $v . '",';
		}
		else
		{
			$strLog .= strval($v) . ',';
		}
	}
	strlen($strLog) > 1 && $strLog = substr($strLog, 0, -1);
	$strLog .= '}';
	return $strLog;
}

function pre_process(&$arrArgs)
{
	$num = count($arrArgs);
	if(count($arrArgs) > 1){
		for($idx = 1; $idx < $num; $idx++)
		{
			if(is_array($arrArgs[$idx])){
				$arrArgs[$idx] = _json_encode($arrArgs[$idx]);
			}
		}
	}
}

$__log__ = null;
function __kc_ub_log($type, $arr) {
	pre_process($arr);
	global $__log__;
	$format = $arr[0];
	array_shift($arr);
	$pid = kc_posix_getpid();
	$bt = debug_backtrace ();
	if (isset ( $bt [1] ) && isset ( $bt [1] ['file'] )) {
		$c = $bt [1];
	} else if (isset ( $bt [2] ) && isset ( $bt [2] ['file'] )) { //Ϊ�˼��ݻص�����ʹ��log
		$c = $bt [2];
	} else if (isset ( $bt [0] ) && isset ( $bt [0] ['file'] )) {
		$c = $bt [0];
	} else {
		$c = array ('file' => 'faint', 'line' => 'faint' );
	}
	$line_no = '[' . $c ['file'] . ':' . $c ['line'] . '] ';
	if (!empty($__log__[$pid])) {
		$log = $__log__[$pid];
		$log->write_log($type, $format, $line_no, $arr);
	} else {
		$s =  __mc_log__::$LOG_NAME[$type] . ' ' . vsprintf($format, $arr) . "\n";
		echo $s;
	}
}

/**
 * ub_log_init Log��ʼ�� 
 * 
 * @param string $logtype  ��־���ͣ�valid value is 'LOCAL_LOG','KC_LOG' 
 * @param string $dir      Ŀ¼��   if logtype = KC_LOG ,this param not used
 * @param string $file     ��־��    if logtype = KC_LOG ,this param not used
 * @param interger $level  ��־���� 
 * @param array $info      ��־����Ϣ,���Բο�__mc_log__::$BASIC_FIELD  
 * @param bool  $flush     �Ƿ���־ֱ��flush��Ӳ��,Ĭ�ϻ���4K�Ļ���
 * @return boolean          true�ɹ�;falseʧ��
 */
function kc_log_init($logtype ,$level, $info, $dir = null, $file = null,$flush=false) {
	global $__log__;
	$pid = kc_posix_getpid();
	if (!empty($__log__[$pid]) ) {
		unset($__log__[$pid]);
	}
	$__log__[kc_posix_getpid()] = new __mc_log__($logtype);
	$log = $__log__[kc_posix_getpid()];
	if ($log->init($dir, $file, $level, $info, $flush)) {
		return true;
	} else {
		unset($__log__[$pid]);
		return false;
	}
}

function KC_LOG_INFO() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_INFO, $arg );
}

function KC_LOG_DEBUG() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_DEBUG, $arg );
}

function KC_LOG_TRACE() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_TRACE, $arg );
}

function KC_LOG_NOTICE() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_NOTICE, $arg );
}

function KC_LOG_MONITOR() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_MONITOR, $arg );
}

function KC_LOG_WARNING() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_WARNING, $arg );
}

function KC_LOG_FATAL() {
	$arg = func_get_args();
	__kc_ub_log(__mc_log__::LOG_FATAL, $arg );
}

function kc_log_pushnotice() {
	global $__log__;
	$arr = func_get_args();
	$pid = kc_posix_getpid();
	if (!empty($__log__[$pid])) {
		$log = $__log__[$pid];
		$format = $arr[0];
		/* shift $type and $format, arr_data left */
		array_shift($arr);
		$log->push_notice($format, $arr);
	} else {
		/* nothing to do */
	}
}

function kc_log_clearnotice() {
	global $__log__;
	$pid = kc_posix_getpid();
	if (!empty($__log__[$pid])) {
		$log = $__log__[$pid];
		$log->clear_notice();
	} else {
		/* nothing to do */
	}
}

function kc_log_addbasic($arr_basic) {
	global $__log__;
	$pid = kc_posix_getpid();
	if (!empty($__log__[$pid])) {
		$log = $__log__[$pid];
		$log->add_basicinfo($arr_basic);
	} else {
		/* nothing to do */
	}
}

function kc_log_flush() {
	global $__log__;
	$pid = kc_posix_getpid();
	if (! empty ( $__log__ [$pid] )) {
		$log = $__log__ [$pid];
		$log->check_flush_log ( true );
	} else {
		/* nothing to do */
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
