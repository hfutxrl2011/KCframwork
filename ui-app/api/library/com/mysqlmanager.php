<?php
/**
 * 数据库管理
 * 
 * 本文件提供数据库管理功能
 * 
 * @author 百度移动.云事业部
 * @copyright Copyright (c) 2013-2020 百度在线网络技术(北京)有限公司
 * @version 1.0.0.0
 * @package
 */

/**
 * 
 * MysqlManager
 * 
 * MysqlManager提供数据库底层管理功能
 * 
 * @author wu_jing@百度云架构部
 * 
 * @version 1.0.0.0
 */
class MysqlManager 
{
	protected $_mysqli = NULL;
	protected $_isConnected = false;
	
	public function __construct() 
	{
        $this->_mysqli = mysqli_init();
    }

    public function __destruct() 
	{
    	if($this->_isConnected) 
		{
       		$this->_mysqli->close();
    	}
    }

	public static function getTbNo($identifier)
	{
		$sign = $identifier;
		is_numeric($identifier) || $sign = Sign::sign64($identifier);
		$tag = (Sign::mod($sign, (AppConf::$dbConf['db_num'] * AppConf::$dbConf['tb_num']))) % AppConf::$dbConf['tb_num'];
		return $tag;
	}

	public static function getDbNo($identifier)
	{
		$sign = $identifier;
		is_numeric($identifier) || $sign = Sign::sign64($identifier);
		$tag = intval((Sign::mod($sign, (AppConf::$dbConf['db_num'] * AppConf::$dbConf['tb_num']))) / AppConf::$dbConf['tb_num']);	
		return $tag;
	}

	/**
	 * 
	 * 开启一个事务
	 */
	public function startTransaction()
	{
		if(false === $this->query('START TRANSACTION'))
		{
			KC_LOG_WARNING(__FUNCTION__ . " failed because execute execute sql failed.");
			return false;
		}
		KC_LOG_DEBUG(__FUNCTION__ . " succ.");
		return true;
	}

	/**
	 * 
	 * 结束一个事务
	 * @param boolean $commit 是否提交事务
	 */
	public function endTransaction($commit = false)
	{
		$sql = 'ROLLBACK';
		if($commit)
		{
			$sql = 'COMMIT';
		}
		if(false === $this->query($sql))
        {
            KC_LOG_WARNING(__FUNCTION__ . " failed because execute execute sql failed.");
            return false;
        }
        KC_LOG_DEBUG(__FUNCTION__ . " succ.");
        return true;	
	}

	public function getError()
	{
		if(!$this->_isConnected)
        {
            KC_LOG_WARNING(__FUNCTION__ . " failed, db has not been connected.") ;
            return false;
        }
		return array(
			'errno' => $this->_mysqli->errno,
			'errmsg' => $this->_mysqli->error,
		);	
	}
	
	/**
	 * 
	 * 获得上次数据库访问影响到的行数
	 */
	public function getAffectRows()
	{
		if(!$this->_isConnected)
        {
            KC_LOG_WARNING(__FUNCTION__ . " failed, db has not been connected [ sql: $sql ]") ;
            return false;
        }
		return $this->_mysqli->affected_rows;	
	}

	/**
	 * 
	 * 执行一条SQL语句
	 * @param string $sql 要执行的SQL语句
	 */
	public function query($sql)
	{
		$start = intval(microtime(true) * 1000);
		if(!$this->_isConnected)
		{
			KC_LOG_WARNING(__FUNCTION__ . " failed, db has not been connected [ sql: $sql ]")	;
			return false;
		}
        $result = $this->_mysqli->query($sql);
		$end = intval(microtime(true) * 1000);
		$cost = $end - $start;
        if(false === $result &&  $this->_mysqli->errno )
        {
            KC_LOG_WARNING("execute sql failed [ sql: ${sql}, error_info: " . $this->_mysqli->error . ', error_no: ' . $this->_mysqli->errno . ' ].');
            return false;
        }

        if ( true === $result )
        {
            KC_LOG_TRACE ( "execute sql succ [ sql: ${sql}, ret: 'true', cost: $cost ms ]." );
            return $result;
        }

        $ret = array();
        while($row = $result->fetch_assoc())
        {
            array_push($ret, $row);
        }
        $result->close();
		KC_LOG_TRACE ( "execute sql succ [ sql: ${sql}, count: " . count($ret) . ", cost: $cost ms ]." );
        return $ret;	
	}

	/**
	 * 
	 * 连接到数据库
	 * @param string $identifier 连接到哪个数据库，分表依据
	 * @param string $tag 直接指明连接到哪个数据库
	 */
	public function fetchMysqlHandler($identifier, $tag = NULL) 
	{
		if($this->_isConnected) 
		{
			$this->_mysqli->close();
       		$this->_isConnected = false;
       		$this->_mysqli = mysqli_init();
    	}
		if(!is_null($tag)) 
		{
			if(!isset(AppConf::$dbConf['server'][Env::getIDC()][intval($tag)]))
			{
				KC_LOG_WARNING(__FUNCTION__ . " failed, invalid tag received [ tag: $tag ]");
				return false;
			}
			$arrMysqlServer = AppConf::$dbConf['server'][Env::getIDC()][intval($tag)];
		}
		else 
		{
			$tag = $this->getDbNo($identifier);
			$arrMysqlServer = AppConf::$dbConf['server'][Env::getIDC()][intval($tag)];
		}
		
		$totalNum = count($arrMysqlServer);
		$index = mt_rand(0, $totalNum-1);
		for($i = 0; $i < $totalNum; $i++) 
		{
			$mysqlServer = $arrMysqlServer[$index];
			if(!isset($mysqlServer['host']) || !isset($mysqlServer['username']) || 
				!isset($mysqlServer['password']) || !isset($mysqlServer['database']) || 
				!isset($mysqlServer['port']))
			{
				KC_LOG_WARNING(__FUNCTION__ . " failed, config must have host/username/password/database/port fields [mysqlServer: " . json_encode($mysqlServer) . "]");
				return false;
			}
			if(false === $this->_mysqli->real_connect(
													$mysqlServer['host'], 
													$mysqlServer['username'], 
													$mysqlServer['password'], 
													$mysqlServer['database'], 
													$mysqlServer['port'], 
													NULL, 
													0
													)) 
			{
				$index = (++$index % $totalNum);
				continue;
			}
			KC_LOG_DEBUG("fetch mysql conntion host [" . $mysqlServer['host'] . "] port [" . $mysqlServer['port'] . "]");
			$this->_mysqli->set_charset(AppConf::$dbConf['charset']);
			$this->_isConnected = true;
			break;
		}
		if(false === $this->_isConnected) 
		{
			KC_LOG_WARNING("fetch mysql conntion [identifier: $identifier, tag: $tag] failed.");
			return false;
		}
		return true;
	}
	
	/**
	 * 
	 * 转义一个字符串
	 * @param string $strVal 要转义的字符串
	 */
	public function escapeString($strVal)
	{
		if(!$this->_isConnected)
		{
			KC_LOG_WARNING(__FUNCTION__ . " failed, db has not been connected [ strVal: $strVal ].")	;
			return false;
		}
		return $this->_mysqli->escape_string($strVal);
	}
	
	/**
	 * 
	 * 批量转义字符串
	 * @param array $arrFields 要转义的数组
	 * @param array $arrStrFields 哪些KEY是字符串类型的
	 */
	public static function escapeStrings(&$arrFields, $arrStrFields = null)
	{
		$db = new MysqlManager();
        if(false === $db->fetchMysqlHandler(null, 0))
        {   
            KC_LOG_WARNING::waning(__FUNCTION__ . ' failed, fetch mysql handler failed [ dbNo: 0, arrFields: ' . json_encode($arrFields) . ', arrStrFields: ' . json_encode($arrStrFields)  . ' ].');
			return false;
        }
		foreach($arrFields as $k => &$v)
		{
			if(is_null($arrStrFields))
			{
				(!is_null($v)) && $v = "'" . $db->escapeString($v) . "'";
				continue;
			}
			in_array($k, $arrStrFields) && (!is_null($v)) && $v = "'" . $db->escapeString($v) . "'";
		}
		return true;
	}
}
?>
