<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/28
 * Time: 9:31
 */
echo "1234";
header("Connection: close");//告诉浏览器，连接关闭了，这样浏览器就不用等待服务器的响应
header("HTTP/1.1 200 OK"); //可以发送200状态码，以这些请求是成功的，要不然可能浏览器会重试，特别是有代理的情况下
//return false;//加了这个下面的就不执行了，不加这个无法返回页面状态，浏览器一直在等待状态，可以关闭，但不是要的效果。
//die(); 或 return ;也一样不执行下面的
//rundata();
//register_shutdown_function("rundata");
//return  ;
echo "running,,,,.";
