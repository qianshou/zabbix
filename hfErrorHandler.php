<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/28
 * Time: 14:54
 */
require_once 'mailto.php';
$errorList = array();
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
    $level = $errorItem['level'];
    $client_id = $errorItem['client_id'];
    $msg = $errorItem['msg'];
    $stime = $errorItem['stime'];
    //短信、邮件报警
    $report_level = array('fatal');
    if(in_array($level,$report_level)){
        //获取邮件收件人，短信收件人
        $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
        if($mysqli->connect_errno){ //连接成功errno应该为0
            writeText('Connect Error:'.$mysqli->connect_error);
        }
        $mysqli->set_charset('utf8');
        $mysql_result = $mysqli->query("SELECT * FROM `hf_alert_setting` WHERE `item` IN ('email','message')");
        $item = array();
        while ($row = $mysql_result->fetch_assoc()){
            $item[$row['item']] = $row['value'];
        }
        $mailto = $item['email'];
        $smsto = $item['message'];
        $mysql_result->free();
        $mysqli->close();
        //发送邮件、短信
        $err_msg = '';
        $err_msg .= "来源ID：".$client_id."<br/>";
        $err_msg .= "错误等级：".$level."<br/>";
        $err_msg .= "发生时间：".date("Y-m-d H-i-s",$stime)."<br/>";
        $err_msg .= "错误详细:".$msg."<br/>";
        $subject = "错误收集接口报警";
        sendmailto($mailto,$subject,$err_msg);
        writeText("mailto ".$mailto);
        writeText("send message to ".$smsto);
    }
    //写入mysql数据库
    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_collected_error`(`occur_time`,`client_id`,`level`,`msg`,`mail`,`sms`,`handle_time`,`finished`) VALUES (?,?,?,?,?,?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $finished = 1;
    $mysqli_stmt->bind_param("sssssssi",date("Y-m-d H-i-s",$stime),$client_id,$level,$msg,$mailto,$smsto,date("Y-m-d H-i-s"),$finished);
    if($mysqli_stmt->execute()){
        $id = $mysqli_stmt->insert_id;
    }else{
        writeText("Insert Error:".date("Y-m-d H-i-s",$stime).",$client_id,$level,$msg");
    }
    $mysqli->close();
}
//写入文本文件中的日志
function writeText($str){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."]".$str."\r\n";
    file_put_contents("/var/log/errorHandler.log",$content,FILE_APPEND);
}