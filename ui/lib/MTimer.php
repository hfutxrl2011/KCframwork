<?php
class MTimer
{
	private $_arrTime = array();
	private $_lastTime = null;

	public function __construct(){
		$this->reset();
	}
	
	/**
	 * 清空之前的计时信息
	 * 
	 */
	public function reset(){
		$this->_lastTime = intval((microtime(true) * 1000));
	}
	
	/**
	 * 设置一个计时里程碑
	 * 
	 * @param array $label 计时标识
	 * @throws Exception
	 */
	public function set($label){
		if(empty($label)){
			throw new Exception('cannot set timer with empty label.');
		}
		$curTime = intval((microtime(true) * 1000));
		if(is_null($this->_lastTime)){
			$timeElapsed = 0;
		}else{
			$timeElapsed = $curTime - $this->_lastTime;
		}
		$this->_lastTime = $curTime;
		if(isset($this->_arrTime[$label])){
			//if label already existed, add index to it
			$i = 1;
			while(isset($this->_arrTime[$label . '_' . $i])){
				$i += 1;
			}
			$label = $label . '_' . $i;
		}
		$this->_arrTime[$label] = $timeElapsed;
	}
	
	/**
	 * 将计时信息以字符串形式输出
	 * 
	 */
	public function getString(){
		$this->set('__spare_time__');
		$ret = '';
		$totalTime = 0;
		foreach ($this->_arrTime as $label => $time){
			$ret .= $label . ': ' . $time . ' ms, ';
			$totalTime += $time;
		}
		if(!empty($ret)){
			$ret = substr($ret, 0, strlen($ret) - strlen(', '));
			$ret = 'total: ' . $totalTime . ' ms, ' . $ret;
		}
		return $ret;
	}
}
?>
