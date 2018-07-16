<?php
require_once 'init.php';


$serv=new swoole_websocket_server("0.0.0.0",9566);


//设置异步任务的工作进程数量
$serv->set(array(
    'task_worker_num' => 1,
    'worker_num'=>4,
    'max_request' => 1,//开发下 可以保证代码每次重新加载
));


$serv->on('request',function($request,$response)use($serv){
    if($request->server['request_uri']=='/favicon.ico'){
        $response->end('');
    }
	try{
		Request::run($request,$response,$serv);
	}catch(Exception $e){
		$response->end($e->getMessage());
	}
});



$serv->on('open', function (swoole_websocket_server $server, $request) {
    echo "server: handshake success with fd{$request->fd}".PHP_EOL;
});

$serv->on('message', function (swoole_websocket_server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    //$server->push($frame->fd, json_encode(["url"=>"http://www.baidu.com"]));
    $data=json_decode($frame->data,true);
    if($data&&$data['id']){
        //清除异步锁
        UtilService::clearAsyncLock($data['id']);
        //数据缓存
        UtilService::cache($data['id'],$data['response']);
    }
});

$serv->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});









//处理异步任务
$serv->on('task', function (swoole_server $serv, $task_id, $from_id, $data) {
	//异步任务分发
	try{
		//任务执行完成
                $data['context']=array(
                    'worker_id'=>$serv->worker_id,
                    'task_id'=>$task_id
                );
		$msg=Task::run($serv,$task_id,$from_id,$data);
		$serv->finish($msg);
		return $msg;
	}catch(Exception $e){
		//任务异常
		 $serv->finish(array('status'=>0,'msg'=>$e->getMessage()));
	}
});

//处理异步任务的结果
$serv->on('finish', function ($serv, $task_id, $data) {
	//Task::finish($serv,$task_id,$data);
    return $data;
});

//server start
$serv->on('start',function($serv){
    echo 'swoole version is '.SWOOLE_VERSION.PHP_EOL;
	echo "master pid is ".$serv->master_pid.PHP_EOL;
	echo "server is runing...".PHP_EOL;
});

$serv->on('workerStart',function($serv,$worker_id){

	if($serv->taskworker){
		//task_worker 加载任务处理文件
		echo $worker_id.' task worker is runing...'.PHP_EOL;
//		require_once './App/Task/registerUser.php';
		require_once './App/Task/curl.php';
	}else{
		//worker进程
		echo $worker_id.' worker is running...'.PHP_EOL;
		//加载业务控制器
        App::load();
	}
	
});

$serv->start();
