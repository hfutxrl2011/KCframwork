<?php
class AppConf
{
	public static $feRoot = "http://mobstatic.youzu.com/";
	public static $feRootForDownload = "http://mobstatic.youzu.com/";	
	
	public static $cookiePrefix = 'tssg';
	//cookie域，目前uc支持同后缀域
	public static $cookieDomain = '192.168.180.23';
	//cookie路径
	public static $cookiePath = '/';
	//cookie超时时间，一个月
	public static $cookieExpire = 2592000;
	//用户状态
	public static $uStatMap = array(
		0 => '正常(已验证)',
		1 => '待验证',
		2 => '已禁用',
	);
    public static $redisRetry = 3;
    //是否启用mset功能
    public static $enableRedisMuti = true;
    //缓存失效时间，10分钟
    public static $redisExpire =36000;
    //redis服务器配置
    public static $arrRedis = array(
        'server' => array(
    		'dx' => array(
            	"192.168.180.50",
            	"192.168.180.50",
    		),
        ),
        'connect_timeout' => 0.5,
        'port' => 6379,
    );
    //IDGen Server
    public static $IDGenConf = array(
        "server"=>array(
            array("host"=>'139.196.180.9',"port" => 8063)),
        "provider" => 'idgen',
        "magicNum" => 13579,
    );
    
    public static $dbRetry = 3;
	public static $dbConf = array(
        'charset' => 'UTF8',
        'db_num' => 1,
        'tb_num' => 1,
        'server' => array(
			'dx' => array(
            	'0' => array(
                	array(
                    	'host' => '114.215.108.115',
                    	'port' => 8808,
                    	'username' => 'root',
                    	'password' => 'qiongmysql',
                    	'database'=> 'tssg_edu',
                    ),
                ),
            ),
		),
    );	
}
?>
