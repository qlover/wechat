<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller {
    
	// 微信首页
    public function index(){
        $this->show('欢迎来到微信世界','utf-8');
    }

    /**
	 * 微信接口与 BAE 接入
	 * 
	 * 接口路径 
	 * http://qloverwechat.duapp.com/wechat/index.php/Home/Index/api
	 */
    public function api(){
    	// 1.获取从微信服务器发送的 GET 参数
    	$signature = $_GET['signature'];
    	$timestamp = $_GET['timestamp'];
    	$nonce = $_GET['nonce'];
    	$echostr = $_GET['echostr'];
    	// 设置与微信服务器连接的 token
    	$token = 'weixintp';
    	// 2.token、timestamp、nonce三个参数进行字典序排序
    	$args = array($timestamp, $nonce, $token);
    	sort($args);
    	// 3. 将排序后的参数拼接成字符串,并用 sha1 加密 
    	$str = sha1( implode($args) );
    	// 4.与 signature 进行校验，标识该请求来源于微信
    	// 当然这也是每一次与微信进行连接时都会进行的过程
    	// 如果微信已经被关注当然就不会进行连接，而直接进入消息推送事件
    	// 也就是下面的 esle
    	if ( $str == $signature && $echostr ) {
    		/*当然也要判断 echostr 是否有效*/
    		echo $echostr;  // 向微信服务器返回消息
    		exit;
    	}else{
    		// 如果不是第一次关注就返回消息接口 responseMsg() 
    		// 因为是用 TP 做的，所以要把这些方法自己再封装下做个 SDK
    		$this->responseMsg();


    	}
    }



    /* 
  		目前将接口实现写在这里，因为不知道什么原因，TP 结构中这里面访问不到
    	模型文件
    */
   
	// 定义静态的文本消息模板
	private static $templates = array(
		'text' => "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>",
		'art' => "",
	);

    // 消息接口
    public function responseMsg(){
		$postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
		if ( !$postArr ) {
			echo '暂无内容';
		}
		
		// 2.处理获取到的  XML 格式数据 
		$postObj 	= simplexml_load_string( $postArr );

		// 再做每件事之前要确认好两端的身份,切记是相反的
		// 发送方
		$fromUser 	= $postObj->ToUserName;
		// 接收方
		$toUser 	= $postObj->FromUserName;

		// 事件推送
		if ( $postObj->MsgType == 'event' ) {

			// 自定义菜单中的点击推送事件
			if ( $postObj->Event == 'click') {
				// postObj 下的 Event 下的 EventKey 可以得到自定义菜单的 key 值
				$tpl = IndexController::$templates['text'];
				$content = '菜单事件推送';
				echo sprintf($tpl, $toUser, $fromUser, time(), $content );

				
				
			}

			// 关注事件 subscribe
			if ( $postObj->Event == 'subscribe' ) {
				// 数据包模板
				$tpl = IndexController::$templates['text'];
				
				// 取得数据包中的信息
				$content  = "欢迎关注我，我是 Qlover。";
				$content .= "\n你可以输入【hello】或者是【你好】\n还可以输入【news】。";
				// 输出连接串
				echo sprintf($tpl, $toUser, $fromUser, time(), $content );

			}

			// 自定义菜单中的跳转推送事件
			// 但是需要注意的是，因为页面已经跳转了
			// 所以并不会有推送内容
			// elseif ( $postObj->Event == 'view') { }

			// 取消事件
			if ( $postObj->Event == 'unsubscribe' ) {
				echo 'unsubscribe (取消事件被触发)';
				exit;
			}
		}



	}

}