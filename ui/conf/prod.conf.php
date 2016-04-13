<?php

class EnvConf {
	public static $debug = false;
	public static $encoding = "UTF-8";
	public static $log_level = 16;
	
	//公共错误映射
	public static $errorMap = array(
        'succ' => array('http_code' => 200, 'error_code' => '0', 'error_msg' => 'SUCC'),
        'internal' => array('http_code' => 200, 'error_code' => '100800', 'error_msg' => 'internal server error'),
        'not_found' => array('http_code' => 200, 'error_code' => '100801', 'error_msg' => 'unsupported API'),
        'param' => array('http_code' => 200, 'error_code' => '100802', 'error_msg' => 'invalid param'),
        'auth' => array('http_code' => 200, 'error_code' => '100803', 'error_msg' => 'auth failed'),
        'submit' => array('http_code' => 200, 'error_code' => '100804', 'error_msg' => 'post method is required'),
        'custom' => array ('http_code' => 200,'error_code' => '100900','error_msg' => ''),
    );	

	//必须post的方法
	public static $postMethod = array();

	//RSA非对称加密配置
	public static $rsaConf = array(
		'max_en_length' => 117,
		'max_de_length' => 128,
		'rsa_public_key' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC/7VlVn9LIrZ71PL2RZMbK/Yxc
db046w/cXVylxS7ouPY06namZUFVhdbUnNRJzmGUZlzs3jUbvMO3l+4c9cw/n9aQ
rm/brgaRDeZbeSrQYRZv60xzJIimuFFxsRM+ku6/dAyYmXiQXlRbgvFQ0MsVng4j
v+cXhtTis2Kbwb8mQwIDAQAB
-----END PUBLIC KEY-----',
		'rsa_private_key' => '-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC/7VlVn9LIrZ71PL2RZMbK/Yxcdb046w/cXVylxS7ouPY06nam
ZUFVhdbUnNRJzmGUZlzs3jUbvMO3l+4c9cw/n9aQrm/brgaRDeZbeSrQYRZv60xz
JIimuFFxsRM+ku6/dAyYmXiQXlRbgvFQ0MsVng4jv+cXhtTis2Kbwb8mQwIDAQAB
AoGAF5ede6EB0BlHiO3Gf7Dbnug78MGoWO7MLFJtqRLsCT9zRF3t59ZaiaDCH7CH
h+sOo6dRlOxbquUxScgrRPQR/ymq3rvD93+SJIV20waX9b8djYyeYWs/4sWZJ67Q
DenDUaHKNnyi4olrlxPxnkBuCvh2PUQpdXtYeB+UESdMjMECQQDjv23VnY3Pf4tX
gYg+wcSg2oYZxxzO53052P52OwSKeqQuMI2re8wqHXKPfbPTslWen/buTn6UxUz4
cpuZEtAtAkEA17xd3pUwA1ZWDwKuMawFrdNHz53OBUEhL0h9JT6HAQrAWpVcURb6
N1Mc06blYFxDu0LGi7nZHFObET1CqF1mLwJAYzwqE4YPIHamtH5Qa2fq0VvmSp0j
xFPBkM8oMUQN+njtyOKHGE1c7IzgOf2/uWJfRDrXUYcKSLCflTH68nvsEQJBAM9k
86zWKQkcR7E4d3OjFvaLZb6uyu78NMW63ywd1zVmO5MZgV0nRLZI/S5vhJVFPYvZ
XvvWV2TG7wz8oocu+tsCQBx0XUEMngbCJb5+wmEVPcPdG4v3TCqloMi/woYyYjFL
7afSFI2IZWN1r/JYxUZlkuEVaiwOYZP1Xlobg70p2t8=
-----END RSA PRIVATE KEY-----',
    );

	//行业模块部署路径
	public static $moduleRoot = '/../ui-app';
	//redis配置
	public static $redisOptions = array(
		'master' => array(
			'port' => 6379, 
			'timeout' => 0.01,
	 		'host' => array(
				'dx' => array('10.3.5.42'),
				'wt' => array('10.3.5.42'),
				'hz' => array('10.3.5.42'),
				'nj' => array('10.3.5.42'),
				'cq02' => array('10.3.5.42'),
			),
		),
		'slave' => array(
			'port' => 6379, 
			'timeout' => 0.01,
	 		'host' => array(
				'dx' => array('10.3.5.42'),
				'wt' => array('10.3.5.42'),
				'hz' => array('10.3.5.42'),
				'nj' => array('10.3.5.42'),
				'cq02' => array('10.3.5.42'),
			),
		),
	);
	//解决域名moudle冲突问题
	public static $moduleMap = array('app'=>'api');
	//mysql
	public static $dbRetry = 3;
	public static $dbConf = array(
        'charset' => 'UTF8',
        'db_num' => 1,
        'tb_num' => 1,
        'server' => array(
			'dx' => array(
            	'0' => array(
                	array(
                    	'host' => '10.3.5.43',
                    	'port' => 3306,
                    	'username' => '',
                    	'password' => '',
                    	'database'=> '',
                    ),
                ),
            ),
			'stat' => array(
            	'0' => array(
                	array(
                    	'host' => '',
                    	'port' => 3306,
                    	'username' => '',
                    	'password' => '',
                    	'database'=> '',
                    ),
                ),
            ),
		),
    );	
}
