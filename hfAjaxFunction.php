<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/27
 * Time: 16:04
 */
ob_start();
require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/triggers.inc.php';
require_once dirname(__FILE__).'/include/media.inc.php';
require_once dirname(__FILE__).'/include/users.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';
require_once dirname(__FILE__).'/include/js.inc.php';

$page['title'] = _('hfAjaxFunction');
$page['file'] = 'hfAjaxFunction.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;
ob_clean();
//记录日志操作
function writeText($str,$method){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."|$method]".$str."\r\n";
    file_put_contents("/var/log/hfAjaxFunction.log",$content,FILE_APPEND);
}
//更新
//if(isset($_REQUEST['item']) && isset($_REQUEST['value']) && !empty($_REQUEST['item']) && !empty($_REQUEST['value'])){
//    $item = $_REQUEST['item'];
//    $value = $_REQUEST['value'];
//    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
//    if($mysqli->connect_errno){ //连接成功errno应该为0
//        writeText('Connect Error:'.$mysqli->connect_error);
//    }
//    $mysqli->set_charset('utf8');
//    $prepare_sql = "SELECT `item`,`value` FROM `alert_setting` WHERE `item`=?";
//    $mysqli_stmt = $mysqli->prepare($prepare_sql);
//    $mysqli_stmt->bind_param("s",$item);
//    $mysqli_stmt->execute();
//    $mysqli_stmt->store_result();
//    if($mysqli_stmt->num_rows()){
//        //更新操作
//        $mysqli_stmt->free_result();
//        $mysqli_stmt = null;
//        $prepare_sql = "UPDATE `alert_setting` SET `value`=? WHERE `item`='$item'";
//        $mysqli_stmt = $mysqli->prepare($prepare_sql);
//        $mysqli_stmt->bind_param("s",$value);
//    }else{
//        //插入操作
//        $mysqli_stmt->free_result();
//        $mysqli_stmt = null;
//        $prepare_sql = "INSERT INTO `alert_setting`(`item`,`value`) VALUES(?,?)";
//        $mysqli_stmt = $mysqli->prepare($prepare_sql);
//        $mysqli_stmt->bind_param("ss",$item,$value);
//    }
//    if($mysqli_stmt->execute()){
//        echo "ok";
//    }else{
//        writeText('Insert/Update Error:$item,$value');
//    }
//    $mysqli->close();
//}
//写入文本文件中的日志

//添加联系人
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'addContact'){
    if(isset($_REQUEST['name']) && !empty($_REQUEST['name'])){
        $name = $_REQUEST['name'];
    }
    if(isset($_REQUEST['email']) && !empty($_REQUEST['email'])){
        $email = $_REQUEST['email'];
    }
    if(isset($_REQUEST['phone']) && !empty($_REQUEST['phone'])){
        $sms = $_REQUEST['phone'];
    }
    if(isset($_REQUEST['comment']) && !empty($_REQUEST['comment'])){
        $comment = $_REQUEST['comment'];
    }
    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_manageContacts`(`name`,`email`,`sms`,`comment`) VALUES (?,?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("ssss",$name,$email,$sms,$comment);
    if($mysqli_stmt->execute()){
        echo $mysqli_stmt->insert_id;
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//删除联系人
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'delContact'){
    if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
        $id = $_REQUEST['id'];
    }else{
        exit;
    }
    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "DELETE FROM `hf_manageContacts` WHERE `id`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("i",$id);
    if($mysqli_stmt->execute()){
        echo "ok";
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//添加收集站
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'addWebsite'){
    if(isset($_REQUEST['client_id']) && !empty($_REQUEST['client_id'])){
        $client_id = $_REQUEST['client_id'];
    }
    if(isset($_REQUEST['client_name']) && !empty($_REQUEST['client_name'])){
        $client_name = $_REQUEST['client_name'];
    }
    if(isset($_REQUEST['comment']) && !empty($_REQUEST['comment'])){
        $comment = $_REQUEST['comment'];
    }
    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_manageWebsite`(`client_id`,`client_name`,`comment`) VALUES (?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("sss",$client_name,$client_name,$comment);
    if($mysqli_stmt->execute()){
        echo $mysqli_stmt->insert_id;
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//删除收集站
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'delWebsite'){
    if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
        $id = $_REQUEST['id'];
    }else{
        exit;
    }
    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "DELETE FROM `hf_manageWebsite` WHERE `id`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("i",$id);
    if($mysqli_stmt->execute()){
        echo "ok";
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//更新联系人
//if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'addContact'){
//    if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
//        $name = $_REQUEST['id'];
//    }
//    if(isset($_REQUEST['name']) && !empty($_REQUEST['name'])){
//        $name = $_REQUEST['name'];
//    }
//    if(isset($_REQUEST['email']) && !empty($_REQUEST['email'])){
//        $email = $_REQUEST['email'];
//    }
//    if(isset($_REQUEST['phone']) && !empty($_REQUEST['phone'])){
//        $sms = $_REQUEST['phone'];
//    }
//    if(isset($_REQUEST['comment']) && !empty($_REQUEST['comment'])){
//        $comment = $_REQUEST['comment'];
//    }
//    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
//    if($mysqli->connect_errno){ //连接成功errno应该为0
//        writeText('Connect Error:'.$mysqli->connect_error);
//    }
//    $mysqli->set_charset('utf8');
//    $prepare_sql = "UPDATE `hf_manageContacts` SET `name`=? AND `email`=? AND `sms`=? AND `comment`=? WHERE `id`=?";
//    $mysqli_stmt = $mysqli->prepare($prepare_sql);
//    $mysqli_stmt->bind_param("ssssi",$name,$email,$sms,$commet,$id);
//    if($mysqli_stmt->execute()){
//        echo "ok";
//    }
//    $mysqli_stmt->free_result();
//    $mysqli->close();
//    exit;
//}