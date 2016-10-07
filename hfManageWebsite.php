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

$page['title'] = _('错误信息站点设置');
$page['file'] = 'hfManageWebsite.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;

$mysqli = new mysqli("127.0.0.1","root","root","zabbix");
if($mysqli->connect_errno){ //连接成功errno应该为0
    $str = 'Connect Error:'.$mysqli->connect_error;
    echo $str;
}
$mysqli->set_charset('utf8');
$mysql_result = $mysqli->query("SELECT * FROM `hf_manageWebsite`");
$rows = array();
while($row = $mysql_result->fetch_assoc()){
    $rows[] = $row;
}
$mysql_result->free();
$mysqli->close();
?>
<div class="header-title table">
    <div class="cell">
        <h1>错误信息站点设置</h1>
    </div>
</div>
<p id="addLink"><a href="javascript:addContact()">添加收集站</a></p>
<div id="contactList">
    <table class="list-table" style="width: 100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>收集站编号</th>
            <th>收集站名称</th>
            <th>备注</th>
            <!--            <th>修改</th>-->
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
            echo "<td class='comment'>".$item['comment']."</td>";
//                echo "<td><a href='javascript:updateContact(".$item['id'].")'>修改</a></td>";
            echo "<td><a href='javascript:delContact(".$item['id'].")'>删除</a></td>";
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
                <div class="table-forms-td-left"><label for="client_id">收集站编号</label></div>
                <div class="table-forms-td-right"><input type="text" id="client_id" name="client_id" value="" maxlength="255" style="width: 300px;" autofocus="autofocus"></div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="client_name">收集站名称</label></div>
                <div class="table-forms-td-right"><input type="text" id="client_name" name="client_name" value="" maxlength="255" style="width: 300px;" autofocus="autofocus"></div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="comment">备注</label></div>
                <div class="table-forms-td-right">
                    <textarea id="comment" name="comment" value=""  style="width: 300px;height: 60px;"></textarea>
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
    //显示添加联系人表单
    function addContact() {
        var addLink = document.getElementById("addLink");
        addLink.hide();
        var contactList = document.getElementById("contactList");
        contactList.hide();
        var addForm = document.getElementById("addForm");
        addForm.show();
    }
    //执行添加联系人操作
    function addSubmit() {
        var client_id = document.getElementById("client_id").value;
        var client_name = document.getElementById("client_name").value;
        var comment = document.getElementById("comment").value;
        var param = "cmd=addWebsite&client_id="+client_id+"&client_name="+client_name+"&comment="+comment;
        ajaxFun(param);
    }
    //删除联系人操作
    function delContact(id) {
        var param = "cmd=delWebsite&id="+id;
        ajaxFun(param);
    }
    //取消添加/修改联系人表单
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