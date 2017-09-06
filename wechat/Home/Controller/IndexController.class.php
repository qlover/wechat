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







    // 消息接口
    public function responseMsg(){
		// 1.获取微信推送来的原生 POST 数据，当然此时只是一个串
		// 并不能直接用，而是需要下一步来解析
		// 这一步很重要，因为只有从微信服务器获取的推送数据才能对开发
		$postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
		
		// 2.处理获取到的  XML 格式数据,得到推送数据对象
		$postObj = simplexml_load_string( $postArr );

		// 3.再做每件事之前要确认好两端的身份,切记是相反的
		// 发送方=微信推送接收方
		$fromUser = $postObj->ToUserName;
		// 接收方=微信推送发送方
		$toUser = $postObj->FromUserName;

		// 事件推送
		// 得到解析后的 XML 数据中的 MsgType 属性来判断否是事件推送
		if ( $postObj->MsgType == 'event') {
			
			// 如果是则判断是什么样的事件推送
			// 而用来判断则是刚才判断是否有的 Event 事件
			

			// 关注事件 # subscribe(订阅)、unsubscribe(取消订阅)

			if ( $postObj->Event == 'subscribe') {
				// 知道了是关注事件推送就可以按 SDK 中的功能为用户提供信息
				// 这里也需特别注意，因为上面已经将身份交换
				
				// 因为是做成了 SDK ，所以内容在此定义
				$content = "谢谢你的关注，我是 Qlover";
				$content .= "\n你可以输入【hello】或者是【你好】\n还可以输入【news】来获取最热新闻。";
				$this->pushText($toUser, $fromUser, $content);
			}
		}




		// 被动回复
		
		// 文本
		if ( $postObj->MsgType == 'text' ) {
			// 1.获取用户输入的关键字
			$postCont = trim($postObj->Content);

			// !! 图文消息比关键字消息先判断
			if ( $postCont == 'news' ) {
				// $this->pushText($toUser, $fromUser, '是新闻吗');
				$this->pushArts($toUser, $fromUser);
			}
			// 关键字判断 
			else{
				// 筛选关键字列表
				switch( $postCont ){
					case 'hello' : $content = 'hello!'; break;
					case '你好' : $content = '你好!'; break;
					default : $content = '对不起，没有这个关键字'; break;
				}
				// 最后确定了回复内容就推送
				$this->pushText($toUser, $fromUser, $content );
			}
		}
		// 语音
		elseif($postObj->MsgType == 'voice') {
			$this->pushText($toUser, $fromUser, '你在说什么，我听不懂' );
		}
		// 其它
		else{
			$this->pushText($toUser, $fromUser, '这里什么？' );	
		}



	}




















	/**
	 * SDK 类
	 * 目前将接口实现写在这里，因为不知道什么原因
	 * TP 结构中这里面访问不到模型文件
    */
   	


	/* 资源类 */

	// 定义静态的文本消息模板
	private static $templates = array(
		'text' => "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>",
		'art' => "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>12345678</CreateTime>
<MsgType><![CDATA[%s]]></MsgType>
<Image>
<MediaId><![CDATA[%s]]></MediaId>
</Image>
</xml>",
	);	


	// 定义图文资源
	private static $arts = array(
		array(
			'title'=>'click here open sina page. ', // 标题
			'description'=>"新浪很棒！", // 描述 
			'picUrl'=>'http://i1.sinaimg.cn/dy/deco/2013/0329/logo/LOGO_1x.png', // 图片地址
			'url'=>'http://www.sina.com', // 可打开的页面地址
		),
		// 一个则是单个图文
		array(
			'title'=>'click here open baidu page.',
			'description'=>"baidu is very cool",
			'picUrl'=>'https://www.baidu.com/img/bdlogo.png',
			'url'=>'http://www.baidu.com',
		),
		array(
			'title'=>'click here open qq page.',
			'description'=>"qq is very cool",
			'picUrl'=>'http://mat1.gtimg.com/www/images/qq2012/qqlogo_1x.png',
			'url'=>'http://www.qq.com',
		),
	);







	/* 消息类 */

	// 推送文本消息
	public function pushText($to, $from, $content ){
		/* 
		  这个方法功能很简单，步骤主要有三步
		  1.得到文本消息的数据包
		  2.文本内容为开发者自定义
		  3.将已知的信息按照顺序依次替换数据包模板中的数据
		 	这里一个提示，PHP 的 sprintf() 可以实现
		*/
	

		// 1.得到文本数据模板
		$tpl = IndexController::$templates['text'];
		// 2.定义文本内容，定义的文本内容在调用该方法处被传入

		// 3.替换数据
		// 特别注意传入的双方身份
		echo sprintf($tpl, $to, $from, time(), $content);


	}

	// 推送图文消息
	public function pushArts($to, $from){
		// 1.获取到图文的资源，该资源可以是开发自定义
		$arr = IndexController::$arts;
		// 2.得到图文消息的数据包
		$tpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<ArticleCount>".count($arr)."</ArticleCount>
					<Articles>";
		// 循环添加多个项目,单个图文就循环一次
		// 目前这样解决单个和多个
		foreach($arr as $k=>$v){
			$tpl .="<item>
						<Title><![CDATA[".$v['title']."]]></Title> 
						<Description><![CDATA[".$v['description']."]]></Description>
						<PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
						<Url><![CDATA[".$v['url']."]]></Url>
						</item>";
		}
		$tpl .="</Articles></xml>";
		// 3.推送
		// 这里后面的几个参数是为了对应 $tpl 前面的几个 %s 数据
		echo sprintf($tpl, $to, $from, time(), 'news');

	}

}