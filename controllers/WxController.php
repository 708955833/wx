<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class WxController extends Controller
{
    public $token ='wxx';
    public $enableCsrfValidation = false;
    public function actionIndex()
    {
        $echoStr = isset($_GET["echostr"])?$_GET["echostr"]:'';

        Yii::$app->db->createCommand("insert into datas(`data`,`name`) values('$echoStr','index')")->execute();

        if($this->checkSignature() && $echoStr){
            echo $echoStr;
            exit;
        }else{
            $this->reponseMsg();
        }

    }
    public function actionAll()
    {
        $data = Yii::$app->db->createCommand("select * from datas")->queryAll();
        echo 'sf';
        print_r($data);
    }
    // 接收事件推送并回复
    public function reponseMsg()
    {
        //1.获取到微信推送过来post数据（xml格式）
        $postArr = isset($GLOBALS['HTTP_RAW_POST_DATA'])?$GLOBALS['HTTP_RAW_POST_DATA']:'';
        //2.处理消息类型，并设置回复类型和内容
        $ser ='111'.serialize($postArr);
        Yii::$app->db->createCommand("insert into datas(`data`) values('$ser')")->execute();
        if($postArr) {
            $postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
//            $postObj->ToUserName = '';
//            $postObj->FromUserName = '';
//            $postObj->CreateTime = '';
//            $postObj->MsgType = '';
//            $postObj->Event = '';
            // gh_e79a177814ed
            //判断该数据包是否是订阅的事件推送
                    if (strtolower($postObj->MsgType) == 'event') {
                        //如果是关注 subscribe 事件
                        if (strtolower($postObj->Event == 'subscribe')) {
                            //回复用户消息(纯文本格式)
                            $toUser = $postObj->FromUserName;
                            $fromUser = $postObj->ToUserName;
                            $time = time();
                            $msgType = 'text';
                            $content = '1111欢迎关注我们的微信公众账号' . $postObj->FromUserName . '-' . $postObj->ToUserName;
                            $template = "<xml>
                                        <ToUserName><![CDATA[%s]]></ToUserName>
                                        <FromUserName><![CDATA[%s]]></FromUserName>
                                        <CreateTime>%s</CreateTime>
                                        <MsgType><![CDATA[%s]]></MsgType>
                                        <Content><![CDATA[%s]]></Content>
                                        </xml>";
                            $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                            echo $info;
                            exit;
                        }
                    }


//用户发送tuwen1关键字的时候，回复一个单图文
            if (strtolower($postObj->MsgType) == 'text' && trim($postObj->Content) == '我要支付') {
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $arr = array(
                    array(
                        'title' => '请点击支付',
                        'description' => "pay",
                        'picUrl' => 'http://123.206.6.227/pay.png',
                        'url' => 'http://123.206.6.227/pay.png',
                    )
                );
                $template = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<ArticleCount>" . count($arr) . "</ArticleCount>
						<Articles>";
                foreach ($arr as $k => $v) {
                    $template .= "<item>
							<Title><![CDATA[" . $v['title'] . "]]></Title> 
							<Description><![CDATA[" . $v['description'] . "]]></Description>
							<PicUrl><![CDATA[" . $v['picUrl'] . "]]></PicUrl>
							<Url><![CDATA[" . $v['url'] . "]]></Url>
							</item>";
                }

                $template .= "</Articles>
						</xml> ";
                ob_clean();
                echo sprintf($template, $toUser, $fromUser, time(), 'news');

                //注意：进行多图文发送时，子图文个数不能超过10个
            }
            if (strtolower($postObj->MsgType) == 'text' && trim($postObj->Content) == '我要支付') {
                $content = '<image src="http://123.206.6.227/xiu.png"/>';
                $template = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[%s]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
//注意模板中的中括号 不能少 也不能多
                $fromUser = $postObj->ToUserName;
                $toUser = $postObj->FromUserName;
                $time = time();
                // $content  = '18723180099';
                $msgType = 'text';
                ob_clean();
                echo sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
            }
            /*else {
                switch (trim($postObj->Content)) {
                    case 1:
                        $content = '您输入的数字是1';
                        break;
                    case 2:
                        $content = '您输入的数字是2';
                        break;
                    case 3:
                        $content = '您输入的数字是3';
                        break;
                    case 4:
                        $content = "<a href='http://www.imooc.com'>慕课</a>";
                        break;
                    case '英文':
                        $content = 'imooc is ok';
                        break;
                }
                $template = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[%s]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
//注意模板中的中括号 不能少 也不能多
                $fromUser = $postObj->ToUserName;
                $toUser = $postObj->FromUserName;
                $time = time();
                // $content  = '18723180099';
                $msgType = 'text';
                ob_clean();
                echo sprintf($template, $toUser, $fromUser, $time, $msgType, $content);

            }*/
        }

    }
    private function checkSignature()
    {
        $signature = isset($_GET["signature"])?$_GET["signature"]:'';
        $timestamp = isset($_GET["timestamp"])?$_GET["timestamp"]:'';
        $nonce = isset($_GET["nonce"])?$_GET["nonce"]:'';

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}
