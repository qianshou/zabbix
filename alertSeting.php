<?php
/*
** Zabbix
** Copyright (C) 2001-2016 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/triggers.inc.php';
require_once dirname(__FILE__).'/include/media.inc.php';
require_once dirname(__FILE__).'/include/users.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';
require_once dirname(__FILE__).'/include/js.inc.php';

$page['title'] = _('错误报警设置');
$page['file'] = 'alertSeting.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;

$mysqli = new mysqli("127.0.0.1","root","root","zabbix");
if($mysqli->connect_errno){ //连接成功errno应该为0
    $str = 'Connect Error:'.$mysqli->connect_error;
    echo $str;
}
$mysqli->set_charset('utf8');
$mysql_result = $mysqli->query("SELECT `item`,`value` FROM `alert_setting`");
$item = array();
while($row = $mysql_result->fetch_assoc()){
    $item[$row['item']] = $row['value'];
}
$mysql_result->free();
$mysqli->close();
?>
<div class="header-title table">
    <div class="cell">
        <h1>错误信息报警设置</h1>
    </div>
</div>
<div>
    <table class="list-table" style="width: 35%">
        <thead>
            <tr>
                <th>项目</th>
                <th>取值</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>短信收件人</td>
                <td><input type='text' id='message' style="width: 100%;margin-right: 20px;" disabled="disabled" value="<?php echo isset($item['message'])? $item['message']:null;?>"></td>
                <td><a href="javascript:update('message')" id="message_link">修改</a></td>
            </tr>
            <tr>
                <td>邮件收件人</td>
                <td><input type='text' id='email'  style="width: 100%;margin-right: 20px;" disabled="disabled" value="<?php echo isset($item['email'])? $item['email']:null;?>"></td>
                <td><a href="javascript:update('email')" id="email_link">修改</a></td>
            </tr>
        </tbody>
    </table>
</div>
<script  type="text/javascript">
    function update(idName) {
        var element = document.getElementById(idName);
        element.removeAttribute("disabled");
        element.focus();
        var link = document.getElementById(idName+"_link");
        link.text = '确定';
        link.setAttribute("href","javascript:save('"+idName+"')");
    }
    function save(idName) {
        box = document.getElementById(idName);
        box.setAttribute("disabled","disabled");
        var link = document.getElementById(idName+"_link");
        link.text = '修改';
        link.setAttribute("href","javascript:update('"+idName+"')");
        var value = box.value;
        getResponse(idName,value);
    };
    function getResponse(item,value) {
        var xmlhttp;
        var responseText;
        if (window.XMLHttpRequest)
        {// code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp=new XMLHttpRequest();
        }
        else
        {// code for IE6, IE5
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange=function()
        {
            if (xmlhttp.readyState==4 && xmlhttp.status==200)
            {
                responseText=xmlhttp.responseText;
                //console.log(responseText);
                if(responseText != 'ok'){
                    alert('保存收件人失败');
                }
            }
        }
        xmlhttp.open("GET","updateAlertSetting.php?item="+item+"&value="+value,true);
        xmlhttp.send();
    }
</script>

<?php
require_once dirname(__FILE__).'/include/page_footer.php';