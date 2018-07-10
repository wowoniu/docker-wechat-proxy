<?php

class WechatMockService{

    /*
     *使用严格遵守微信公众平台参数配置http://mp.weixin.qq.com/wiki/index.php?title=消息接口指南
     *如果是text或者image类型就直接输入$content
     *其他的就输入array 譬如地理位置输入
     *<Location_X>23.134521</Location_X>
     *<Location_Y>113.358803</Location_Y>
     *   <Scale>20</Scale>
     * <Label><![CDATA[位置信息]]></Label>
     * array('1.29290','12.0998','20','位置信息');
     *
     */

    /**
     * @param $event 推送类型 text:文本 image:图片 link:链接 location:定位
     * @param $url
     * @param $content
     */
    public static function send($event,$url,$content){
        $str="";
        $xmlData=self::create_xml_data($event,$content);
        return $xmlData;
        $postObj = simplexml_load_string(self::post($url,$xmlData), 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ((array)$postObj as $key => $value) {
            $str.=$key.'=>'.$value."<br>";
        }
        return $str;
    }



    //处理成xml数据
    private  static function create_xml_data($event,$content){
        $detail=self::judgment($event,$content);
        $str = "
            <xml>
                 <ToUserName>100012</ToUserName>
                 <FromUserName>100012</FromUserName>
                 <CreateTime>".time()."</CreateTime>
                 <MsgType>{$event}</MsgType>
                 {$detail}
                 <MsgId>1234567890123456</MsgId>
            </xml>
         ";
        return $str;
    }


    //模拟post提交
    private static function post($url,$xmlData){
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


    //文本消息
    private static function text($content){
        return  "<Content>{$content}</Content>";
    }


    //图形消息
    private static function image($content){
        return "<PicUrl>{$content}</PicUrl>";
    }


    //链接消息
    private  static function link($content=array()){
        $data = $content;
        $str = "
            <Title>{$data[0]}</Title>
            <Description>{$data[1]}</Description>
            <Url>{$data[2]}</Url>
        ";
        return $str;
    }


    //地理位置消息
    private static function location($content=array()){
        $data = $content;
        $str = "
            <Location_X>{$data[0]}</Location_X>
            <Location_Y>{$data[1]}</Location_Y>
            <Scale>20</Scale>
            <Label>{$data[3]}</Label>";
        return $str;
    }


    //根据消息类型加载相应的东西
    private static function judgment($event,$content){
        $type = $event;
        return self::$type($content);
    }

}