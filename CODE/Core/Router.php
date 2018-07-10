<?php 

class Router{
	static $default_controller	='index';
	static $default_action		='index';
	static public function parse($request){
		$request_uri=$request->server['request_uri'];
		$routeInfo=array();
		if($request_uri=='/favicon.ico'){
			return false;
		}else{
			$requestInfo=explode("/",trim($request_uri,'/'));
			$routeInfo=array(
				'controller'=>self::$default_controller,
				'action'	=>self::$default_action
			);
			if($requestInfo){
                    while(($param=current($requestInfo))&&current($routeInfo)){
                            $routeInfo[key($routeInfo)]=$param;
                            next($routeInfo);
                            next($requestInfo);
                    }
            }
			return $routeInfo;
		}		
	}

}