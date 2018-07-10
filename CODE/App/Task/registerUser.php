<?php 

class registerUser{
	
	//注册用户任务
	public function run($param){
		//模拟注册
		sleep(4);
		file_put_contents('./user.txt',var_export($param,true)."\r\n",FILE_APPEND);
		return 'success';
	}
}

?>