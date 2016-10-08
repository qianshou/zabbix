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
require_once dirname(__FILE__).'/include/mysqli.inc.php';
$page['title'] = _('错误报警设置');
$page['file'] = 'hfAlertSeting.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;

$mysqli = new Zmysqli();
if($mysqli->connect_errno){ //连接成功errno应该为0
    $str = 'Connect Error:'.$mysqli->connect_error;
    echo $str;
}
$mysqli->set_charset('utf8');
//查询已有报警配置
$mysql_result = $mysqli->query("SELECT a.id ,a.client_id,w.client_name,a.contact_name,a.sendmail,a.maillevel,a.smslevel,a.sendsms,c.email,c.sms FROM hf_alert_setting AS a INNER JOIN hf_manageContacts AS c ON a.contact_name=c.name INNER JOIN hf_manageWebsite AS w ON a.client_id=w.client_id");
$rows = array();
while($row = $mysql_result->fetch_assoc()){
    $rows[] = $row;
}
$mysql_result->free();
//查询所有收集站
$mysql_result = $mysqli->query("SELECT  client_id,client_name FROM hf_manageWebsite");
$website_rows = array();
while($row = $mysql_result->fetch_assoc()){
    $website_rows[] = $row;
}
$mysql_result->free();
//查询所有联系人
$mysql_result = $mysqli->query("SELECT name FROM hf_manageContacts");
$contacts_rows = array();
while($row = $mysql_result->fetch_assoc()){
    $contacts_rows[] = $row;
}
//查询错误分级
$mysql_result = $mysqli->query("SELECT level_num,level_name FROM hf_error_level");
$level_rows = array();
while($row = $mysql_result->fetch_assoc()){
    $level_rows[] = $row;
}
$mysql_result->free();
$mysqli->close();
//错误分级映射
$level_map = array();
foreach ($level_rows as $row){
    $level_map[$row['level_num']] = $row['level_name'];
}
?>
<div class="header-title table">
    <div class="cell">
        <h1>错误报警设置</h1>
    </div>
</div>
<p id="addLink"><a href="javascript:addSetting()">添加报警规则</a></p>
<div id="contactList">
    <table class="list-table" style="width: 100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>收集站编号</th>
            <th>收集站名称</th>
            <th>联系人姓名</th>
            <th>是否发送邮件</th>
            <th>邮件错误级别</th>
            <th>是否发送短信</th>
            <th>短信错误级别</th>
            <th>删除</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $id = 0;
        foreach ($rows as $item){
            $id++;
            echo "<tr id='".$item['id']."'>";
            echo "<td class='id'>".$id."</td>";
            echo "<td class='client_id'>".$item['client_id']."</td>";
            echo "<td class='client_name'>".$item['client_name']."</td>";
            echo "<td class='contact_name'>".$item['contact_name']."</td>";
            echo "<td class='sendmail'>";
            if($item['sendmail'] == 1){
                echo "是（".$item['email']."）";
            }elseif ($item['sendmail'] == 0){
                echo "否";
            }
            echo "</td>";
            echo "<td class='maillevel'>";
            echo ($item['maillevel']!=0)? $level_map[$item['maillevel']]:"未设置";
            echo "</td>";
            echo "<td class='sendsms'>";
            if($item['sendsms'] == 1){
                echo "是（".$item['sms']."）";
            }elseif ($item['sendsms'] == 0){
                echo "否";
            }
            echo "</td>";
            echo "<td class='smslevel'>";
            echo ($item['smslevel']!=0)? $level_map[$item['smslevel']]:"未设置";
            echo "</td>";
            echo "<td><a href='javascript:delSetting(".$item['id'].")'>删除</a></td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</div>
<div id="addForm" style="display: none">
    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom" style="margin-top: 30px;">
        <ul class="table-forms" id="userFormList">
            <li>
                <div class="table-forms-td-left"><label for="website">选择收集站</label></div>
                <div class="table-forms-td-right">
                    <select name="website" id="website"  style="width: 300px;">
                        <?php
                            foreach ($website_rows as $website){
                                echo "<option value=\"".$website['client_id']."\">".$website['client_name']."</option>";
                            }
                        ?>
                    </select>
                </div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="contact">选择联系人</label></div>
                <div class="table-forms-td-right">
                    <select name="contact" id="contact"  style="width: 300px;">
                        <?php
                        foreach ($contacts_rows as $contact){
                            echo "<option value=\"".$contact['name']."\">".$contact['name']."</option>";
                        }
                        ?>
                    </select>
                </div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="comment">操作</label></div>
                <div class="table-forms-td-right">
                    <p>
                        <input type="checkbox" value="email" id="email" onclick="javascript:mailFun(this)"/>&nbsp;&nbsp;发送邮件
                        <select name="maillevel" id="maillevel"  style="width: 100px;margin-left: 30px;" disabled="disabled">
                            <?php
                                foreach ($level_rows as $level){
                                    echo " <option value=\"".$level['level_num']."\">".$level['level_name']."</option>";
                                }
                            ?>
                        </select>
                    </p>
                    <p>
                        <input type="checkbox" value="sms" id="sms" onclick="javascript:smsFun(this)"/>&nbsp;&nbsp;发送短信
                        <select name="smslevel" id="smslevel"  style="width: 100px;margin-left: 30px;" disabled="disabled">
                            <?php
                            foreach ($level_rows as $level){
                                echo " <option value=\"".$level['level_num']."\">".$level['level_name']."</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>
            </li>
            <li>
                <div class="table-forms-td-left"></div>
                <div class="table-forms-td-right tfoot-buttons">
                    <button type="submit" id="add" name="add" value="添加" onclick="javascript:addSubmit()">添加</button>
                    <button type="button" id="cancel" name="cancel" onclick="javascript:cancelForm()" class="btn-alt">取消</button>
                </div>
            </li>
        </ul>
    </div>
</div>
<script  type="text/javascript">
    //设置发送邮件错误等级
    function mailFun(obj) {
        var tag = obj.checked;
        var maillevel = document.getElementById("maillevel");
        if(tag == true){
            maillevel.removeAttribute("disabled");
        }else{
            maillevel.setAttribute("disabled","disabled");
        }
    }
    //设置发送短信错误等级
    function smsFun(obj) {
        var tag = obj.checked;
        var smslevel = document.getElementById("smslevel");
        if(tag == true){
            smslevel.removeAttribute("disabled");
        }else{
            smslevel.setAttribute("disabled","disabled");
        }
    }
    //显示添加设置表单
    function addSetting() {
        var addLink = document.getElementById("addLink");
        addLink.hide();
        var contactList = document.getElementById("contactList");
        contactList.hide();
        var addForm = document.getElementById("addForm");
        addForm.show();
    }
    //执行添加设置操作
    function addSubmit() {
        var wobj = document.getElementById("website");
        var windex = wobj.selectedIndex;
        var website = wobj.options[windex].value;
        var cobj = document.getElementById("contact");
        var cindex = cobj.selectedIndex;
        var contact = cobj.options[cindex].value;
        var sendmail = 0;
        var maillevel = 0;
        if(document.getElementById("email").checked){
            sendmail = 1;
            var obj = document.getElementById("maillevel")
            var index = obj.selectedIndex;
            maillevel = obj.options[index].value;
        }
        var sendsms = 0;
        var smslevel = 0;
        if(document.getElementById("sms").checked){
            sendsms = 1;
            var obj = document.getElementById("smslevel")
            var index = obj.selectedIndex;
            smslevel = obj.options[index].value;
        }
        var param = "cmd=addSetting&website="+website+"&contact="+contact+"&sendmail="+sendmail+"&maillevel="+maillevel+"&sendsms="+sendsms+"&smslevel="+smslevel;
        //console.log(param);
        ajaxFun(param);
    }
    //删除设置操作
    function delSetting(id) {
        var param = "cmd=delSetting&id="+id;
        ajaxFun(param);
    }
    //取消添加设置表单
    function cancelForm() {
        var addForm = document.getElementById("addForm");
        addForm.hide();
        var addLink = document.getElementById("addLink");
        addLink.show();
        var contactList = document.getElementById("contactList");
        contactList.show();
    }
    //执行ajax请求
    function ajaxFun(param) {
        //发送ajax请求
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
                location.reload();
                console.log(responseText);
            }
        }
        xmlhttp.open("POST","hfAjaxFunction.php",true);
        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xmlhttp.send(param);
    }
</script>
<?php
require_once dirname(__FILE__).'/include/page_footer.php';