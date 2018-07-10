<?php 

class App{


    //加载依赖
    static public function load(){
        self::loadControllers();
        self::loadSerices();
    }


	//处理请求 分发至相应控制器
	static public function loadControllers(){
        $controllersPath = "./App/Controllers/*";

        $fileList = glob($controllersPath);

        foreach($fileList as $file){
            require_once $file;
        }
	}

	//加载业务服务类
	static public function loadSerices(){
        $controllersPath = "./App/Service/*";

        $fileList = glob($controllersPath);

        foreach($fileList as $file){
            require_once $file;
        }
    }

    //默认错误处理
    static public function errorHandle(){
        register_shutdown_function(function(){
            $message="";
            if ($error = error_get_last()) {
                //程序报错处理，通常会跳转到用户自定义的页面，同时记录错误信息
                $separator = PHP_EOL;
                $message .= "错误:" . $error['message'] . $separator;
                $message .= "文件:" . $error['file'] . $separator;
                $message .= "行数:" . $error['line'] . $separator;
                print_r($message);
            }
        });
    }



}