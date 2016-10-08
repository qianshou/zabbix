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
require_once dirname(__FILE__).'/include/mysqli.inc.php';
$page['title'] = _('hfAjaxFunction');
$page['file'] = 'hfAjaxFunction.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;
ob_clean();

//添加联系人
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'addContact'){
    $name = false;
    if(isset($_REQUEST['name']) && !empty($_REQUEST['name'])){
        $name = $_REQUEST['name'];
    }
    $email = false;
    if(isset($_REQUEST['email']) && !empty($_REQUEST['email'])){
        $email = $_REQUEST['email'];
    }
    $sms = false;
    if(isset($_REQUEST['phone']) && !empty($_REQUEST['phone'])){
        $sms = $_REQUEST['phone'];
    }
    //检测必须的字段是否已经赋值
    if(!($name&&$email&&$sms)){
        echo -1;exit;
    }
    //检测姓名是否重复
    if(!checkContactName($name)){
        echo -2;exit;
    }
    if(isset($_REQUEST['comment']) && !empty($_REQUEST['comment'])){
        $comment = $_REQUEST['comment'];
    }
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_manageContacts`(`name`,`email`,`sms`,`comment`) VALUES (?,?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("ssss",$name,$email,$sms,$comment);
    if($mysqli_stmt->execute()){
        echo 1;
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
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "DELETE FROM `hf_manageContacts` WHERE `id`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("i",$id);
    if($mysqli_stmt->execute()){
        echo 1;
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//添加收集站
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'addWebsite'){
    $client_id = false;
    if(isset($_REQUEST['client_id']) && !empty($_REQUEST['client_id'])){
        $client_id = $_REQUEST['client_id'];
    }
    $client_name = false;
    if(isset($_REQUEST['client_name']) && !empty($_REQUEST['client_name'])){
        $client_name = $_REQUEST['client_name'];
    }
    //检查必填字段是否填写
    if($client_id && $client_name){
        echo -1;exit;
    }
    //检查收集站名称是否重复
    if(checkWebsiteName($client_name)){
        echo -2;exit;
    }
    if(isset($_REQUEST['comment']) && !empty($_REQUEST['comment'])){
        $comment = $_REQUEST['comment'];
    }
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_manageWebsite`(`client_id`,`client_name`,`comment`) VALUES (?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("sss",$client_name,$client_name,$comment);
    if($mysqli_stmt->execute()){
        echo 1;
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
    $mysqli = new Zmysqli();
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

//添加报警规则
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'addSetting'){
    if(isset($_REQUEST['website']) && !empty($_REQUEST['website'])){
        $client_id = $_REQUEST['website'];
    }
    if(isset($_REQUEST['contact']) && !empty($_REQUEST['contact'])){
        $contact_name = $_REQUEST['contact'];
    }
    $sendmail = 0;
    if(isset($_REQUEST['sendmail']) && !empty($_REQUEST['sendmail'])){
        $sendmail = $_REQUEST['sendmail'];
    }
    $sendsms = 0;
    if(isset($_REQUEST['sendsms']) && !empty($_REQUEST['sendsms'])){
        $sendsms = $_REQUEST['sendsms'];
    }
    $maillevel = 0;
    if(isset($_REQUEST['maillevel']) && !empty($_REQUEST['maillevel'])){
        $maillevel = $_REQUEST['maillevel'];
    }
    $smslevel = 0;
    if(isset($_REQUEST['smslevel']) && !empty($_REQUEST['smslevel'])){
        $smslevel = $_REQUEST['smslevel'];
    }
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    //此处验证收集站名称是否重复
    $prepare_sql = "INSERT INTO `hf_alert_setting`(`client_id`,`contact_name`,`sendmail`,`maillevel`,`sendsms`,`smslevel`) VALUES (?,?,?,?,?,?)";

    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("ssiiii",$client_id,$contact_name,$sendmail,$maillevel,$sendsms,$smslevel);
    if($mysqli_stmt->execute()){
        echo $mysqli_stmt->insert_id;
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//删除报警规则
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'delSetting'){
    if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
        $id = $_REQUEST['id'];
    }else{
        exit;
    }
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "DELETE FROM `hf_alert_setting` WHERE `id`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("i",$id);
    if($mysqli_stmt->execute()){
        echo 1;
    }
    $mysqli_stmt->free_result();
    $mysqli->close();
    exit;
}

//检测联系人姓名是否重复函数
function checkContactName($name){
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "SELECT * FROM `hf_manageContacts` WHERE `name`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("s",$name);
    $mysqli_stmt->execute();
    $mysqli_stmt->store_result();
    $num = $mysqli_stmt->num_rows;
    $mysqli_stmt->free_result();
    $mysqli->close();
    if($num > 0){
        return false;
    }else{
        return true;
    }
}

//检测收集站名称是否重复函数
function checkWebsiteName($name){
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "SELECT * FROM `hf_manageWebsite` WHERE `client_name`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("s",$name);
    $mysqli_stmt->execute();
    $mysqli_stmt->store_result();
    $num = $mysqli_stmt->num_rows;
    $mysqli_stmt->free_result();
    $mysqli->close();
    if($num > 0){
        return false;
    }else{
        return true;
    }
}

//记录日志操作
function writeText($str,$method){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."|$method]".$str."\r\n";
    file_put_contents("/var/log/hfAjaxFunction.log",$content,FILE_APPEND);
}
