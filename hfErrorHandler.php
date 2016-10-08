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

//从队列取出错误信息
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
    $msg = $errorItem['msg'];
    $stime = $errorItem['stime'];
    $id = $errorItem['id'];

    //查询client_id是否存在报警规则
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $sql = "SELECT a.*,b.email,b.sms FROM `hf_alert_setting` AS a INNER JOIN `hf_manageContacts` AS b ON a.contact_name=b.name AND a.client_id='$client_id'";
    writeText($sql);
    $mysql_result = $mysqli->query($sql);
    $mail_total = array();
    $sms_total = array();
    while ($rule = $mysql_result->fetch_assoc()){
        //对每条规则进行操作
        //判断是否需要发送邮件
        if($rule['sendmail'] == 1 && $level <= $rule['maillevel']){
            //执行发送邮件的操作
            writeText("mailto ".$rule['email']);
            $mail_total[] = $rule['contact_name'];
            $subject = "错误收集接口报警";
            $err_msg = '';
            $err_msg .= "来源ID：".$client_id."<br/>";
            $err_msg .= "错误等级：".$level."<br/>";
            $err_msg .= "发生时间：".date("Y-m-d H-i-s",$stime)."<br/>";
            $err_msg .= "错误详细:".$msg."<br/>";
            sendmailto($rule['email'],$subject,$err_msg);
        }
        //判断是否需要发送短信
        if($rule['sendsms'] == 1 && $level <= $rule['smslevel']){
            //执行发送短信操作
            $sms_total[] = $rule['contact_name'];
            writeText("send sms to ".$rule['sms']);
        }
    }
    $mysql_result->free();
    //更新数据库
    $prepare_sql = "UPDATE `hf_collected_error` SET `mail`=? , `sms`=? , `handle_time`=? , `finished`=? WHERE `id`=?";
    $mail_json = json_encode($mail_total);
    $sms_json = json_encode($sms_total);
    $handle_date = date("Y-m-d H-i-s");
    $finished = 1;
    $real_sql = "UPDATE `hf_collected_error` SET `mail`='$mail_json' , `sms`='$sms_json' , `handle_time`='$handle_date' , `finished`=$finished WHERE `id`=$id";
    writeText($real_sql);
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("sssii",$mail_json,$sms_json,$handle_date,$finished,$id);
    if(!$mysqli_stmt->execute()){
        writeText("Update Error:$client_id,$level,$msg");
    }
    $mysqli->close();
}
//写入文本文件中的日志
function writeText($str){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."]".$str."\r\n";
    file_put_contents("/var/log/hfErrorHandler.log",$content,FILE_APPEND);
}