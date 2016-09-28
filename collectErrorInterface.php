<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/27
 * Time: 9:31
 */
header("charset='utf-8'");
extract($_REQUEST,EXTR_SKIP);
//验证参数
$keys = array_keys($_REQUEST);
$params = array("client_id","level","msg","stime","sign");
$diff = array_diff($params,$keys);
if(!empty($diff)){
    printJson(2001,'参数错误');
}
//验证数字签名
$tmp_arr = $_REQUEST;
$request_sign = $tmp_arr['sign'];
unset($tmp_arr['sign']);
ksort($tmp_arr);
$tmp_str = implode($tmp_arr);
$skey = "838fd508b97cab5f668ae073a61b7c5b";
$real_sign = md5($tmp_str.$skey);
if($real_sign != $request_sign){
    //printJson(2002,'数字签名验证失败');
}
//将请求数据插入队列
$valid_data = array();
$valid_data['client_id'] = $client_id;
$valid_data['level'] = $level;
$valid_data['msg'] = $msg;
$valid_data['stime'] = $stime;
$json_data = json_encode($valid_data);
$redis = new Redis();
if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
    $redis->lPush("errorList",$json_data);
    $redis->close();
}else{
    printJson(2003,'redis打开失败');
}
printJson(2000,'ok');
//对外输出函数
function printJson($code,$msg){
    $arr = array("code"=>$code,"msg"=>urlencode($msg));
    $str = json_encode($arr);
    echo urldecode($str);
    writeText("$code:$msg");
    exit;
}
//写入文本文件中的日志
function writeText($str){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."]".$str."\r\n";
    file_put_contents("/var/log/colletErrorInterface.log",$content,FILE_APPEND);
}