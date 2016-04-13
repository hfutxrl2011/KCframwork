<?php
class Task_Client
{
	    private $client;
		private $task_server = "10.1.1.182";
		private $task_server_port = 9502;

		public function __construct() {
			$this->client = new swoole_client(SWOOLE_SOCK_TCP);
		}

		public function send($task,$params=array()) {
			if( !$this->client->connect($this->task_server, $this->task_server_port , 1) ) {
				exit("Error: {$fp->errMsg}[{$fp->errCode}]\n");
			}
			
			$conf = array(
							"callback"=>"",//回调url
							"tasktype"=>false,//任务类型
							"timer"=>false,//任务触发时间间隔
						);
						
			foreach($task as $k => $v){
				isset($conf[$k]) && $conf[$k] = $v;
			}
			
			$requestid = time().rand();
			isset($params["requestid"]) && $requestid = $params["requestid"];

            $data = array(
							"requestid"=>$requestid,
							"data"=>array("conf"=>$conf,"params" => $params),
						);	

			$message = $this->client->recv();
			
			if($message){
				$this->client->send(json_encode($data));
			}
			
			return $message;
		}
}
?>
