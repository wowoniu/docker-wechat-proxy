<?php 

class Request{
	//处理请求 分发至相应控制器
	static public function run($request,$response,$serv){
		$request_uri=$request->server['request_uri'];
		if($request_uri=='/favicon.ico'){
			return;
		}
		$routeInfo=Router::parse($request);
		if(!$routeInfo){
			throw new exception("invalid request");
		}
		$controller=$routeInfo['controller'];
		$action	=$routeInfo['action'].'Action';
		$class=ucwords($controller).'Controller';
		//控制器不存在
		if(!class_exists($class)){
			throw new exception("invalid controller:".$class);
		}
		$obj=new $class($request,$response,$serv);
                
		//action不存在
		if(!method_exists($obj,$action)){
			throw new exception("invalid action");
		}
		//控制器执行
		call_user_func_array(array($obj,$action),array());
	}

}