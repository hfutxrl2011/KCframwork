<?php
/**
* @file IDGen.class.php
* @Brief IDGen client PHP SDK
* @author wu_jing@baidu.com
* @version 1.0.0
* @date 2016-03-20
*/
require_once __DIR__ . '/pb_proto_idgen_request.php';
require_once __DIR__ . '/pb_proto_idgen_response.php';

class IDGen_IDGen
{
	protected $_provider = null;
	protected $_version = 1;
	protected $_magincNum = 0;
	protected $_reserved = 0;
	protected $_arrServer = array();
	protected $_socket = null;
	protected $_logid = 0;
	protected $_errorInfo = array(
			'error_code' => 0,
			'error_info' => 'SUCC',
			);	
	protected $_resDetail = array(
			'method' => '',
			'provider' => '',
			'version' => 0,
			'status' => 0,
			'maginc_num' => 0,
			'reserved' => 0,
			'bodylen' => 0,
			'logid' => 0,
			'id' => 0,
			);

	public function __construct($provider, $magicNum, $arrServer, $requestId = 0, $version = 1, $reserved = 0)
	{
		$this->_provider = $provider;
		$this->_version = $version;
		$this->_magicNum = $magicNum;
		$this->_reserved = $reserved;
		$this->_arrServer = $arrServer;
		$this->_logid = $requestId;
		if($version >= 256){
			throw new Exception("invalid version, which must be smaller than 256 [ version: $version ]");
		}
		if(empty($arrServer)){
			throw new Exception("invalid param, no server was specified");
		}
	}

	protected function _prepareRequest($idType, $method = 'get_id')
	{
		$tmp = new IdgenReqT();
		$tmp->setType($idType);
		$body = $tmp->serializeToString();
		$bodylen = strlen($body);
		if(empty($this->_logid)){
			$this->_logid = hexdec(uniqid());
		}
		$head = pack('a32a32CCx2L5', $method, $this->_provider, $this->_version, 0, 
				$this->_magicNum, $this->_reserved, $bodylen, (0xFFFFFFFF & $this->_logid), ($this->_logid >> 32));
		return array('head' => $head, 'body' => $body);
	}	

	protected function _sendRequest($req)
	{
		$total = $req['head'] . $req['body'];
		$sLen = @socket_write($this->_socket, $total, strlen($total));
		if($sLen != strlen($total)){
			$this->_setError(102);
			socket_close($this->_socket);
			$this->_socket = null;
			return false;
		}
		return true;	
	}

	protected function _setResDetail($method = '', $provider = '', 
			$version = 0, $status = 0, $magicNum = 0, $reserved = 0, $bodylen = 0, $logid = 0)
	{
		$this->_resDetail = array(
				'method' => $method,
				'provider' => $provider,
				'version' => $version,
				'status' => $status,
				'maginc_num' => $magicNum,
				'reserved' => $reserved,
				'bodylen' => $bodylen,
				'logid' => $logid,
				);
	}
	
	public function  getResDetail()
	{
		return array_merge($this->_resDetail, $this->_errorInfo);
	}

	public function  getResDetailAsString()
	{
		$str = '';
		foreach ($this->_resDetail as $key => $value){
			$str .= "$key: $value, ";
		}
		$str .= "error_code: " . $this->_errorInfo['error_code'] . ', ';
		$str .= "error_info: " . $this->_errorInfo['error_info'];
		return $str;
	}

	protected function _readResponse($req)
	{
		$headString = @socket_read($this->_socket, strlen($req['head']));
		if(strlen($headString) != strlen($req['head'])){
			$this->_setError(103);
			socket_close($this->_socket);
			$this->_socket = null;
			return false;
		}
		$ret = unpack('a32method/a32provider/Cversion/Cstatus/x2null/LmagicNum/Lreserved/Lbodylen/L2logid', $headString);
		extract($ret);
		$method = substr($method, 0, strpos($method, 0x00));
		$provider = substr($provider, 0, strpos($provider, 0x00));
		$logid = ($logid1 | ($logid2 << 32));
		if(0 != $status){
			$this->_setError($status);
			socket_close($this->_socket);
			$this->_socket = null;
			return false;
		}
		$this->_setResDetail($method, $provider, $version, $status, $magicNum, $reserved, $bodylen, $logid);
		$bodyString = @socket_read($this->_socket, $bodylen);
		if(strlen($bodyString) != $bodylen){
			$this->_setError(104);
			socket_close($this->_socket);
			$this->_socket = null;
			return false;
		}
		return $bodyString;
	}

	public function getID($idType)
	{
		$this->_setError(0);
		$this->_setResDetail();
		$req = $this->_prepareRequest($idType, 'get_id');
		$cret = $this->_getConnection(false);
		if(false === $cret){
			return false;
		}
		if(false === $this->_sendRequest($req)){
			return false;
		}
		if(false === ($bodyString = $this->_readResponse($req))){
			return false;
		}
		$res = new IdgenResT();	
		try{
			$res->parseFromString($bodyString);
			$this->_setError($res->getErrorCode(), $res->getErrorInfo());
			if(0 == $res->getErrorCode()){
				$this->_resDetail['id'] = $res->getId();
				return $this->_resDetail['id'];
			}
			return false;
		}catch(Exception $ex){
			$this->_setError(104);
			socket_close($this->_socket);
			$this->_socket = null;
			return false;
		}		
	}

	public function getErrorCode()
	{
		return $this->_errorInfo['error_code'];
	}

	public function getErrorInfo()
	{
		return $this->_errorInfo['error_info'];
	}

	public function getError()
	{
		return $this->_errorInfo;
	}

	protected function _setError($errorCode, $errorInfo = null)
	{
		static $errMap = array(
				'0' => 'SUCC',
				'1' => 'method does not exists',
				'2' => 'magic number error',
				'3' => 'request package too big',
				'4' => 'response package too big',
				'5' => 'pack returned package fail',
				'100' => 'create socket failed',
				'101' => 'try to connect to server failed',
				'102' => 'send to request failed',
				'103' => 'recv from server failed',
				'104' => 'parse returned data failed',
				);
		$this->_errorInfo['error_code'] = $errorCode;
		if(is_null($errorInfo)){
			if(isset($errMap[$errorCode])){
				$errorInfo = $errMap[$errorCode];
			}else{
				$errorInfo = 'unknown error';
			}
		}
		$this->_errorInfo['error_info'] = $errorInfo;
	}

	protected function _getConnection($reCreate = false)
	{
		if($this->_socket && false === $reCreate){
			return $this->_socket;
		}
		if($this->_socket){
			socket_close($this->_socket);
			$this->_socket = null;
		}
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(false === $this->_socket){
			$this->_setError(100);
		}
		$svrNum = count($this->_arrServer);
		$startIdx = $idx = rand(0, $svrNum - 1);
		$cret = false;
		while(true){
			$svr = $this->_arrServer[$idx];
			$cret = @socket_connect($this->_socket, $svr['host'], $svr['port']);
			if(true === $cret){
				break;
			}
			$idx = ($idx + 1) % $svrNum;
			if($idx === $startIdx){
				break;
			}
		}
		if(false === $cret){
			socket_close($this->_socket);
			$this->_socket = null;
			$this->_setError(101);
			return false;
		}
		return $this->_socket;
	}
}

?>
