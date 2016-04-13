<?php 
class Api_Com_Utils
{
    public static function getUserInfo(){

    }
    /**
     * 获取默认的信息
     */
    public static function getPageInfo($page=1,$size=10){
        $_tmp = array_merge($_POST,$_GET);
        $page_info = array();
        $page = isset($_tmp['page'])?intval($_tmp['page']):$page;
        $limit = isset($_tmp['page_size'])?intval($_tmp['page_size']):$size;
        $page_info['page'] = $page;
        $page_info['page_size'] = $limit;
        return $page_info;
    }
	public static function sliceAndGetPageInfo(&$list, $page, $pageSize)
	{
		$totalNum = count($list);
		$start = ($page - 1) * $pageSize;
		$end = $page * $pageSize;
		$cutNum = $pageSize;
		if($end > $totalNum){
			$cutNum = $totalNum - $start;
		}
		$list = array_slice($list, $start, $cutNum);
		$totalPage = intval(($totalNum - 1) / $pageSize) + 1;
		if($totalNum == 0){
			$totalPage = 0;
			$page = 0;
		}
		return array(
			'total' => $totalNum,
			'page' => $page, 
			'page_size' => $pageSize,
			'total_page' => $totalPage,
		);
	}
	
	public static function sEnCode($string = '', $skey = 'daimazuozheshixieronglinyouwentizhaota')
	 {
	    $skey = array_reverse(str_split($skey));
	    $strArr = str_split(base64_encode($string));
	    $strCount = count($strArr);
	    foreach ($skey as $key => $value) {
	        $key < $strCount && $strArr[$key].=$value;
	    }
	    return str_replace('=', 'O0O0O', join('', $strArr));
	}
	
	public static function sDecode($string = '', $skey = '')
	{
	    $skey = array_reverse(str_split($skey));
	    $strArr = str_split(str_replace('O0O0O', '=', $string), 2);
	    $strCount = count($strArr);
	    foreach ($skey as $key => $value) {
	        $key < $strCount && $strArr[$key] = $strArr[$key][0];
	    }
	    return base64_decode(join('', $strArr));
	}
	
	/**
	 * 为保证个人用户帐号体系安全，对用户ID做加密后下发
	 * 
	 * @param string $user_id 真实用户ID
	 */
	public static function encodeUserId($user_id) {
        $id = ($user_id & 0x0000ff00) << 16;
        $id += (($user_id & 0xff000000) >> 8) & 0x00ff0000;
        $id += ($user_id & 0x000000ff) << 8;
        $id += ($user_id & 0x00ff0000) >> 16;
        $id ^= 457854;
        return $id;
    }

    /**
     * 为保证个人用户帐号体系安全，对用户ID做加密后下发
     * 
     * @param string  $user_id 加密后的用户ID
     */
    public static function decodeUserId($user_id) {
        if (!is_int($user_id) && !is_numeric($user_id)) {
            return false;
        }
        $user_id ^= 457854;
        $id = ($user_id & 0x00ff0000) << 8;
        $id += ($user_id & 0x000000ff) << 16;
        $id += (($user_id & 0xff000000) >> 16) & 0x0000ff00;
        $id += ($user_id & 0x0000ff00) >> 8;
        return $id;
    }
	
    /**
     * 对密码做加密存储
     * 
     * @param string $account 用户帐号
     * @param string $pwd 用户原始密码
     */
	public static function encodePassword($account, $pwd)
	{
		$str = AppConf::$pwdPrefix . '_' . $account . '_' . $pwd;
		$str = md5(base64_encode($str));
		return $str;
	}
	
	/**
	 * 生成邮件校验地址
	 * 
	 * @param string $email 邮箱地址
	 * @param uid $uid 用户ID号
	 * @param int $regTime 注册时间戳
	 */
	public static function makeEmailVeriUrl($email, $uid, $regTime)
	{
		$euid = Uc_Com_Utils::encodeUserId($uid);
		$orgStr = AppConf::$emailVeriPrefix . '_' . $euid . '_' . $email . '_' . date('Y-m-d H:i:s', $regTime);
		$str = md5(base64_encode($orgStr));
		$code = Sign::sign64($str);
		$qs = array('code' => $code, 'uid' => Uc_Com_Utils::encodeUserId($uid));
		$url = Utils::addUrlQueryString('error', AppConf::$emailVeriUrl, $qs);
		KC_LOG_DEBUG("make email verifiy url succ [ email: $email, uid: $uid, reg time: $regTime, str: $orgStr, code: $code, url: $url ].");
		return $url;
	}
	
	public static function makeEmailRetriPassUrl($uid, $account, $password, $email, $phone, $qq, $registTime)
	{
		$now = time();
		$euid = Uc_Com_Utils::encodeUserId($uid);
		$orgStr = AppConf::$emailVeriPrefix . '_' . $euid . '_' . $account . 
			'_' . $password . '_' . $email . '_' . $phone. '_' . $qq . '_' . 
			date('Y-m-d H:i:s', $registTime) . '_' . date('Y-m-d H:i:s', $now);
		$str = md5(base64_encode($orgStr));
		$code = Sign::sign64($str);
		$qs = array('code' => $code, 'uid' => Uc_Com_Utils::encodeUserId($uid), 'timestamp' => $now);
		$url = Utils::addUrlQueryString('error', AppConf::$emailRetriPassUrl, $qs);
		KC_LOG_DEBUG("make email retrieve password url succ [ email: $email, uid: $uid, reg time: $registTime, str: $orgStr, code: $code, url: $url ].");
		return $url;
	}
	
	public static function checkRetriPassParam($db, $euid, $code, $timestamp, $jump = 'reject')
	{
		$now = time();
		if($timestamp + AppConf::$emailRetriPassExpire < $now){
			if(is_string($jump)){
				throw new Exception("param $jump time expired [ now: $now, timestamp: " . 
					"$timestamp, expire time: " . AppConf::$emailRetriPassExpire . ' ].', 1);
			}
			throw new Exception("param time expired [ now: $now, timestamp: " . 
					"$timestamp, expire time: " . AppConf::$emailRetriPassExpire . ' ].');
		}
		$uid = Uc_Com_Utils::decodeUserId($euid);
		$sql = "SELECT uid, account, password, email, phone, qq, regist_time FROM uc_user_info WHERE uid = $uid";
		$dbRet = $db->query($sql, is_string($jump) ? 'error' : true);
		if(count($dbRet) <= 0){
			if(is_string($jump)){
				throw new Exception("nouser $jump such user does not exists [ uid: " . $uid . ' ].', 1);
			}
			throw new Exception('nouser such user does not exists [ uid: ' . $uid . ' ].');
		}
		$uInfo = $dbRet[0];
		$orgStr = AppConf::$emailVeriPrefix . '_' . $euid . '_' . $uInfo['account'] . 
			'_' . $uInfo['password'] . '_' . $uInfo['email'] . '_' . $uInfo['phone']. '_' . $uInfo['qq'] . '_' . 
			date('Y-m-d H:i:s', $uInfo['regist_time']) . '_' . date('Y-m-d H:i:s', $timestamp);
		$str = md5(base64_encode($orgStr));
		$dbCode = Sign::sign64($str);
		if($dbCode != $code){
			if(is_string($jump)){
				throw new Exception("param $jump invalid code [ uid: $uid, code: $code, db code: $dbCode, str: $orgStr ]", 1);
			}
			throw new Exception("param invalid code [ uid: $uid, code: $code, db code: $dbCode, str: $orgStr ]");
		}
		KC_LOG_DEBUG("check retrieve password params succ [ euid: $euid, uid: $uid, code: $code, str: $orgStr ].");
	}
	
	public static function noticeApi(){
		$notice_url =  isset($_COOKIE[AppConf::$cookiePrefix . '_notice_url'])?$_COOKIE[AppConf::$cookiePrefix . '_notice_url']:0;
		$api_url =  isset($_COOKIE[AppConf::$cookiePrefix . '_api_url'])?$_COOKIE[AppConf::$cookiePrefix . '_api_url']:0;
		$token =  isset($_COOKIE[AppConf::$cookiePrefix . '_token'])?$_COOKIE[AppConf::$cookiePrefix . '_token']:0;

		if($api_url != 0){
			$params = array(
							"notice_url"=>Uc_Com_Utils::sDecode($notice_url),
							"api_url"=>Uc_Com_Utils::sDecode($api_url),
							"token"=>$token,
							"uid"=>Uc_Com_Utils::encodeUserId($uid)
					  );
			$res = Utils::httpRequest(AppConf::$noticeApp,'POST',$params);
			
			setcookie(AppConf::$cookiePrefix . '_notice_url', "", time() - 3600);
			setcookie(AppConf::$cookiePrefix . '_api_url', "", time() - 3600);
			setcookie(AppConf::$cookiePrefix . '_token', "", time() - 3600);
			KC_LOG_DEBUG("notice succ [ url:".AppConf::$noticeApp.", params:".json_encode($params)." , res: $res ].");
		}
	}
    
}
?>
