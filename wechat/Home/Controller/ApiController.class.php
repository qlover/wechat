<?php
namespace Home\Controller;
use Think\Controller;
/**
 * Api 接口
 *
 * 微信与百度 BAE 连接的 API 接口
 */
class ApiController extends Controller {


	/**
	 * 一、微信接口与 BAE 接入
	 * 
	 * 接口
	 * 路径 http://qloverwechat.duapp.com/wechat/index.php/home/api/api
	 */
	public function api() {
		//1. 获得参数 signature nonce token timestamp echostr
	    $nonce 		 = $_GET['nonce'];
	    $token 		 = 'weixintp';
	    $timestamp	 = $_GET['timestamp'];
	    $echostr 	 = $_GET['echostr'];
	    $signature	 = $_GET['signature'];
	    //2. 形成数组，然后按字典序排序
	    $array 		 = array();
	    $array 		 = array($nonce, $timestamp, $token);
	    sort($array);
	    //3. 拼接成字符串,sha1加密 ，然后与signature进行校验
	    $str = sha1( implode( $array ) );
	    if( $str  == $signature && $echostr ){
	      echo  $echostr;
	      exit;
	    }else{
	    	// 如果不是第一次关注就做消息接口
	    	$this->responseMsg();
	    }
	}

	// 定义数据包结构
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

	// 定义图文资源
	private static $arts = array(
		array(
			'title'=>'click here open sina page. ', 
			'description'=>"sina is very cool.\n\nsina is very cool.sina is very cool.sina is very cool.",
			'picUrl'=>'http://i1.sinaimg.cn/dy/deco/2013/0329/logo/LOGO_1x.png',
			'url'=>'http://www.sina.com',
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


	/**
	 * 二、消息接口
	 * 接收事件推送并回复
	 * 
	 */
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

		// 关注取消关注事件
		if ( $postObj->MsgType == 'event' ) {

			// 关注事件 subscribe
			if ( $postObj->Event == 'subscribe' ) {
				// 数据包模板
				$tpl = ApiController::$templates['text'];
				
				// 取得数据包中的信息
				$content  = "欢迎关注我，我是 Qlover。";
				$content .= "\n你可以输入【hello】或者是【你好】\n还可以输入【news】。";
				
				// 输出连接串
				echo sprintf($tpl, $toUser, $fromUser, time(), $content );

			}
			// 取消事件
			elseif ( $postObj->event == 'unsubscribe' ) {
				echo 'unsubscribe (取消事件被触发)';
				exit;
			}
		}

		/* 关键字消息 */
		// 先判断图文消息
		if ( $postObj->MsgType == 'text' ) {
			$postCont = $postObj->Content;

			// 图文消息比关键字消息先判断
			if ( $postCont == 'news' ) {
				$this->sendTuWen($toUser, $fromUser);
			}

			// 关键字判断 
			else{
				// 同样先确认两端身份,然后在确认消息数据包结构
				$tpl = ApiController::$templates['text'];

				// 判断用户输入关键字，用户输入关键字用 Content 获取
				switch( $postCont ){
					case 'hello' :
						$content = '你好!';
						echo sprintf($tpl, $toUser, $fromUser, time(), $content );
					break;
					case '你好' :
						$content = '你好!';
						echo sprintf($tpl, $toUser, $fromUser, time(), $content );
					break;
				}
			}
		}else{
			echo '不是文本消息';
		}

		


	}


	// 定义图文发送
	private function sendTuWen($tu, $fu) {
		$arr = ApiController::$arts;		
		$template = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<ArticleCount>".count($arr)."</ArticleCount>
					<Articles>";
		// 循环添加多个项目,单个图文就循环一次
		foreach($arr as $k=>$v){
			$template .="<item>
						<Title><![CDATA[".$v['title']."]]></Title> 
						<Description><![CDATA[".$v['description']."]]></Description>
						<PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
						<Url><![CDATA[".$v['url']."]]></Url>
						</item>";
		}
		$template .="</Articles></xml> ";
		echo sprintf($template, $tu, $fu, time(), 'news');				
	}



/*	public function httpCurl( $url='http://www.baidu.com' ){
		// 获取 $url 
		// 1.初始化
		$ch = curl_init();
		// 2.设置 curl 的参数
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
		// 3.采集
		$output = curl_exec($ch);
		// 4.关闭
		curl_close($ch);
		echo $output;
		// return $output;
	}
*/

	/**
	 * 获取微信 access_token 接口
	 * 
	 * (
	 * 	微信中的 access_token 很重要，又因为 access_token 是为生存周期的
	 *  也就是说在一定时间内会改变
	 * )
	 * 
	 * 微信公众号账号测试系统中获得的 AppID 和 AppSecret 
	 * AppID：wx9c807e4f802a2033
	 * AppSecret: 2a385fecf5756cd4ed3402a99c3bc979
	 */
/*	public function getWxAccessToken(){
		//1.请求url地址
		$appid = 'wx9c807e4f802a2033';
		$appsecret =  '2a385fecf5756cd4ed3402a99c3bc979';
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
		//2初始化
		$ch = curl_init();
		//3.设置参数
		curl_setopt($ch , CURLOPT_URL, $url);
		curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
		//4.调用接口 
		$res = curl_exec($ch);
		//5.关闭curl
		curl_close( $ch );
		if( curl_errno($ch) ){
			var_dump( curl_error($ch) );
		}
		$arr = json_decode($res, true);
		// var_dump($arr);
		return  $arr;
		// 如果返回错误则将服务器地址加入白名单,又因为 BAE 服务器 IP 是不固定的
		
		// 目前发现服务器给我的 IP 有三个
		// 111.13.102.166, 111.13.102.165, 111.13.102.39
	}*/


	/**
	 * 获取微信的服务器地址接口
	 */
	public function getWxServerIp(){
		// 但因为 IP 不固定，所以这个也是不固定的
		$accessToken = "2De7jALxC4gVV6KiA6Qs2hqA5JtHXRNDoi4FNFSZ1jj4YtH-kEfDG-9SAlOwOcpiACuI6TQvpvayzg8tDFlHCvhlzGAvAiAcPdBmLzA9wBaQAk9AiyUc0_MwrOh_TlgdZVYdADAMIL";
		$url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$accessToken;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$res = curl_exec($ch);
		curl_close($ch);
		if(curl_errno($ch)){
			var_dump(curl_error($ch));
		}
		$arr = json_decode($res,true);
		// var_dump( $arr );
		return $arr;
	}









	// 重写 httpCurl 方法
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
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过证书验证，
		// curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//从证书中检查ssl加密算法是否存在
		// 设置curl 参数,默认传来的是用 get 方法处理
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 如果是 post 方式，则
		if ($type  == 'post') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
		}
		// 3. 采集
		$output = curl_exec($ch);
		// 4.关闭
		curl_close($ch);
		if ($res == 'json') {
			if ( curl_error($ch) ) {
				// 请求失败，返回错误信息
				return curl_error($ch);
			}else{
				// 请求成功
				return json_decode($output, true);
			}
		}
	}



	// 重写 getWxAccessToken 方法
	/**
	 * 返回 access_token
	 * 
	 * (
	 * 	微信中的 access_token 很重要，又因为 access_token 是为生存周期的
	 *  也就是说在一定时间内会改变
	 *  所以这个地方可以用最简单的方式，将 access_token 存放在 session 中
	 * )
	 * 
	 * 微信公众号账号测试系统中获得的 AppID 和 AppSecret 
	 * AppID：wx9c807e4f802a2033
	 * AppSecret: 2a385fecf5756cd4ed3402a99c3bc979
	 * 
	 */
	public function getWxAccessToken(){
		// access_token 没有过期
		if ( $_SEESION['access_token'] && /* access_token 是否存在 session 中 */
			 $_SEESION['expire_time'] > time() /* 并且看过期时间是否在当前时间之后 */
			) {
			//  access_token 在 session 并没有过期,就直接返回 session 中的 access_token
			return $_SEESION['access_token'];
		}
		// access_token 过期
		else{
			// 或者是 access_token 不存在或者是过期则重新获取 
			$appid = 'wx9c807e4f802a2033';
			$appsecret =  '2a385fecf5756cd4ed3402a99c3bc979';
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
			$res = $this->httpCurl($url, 'get', 'json');
			$access_token = $res['access_token'];
			// 将重新获取到的 access_token 存到 session 中
			$_SEESION['access_token'] = $access_token;
			$_SEESION['expire_time'] = time()+7200;
			return $access_token;
		}
	}	



	// 创建微信菜单
	// 目前微信接口的调用方式都是通过 curl，所以要重写 httpCurl() 方法
	public function definedItem(){
		// 因为是自己开发，所以要在测试系统下完成自定义菜单
		// 但测试系统下的 access_token 和原来的 access_token 不同，所以要重写 getWxAccessToken 方法
		$access_token = $this->getWxAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		// 构建一个 click/view 请求实例的 JSON 数组对象
		// 需要注意的是 json 上传中文时会报错
		 $postArr = array(
            'button' => array(
                array(
                    'name' => urlencode('菜单一'),
                    'type' => 'click',
                    'key' => 'item1',
                ),
                array('name' => urlencode('菜单二'), 'sub_button' => array(
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
                    'name' => urlencode('菜单三'),
                    'type' => 'view',
                    'url' => 'http://www.qq.com',
                ),
	        )
        );
        $postJson = urldecode(json_encode($postArr));
        $res = $this->httpCurl($url,'post','json',$postJson);
    }

}