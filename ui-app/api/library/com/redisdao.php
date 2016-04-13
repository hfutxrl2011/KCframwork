<?php 
/**
 * redis基础管理逻辑
 * 
 * 本文件提供redis基础管理逻辑
 * 
 * @author 百度移动.云事业部
 * @copyright Copyright (c) 2013-2020 百度在线网络技术(北京)有限公司
 * @version 1.0.0.0
 * @package
 */
/**
 * 
 * RedisDao
 * 
 * RedisDao提供redis基础管理逻辑
 * 
 * @author wu_jing@百度云架构部
 * 
 * @version 1.0.0.0
 */
class Uc_Com_RedisDao{
	private $_redisObj = null;
	/**
	 * 获取一个redis连接
	 * 
	 * @param boolean 是否强制重置连接
	 * @throws Exception
	 */
	public function getConnection($force = false){
		if(false === !!$force && !is_null($this->_redisObj)){
			return $this->_redisObj;
		}
		if(!is_null($this->_redisObj)){
			unset($this->_redisObj);
			$this->_redisObj = null;
		}
		$servers  = AppConf::$arrRedis['server'][Env::getIDC()];
		$port = AppConf::$arrRedis['port'];
		shuffle($servers);
		foreach ($servers as $server){
			$this->_redisObj = new Redis();
    		$conRet = $this->_redisObj->connect($server, $port, AppConf::$arrRedis['connect_timeout']);
			if(false === $conRet){
				unset($this->_redisObj);
				$this->_redisObj = null;
				KC_LOG_WARNING('connect to redis failed [ server: %s, port: %s,  timeout: %s ].', $server, $port, AppConf::$arrRedis['connect_timeout']);
			}else{
				KC_LOG_DEBUG('connect to redis succ [ server: %s, port: %s,  timeout: %s ].', $server, $port, AppConf::$arrRedis['connect_timeout']);
				break;
			}
		}
		if(is_null($this->_redisObj)){
			throw new Exception('redis connect to redis all failed failed.');
		}
		return $this->_redisObj;
	}
	
	/**
	 * 获取N个redis KEY的信息
	 * 
	 * @param array $arrKeys 要获取哪些KEY
	 * @throws Exception
	 */
	public function mgetEx($arrKeys, $isAssoc = true){
		if(!is_array($arrKeys)){
			throw new Exception('internal invalid arrKeys was received, no array [ keys: %s ].', $arrKeys);
		}
		if(empty($arrKeys)){
			return array();
		}
		$retry = 0;
		$ret = false;
		do{	
			$this->getConnection($retry !== 0);
			try{
				$ret = $this->_redisObj->mget($arrKeys);
			}catch(Exception $ex){
				KC_LOG_WARNING('try to get multi-keys from redis returned false [ times: %s ].', $retry);
				sleep(1);
				$ret = false;
				continue;
			}
			break;
		}while($retry++ < AppConf::$redisRetry);
		if(false === $ret){
			throw new Exception('query redis to get all failed.');
		}
		if(count($arrKeys) !== count($ret)){
			throw new Exception('redis return count is not equal to arr key [ return: %s, key: %s ].', count($ret), count($arrKeys));
		}
		$total = count($arrKeys);
		$finRet = array();
		if($isAssoc){
			for($i = 0; $i < $total; $i++){
				$finRet[$arrKeys[$i]] = $ret[$i];
			}
		}else{
			$finRet = $ret;
		}
		KC_LOG_DEBUG('function %s succ [ count: %s ].', __FUNCTION__, count($finRet));
		if(EnvConf::$debug === true){
			KC_LOG_DEBUG('keys [ %s ].', json_encode($arrKeys));
		}
		return $finRet;
	}
	
	/**
	 * 批量删除redis中key的信息
	 * 
	 * @param array $keys 要删除哪些key的信息
	 * @throws Exception
	 */
	public function del($keys){
		if(empty($keys)){
			return ;
		}
		$retry = 0;
		$ret = false;
		do{
			$this->getConnection($retry !== 0);
			try{
				$ret = $this->_redisObj->del($keys);
			}catch(Exception $ex){
				KC_LOG_WARNING('try to del keys from redis returned false [ retry: %s ].', $retry);
				sleep(1);
				$ret = false;
				continue;
			}
			break;
		}while($retry++ < AppConf::$redisRetry);
		if(false === $ret){
			throw new Exception('query redis to del keys failed.');
		}
		KC_LOG_TRACE('function %s succ [ count keys: %s ].', __FUNCTION__, count($keys));
	}
	
	/**
	 * 获取某种规则的KEY（注意，知心线上集群不支持）
	 * 
	 * @param string $pattern key匹配模式
	 * @throws Exception
	 */
	public function getKeys($pattern){
		if(empty($pattern) || !is_string($pattern)){
			return array();
		}
		$retry = 0;
		$ret = false;
		do{
			$this->getConnection($retry !== 0);
			try{
				$ret = $this->_redisObj->keys($pattern);
			}catch(Exception $ex){
				KC_LOG_WARNING('try to get keys from redis returned false [ retry: %s ].', $retry);
				sleep(1);
				$ret = false;
				continue;
			}
			break;
		}while($retry++ < AppConf::$redisRetry);
		if(false === $ret){
			throw new Exception('query redis to get keys failed.');
		}
		KC_LOG_TRACE('function %s succ [ pattern: %s, count ret: %s ].', __FUNCTION__, $pattern, $ret);
		return $ret;
	}
	
	/**
	 * 批量设置key的信息
	 * 
	 * @param array $arrValues 要设置的key value对集合
	 * @throws Exception
	 */
	public function msetEx($arrValues){
		if(!is_array($arrValues)){
			throw new Exception('internal invalid $arrValues was received, no array [ keys: %s ].', $arrValues);
		}
		if(empty($arrValues)){
			return ;
		}
		$retry = 0;
		$ret = false;
		do{
			$this->getConnection($retry !== 0);
			try{
				$ret = $this->_redisObj->mset($arrValues);
			}catch(Exception $ex){
				KC_LOG_WARNING('try to set multi-values to redis returned false [ retry: %s ].', $retry);
				sleep(1);
				$ret = false;
				continue;
			}
			break;
		}while($retry++ < AppConf::$redisRetry);
		if(false === $ret){
			throw new Exception('query redis to save all failed.');
		}
		KC_LOG_TRACE('function %s sycc [ count: %s ].', __FUNCTION__, count($arrValues));
	}
	
	/**
	 * 设置key的信息
	 * 
	 * @param string $key 要设置的key
	 * @param string $value 要设置的value
	 * @param string $timeout 超时时间
	 * @throws Exception
	 */
	public function setEx($key, $value, $timeout = null){
		$retry = 0;
		$ret = false;
		do{
			$this->getConnection($retry !== 0);
			try{
				if(!is_null($timeout)){
					$ret = $this->_redisObj->setex($key, $timeout, $value);
				}else{
					$ret = $this->_redisObj->setex($key, $value);
				}
			}catch(Exception $ex){
				KC_LOG_WARNING('try to set key value to redis returned false [ retry: %s ].', $retry);
				sleep(1);
				$ret = false;
				continue;
			}
			break;
		}while($retry++ < AppConf::$redisRetry);
		if(false === $ret){
			throw new Exception('query redis to save all failed.');
		}
		KC_LOG_DEBUG('function %s sycc [ key: %s, value: %s, timeout: %s ].', __FUNCTION__, $key, $value, strval($timeout));
	}
    /**
     * 原子增加一个
     */
    public function setIncr($key,$timeout=0){
        $retry = 0;                     
        $ret = false;
        do{ 
            $this->getConnection($retry !== 0);
            try{
                $ret = $this->_redisObj->incr($key); 
                if($timeout){
                    $this->_redisObj->expire($key,$timeout); 
                }
                break;
            }catch(Exception $ex){          
                KC_LOG_WARNING('try to set expire redis returned false [ times: %s ].', $retry);
                sleep(1);
            }
        }while($retry++ < Conf::$redisRetry);   
        if(false === $ret){
            throw new Exception('query redis to get all failed.');
        }
        return $ret;
    }
}
?>
