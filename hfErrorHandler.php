<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/28
 * Time: 14:54
 */
require_once 'mailto.php';
require_once dirname(__FILE__).'/include/mysqli.inc.php';
ini_set('default_socket_timeout', -1);  //不超时

//从redis取出错误信息
$redis = new Redis();
if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
    $redis->subscribe(array("errorList"),"handleFunction");
    $redis->close();
}else{
    writeText('redis打开失败');
}
//错误信息错误函数
function handleFunction($redis, $chan, $msg){
    $errorItem = json_decode($msg,true);
    $level = intval($errorItem['level']);
    $client_id = $errorItem['client_id'];
    $error_hash = $errorItem['error_hash'];
    $msg = $errorItem['msg'];
    $stime = $errorItem['stime'];

    //查询client_id是否存在报警规则
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $sql = "SELECT * FROM `hf_manageContacts`";
    $mysql_result = $mysqli->query($sql);
    $contacts_rows = array();
    while ($row = $mysql_result->fetch_assoc()){
        $contacts_rows[] = $row;
    }
    $mysql_result->free();
    $mysqli->close();
    //联系人邮件信息映射
    $email_map = array();
    foreach ($contacts_rows as $row){
        $email_map[$row['id']] = $row['email'];
    }
    $sms_map = array();
    foreach ($contacts_rows as $row){
        $sms_map[$row['id']] = $row['sms'];
    }
    //从redis取出报警信息
    $warning_rule = array();
    $key = $client_id."#".substr(md5($error_hash),0,10);
    $warning_key = "warning#".$key;
    $redis = new Redis();
    if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
        $warning_rule = json_decode($redis->get($warning_key),true);
    }else{
        writeText('redis打开失败');
    }
    //判断是否需要发送邮件
    if($warning_rule['send_mail'] == 1 && $level <= $warning_rule['mail_level'] && $warning_rule['mailtimes'] > 0){
        //执行发送邮件的操作
        $tmp = explode(",",$warning_rule['contact_id']);
        $mailList = array();
        foreach ($tmp as $item){
            $mailList[] = $email_map[$item];
        }
        $mailto = implode(",",$mailList);
        $subject = "错误收集接口报警";
        $err_msg = '';
        $err_msg .= "来源ID：".$client_id."<br/>";
        $err_msg .= "错误等级：".$level."<br/>";
        $err_msg .= "发生时间：".date("Y-m-d H-i-s",$stime)."<br/>";
        $err_msg .= "错误详细:".$msg."<br/>";
        //sendmailto($mailto,$subject,$err_msg);
        writeText("send mail to ".$mailto);
        $warning_rule['mailtimes'] = $warning_rule['mailtimes'] - 1;
    }
    //判断是否需要发送短信
    if($warning_rule['send_sms'] == 1 && $level <= $warning_rule['sms_level']  && $warning_rule['smstimes'] > 0){
        //执行发送短信操作
        $tmp = explode(",",$warning_rule['contact_id']);
        $smsList = array();
        foreach ($tmp as $item){
            $smsList[] = $sms_map[$item];
        }
        $smsto = implode(",",$smsList);
        writeText("send sms to ".$smsto);
        $warning_rule['smstimes'] = $warning_rule['smstimes'] - 1;
    }
    if($warning_rule['smstimes'] > 0 || $warning_rule['mailtimes'] > 0){
        //更新redis
        $redis->set($warning_key,json_encode($warning_rule));
    }else{
        $redis->del($warning_key);
    }
    $redis->close();
    if(isset($errorItem['id'])){
        //只有首次请求，才会有id参数
        $id = $errorItem['id'];
        $mysqli = new Zmysqli();
        if($mysqli->connect_errno){ //连接成功errno应该为0
            writeText('Connect Error:'.$mysqli->connect_error);
        }
        $mysqli->set_charset('utf8');
        $prepare_sql = "UPDATE `hf_collected_error` SET `contact_id`=? , `alarm_time`=? , `alarmed`=? WHERE `id`=?";
        $mysqli_stmt = $mysqli->prepare($prepare_sql);
        $alarm_time = date("Y-m-d H-i-s");
        $alarmed = 1;
        $mysqli_stmt->bind_param("ssii",$warning_rule['contact_id'],$alarm_time,$alarmed,$id);
        if(!$mysqli_stmt->execute()){
            writeText("Update Error:$client_id,$level,$msg");
        }
        writeText("Insert into sql");
        $mysqli->close();
    }
}
//写入文本文件中的日志
function writeText($str){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."]".$str."\r\n";
    file_put_contents("/var/log/hfErrorHandler.log",$content,FILE_APPEND);
}