<?php
require_once dirname(__FILE__) . '/loader.inc.php';

class Env
{
	public static $uiEnv = 'prod';
	public static $idc = null;
	public static $urlEncObj = array();
	public static $redisObjs = array();

	public static function getEnvironment()
	{
		if (self::$uiEnv !== null) {
			return self::$uiEnv;
		}
		$_ui_env = get_cfg_var('ui.environment');
		$_ui_env = $_ui_env ? $_ui_env : 'dev';
		self::$uiEnv = $_ui_env;
		return self::$uiEnv;
	}

	public static function getIDC()
    {
		if(!is_null(self::$idc)){
			return self::$idc;
		}
        $idc    = explode('-', php_uname('n'));
        $mapIDC = array(
            'ai'   => 'dx',
            'yf'   => 'dx',
            'jx'   => 'dx',
            'hz01' => 'hz',
            'db'   => 'wt',
            'cq01' => 'wt',
            'tc'   => 'wt',
            'st01' => 'wt',
            'cq02' => 'cq02',
            'nj02' => 'nj',
        );
        if (!isset($mapIDC[$idc[0]])) {
            $idc_real = 'dx';
        } else {
            $idc_real = $mapIDC[$idc[0]];
        }
       	self::$idc = $idc_real;
		return self::$idc;
    }
    
    public static function getUrlEnc()
    {
		$option =  EnvConf::$urlEncOptions;
        $option['host'] =  $option['host'][Env::getIDC()];
        if (isset(self::$urlEncObj[0]) && self::$urlEncObj[0] instanceof UrlEnc_Client) {
            return self::$urlEncObj[0];
        }
        self::$urlEncObj[0] = new UrlEnc_Client($option);
        return self::$urlEncObj[0];
    }
    
    public static function getRedis($readOnly = false)
    {
        $readOnly = false;
        if (isset(self::$redisObjs[$readOnly ? 1 : 0]) && self::$redisObjs[$readOnly ? 1 : 0] instanceof Redis) {
            return self::$redisObjs[$readOnly ? 1 : 0];
        }
        if ($readOnly) {
            $option = EnvConf::$redisOptions[array_rand(EnvConf::$redisOptions)];
            $option['host'] = $option['host'][Env::getIDC()];
        } else {
            $option = EnvConf::$redisOptions['master'];
            $option['host'] = $option['host'][Env::getIDC()];
        }
        $redis = new Redis();
        $redis->connect($option['host'][array_rand($option['host'])], $option['port'], $option['timeout']);
        self::$redisObjs[$readOnly ? 1 : 0] = $redis;
        return $redis;
    }
}
?>
