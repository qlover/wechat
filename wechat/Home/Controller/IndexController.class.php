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
			// 自定义菜单事件推送
			// 从开发者文档中可知道，此时 Event 返回的是大写的 CLICK 
			// 所以不能直接写 click 判断
			elseif ( strtolower($postObj->Event) == 'click') {
				$this->pushText($toUser, $fromUser, '点击了项目');
			}
			// 其它
			else{
				$this->pushText($toUser, $fromUser, '什么事件？' );	
			}


		}
		// 被动回复
		
		// 文本
		elseif ( $postObj->MsgType == 'text' ) {
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



	// 自定义菜单 
	public function definedItem(){
		// 1.获取 access_token，并调用自定义菜单调用接口
		$access_token = $this->getAccessToken();
		// 将刚获取到的 access_token 接入接口
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		// 2.定义自定义菜单项
		// 不知道为什么，这个数组离开了该方法就会报错目前先这样写吧
		$items = array(
	        'button' => array(
	            array(
	                'name' => urlencode('项目一'),
	                'type' => 'click',
	                'key' => 'item1',
	            ),
	            array('name' => urlencode('项目二'), 'sub_button' => array(
	                    array(
	                        'name' => urlencode('歌曲'),
	                        'type' => 'click',
	                        'key' => 'songs'
	                    ),//第一个二级菜单
	                    array(
	                        'name' => urlencode('电影'),
	                        'type' => 'view',
	                        'url' => 'http://www.baidu.com'
	                    ),//第二个二级菜单
	                )
	            ),
	            array(
	                'name' => urlencode('项目三'),
	                'type' => 'view',
	                'url' => 'http://www.qq.com',
	            ),
	        ),
	    );
		// 3.对该数组编码
		$postJSON = urldecode(json_encode($items));
		// 采集信息
		$res = $this->httpCurl($url, 'post', 'json', $postJSON);
		// 测试地址： http://qloverwechat.duapp.com/wechat/index.php/home/index/definedItem
	}





	/* 工具类 */
	

	// 信息采集工具 
	/**
	 * [httpCurl 调用微信接口]
	 * @param  string $url  接口 url 地址
	 * @param  string $type 调用方式，默认 get 
	 * @param  string $res  返回数据类型，默认 json 格式 
	 * @param  string $arr  post 请求参数
	 * @return array 		一个 json 格式数组对象
	 */	
	public function httpCurl($url, $type='get', $res='json', $arr=''){
		// 1.初始化 curl
		$ch = curl_init();
		// 2.设置 curl, 默认传来的是用 get 方法处理
		// curl_setopt() 
		// 参数一则是一个 由 curl_init()  返回的 cURL 句柄。
		// 参数二则是一个标记的整型数，可以去手册查看，
		// 	这里表示：需要获取的URL地址，也可以在 curl_init() 函数中设置。 
		// 参数三则是应该被设置一个 bool 类型的值,一般就 url 地址，不然就是0 或 1
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 将 curl_exec() 获取的信息以文件流的形式返回，而不是直接输出。 
		// 3.采集信息
		// 如果是不是 get 方式则
		if ( $type == 'post') {
			// 启用时会发送一个常规的POST请求，
			//   类型为：application/x-www-form-urlencoded，就像表单提交的一样
			curl_setopt($ch, CURLOPT_POST, 1);
			// 全部数据使用HTTP协议中的"POST"操作来发送
			curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
		}
		$output = curl_exec($ch);
		// 4.关闭资源
		curl_close($ch);
		// 资源关闭后要返回结果
		if ($res == 'json') {
			if ( curl_error($ch) ) {
				// 请求失败，返回错误信息
				return curl_error($ch);
			}else{
				// 请求成功, 将采集到的串以 JSON 形式编码
				return json_decode($output, true);
			}
		}


	}


	// 获取 access_token
	public function getAccessToken(){
		// 1.判断是否存在或者是过期
		if ($_SESSION['access_token'] && $_SESSION['expire_time'] > time() ) {
			// 2.直接返回 access_token
			return $_SESSION['access_token'];
		}
		// 3.重新获取 access_token
		else{
			// 该信息从微信开发者中心获取
			$appid = 'wx9c807e4f802a2033';
			$appSecret =  '2a385fecf5756cd4ed3402a99c3bc979';
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appSecret;
			// 采集该地址接口中的信息
			$rec = $this->httpCurl($url); // 其余参数全默认
			$access_token = $rec['access_token'];
			// 将重新获取到的 access_token 存到 session 中并返回
			$_SEESION['access_token'] = $access_token;
			$_SEESION['expire_time'] = time()+7200;
			return $access_token;
		}


	}






}