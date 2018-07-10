<?php

class WechatService{


    //接入校验
    public static function checkToken(){
        // 接入网址
        $signature = $_GET['signature'];
        $nonce = $_GET['nonce'];
        $timestamp = $_GET['timestamp'];
        $echostr = $_GET['echostr'];
        $token = "amyaao1429176721";
        $arr = array($token,$nonce,$timestamp);
        sort($arr);
        $resultStr = sha1(join($arr));
        if ($resultStr == $signature) {
            return $echostr;
        }

        // 接收数据

        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        $xmlObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        $xmlArr = json_decode(json_encode($xmlObj), true);

        include "./session.php";

        if ($xmlArr['MsgType'] == "text") {

            if (! empty($xmlArr['Content'])) {

                if ($xmlArr['Content'] == "新闻") { // 如果发送的内容为新闻的话

                    $_SESSION['status'] = 1;

                    $reply = "请选择以下新闻地区分类：\n1---国内新闻\n2---国际新闻";

                    echo send($reply);

                    exit();
                }
                if ($_SESSION['status'] == 1) {

                    if (empty($_SESSION['one']) && empty($_SESSION['two'])) {

                        if ($xmlArr['Content'] == "*") {

                            session_destroy();

                            $reply = "已经退出新闻！";

                            echo send($reply);

                            exit();
                        }
                    }

                    if (empty($_SESSION['one'])) {

                        if ($xmlArr['Content'] == '1') { // 如果发送的内容是1的话

                            $_SESSION['one'] = 1;

                            $reply = "请接着选择新闻的类别：\n1---娱乐新闻\n2---体育新闻\n3---政坛新闻\n*---返回上一级";

                            echo send($reply);

                            exit();
                        }
                    } else {

                        if (! empty($_SESSION['two'])) {

                            if ($xmlArr['Content'] == "*") {

                                unset($_SESSION['two']);

                                $reply = "请接着选择新闻的类别：\n1---娱乐新闻\n2---体育新闻\n3---政坛新闻\n*---返回上一级";

                                echo send($reply);

                                exit();
                            }
                        }
                    }

                    if (! empty($_SESSION['one']) && empty($_SESSION['two'])) {

                        if ($xmlArr['Content'] == 1) {

                            $_SESSION['two'] = 1;

                            $reply = "显示娱乐新闻！\n输入*返回上一级";

                            echo send($reply);

                            exit();
                        }

                        if ($xmlArr['Content'] == 2) {

                            $_SESSION['two'] = 2;

                            $reply = "显示体育新闻！\n输入*返回上一级";

                            echo send($reply);

                            exit();
                        }

                        if ($xmlArr['Content'] == 3) {

                            $_SESSION['two'] = 3;

                            $reply = "显示政坛新闻！\n输入*返回上一级";

                            echo send($reply);

                            exit();
                        }
                    } else {

                        if ($xmlArr['Content'] == '*') {

                            unset($_SESSION['two']);

                            $reply = "请接着选择新闻的类别：\n1---娱乐新闻\n2---体育新闻\n3---政坛新闻";

                            echo send($reply);

                            exit();
                        }
                    }
                }
            }
        }


    }

    function send ($reply)
    {
        global $xmlArr;
        $str = "<xml>
				 <ToUserName><![CDATA[" . $xmlArr['FromUserName'] . "]]></ToUserName>
				 <FromUserName><![CDATA[" . $xmlArr['ToUserName'] . "]]></FromUserName>
				 <CreateTime>" . $xmlArr['CreateTime'] . "</CreateTime>
				 <MsgType><![CDATA[text]]></MsgType>
				 <Content><![CDATA[" . $reply . "]]></Content>
				</xml>";
        return $str;
    }


}