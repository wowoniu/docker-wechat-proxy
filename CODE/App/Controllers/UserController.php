<?php 

class User{
	protected $request;
	protected $response;
	protected $serv;
	public function __construct($request,$response,$serv){
	$this->request=$request;
	$this->response=$response;
			$this->serv=$serv;
	}
	//个人中心
	public function accountAction(){
	
		global $serv;
		$this->response->end("Welecome to user account");
	}
	//注册
	public function registerAction(){
		global $serv;
		$userInfo=array(
			'name'=>'张三',
			'age'=>23,
			'pwd'=>'123456'
		);
		$taskInfo=array(
			'task'=>'registerUser',
			'param'=>$userInfo
			
		);
		//执行异步注册
		$taskId=$serv->task($taskInfo);
		$this->response->end("login success;task_id:$taskId");
	}
	public function getserverAction(){
			print_r($GLOBALS);
	}
        
        public function testAction(){
            $taskInfo=array(
				'task'=>'curl',
				'param'=>array()
				
			);
            $info=$this->serv->task($taskInfo);
            $this->response->end('success oo '.var_export($info,true));
        }
        public function reloadAction(){
            $this->serv->reload();
            $this->response->end('reload..');
        }
}

?>