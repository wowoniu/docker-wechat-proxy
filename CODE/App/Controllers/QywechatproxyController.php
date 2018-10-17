<?php

/**
 * Class Wechatproxy
 * 企业微信转发服务器
 */
class QywechatproxyController extends Controller {

    //微信请求转发至本地 请求下行
    public function tolocalAction(){
        $start=time();
        $context=$this;
        print_r("-------------------------------------------->".PHP_EOL);
        if($this->request->get['echostr']){
            //服务器初次与微信服务器通信验证 暂不做签名验证
            print_r("[S转发]服务器握手".PHP_EOL);
            $this->response->end($this->checkEncrypt());
        }else{
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

            while(UtilService::asyncLocked($requestId)&&$count<=3){
                print_r("[S]第{$count}次循环".PHP_EOL);
                Swoole\Coroutine::sleep(1);
                $count++;
            }
            //异步获取文件缓存
            $response=UtilService::cache($requestId);
            print_r("[S响应]".var_export($response,true)."" . PHP_EOL);
            $end=time();
            print_r("[S结束]耗时：".($end-$start)."秒" . PHP_EOL);
            print_r("-------------------------------------------->".PHP_EOL.PHP_EOL.PHP_EOL);
            if($response){
                //正常获取响应
                $this->response->end($response);
            }else{
                $this->response->end("success");
            }
        }
    }

    //本地请求转发至微信服务器 请求上行
    public function toremoteAction(){

    }

    //加密签名校验
    public function checkEncrypt()
    {
        $token="7ZaERvIpwMYClfAT00UvmqgK8CW";
        $encodingAesKey="rh4pgX2x7Rx6mX8micd53LRXKkI4sPPJUyw4tohJMHd";
        $corpId="ww4b8172c0439ea4a4";
        $sVerifyMsgSig = $this->request->get['msg_signature'];
        $sVerifyTimeStamp = $this->request->get["timestamp"];
        $sVerifyNonce = $this->request->get['nonce'];
        $sVerifyEchoStr = $this->request->get['echostr'];
        // 需要返回的明文
        $sEchoStr = "";
        $wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, $corpId);
        $errCode = $wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
        if ($errCode == 0) {
            return $sEchoStr;
        } else {
            return false;
        }
    }

    //模拟微信事件推送
    public function mockAction(){
        $mockUrl="http://swoole_proxy.ie:9566/wechatproxy/tolocal";
       $response= WechatMockService::send('text',$mockUrl,"TEST");
       $this->response->end(var_export($response,true));
    }

}









class WXBizMsgCrypt
{
    private $m_sToken;
    private $m_sEncodingAesKey;
    private $m_sReceiveId;
    /**
     * 构造函数
     * @param $token string 开发者设置的token
     * @param $encodingAesKey string 开发者设置的EncodingAESKey
     * @param $receiveId string, 不同应用场景传不同的id
     */
    public function __construct($token, $encodingAesKey, $receiveId)
    {
        $this->m_sToken = $token;
        $this->m_sEncodingAesKey = $encodingAesKey;
        $this->m_sReceiveId = $receiveId;
    }

    /*
	*验证URL
    *@param sMsgSignature: 签名串，对应URL参数的msg_signature
    *@param sTimeStamp: 时间戳，对应URL参数的timestamp
    *@param sNonce: 随机串，对应URL参数的nonce
    *@param sEchoStr: 随机串，对应URL参数的echostr
    *@param sReplyEchoStr: 解密之后的echostr，当return返回0时有效
    *@return：成功0，失败返回对应的错误码
	*/
    public function VerifyURL($sMsgSignature, $sTimeStamp, $sNonce, $sEchoStr, &$sReplyEchoStr)
    {
        if (strlen($this->m_sEncodingAesKey) != 43) {
            return ErrorCode::$IllegalAesKey;
        }
        $pc = new Prpcrypt($this->m_sEncodingAesKey);
        //verify msg_signature
        $sha1 = new SHA1;
        $array = $sha1->getSHA1($this->m_sToken, $sTimeStamp, $sNonce, $sEchoStr);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        if ($signature != $sMsgSignature) {
            return ErrorCode::$ValidateSignatureError;
        }
        $result = $pc->decrypt($sEchoStr, $this->m_sReceiveId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $sReplyEchoStr = $result[1];
        return ErrorCode::$OK;
    }
    /**
     * 将公众平台回复用户的消息加密打包.
     * <ol>
     *    <li>对要发送的消息进行AES-CBC加密</li>
     *    <li>生成安全签名</li>
     *    <li>将消息密文和安全签名打包成xml格式</li>
     * </ol>
     *
     * @param $replyMsg string 公众平台待回复用户的消息，xml格式的字符串
     * @param $timeStamp string 时间戳，可以自己生成，也可以用URL参数的timestamp
     * @param $nonce string 随机串，可以自己生成，也可以用URL参数的nonce
     * @param &$encryptMsg string 加密后的可以直接回复用户的密文，包括msg_signature, timestamp, nonce, encrypt的xml格式的字符串,
     *                      当return返回0时有效
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function EncryptMsg($sReplyMsg, $sTimeStamp, $sNonce, &$sEncryptMsg)
    {
        $pc = new Prpcrypt($this->m_sEncodingAesKey);
        //加密
        $array = $pc->encrypt($sReplyMsg, $this->m_sReceiveId);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        if ($sTimeStamp == null) {
            $sTimeStamp = time();
        }
        $encrypt = $array[1];
        //生成安全签名
        $sha1 = new SHA1;
        $array = $sha1->getSHA1($this->m_sToken, $sTimeStamp, $sNonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        //生成发送的xml
        $xmlparse = new XMLParse;
        $sEncryptMsg = $xmlparse->generate($encrypt, $signature, $sTimeStamp, $sNonce);
        return ErrorCode::$OK;
    }
    /**
     * 检验消息的真实性，并且获取解密后的明文.
     * <ol>
     *    <li>利用收到的密文生成安全签名，进行签名验证</li>
     *    <li>若验证通过，则提取xml中的加密消息</li>
     *    <li>对消息进行解密</li>
     * </ol>
     *
     * @param $msgSignature string 签名串，对应URL参数的msg_signature
     * @param $timestamp string 时间戳 对应URL参数的timestamp
     * @param $nonce string 随机串，对应URL参数的nonce
     * @param $postData string 密文，对应POST请求的数据
     * @param &$msg string 解密后的原文，当return返回0时有效
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function DecryptMsg($sMsgSignature, $sTimeStamp = null, $sNonce, $sPostData, &$sMsg)
    {
        if (strlen($this->m_sEncodingAesKey) != 43) {
            return ErrorCode::$IllegalAesKey;
        }
        $pc = new Prpcrypt($this->m_sEncodingAesKey);
        //提取密文
        $xmlparse = new XMLParse;
        $array = $xmlparse->extract($sPostData);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        if ($sTimeStamp == null) {
            $sTimeStamp = time();
        }
        $encrypt = $array[1];
        //验证安全签名
        $sha1 = new SHA1;
        $array = $sha1->getSHA1($this->m_sToken, $sTimeStamp, $sNonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        if ($signature != $sMsgSignature) {
            return ErrorCode::$ValidateSignatureError;
        }
        $result = $pc->decrypt($encrypt, $this->m_sReceiveId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $sMsg = $result[1];
        return ErrorCode::$OK;
    }
}
class ErrorCode
{
    public static $OK = 0;
    public static $ValidateSignatureError = -40001;
    public static $ParseXmlError = -40002;
    public static $ComputeSignatureError = -40003;
    public static $IllegalAesKey = -40004;
    public static $ValidateCorpidError = -40005;
    public static $EncryptAESError = -40006;
    public static $DecryptAESError = -40007;
    public static $IllegalBuffer = -40008;
    public static $EncodeBase64Error = -40009;
    public static $DecodeBase64Error = -40010;
    public static $GenReturnXmlError = -40011;
}


class PKCS7Encoder
{
    public static $block_size = 32;
    /**
     * 对需要加密的明文进行填充补位
     * @param $text 需要进行填充补位操作的明文
     * @return 补齐明文字符串
     */
    function encode($text)
    {
        $block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder::block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }
    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    function decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > PKCS7Encoder::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
}
/**
 * Prpcrypt class
 *
 * 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt
{
    public $key = null;
    public $iv = null;
    /**
     * Prpcrypt constructor.
     * @param $k
     */
    public function __construct($k)
    {
        $this->key = base64_decode($k . '=');
        $this->iv  = substr($this->key, 0, 16);
    }
    /**
     * 加密
     *
     * @param $text
     * @param $receiveId
     * @return array
     */
    public function encrypt($text, $receiveId)
    {
        try {
            //拼接
            $text = $this->getRandomStr() . pack('N', strlen($text)) . $text . $receiveId;
            //添加PKCS#7填充
            $pkc_encoder = new PKCS7Encoder;
            $text        = $pkc_encoder->encode($text);
            //加密
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
            return [ErrorCode::$OK, $encrypted];
        } catch (Exception $e) {
            print $e;
            return [MyErrorCode::$EncryptAESError, null];
        }
    }
    /**
     * 解密
     *
     * @param $encrypted
     * @param $receiveId
     * @return array
     */
    public function decrypt($encrypted, $receiveId)
    {
        try {
            //解密
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
        } catch (Exception $e) {
            return [ErrorCode::$DecryptAESError, null];
        }
        try {
            //删除PKCS#7填充
            $pkc_encoder = new PKCS7Encoder;
            $result      = $pkc_encoder->decode($decrypted);
            if (strlen($result) < 16) {
                return [];
            }
            //拆分
            $content     = substr($result, 16, strlen($result));
            $len_list    = unpack('N', substr($content, 0, 4));
            $xml_len     = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_receiveId = substr($content, $xml_len + 4);
        } catch (Exception $e) {
            print $e;
            return [ErrorCode::$IllegalBuffer, null];
        }
        if ($from_receiveId != $receiveId) {
            return [ErrorCode::$ValidateCorpidError, null];
        }
        return [0, $xml_content];
    }
    /**
     * 生成随机字符串
     *
     * @return string
     */
    private function getRandomStr()
    {
        $str     = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max     = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}
class SHA1
{
    /**
     * 用SHA1算法生成安全签名
     * @param string $token 票据
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @param string $encrypt 密文消息
     */
    public function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
    {
        //排序
        try {
            $array = array($encrypt_msg, $token, $timestamp, $nonce);
            sort($array, SORT_STRING);
            $str = implode($array);
            return array(ErrorCode::$OK, sha1($str));
        } catch (Exception $e) {
            print $e . "\n";
            return array(ErrorCode::$ComputeSignatureError, null);
        }
    }
}

class XMLParse
{
    /**
     * 提取出xml数据包中的加密消息
     * @param string $xmltext 待提取的xml字符串
     * @return string 提取出的加密消息字符串
     */
    public function extract($xmltext)
    {
        try {
            $xml = new DOMDocument();
            $xml->loadXML($xmltext);
            $array_e = $xml->getElementsByTagName('Encrypt');
            $encrypt = $array_e->item(0)->nodeValue;
            return array(0, $encrypt);
        } catch (Exception $e) {
            print $e . "\n";
            return array(ErrorCode::$ParseXmlError, null);
        }
    }
    /**
     * 生成xml消息
     * @param string $encrypt 加密后的消息密文
     * @param string $signature 安全签名
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     */
    public function generate($encrypt, $signature, $timestamp, $nonce)
    {
        $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }
}


?>
