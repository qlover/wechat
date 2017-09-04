<?php
/**
 * 自定义菜单
 *
 * 1.获取微信的服务器接口
 * 2.获取 access_token 
 * 3.重写 http_curl 去调用微信接口
 * 4.创建微信菜单，用默认方法 definedItem
 * 5.事件推送，此处的事件推送是 click 和 view 
 * 		也就是点击链接和跳转时所推送的事件
 *
 * 
 * 
 */


header("content-type:text/html; charset=utf-8");
define('APP_DEBUG', true);   // 开发模式
// define('APP_DEBUG', false);   // 生产模式
require '../ThinkPHP/ThinkPHP.php';