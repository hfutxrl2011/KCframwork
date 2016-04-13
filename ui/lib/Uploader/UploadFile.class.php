<?php

class Uploader_UploadFile
{
	protected $_url = null;
	protected $_errorInfo = array(
			'retcode' => 0,
			'retmsg' => 'SUCC',
			);	
	protected $_resDetail = array(
			'fname' => '',
			'fsize' => '',
			'url' => 0,
			'retcode' => 0,
			'retmsg' => 0,
			);

	public function __construct($url)
	{
		if(empty($url)){
			throw new Exception("invalid param, url must be specified.");
		}
		$this->_url = $url;
	}

	public function uploadImg($filePath, $postParam = array(), $getParam = array(), $fileVar = 'file_field')
	{
		$getParam['isimg'] = '1';
		return $this->uploadFile($filePath, $postParam, $getParam, $fileVar);
	}

	public function uploadFile($filePath, $postParam = array(), $getParam = array(), $fileVar = 'file_field')
	{
		$this->_setError(0);
		$this->_setResDetail();
		if(!is_file($filePath)){
			$this->_setError(100, "file is not readable [ file path: $filePath ]");
			return false;	
		}	
		$url = $this->_url;
		if(!empty($getParam)){
			$url = $this->_url . '?' . http_build_query($getParam);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_POST, true);
		$post = $postParam;
		$post[$fileVar] = '@' . $filePath;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post );	
		$response = curl_exec($ch);
		if(false === $response){
			$this->_setError(101, curl_error($ch));
			$this->_setResDetail($filePath);
			return false;
		}
		$ret = json_decode($response, true);
		if(!is_array($ret)){
			$this->_setError(102, "parse returned data failed [ $response ]");
			$this->_setResDetail($filePath);
		}else{
			var_dump($ret);
			$this->_setResDetail($filePath, $ret['fsize'], $ret['url'], $ret['retcode'], $ret['retmsg']);
		}
		return $ret["url"];
	}

	protected function _setResDetail($fname = '', $fsize = 0, $url = '', $retCode = 0, $retmsg = '')
	{
		$this->_resDetail = array(
				'fname' => $fname,
				'fsize' => $fsize,
				'url' => $url,
				'retcode' => $retCode,
				'regmsg' => $retmsg,
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
		$str .= "retcode: " . $this->_errorInfo['retcode'] . ', ';
		$str .= "retmsg: " . $this->_errorInfo['retmsg'];
		return $str;
	}

	public function getErrorCode()
	{
		return $this->_errorInfo['retcode'];
	}

	public function getErrorInfo()
	{
		return $this->_errorInfo['retmsg'];
	}

	public function getError()
	{
		return $this->_errorInfo;
	}

	protected function _setError($errorCode, $errorInfo = null)
	{
		static $errMap = array(
				'0' => 'SUCC',
				);
		$this->_errorInfo['retcode'] = $errorCode;
		if(is_null($errorInfo)){
			if(isset($errMap[$errorCode])){
				$errorInfo = $errMap[$errorCode];
			}else{
				$errorInfo = 'unknown error';
			}
		}
		$this->_errorInfo['retmsg'] = $errorInfo;
	}
}

?>
