<?php
/**
 * 签名逻辑
 * 
 * 本文件提供迅雷吸血及回收模块的签名运算逻辑
 * 
 * @author 百度移动.云事业部
 * @copyright Copyright (c) 2013-2020 百度在线网络技术(北京)有限公司
 * @version 1.0.0.0
 * @package
 */

/**
 * 
 * Sign本文件提供常见签名逻辑的函数
 * 
 * @author wu_jing@百度云架构部
 * 
 * @version 1.0.0.0
 */
class Sign {
	/**
	 * 
	 * 对一个字符串做sign64签名
	 * @param string $value 要签名的字符串
	 */
	public static function sign64($value) {
		$str = md5 ( $value, true );
		$high1 = unpack ( "@0/L", $str );
		$high2 = unpack ( "@4/L", $str );
		$high3 = unpack ( "@8/L", $str );
		$high4 = unpack ( "@12/L", $str );
		if(!isset($high1[1]) || !isset($high2[1]) || !isset($high3[1]) || !isset($high4[1]) ) {
			return false;
		}
		$sign1 = $high1 [1] + $high3 [1];
		$sign2 = $high2 [1] + $high4 [1];
		$sign = ($sign1 & 0xFFFFFFFF) | ($sign2 << 32);
		return sprintf ( "%u", $sign );
	}

	/**
	 * 
	 * 取模
	 * @param string $number 要取模的数字字符串
	 * @param int $mod 模是多少
	 */
	public static function mod($number, $mod) {
		$length = strlen($number);
		$left = 0;
		for($i = 0; $i < $length; $i++) {
			$digit = substr($number, $i, 1);
			$left = intval($left.$digit);
			if($left < $mod) {
				continue;
			}else if($left == $mod) {
				$left = 0;
				continue;
			}else{
				$left = $left%$mod;
			}
		}
		return $left;
	}
}
?>
