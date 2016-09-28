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

$page['title'] = _('Alert Setting');
$page['file'] = 'alertSeting.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;
ob_clean();

if(isset($_REQUEST['item']) && isset($_REQUEST['value']) && !empty($_REQUEST['item']) && !empty($_REQUEST['value'])){
    $item = $_REQUEST['item'];
    $value = $_REQUEST['value'];
    $mysqli = new mysqli("127.0.0.1","root","root","zabbix");
    if($mysqli->connect_errno){ //连接成功errno应该为0
        writeText('Connect Error:'.$mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    $prepare_sql = "SELECT `item`,`value` FROM `alert_setting` WHERE `item`=?";
    $mysqli_stmt = $mysqli->prepare($prepare_sql);
    $mysqli_stmt->bind_param("s",$item);
    $mysqli_stmt->execute();
    $mysqli_stmt->store_result();
    if($mysqli_stmt->num_rows()){
        //更新操作
        $mysqli_stmt->free_result();
        $mysqli_stmt = null;
        $prepare_sql = "UPDATE `alert_setting` SET `value`=? WHERE `item`='$item'";
        $mysqli_stmt = $mysqli->prepare($prepare_sql);
        $mysqli_stmt->bind_param("s",$value);
    }else{
        //插入操作
        $mysqli_stmt->free_result();
        $mysqli_stmt = null;
        $prepare_sql = "INSERT INTO `alert_setting`(`item`,`value`) VALUES(?,?)";
        $mysqli_stmt = $mysqli->prepare($prepare_sql);
        $mysqli_stmt->bind_param("ss",$item,$value);
    }
    if($mysqli_stmt->execute()){
        echo "ok";
    }else{
        writeText('Insert/Update Error:$item,$value');
    }
    $mysqli->close();
}else{
    echo "invalid request";
}
//写入文本文件中的日志
function writeText($str){
    $content = date("Y-m-d H:i:s");
    $content = "[".$content."|updateAlertSetting]".$str."\r\n";
    file_put_contents("/var/log/colletErrorInterface.log",$content,FILE_APPEND);
}