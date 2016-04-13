<?php
class ApiModule
{
	//add self-define error map to EnvConf
	protected static function _addCustomErrorMap()
	{
		$addMap = array(
			'database' => array('http_code' => 200, 'error_code' => '200800', 'error_msg' => 'database error occured'),
			'duplicate' => array('http_code' => 200, 'error_code' => '200801', 'error_msg' => 'duplicated record was found in database'),
			'cache' => array('http_code' => 200, 'error_code' => '200802', 'error_msg' => 'cache error occured'),
			'submit' => array('http_code' => 200, 'error_code' => '200803', 'error_msg' => 'method must use POST'),
			'formhash' => array('http_code' => 200, 'error_code' => '200804', 'error_msg' => 'formhash check failed'),
			'dupreg' => array('http_code' => 200, 'error_code' => '200805', 'error_msg' => 'user or email has been already registed'),
			'verify' => array('http_code' => 200, 'error_code' => '200806', 'error_msg' => 'verify reject, invalid'),
			'nouser' => array('http_code' => 200, 'error_code' => '200807', 'error_msg' => 'such user does not exist'),
			'errpass' => array('http_code' => 200, 'error_code' => '200808', 'error_msg' => 'error password'),
			'needlogin' => array('http_code' => 200, 'error_code' => '200809', 'error_msg' => 'user should login before such operation'),
			'erremail' => array('http_code' => 200, 'error_code' => '200810', 'error_msg' => 'account does not match with email'),
			'exist' => array('http_code' => 200, 'error_code' => '200811', 'error_msg' => 'such item already exists'),
		);
		EnvConf::$errorMap = array_merge(EnvConf::$errorMap, $addMap);
	}

	protected static function _requireAppConf()
	{
		$env = Env::getEnvironment();
		$file = dirname(__FILE__) . '/conf/' . $env . '.conf.php';
		require_once($file);
	}

	//some self-define procedures can be specified here
	public static function initModule()
	{
		self::_addCustomErrorMap();
		self::_requireAppConf();
	}
}

?>
