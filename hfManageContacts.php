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
require_once dirname(__FILE__).'/include/mysqli.inc.php';

$page['title'] = _('报警联系人设置');
$page['file'] = 'hfManageContacts.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;

$mysqli = new Zmysqli();
if($mysqli->connect_errno){ //连接成功errno应该为0
    $str = 'Connect Error:'.$mysqli->connect_error;
    echo $str;
}
$mysqli->set_charset('utf8');
$mysql_result = $mysqli->query("SELECT * FROM `hf_manageContacts`");
$rows = array();
while($row = $mysql_result->fetch_assoc()){
    $rows[] = $row;
}
$mysql_result->free();
$mysqli->close();
?>
<div class="header-title table">
    <div class="cell">
        <h1>报警联系人设置</h1>
    </div>
</div>
<p id="addLink"><a href="javascript:addContact()">添加联系人</a></p>
<div id="contactList">
    <table class="list-table" style="width: 100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>姓名</th>
            <th>邮箱</th>
            <th>电话</th>
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
                echo "<td class='name'>".$item['name']."</td>";
                echo "<td class='email'>".$item['email']."</td>";
                echo "<td class='sms'>".$item['sms']."</td>";
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
                <div class="table-forms-td-left"><label for="name">姓名</label></div>
                <div class="table-forms-td-right"><input type="text" id="name" name="name" value="" maxlength="255" style="width: 300px;" autofocus="autofocus">&nbsp;&nbsp;必填</div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="email">邮箱</label></div>
                <div class="table-forms-td-right"><input type="text" id="email" name="email" value="" maxlength="255" style="width: 300px;" autofocus="autofocus">&nbsp;&nbsp;必填</div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="phone">电话</label></div>
                <div class="table-forms-td-right"><input type="text" id="phone" name="phone" value="" maxlength="255" style="width: 300px;" autofocus="autofocus">&nbsp;&nbsp;必填</div>
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
        var name = document.getElementById("name").value;
        var email = document.getElementById("email").value;
        var phone = document.getElementById("phone").value;
        var comment = document.getElementById("comment").value;
        //检查必填字段
        if(name == '' || email == '' || phone == ''){
            alert("请检查必填字段是否已填写");
            return false;
        }
        var param = "cmd=addContact&name="+name+"&email="+email+"&phone="+phone+"&comment="+comment;
        ajaxFun(param);
    }

    //删除联系人操作
    function delContact(id) {
        var param = "cmd=delContact&id="+id;
        ajaxFun(param);
    }

    //取消联系人表单
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
                var responseNum=xmlhttp.responseText;
                if(responseNum == 1){
                    location.reload();
                    return;
                }
                if(responseNum == -1){
                    alert("请检查必填字段是否已填写");
                    return;
                }
                if(responseNum == -2){
                    alert("姓名重复，请修改后重试");
                    return;
                }
                alert(responseNum);
            }
        }
        xmlhttp.open("POST","hfAjaxFunction.php",true);
        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xmlhttp.send(param);
    }
</script>
<?php
require_once dirname(__FILE__).'/include/page_footer.php';