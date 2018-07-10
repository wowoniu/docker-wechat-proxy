<?php

require  './init.php';

$remoteServer="www.remotexxx.com";
$remoteIp="80";

$localServer="http://local.xxx.com/wxapi";


//websocket 客户端连接域名需要先解析DNS
Swoole\Async::dnsLookup($remoteServer, function ($domainName, $ip)use($remoteIp,$localServer) {
    listen($ip,$remoteIp,$localServer);
});


//listen("172.32.1.106",'9566');

//客户端建立连接并监听消息
function listen($ip,$port,$localServer){
    print_r("连接:$ip".PHP_EOL);
    $cli = new swoole_http_client($ip, $port);
    $cli->setHeaders(['Trace-Id' => md5(time()),]);



    $cli->on('message', function ($_cli, $frame)use($localServer) {
        $data=json_decode($frame->data,true);
        $url=$localServer;
        //转发
        $get_data=$data['get'];
        if($get_data){
            if(strpos($url,'?')!==false){
                $url=$url.'&'.http_build_query($get_data);
            }else{
                $url=$url.'?'.http_build_query($get_data);
            }
        }
        $response = postXml($url, $data['data']);

        print_r("-------------------------------------------->".PHP_EOL);
        print_r("[C转发]$url" . PHP_EOL);
        print_r("[C数据]".json_encode(json_decode($frame->data,true),JSON_PRETTY_PRINT).PHP_EOL);
        print_r("[C响应]".var_export($response,true)."" . PHP_EOL);
        $_cli->push(json_encode(['id'=>$data['id'],'response'=>$response]));
        print_r("-------------------------------------------->".PHP_EOL.PHP_EOL.PHP_EOL);

    });
    $cli->upgrade('/', function ($cli) {
        //echo $cli->body;
        echo "连接建立" . PHP_EOL;
    });
}


//转发XML数据
function postXml($url,$xmlData){
    $header[] = "Content-type: text/xml";//定义content-type为xml
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url);//设置链接
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置HTTP头
    curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);//POST数据
    $response = curl_exec($ch);//接收返回信息
    if(curl_errno($ch)){//出错则显示错误信息
        print curl_error($ch);
    }
    curl_close($ch); //关闭curl链接
    return $response;

}