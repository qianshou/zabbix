<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/9/27
 * Time: 16:04
 */

require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/mysqli.inc.php';

$page['title'] = _('hfAjaxFunction');
$page['file'] = 'hfAjaxFunction.php';

ob_start();
require_once dirname(__FILE__).'/include/page_header.php';
$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;
ob_clean();
//未解决错误列表
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'getErrorList'){

    $page_limit = 25;   //每页显示25条数据
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        $str = 'Connect Error:'.$mysqli->connect_error;
        echo $str;
    }
    $mysqli->set_charset('utf8');
    $sql = "SELECT * FROM `hf_collected_error`";
    //筛选条件
    if(isset($_REQUEST['level']) && $_REQUEST['level'] != 'all' && $_REQUEST['level'] != ''){
        $sql .= " AND `level`=".intval($_REQUEST['level']);
    }
    if(isset($_REQUEST['client_id']) && $_REQUEST['client_id'] != 'all' && $_REQUEST['client_id'] != ''){
        $sql .= " AND `client_id`='".addslashes($_REQUEST['client_id'])."'";
    }
    if(isset($_REQUEST['stime']) && $_REQUEST['stime'] != ''){
        $sql .= " AND `occur_time`>='".addslashes($_REQUEST['stime'])."'";
    }
    if(isset($_REQUEST['etime']) && $_REQUEST['etime'] != ''){
        $sql .= " AND `occur_time`<='".addslashes($_REQUEST['etime'])."'";
    }
    //排序
    $sql .= " ORDER BY `level` ASC ,`occur_time` DESC";
    //分页
    if(isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0){
        $page = intval($_REQUEST['page']);
        $start = ($page-1)*$page_limit;
        $sql .= " LIMIT ".$start.",".($page_limit+1);
    }else{
        exit;
    }
    $mysql_result = $mysqli->query($sql);
    $rows = array();
    while($row = $mysql_result->fetch_assoc()){
        $rows[] = $row;
    }

    //从数据库中读取错误等级信息
    $mysqli_result = $mysqli->query("SELECT * FROM hf_manageWebsite");
    while ($row = $mysqli_result->fetch_assoc()) {
        $client_id_array[] = $row;
    }
    //从数据库中读取联系人信息
    $mysql_result = $mysqli->query("SELECT * FROM `hf_manageContacts`");
    while ($row = $mysql_result->fetch_assoc()){
        $contacts_array[] = $row;
    }

    $mysqli->close();

    //创建收集站映射表
    $client_map = array();
    foreach ($client_id_array as $item){
        $client_map[$item['client_id']] = $item['client_name'];
    }
    //创建联系人映射表
    $contact_map = array();
    foreach ($contacts_array as $item){
        $contact_map[$item['id']] = $item['name'];
    }

    $res = array();
    $num = count($rows);
    if($num == $page_limit+1){
        $res['num'] = $page_limit;
        $res['next'] = 1;
        array_pop($rows);
    }else{
        $res['num'] = $num;
        $res['next'] = 0;
    }

    $redis = new Redis();
    if( !($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")) ){
        writeText("redis打开失败");
        exit;
    }
    $number = 1;
    foreach ($rows as $k=>$row){
        $row['number'] = $number + $start;
        $key = $row['client_id']."#".substr(md5($row['error_hash']),0,10);
        $row['error_counter'] = $redis->get($key);
        $row['client_id'] = $client_map[$row['client_id']];
        $contact_id = array();
        $tmp = explode(',',$row['contact_id']);
        foreach ($tmp as $item){
            $contact_id[] = $contact_map[$item];
        }
        $row['contact_id'] = implode(',',$contact_id);
        $rows[$k] = $row;
        $number++;
    }
    $redis->close();

    $res['data'] = $rows;
    echo json_encode($res);
    exit;
}
//已解决错误列表
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'getHandledErrorList'){
    $page_limit = 25;   //每页显示25条数据
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        $str = 'Connect Error:'.$mysqli->connect_error;
        echo $str;
    }
    $mysqli->set_charset('utf8');
    $sql = "SELECT * FROM `hf_handled_error`";
    //筛选条件
    if(isset($_REQUEST['level']) && $_REQUEST['level'] != 'all' && $_REQUEST['level'] != ''){
        $sql .= " AND `level`=".intval($_REQUEST['level']);
    }
    if(isset($_REQUEST['client_id']) && $_REQUEST['client_id'] != 'all' && $_REQUEST['client_id'] != ''){
        $sql .= " AND `client_id`='".addslashes($_REQUEST['client_id'])."'";
    }
    if(isset($_REQUEST['stime']) && $_REQUEST['stime'] != ''){
        $sql .= " AND `occur_time`>='".addslashes($_REQUEST['stime'])."'";
    }
    if(isset($_REQUEST['etime']) && $_REQUEST['etime'] != ''){
        $sql .= " AND `occur_time`<='".addslashes($_REQUEST['etime'])."'";
    }
    //排序
    $sql .= " ORDER BY `level` ASC ,`occur_time` DESC";
    //分页
    if(isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0){
        $page = intval($_REQUEST['page']);
        $start = ($page-1)*$page_limit;
        $sql .= " LIMIT ".$start.",".($page_limit+1);
    }else{
        exit;
    }
    $mysql_result = $mysqli->query($sql);
    $rows = array();
    while($row = $mysql_result->fetch_assoc()){
        $rows[] = $row;
    }

    //从数据库中读取错误等级信息
    $mysqli_result = $mysqli->query("SELECT * FROM hf_manageWebsite");
    while ($row = $mysqli_result->fetch_assoc()) {
        $client_id_array[] = $row;
    }
    //从数据库中读取联系人信息
    $mysql_result = $mysqli->query("SELECT * FROM `hf_manageContacts`");
    while ($row = $mysql_result->fetch_assoc()){
        $contacts_array[] = $row;
    }

    $mysqli->close();

    //创建收集站映射表
    $client_map = array();
    foreach ($client_id_array as $item){
        $client_map[$item['client_id']] = $item['client_name'];
    }
    //创建联系人映射表
    $contact_map = array();
    foreach ($contacts_array as $item){
        $contact_map[$item['id']] = $item['name'];
    }

    $res = array();
    $num = count($rows);
    if($num == $page_limit+1){
        $res['num'] = $page_limit;
        $res['next'] = 1;
        array_pop($rows);
    }else{
        $res['num'] = $num;
        $res['next'] = 0;
    }

    $number = 1;
    foreach ($rows as $key=>$row){
        $row['number'] = $number + $start;
        $row['client_id'] = $client_map[$row['client_id']];
        $contact_id = array();
        $tmp = explode(',',$row['contact_id']);
        foreach ($tmp as $item){
            $contact_id[] = $contact_map[$item];
        }
        $row['contact_id'] = implode(',',$contact_id);
        $rows[$key] = $row;
        $number++;
    }

    $res['data'] = $rows;
    echo json_encode($res);
    exit;
}
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
    $mysqli_stmt->close();
    $mysqli->close();
    exit;
}
//手动处理错误
if(isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'handleError'){
    $error_id = false;
    if(isset($_REQUEST['error_id']) && !empty($_REQUEST['error_id'])){
        $error_id = intval($_REQUEST['error_id']);
    }
    $error_counter = false;
    if(isset($_REQUEST['error_counter']) && !empty($_REQUEST['error_counter'])){
        $error_counter = intval($_REQUEST['error_counter']);
    }
    $handle_person = false;
    if(isset($_REQUEST['handle_person']) && !empty($_REQUEST['handle_person'])){
        $handle_person = $_REQUEST['handle_person'];
    }
    $handle_detail = null;
    if(isset($_REQUEST['handle_detail']) && !empty($_REQUEST['handle_detail'])){
        $handle_detail = $_REQUEST['handle_detail'];
    }
    //检测必须的字段是否已经赋值
    if(!($error_id && $handle_person && $error_counter)){
        echo -1;exit;
    }
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    //从 hf_collected_error表中查询得到数据
    $prepare_sql = "SELECT id,client_id,error_hash,error_counter,occur_time,level,msg,contact_id,alarm_time,alarmed FROM hf_collected_error WHERE id=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("i",$error_id);
    $mysqli_stmt->execute();
    $mysqli_stmt->store_result();
    $cdata = array();
    $mysqli_stmt->bind_result($cdata['id'],$cdata['client_id'],$cdata['error_hash'],$cdata['error_counter'],$cdata['occur_time'],$cdata['level'],$cdata['msg'],$cdata['contact_id'],$cdata['alarm_time'],$cdata['alarmed']);
    $mysqli_stmt->fetch();
    $mysqli_stmt->close();

    //开始事务操作，从 hf_collected_error 表中将数据迁移到 hf_handled_error
    $mysqli->autocommit(false);
    //将记录插入 hf_handled_error 数据表
    $prepare_sql = "INSERT INTO hf_handled_error(id,client_id,error_hash,error_counter,occur_time,level,msg,contact_id,alarm_time,alarmed,handle_time,handle_detail,handle_person) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("issisisssisss",$cdata['id'],$cdata['client_id'],$cdata['error_hash'],$cdata['error_counter'],$cdata['occur_time'],$cdata['level'],$cdata['msg'],$cdata['contact_id'],$cdata['alarm_time'],$cdata['alarmed'],date("Y-m-d H:i:s"),$handle_detail,$handle_person);
    $mysqli_stmt->execute();
    $insert_res = $mysqli_stmt->affected_rows;

    //从 hf_collected_error 数据表中删除记录
    $prepare_sql = "DELETE FROM hf_collected_error WHERE id=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("i",$error_id);
    $mysqli_stmt->execute();
    $delete_res = $mysqli_stmt->affected_rows;
    if($insert_res == 1 && $delete_res == 1){
        echo 1;
        $mysqli->commit();
    }else{
        echo -2;
        $mysqli->rollback();
        exit;
    }
    $mysqli_stmt->close();
    $mysqli->autocommit(true);
    $mysqli->close();
    //释放相关的redis资源
    $redis = new Redis();
    $key = $cdata['client_id']."#".substr(md5($cdata['error_hash']),0,10);
    $warning_key = "warning#".$key;
    if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
        //删除该错误的计数器
        $redis->del($key);
        //删除该错误的报警信息
        $redis->del($warning_key);
        $redis->close();
    }else{
        writeText('redis资源释放失败');
    }
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
    $mysqli_stmt->close();
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
    if(!($client_id && $client_name)){
        echo -1;exit;
    }
    //检查收集站名称是否重复
    if(!checkWebsiteName($client_name)){
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
    //插入收集站
    $prepare_sql = "INSERT INTO `hf_manageWebsite`(`client_id`,`client_name`,`comment`) VALUES (?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("sss",$client_id,$client_name,$comment);
    if($mysqli_stmt->execute()){
        echo 1;
    }
    $mysqli_stmt->close();

    //更新redis中的收集站信息
    $client_id_array = array();
    $mysqli_result = $mysqli->query("SELECT * FROM hf_manageWebsite");
    while ($row = $mysqli_result->fetch_assoc()){
        $client_id_array[] = $row;
    }
    $mysqli_result->free_result();
    $mysqli->close();
    //更新redis中的信息
    $redis = new Redis();
    if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
        $redis->set("client_data",json_encode($client_id_array));
    }else{
        writeText("redis打开失败");
    }
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
        echo 1;
    }
    $mysqli_stmt->close();

    //更新redis中的收集站信息
    $client_id_array = array();
    $mysqli_result = $mysqli->query("SELECT client_id FROM hf_manageWebsite");
    while ($row = $mysqli_result->fetch_assoc()){
        $client_id_array[] = $row['client_id'];
    }
    $mysqli_result->free_result();
    $mysqli->close();
    //更新redis中的信息
    $redis = new Redis();
    if($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")){
        $redis->set("client_id",json_encode($client_id_array));
    }else{
        writeText("redis打开失败");
    }
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
    $mailtimes = 1;
    if(isset($_REQUEST['mailtimes']) && !empty($_REQUEST['mailtimes'])){
        $mailtimes = $_REQUEST['mailtimes'];
    }
    $smstimes = 1;
    if(isset($_REQUEST['smstimes']) && !empty($_REQUEST['smstimes'])){
        $smstimes = $_REQUEST['smstimes'];
    }
    if(checkRule($client_id)){
        //检查收集站规则是否已存在
        echo -3;exit;
    }
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "INSERT INTO `hf_alert_setting`(`client_id`,`contact_name`,`sendmail`,`maillevel`,`mailtimes`,`sendsms`,`smslevel`,`smstimes`) VALUES (?,?,?,?,?,?,?,?)";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("ssiiiiii",$client_id,$contact_name,$sendmail,$maillevel,$mailtimes,$sendsms,$smslevel,$mailtimes);
    if($mysqli_stmt->execute()){
        echo $mysqli_stmt->insert_id;
    }
    $mysqli_stmt->close();
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
    $mysqli_stmt->close();
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
    $mysqli_stmt->close();
    $mysqli->close();
    if($num > 0){
        return false;
    }else{
        return true;
    }
}
//检查收集站规则是否已存在
function checkRule($client_id){
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "SELECT * FROM `hf_alert_setting` WHERE `client_id`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("s",$client_id);
    $mysqli_stmt->execute();
    $mysqli_stmt->store_result();
    $num = $mysqli_stmt->num_rows;
    $mysqli_stmt->close();
    $mysqli->close();
    if($num > 0){
        return true;
    }else{
        return false;
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
    $mysqli_stmt->close();
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
