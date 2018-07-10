<?php 

class Task{
        static $instance=array();
	//任务分发执行
	static public function run($serv,$task_id,$from_id,$data){
		$task=self::getTaskHandle($data['task']);
		return $task->run($data);
	}
	//任务结束处理
	static public function finish($serv, $task_id, $data){
		$log='【'.date('Y-m-d H:i:s')."】 Task finish task_id:".$task_id.' result:'.var_export($data,true)."\r\n";
		//任务写入日志
		file_put_contents('./task_log.txt',$log,FILE_APPEND);
	}
	//获取任务执行处理器
	static protected function getTaskHandle($taskName){
		if(!class_exists($taskName)){
			throw new exception('invalid task');
		}
                if(!isset(self::$instance[$taskName])){
                    self::$instance[$taskName]=new $taskName();
                }
		return self::$instance[$taskName];
	}
}