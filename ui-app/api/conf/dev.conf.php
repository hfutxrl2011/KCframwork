<?php
class AppConf
{
    //密码加密前缀
	public static $pwdPrefix = 'yz_mobile_password';
    //邮件验证码前缀
	public static $emailVeriPrefix = 'yz_mobile_email_verify';
	//fe代码根目录
	public static $feRoot = 'http://192.168.180.23:8081/';
	//邮件验证线上服务URL
	//cookie KEY的前缀
	public static $cookiePrefix = 'yz';
	//cookie域，目前uc支持同后缀域
	public static $cookieDomain = '192.168.180.23';
	//cookie路径
	public static $cookiePath = '/';
	//cookie超时时间，一个月
	public static $cookieExpire = 2592000;
    public static $redisPrex = "yz_mobile_uc";
	//redis重试次数
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
                    	'host' => '192.168.180.50',
                    	'port' => 8808,
                    	'username' => 'root',
                    	'password' => 'root',
                    	'database'=> 'uc',
                    ),
                ),
            ),
		),
    );	
}
?>
