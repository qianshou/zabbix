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
$params = array("client_id","error_hash","level","msg","stime","sign");
$diff = array_diff($params,$keys);
if(!empty($diff)){
    printJson(2001,'参数错误');
}else{
    $client_id = addslashes($_REQUEST['client_id']);
    $error_hash = addslashes($_REQUEST['error_hash']);
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
if( !($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")) ){
    writeText("redis打开失败");
    printJson(2005,'接口发生错误');
    exit;
}
//从redis获取收集站信息
$client_id_array = array();
$key = "client_data";
if($redis->exists($key)){
    $tmp = $redis->get($key);
    $client_id_data = json_decode($tmp,true);
    foreach ($client_id_data as $row){
        $client_id_array[] = $row['client_id'];
    }
}else{
    //从数据库中读取信息
    $mysqli_result = $mysqli->query("SELECT * FROM hf_manageWebsite");
    while ($row = $mysqli_result->fetch_assoc()){
        $client_id_array[] = $row;
    }
    $redis->set($key,json_encode($client_id_array));
}
if(!in_array($client_id,$client_id_array)){
    printJson(2004,'收集站未注册');
    $redis->close();
    exit;
}
//判断是否为首次请求
$key = $client_id."#".substr(md5($error_hash),0,10);
if($redis->exists($key)){
    //本次请求为二次请求，只更新计数器，不做其他处理
    $redis->incr($key); //计数器进行计数
    $warning_key = "warning#".$key;
    if($redis->exists($warning_key)){
        $valid_data = array();
        $valid_data['error_hash'] = $error_hash;
        $valid_data['client_id'] = $client_id;
        $valid_data['level'] = $level;
        $valid_data['msg'] = $msg;
        $valid_data['stime'] = $stime;
        handleError($valid_data);   //通过脚本处理错误
    }
    $redis->close();
    printJson(2000,'do not come back again!');
}else{
    //本次请求为首次请求，插入数据库，并进行告警处理
    $redis->set($key,1);
    //请求数据插入mysql数据库
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_collected_error`(`occur_time`,`error_hash`,`client_id`,`level`,`msg`) VALUES (?,?,?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("sssss",date("Y-m-d H-i-s",$stime),$error_hash,$client_id,$level,$msg);
    if(!$mysqli_stmt->execute()){
        writeText("Insert Error:$client_id,$level,$msg");
        printJson(2003,'数据录入失败');
        exit;
    }
    $insert_id = $mysqli_stmt->insert_id;
    //查询报警规则
    $prepare_sql = "SELECT id,client_id,contact_id,sendmail,maillevel,mailtimes,sendsms,smslevel,smstimes FROM `hf_alert_setting` WHERE client_id=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("s",$client_id);
    $mysqli_stmt->execute();
    $mysqli_stmt->store_result();
    if($mysqli_stmt->num_rows > 0){
        //如果存在相应的报警规则，则存入redis
        $warning_json = array();
        $mysqli_stmt->bind_result($warning_json['id'],$warning_json['client_id'],$warning_json['contact_id'],$warning_json['send_mail'],$warning_json['mail_level'],$warning_json['mailtimes'],$warning_json['send_sms'],$warning_json['sms_level'],$warning_json['smstimes']);
        $mysqli_stmt->fetch();
        $warning_key = "warning#".$key;
        $redis->set($warning_key,json_encode($warning_json));
    }
    $redis->close();
    $mysqli->close();
    $valid_data = array();
    $valid_data['id'] = $insert_id;
    $valid_data['error_hash'] = $error_hash;
    $valid_data['client_id'] = $client_id;
    $valid_data['level'] = $level;
    $valid_data['msg'] = $msg;
    $valid_data['stime'] = $stime;
    handleError($valid_data);   //通过脚本处理错误
    printJson(2000,'ok');
}
function handleError($valid_data){
    //请求数据发布到redis
    $redis = new Redis();
    if( !($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")) ){
        writeText("redis打开失败");
        printJson(2005,'接口发生错误');
        exit;
    }
    $json_data = json_encode($valid_data);
    $redis->publish("errorList",$json_data);
    $redis->close();
}
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