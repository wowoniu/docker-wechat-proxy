<?php

/**
 * Class Wechatproxy
 * 微信转发服务器
 */
class WechatproxyController extends Controller {

    //微信请求转发至本地 请求下行
    public function tolocalAction(){
        $context=$this;
        print_r("-------------------------------------------->".PHP_EOL);
        if($this->request->get['echostr']){
            //服务器初次与微信服务器通信验证 暂不做签名验证
            print_r("[S转发]服务器握手".PHP_EOL);
            $this->response->end($this->request->get['echostr']);
        }
        $requestId=date('YmdHis');
        print_r("[S转发]$requestId".PHP_EOL);
        //解析数据
        $xmlData=$this->request->rawContent();
        //锁
        UtilService::setAsyncLock($requestId);
        //转发推送
        $proxyData=[
            'id'=>$requestId,
            'data'=>$xmlData,
            'get'=>$this->request->get
        ];
        print_r("[S数据]".json_encode($proxyData,JSON_PRETTY_PRINT).PHP_EOL);

        //推送到需要转发的客户端上 todo 多用户支持
        if(!WebsocketService::broadCast($this->serv,json_encode($proxyData))){
            print_r("[S转发失败]无可用的客户端连接".PHP_EOL);
            print_r("-------------------------------------------->".PHP_EOL.PHP_EOL.PHP_EOL);
            $this->response->end("");
        }
        $count=0;
        while(UtilService::asyncLocked($requestId)||$count<=10){
            Swoole\Coroutine::sleep(1);
            $count++;
        }
        //异步获取文件缓存
        $response=UtilService::cache($requestId);
        print_r("[S响应]".var_export($response,true)."" . PHP_EOL);
        print_r("-------------------------------------------->".PHP_EOL.PHP_EOL.PHP_EOL);
        $this->response->end($response);
    }

    //本地请求转发至微信服务器 请求上行
    public function toremoteAction(){

    }

    //模拟微信事件推送
    public function mockAction(){
        $mockUrl="http://swoole_proxy.ie:9566/wechatproxy/tolocal";
       $response= WechatMockService::send('text',$mockUrl,"TEST");
       $this->response->end(var_export($response,true));
    }

}

?>
