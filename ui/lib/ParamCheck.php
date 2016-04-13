<?php
/**
 * 参数检查
 * 
 * 本文件参数检查的逻辑
 * 
 * @author 百度移动.云事业部
 * @copyright Copyright (c) 2013-2020 百度在线网络技术(北京)有限公司
 * @version 1.0.0.0
 * @package
 */

/**
 * 
 * ParamCheck
 * 
 * ParamCheck类提供本模块的参数检查逻辑
 * 
 * @author wu_jing@百度云架构部
 * 
 * @version 1.0.0.0
 */
class ParamCheck
{
	/**
	 * 
	 * 整型参数检查
	 * @param string $k 参数的名字，用于打印日志
	 * @param int $v 参数的值
	 * @param int $low 参数最小值
	 * @param int $high 参数最大值
	 * @throws Exception
	 */
	public static function checkInt($tpl, $k, $v, $low = null, $high = null)
	{
		$errStr = null;
		if(!is_numeric($v) || strval(intval($v)) !== strval($v))
		{
			$errStr = "[ $k ] is not an integer [ value: $v ].";
		}
		else if(!is_null($low) && $v < $low)
		{
			$errStr = "[ $k ] is less than low limit [ value: $v, low: $low ].";
		}
		else if(!is_null($high) && $v > $high)
		{
			$errStr = "[ $k ] is more than high limit [ value: $v, high: $high ].";
		}
		if(!is_null($errStr)){
			if(!is_null($tpl)){
				throw new Exception('param ' . $tpl . ' ' . $errStr, 1);
			}
			throw new Exception("param $errStr");
		}
	}

	/**
	 * 
	 * 检查参数是否是一个数字字符串
	 * @param string $k 参数的名字，用于打印日志
	 * @param int $v 参数的值
	 * @param int $low 参数最小值
	 * @param int $high 参数最大值
	 * @throws Exception
	 */
	public static function checkNumString($tpl, $k, $v, $low = null, $high = null)
	{
		is_string($v) || $v = strval($v);
		self::checkString($tpl, $k, $v, $low, $high);
		$len = strlen($v);
		for($i = 0; $i < $len; $i++)
		{
			if('0' > $v[$i] || '9' < $v[$i])
			{
				if(is_null($tpl)){
					throw new Exception("param [ $k ] is not a numeric string [ value: $v ].");
				}
				throw new Exception("param $tpl [ $k ] is not a numeric string [ value: $v ].", 1);
			}
		}
	}
	
	/**
	 * 检查元素、数组中的元素是否在允许的范围内
	 *
	 * @param string $k 参数名字 
	 * @param mixed $v 要检查的元素/数组
	 * @param array $allow 允许的数据
	 * @throws Exception
	 */
	public static function checkInArray($tpl, $k, $v, $allow)
	{
		if(is_null($v) || (is_array($v) && empty($v))){
			if(is_null($tpl)){
				throw new Exception("param [ $k ] is empty.");
			}
			throw new Exception("param $tpl [ $k ] is empty.", 1);
		}
		$v = (array)$v;
		$tmp = array_diff($v, $allow);
		if(!empty($tmp)){
			if(is_null($tpl)){
				throw new Exception("param [ $k ] is not in allowd values [ allowed: " . 
					json_encode($allow) . ", $k: " . json_encode($v) . ' ].');
			}
			throw new Exception("param $tpl [ $k ] is not in allowd values [ allowed: " . 
					json_encode($allow) . ", $k: " . json_encode($v) . ' ].', 1);
		}
	}
	
	/**
	 * 
	 * 字符串检查
	 * @param string $k 参数的名字，用于打印日志 
	 * @param string $v 参数的值
	 * @param int $low 参数最短是多长
	 * @param int $high 参数最长是多长
	 * @throws Exception
	 */
	public static function checkString($tpl, $k, $v, $low = null, $high = null)
	{
		$errStr = null;
		if(!is_string($v))
		{
			$errStr = "[ $k ] is not a string [$v].";
		}
		else if(!is_null($low) && mb_strlen($v, 'utf-8') < $low)
		{
			$errStr = "[ $k ] is shorter than low limit [ length of value: " . mb_strlen($v, 'utf-8') . ", low: $low ].";
		}
		else if(!is_null($high) && mb_strlen($v, 'utf-8') > $high)
		{
			$errStr = "[ $k ] is longer than high limit [ value: " . mb_strlen($v, 'utf-8') . ", high: $high ].";
		}
		if(!is_null($errStr)){
			if(is_null($tpl)){
				throw new Exception("param $errStr");
			}
			throw new Exception("param $tpl $errStr", 1);
		}
	}

	static function checkEmail($tpl, $k, $v) {
		$errStr = null;
		$local_part_pos = strpos ( $v, '@' );
		//找不到@，或者@开头，返回false
		if ($local_part_pos == false) {
			$errStr = "[ $k ] is invalid1 [ value: $v ].";
		}
		$str_local_part = substr ( $v, 0, $local_part_pos );
		$str_len = strlen ( $str_local_part );
		if ($str_len > 64) {
			$errStr = "[ $k ] is invalid2 [ value: $v ].";
		}
		$str_domain_name = substr ( $v, $local_part_pos + 1 );
		$str_len = strlen ( $str_domain_name );
		if ($str_len == 0 || $str_len > 255) {
			$errStr = "[ $k ] is invalid3 [ value: $v ].";
		}
		$v = trim($v);
		if (filter_var ( $v, FILTER_VALIDATE_EMAIL ) === false) {
			$errStr = "[ $k ] is invalid4 [ value: $v ].";
		}
		if(!is_null($errStr)){
			if(is_null($tpl)){
				throw new Exception('param ' . $errStr);
			}
			throw new Exception("param $tpl $errStr", 1);
		}
		
	}	

	/**
	 * 
	 * 校验电话号码是否正确
	 * @param string $key 值的KEY
	 * @param string $str 电话号码字符串
	 */
	static function checkPhone($tpl, $key, &$str)
	{
	    $pattern = "/^(13|15|18|14|17)\d{9}$/";
	    $reg_pattern = array ('options' => array ('regexp' => $pattern ) );
	    if (false !== filter_var ( $str, FILTER_VALIDATE_REGEXP, $reg_pattern )){
	        return ;
	    }
	
	    $pattern = "/^\+86(13|15|18|14|17)\d{9}$/";
	    $reg_pattern = array ('options' => array ('regexp' => $pattern ) );
	    if (false !== filter_var ( $str, FILTER_VALIDATE_REGEXP, $reg_pattern )){
	    	$str = substr($str, strlen('+86'));
	        return ;
	    }
		if(is_null($tpl)){
	    	throw new Exception("param invalid param [ key: $key, value: $str ]");
		}
		throw new Exception("param $tpl invalid param [ key: $key, value: $str ]", 1);
	}
    
    /**
     * 
     * 检查IP是否符合规则
     * @param string $pattern 模式
     * @param string $ip IP字符串
     * @throws Exception
     */
	public static function checkAllowIp ( $tpl, $pattern, $ip )
    {
        if ( self::isLocalIp( $ip ) )
        {
            return true;
        }
        $arrPattern = explode ( '.', $pattern );
        $arrIp = explode ( '.', $ip );
        if ( 4 !== count ( $arrPattern ) )
        {
			if(is_null($tpl)){
           		throw new Exception ( 'internal invalid pattern while ' . __FUNCTION__ . ' [ pattern: ' .  $pattern . ' ]' );
			}
			throw new Exception("internal $tpl invalid pattern while " . __FUNCTION__ . ' [ pattern: ' .  $pattern . ' ]', 1);
        }
        else if ( 4 !== count ( $arrIp ) )
        {
			if(is_null($tpl)){
            	throw new Exception ( 'internal invalid ip while ' . __FUNCTION__ . ' [ ip: ' .  $ip . ' ]' );
			}
			throw new Exception("internal $tpl invalid ip while " . __FUNCTION__ . ' [ ip: ' .  $ip . ' ]', 1);
        }

        for ( $i = 0; $i < 4; $i++ )
        {
            $pi = trim ( strval ( $arrPattern [ $i ] ) );
            $ii = trim ( strval ( $arrIp [ $i ] ) );
            if ( ! is_numeric ( $pi ) && '*' !== $pi )
            {
				if(is_null($tpl)){
                	throw new Exception ( 'internal invalid pattern while ' . __FUNCTION__ . ' [ pattern: ' .  $pattern . ' ]' );
				}
				throw new Exception("internal $tpl invalid pattern while " . __FUNCTION__ . ' [ pattern: ' .  $pattern . ' ]', 1);
            }
            if ( !is_numeric ( $ii ) )
            {
				if(is_null($tpl)){
                	throw new Exception ( 'internal invalid ip while ' . __FUNCTION__ . ' [ ip: ' .  $ip . ' ]' );
				}
				throw new Exception("internal $tpl invalid ip while " . __FUNCTION__ . ' [ ip: ' .  $ip . ' ]', 1);
            }
            if ( is_numeric ( $pi ) && $pi !== $ii)
            {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 
     * 检查是否是本地IP
     * @param string $ip
     */
	public static function isLocalIp($ip) {
        if (0 == strncmp ( $ip, '10.', 3 )
            || 0 == strncmp ( $ip, '172.', 4 )
            || 0 == strncmp ( $ip, '127.', 4 )
            || 0 == strncmp ( $ip, '192.', 4 )) {
            return true;
        }
        return false;
    }
    
    /**
     * 检查URL是否正常
     * 
     * @param string $tpl 如果失败，是否要跳转到模板页面
     * @param string $k 要检查参数的key，打日志用
     * @param string $v 要检查的URL字符串
     * @param string $autoAppendScheme 如果URL没有协议，则自动补充协议部分
     */
    static function checkUrl($tpl, $k, &$v, $autoAppendScheme = 'http://') {
    	$arrUrl = parse_url($v);
    	if(false === $arrUrl || !isset($arrUrl['host']) || empty($arrUrl['host'])){
    		if(is_null($tpl)){
    			throw new Exception("param invalid url [ $k ] values [ $v ].");
    		}else{
    			throw new Exception("param $tpl invalid url [ $k ] values [ $v ].", 1);
    		}
    	}
    	if(!isset($arrUrl['scheme']) && !is_null($autoAppendScheme)){
    		$v = $autoAppendScheme . $v;
    	}	
    }
}
?>
