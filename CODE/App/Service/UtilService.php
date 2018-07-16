<?php


class UtilService{


    //设置异步锁
    static public function setAsyncLock($lockId){
        if(!is_dir('./App/Cache')){
            mkdir('./App/Cache',0777);
        }
        $lockFile="./App/Cache/".$lockId.".lock";
        file_put_contents($lockFile,'1');
        sleep(1);
    }

    //清除异步锁
    static public function clearAsyncLock($lockId){
        $lockFile="./App/Cache/".$lockId.".lock";
        $res=unlink($lockFile);
        if(!$res){
            print_r("锁文件删除失败" . PHP_EOL);
        }
    }

    //异步锁(通过文件进行锁处理)
    static public function asyncLocked($lockId){
        $lockFile="./App/Cache/".$lockId.".lock";
        if(!file_exists($lockFile)){
            return false;
        }
        $lockCon=file_get_contents($lockFile);
        if($lockCon=='1'){
            //锁定状态
            return true;
        }
        return false;
    }

    //数据缓存
    static public function cache($cacheId,$value=null,$writeCallack=null){
        $cacheFile="./App/Cache/".$cacheId.".cache";
        if(is_null($value)){
            //async get
//            swoole_async_readfile($cacheFile, function($filename, $content)use($callback) {
//                if(!is_null($callback)){
//                    call_user_func_array($callback, array($filename,$content));
//                }
//            });
            return file_get_contents($cacheFile);
        }else{
            //async write
            swoole_async_writefile($cacheFile, $value, function($filename)use($writeCallack) {
                if(!is_null($writeCallack)){
                    call_user_func($writeCallack);
                }
            }, $flags = 0);
        }
    }
}