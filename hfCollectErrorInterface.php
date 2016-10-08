<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/27
 * Time: 9:31
 */
require_once dirname(__FILE__).'/include/mysqli.inc.php';
header("charset='utf-8'");
//验证参数
$keys = array_keys($_REQUEST);
$params = array("client_id","level","msg","stime","sign");
$diff = array_diff($params,$keys);
if(!empty($diff)){
    printJson(2001,'参数错误');
}else{
    $client_id = addslashes($_REQUEST['client_id']);
    $level = addslashes($_REQUEST['level']);
    $msg = addslashes($_REQUEST['msg']);
    $stime = intval($_REQUEST['stime']);
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
//验证收集站是否已注册
$redis = new Redis();
if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
    $client_id_json = $redis->get("client_id");
    $client_id_array = json_decode($client_id_json,false);
    if(!in_array($client_id,$client_id_array)){
        printJson(2004,'收集站未注册');
        exit;
    }
}else{
    writeText("redis打开失败");
    printJson(2005,'接口发生错误');
    exit;
}
//请求数据插入mysql数据库
$mysqli = new Zmysqli();
if($mysqli->connect_errno){ //连接成功errno应该为0
    writeText('Connect Error:'.$mysqli->connect_error);
}
$mysqli->set_charset('utf8');
$prepare_sql = "INSERT INTO `hf_collected_error`(`occur_time`,`client_id`,`level`,`msg`) VALUES (?,?,?,?)";
$mysqli_stmt = $mysqli->prepare($prepare_sql);
$mysqli_stmt->bind_param("ssss",date("Y-m-d H-i-s",$stime),$client_id,$level,$msg);
if(!$mysqli_stmt->execute()){
    writeText("Insert Error:$client_id,$level,$msg");
    printJson(2003,'数据录入失败');
    exit;
}
$insert_id = $mysqli_stmt->insert_id;
$mysqli_stmt->free_result();
$mysqli->close();

//请求数据发布到redis
$valid_data = array();
$valid_data['id'] = $insert_id;
$valid_data['client_id'] = $client_id;
$valid_data['level'] = $level;
$valid_data['msg'] = $msg;
$valid_data['stime'] = $stime;
$json_data = json_encode($valid_data);
$redis = new Redis();
if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
    $redis->publish("errorList",$json_data);
    $redis->close();
}else{
    writeText("redis发布失败");
    printJson(2003,'数据录入失败');
    exit;
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