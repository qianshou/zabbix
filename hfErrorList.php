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

$page['title'] = _('未解决错误列表');
$page['file'] = 'hfErrorList.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;

$redis = new Redis();
if( !($redis->connect('127.0.0.1', 6379) && $redis->auth("hfcasnic")) ){
    writeText("redis打开失败");
    exit;
}
//从redis获取错误分级信息
$error_level_array = array();
$error_key = "error_level";
$client_id_array = array();
$client_key = "client_data";
if($redis->exists($error_key) && $redis->exists($client_key)){
    $error_level_json = $redis->get($error_key);
    $client_id_json = $redis->get($client_key);
    $redis->close();
    $error_level_array = json_decode($error_level_json,true);
    $client_id_array = json_decode($client_id_json,true);
}else{
    $mysqli = new Zmysqli();
    if($mysqli->connect_errno){ //连接成功errno应该为0
        $str = 'Connect Error:'.$mysqli->connect_error;
        echo $str;
    }
    $mysqli->set_charset('utf8');
    //从数据库中读取错误等级信息
    if(!$redis->exists($error_key)){
        $mysql_result = $mysqli->query("SELECT * FROM hf_error_level");
        while($row = $mysql_result->fetch_assoc()){
            $error_level_array[] = $row;
        }
        $redis->set($error_key,json_encode($error_level_array));
    }
    //从数据库中读取收集站信息
    if(!$redis->exists($client_key)) {
        $mysqli_result = $mysqli->query("SELECT * FROM hf_manageWebsite");
        while ($row = $mysqli_result->fetch_assoc()) {
            $client_id_array[] = $row;
        }
        $redis->set($client_key, json_encode($client_id_array));
    }
}
?>
<div class="header-title table">
    <div class="cell">
        <h1>未解决错误列表</h1>
    </div>
</div>
<div class="filter-btn-container">
    <button type="button" class="filter-trigger" id="filter-mode" onclick="javascript:
        jQuery(&quot;#filter-space&quot;).toggle();
        jQuery(&quot;#filter-mode&quot;).toggleClass(&quot;filter-active&quot;);
        jQuery(&quot;#filter-arrow&quot;).toggleClass(&quot;arrow-up arrow-down&quot;);
        updateUserProfile(&quot;web.avail_report.filter.state&quot;, jQuery(&quot;#filter-arrow&quot;).hasClass(&quot;arrow-up&quot;) ? 1 : 0, []);
        if (jQuery(&quot;.multiselect&quot;).length > 0 &amp;&amp; jQuery(&quot;#filter-arrow&quot;).hasClass(&quot;arrow-up&quot;)) {
            jQuery(&quot;.multiselect&quot;).multiSelect(&quot;resize&quot;);
        }
        if (jQuery(&quot;#filter-arrow&quot;).hasClass(&quot;arrow-up&quot;)) {
            jQuery(&quot;#filter-space [autofocus=autofocus]&quot;).focus();
        }">过滤器<span id="filter-arrow" class="arrow-down"></span>
    </button>
</div>
<div class="filter-container" id="filter-space" style="display: none;">
    <div class="table filter-forms">
        <div class="row">
            <div class="cell">
                <ul class="table-forms">
                    <li>
                        <div class="table-forms-td-left">
                            <label for="filter_groupid">错误等级</label>
                        </div>
                        <div class="table-forms-td-right">
                            <select id="level" name="level" onchange="javascript: singleSelect();" autocomplete="off" autofocus="autofocus">
                                <option value='all' >全部错误</option>
                                <?php
                                foreach ($error_level_array as $item){
                                    echo "<option value='".$item['level_num']."' >".$item['level_name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </li>
                    <li>
                        <div class="table-forms-td-left">
                            <label for="filter_hostid">收集站名</label>
                        </div>
                        <div class="table-forms-td-right">
                            <select id="client_id" name="client_id" onchange="javascript: singleSelect();" autocomplete="off">
                                <option value='all' >全部收集站</option>
                                <?php
                                foreach ($client_id_array as $item){
                                    echo "<option value='".$item['client_id']."' >".$item['client_name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="cell">
                <ul class="table-forms">
                    <li>
                        <div class="table-forms-td-left">
                            <label for="start_year">开始时间（错误发生）</label>
                        </div>
                        <div class="table-forms-td-right">
                            <input type="text" id="start_year" name="start_year" value="0000" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>-<div class="form-input-margin"></div>
                            <input type="text" id="start_month" name="start_month" value="00" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>-<div class="form-input-margin"></div>
                            <input type="text" id="start_day" name="start_day" value="00" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>
                            <div class="form-input-margin"></div>
                            <input type="text" id="start_hours" name="start_hours" value="00" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>:<div class="form-input-margin"></div>
                            <input type="text" id="start_minutes" name="start_minutes" value="00" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>
                            <div class="form-input-margin"></div>
                            <button type="button" id="button" name="button" class="icon-cal" onclick="javascript:showCalendar(this,'start');"></button>
                        </div>
                    </li>
                    <li>
                        <div class="table-forms-td-left">
                            <label for="end_year">结束时间（错误发生）</label>
                        </div>
                        <div class="table-forms-td-right">
                            <input type="text" id="end_year" name="end_year" value="" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>-<div class="form-input-margin"></div>
                            <input type="text" id="end_month" name="end_month" value="" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>-<div class="form-input-margin"></div>
                            <input type="text" id="end_day" name="end_day" value="" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>
                            <div class="form-input-margin"></div>
                            <input type="text" id="end_hours" name="end_hours" value="" maxlength="4" style="text-align: right;width: 50px;"/>
                            <div class="form-input-margin"></div>:<div class="form-input-margin"></div>
                            <input type="text" id="end_minutes" name="end_minutes" value="" maxlength="4" style="text-align: right;width: 50px;" />
                            <div class="form-input-margin"></div>
                            <div class="form-input-margin"></div>
                            <button type="button" id="button" name="button" class="icon-cal" onclick="javascript:showCalendar(this,'end');"></button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="filter-forms">
        <button type="submit" id="apply" value="1" onclick="javascript: totalSelect();">应用</button>
        <button type="button" id='reset' class="btn-alt" onclick="javascript: resetSelect();">重设</button>
    </div>
</div>
<div id="listTable">

</div>
<div id="handleForm" style="display: none">
    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom" style="margin-top: 30px;">
        <ul class="table-forms" id="userFormList">
            <input type="hidden" id="error_id" />
            <input type="hidden" id="error_counter" />
            <li>
                <div class="table-forms-td-left"><label for="handle_person">错误处理人</label></div>
                <div class="table-forms-td-right"><input type="text" id="handle_person" name="handle_person" value="" maxlength="255" style="width: 300px;" autofocus="autofocus">&nbsp;&nbsp;必填</div>
            </li>
            <li>
                <div class="table-forms-td-left"><label for="handle_detail">错误处理细节</label></div>
                <div class="table-forms-td-right">
                    <textarea id="handle_detail" name="handle_detail" value=""  style="width: 300px;height: 60px;"></textarea>
                </div>
            </li>
            <li>
                <div class="table-forms-td-left"></div>
                <div class="table-forms-td-right tfoot-buttons">
                    <button type="submit" id="add" name="add" value="确定" onclick="javascript:confirmForm()">确定</button>
                    <button type="button" id="cancel" name="cancel" onclick="javascript:cancelForm()" class="btn-alt">取消</button>
                </div>
            </li>
        </ul>
    </div>
</div>
    <div id="calendar" class="overlay-dialogue calendar" style="display: none; top: 176px; left: 1088px;">
        <div class="calendar-header">
            <div class="calendar-year">
                <button type="button" class="btn-grey" onclick="javascript:calendarYearDec()">
                    <span class="arrow-left"></span>
                </button>
                <span id="display_year">2016</span>年
                <button type="button" class="btn-grey" onclick="javascript:calendarYearInc()">
                    <span class="arrow-right"></span>
                </button>
            </div>
            <div class="calendar-month" >
                <button type="button" class="btn-grey" onclick="javascript:calendarMonthDec()">
                    <span class="arrow-left"></span>
                </button>
                <span id="display_month">8</span>月
                <button type="button" class="btn-grey" onclick="javascript:calendarMonthInc()">
                    <span class="arrow-right"></span>
                </button>
            </div>
        </div>
        <table id="calendar_table">
            <thead>
            <tr><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th><th>S</th></tr>
            </thead>
            <tbody id="calendar_body">

            </tbody>
        </table>
        <div class="calendar-time">
            时间 <input type="text" id="hour" value="00" maxlength="2" class="calendar_textbox"> : <input type="text" id="minute" value="00" maxlength="2" class="calendar_textbox">
        </div>
        <div class="calendar-footer">
            <button id="now" class="btn-grey" type="button" value="现在" >现在</button>
            <button id="finished" type="button">完成</button>
        </div>
    </div>
<script  type="text/javascript">
    //单条件查询
    function singleSelect() {
        var param = '';
        var level_value = jQuery("#level").find("option:selected").val();
        param += "level="+level_value;
        var client_id_value = jQuery("#client_id").find("option:selected").val();
        param += "&client_id="+client_id_value;
        generatePages(param,1);
    }
    //复合查询
    function totalSelect() {
        var param = '';
        var level_value = jQuery("#level").find("option:selected").val();
        param += "level="+level_value;
        var client_id_value = jQuery("#client_id").find("option:selected").val();
        param += "&client_id="+client_id_value;
        var s_year = jQuery("#start_year").val();
        var s_month = jQuery("#start_month").val();
        var s_day = jQuery("#start_day").val();
        var s_hour = jQuery("#start_hours").val();
        var s_minute = jQuery("#start_minutes").val();
        param += "&stime="+s_year+"-"+s_month+"-"+s_day+" "+s_hour+":"+s_minute;
        var e_year = jQuery("#end_year").val();
        var e_month = jQuery("#end_month").val();
        var e_day = jQuery("#end_day").val();
        var e_hour = jQuery("#end_hours").val();
        var e_minute = jQuery("#end_minutes").val();
        param += "&etime="+e_year+"-"+e_month+"-"+e_day+" "+e_hour+":"+e_minute;
        generatePages(param,1);
    }
    //重设查询
    function resetSelect() {
        jQuery("#level").val('all');
        jQuery("#client_id").val('all');
        jQuery("#start_year").val('0000');
        jQuery("#start_month").val('00');
        jQuery("#start_day").val('00');
        jQuery("#start_hours").val('00');
        jQuery("#start_minutes").val('00');
        var date = new Date();
        jQuery("#end_year").val(date.getFullYear());
        jQuery("#end_month").val(parseInt(date.getMonth())+1);
        jQuery("#end_day").val(date.getDate());
        jQuery("#end_hours").val(date.getHours());
        jQuery("#end_minutes").val(date.getMinutes());
        jQuery("#apply").click();
    }
    //默认结束时间为当前时间
    var date = new Date();
    document.getElementById("end_year").value = date.getFullYear();
    document.getElementById("end_month").value = parseInt(date.getMonth())+1;
    document.getElementById("end_day").value = date.getDate();
    document.getElementById("end_hours").value = date.getHours();
    document.getElementById("end_minutes").value = date.getMinutes();
    //默认开始时间为一周前
    date.setDate(date.getDate()-7);
    document.getElementById("start_year").value = date.getFullYear();
    document.getElementById("start_month").value = parseInt(date.getMonth())+1;
    document.getElementById("start_day").value = date.getDate();
    document.getElementById("start_hours").value = date.getHours();
    document.getElementById("start_minutes").value = date.getMinutes();
    generatePages(null,1);
    //生成页面分页
    function generatePages(param,number) {
        //生成所有的分页div
        jQuery.ajax({
            type:"GET",
            url:"hfAjaxFunction.php?cmd=getErrorList&page="+number+"&"+param,
            dataType:"json",
            success:function (data) {
                content = '<div class="page">';
                //显示错误列表
                content += '<table class="list-table">';
                content += '<thead>' +
                    '<tr>' +
                    '<th style="width: 2%">序号</th>'+
                    '<th style="width: 3%">错误等级</th> ' +
                    '<th style="width: 8%">发生时间</th>' +
                    '<th style="width: 2%">发生次数</th>' +
                    '<th style="width: 5%">错误来源</th>' +
                    '<th style="width: 5%">错误标识</th>' +
                    '<th>错误信息</th>' +
                    '<th style="width: 5%">是否报警</th>' +
                    '<th style="width: 8%">告警时间</th>' +
                    '<th style="width: 8%">报警联系人</th>' +
                    '<th style="width: 5%">处理错误</th>' +
                    '</tr>' +
                    '</thead>'+
                    '<tbody>';
                if(data.num == 0){
                    //没有数据可以显示
                    content += "<tr style='line-height: 50px;text-align: center'><td colspan='12'>没有数据<td></tr>"+
                         "</tbody>"+
                         "</table></div>";
                    jQuery("#listTable").empty().append(content);
                }else{
                    //显示错误信息列表
                   for(var j = 0 ; j < data.num ; j++){
                        content += "<tr>";
                        content += "<td>"+data.data[j].number+"</td>";
                        switch (data.data[j].level){
                            case '1':
                                content += "<td class='high-bg'>错误</td>";
                                break;
                            case '2':
                                content += "<td class='average-bg'>警告</td>";
                                break;
                            case '3':
                                content += "<td class='info-bg'>通知</td>";
                                break;
                        }
                        content += "<td>"+data.data[j].occur_time+"</td>";
                        content += "<td>"+data.data[j].error_counter+"</td>";
                        content += "<td>"+data.data[j].client_id+"</td>";
                        content += "<td>"+data.data[j].error_hash+"</td>";
                        content += "<td><pre>"+data.data[j].msg+"</pre></td>";
                        content += "<td>";
                        content += (data.data[j].alarmed == 1)? "是":"否";
                        content += "</td>";
                        content += "<td>"+data.data[j].alarm_time+"</td>";
                        content += "<td>"+data.data[j].contact_id+"</td>";
                        content += "<td>";
                        content += "<a href='javascript:handleError(\""+data.data[j].id+"\",\""+data.data[j].error_counter+"\")'>手动处理</a></td>";
                        content += "</tr>";
                    }
                    content += "</tbody>";
                    content += "</table>";

                    //显示分页按钮
                    content += '<div class="table-paging"><div class="paging-btn-container">';
                    if(number > 1){
                        content += '<a href="javascript:generatePages(\''+param+'\','+(number-1)+')"><span class="arrow-left"></span></a>';
                    }

                    if(number > 1){
                        content += '<a href="javascript:generatePages(\''+param+'\','+(number-1)+')">'+(parseInt(number-1))+'</a>';
                    }
                    content += '<a class="paging-selected" href="javascript:generatePages(\''+param+'\','+number+')">'+number+'</a>';
                    if(data.next == 1){
                        content += '<a href="javascript:generatePages(\''+param+'\','+(number+1)+')">'+(parseInt(number)+1)+'</a>';
                    }

                    if(data.next == 1){
                        content += '<a href="javascript:generatePages(\''+param+'\','+(number+1)+')"><span class="arrow-right"></span></a>';
                    }

                }
                jQuery("#listTable").empty().append(content);
            }
        });
    }
    //显示错误处理表单
    function handleError(id,counter,key) {
        var listTable = document.getElementById("listTable");
        listTable.hide();
        var handleForm = document.getElementById("handleForm");
        handleForm.show();
        var error_id = document.getElementById("error_id");
        error_id.value = id;
        var error_counter = document.getElementById("error_counter");
        error_counter.value = counter;
        var counter_key = document.getElementById("counter_key");
        counter_key.value = key;
    }

    //执行错误处理操作
    function confirmForm() {
        var error_id = document.getElementById("error_id").value;
        var error_counter = document.getElementById("error_counter").value;
        var handle_person = document.getElementById("handle_person").value;
        var handle_detail = document.getElementById("handle_detail").value;
        //检查必填字段
        if(error_id == '' || handle_person == '' || error_counter == ''){
            alert("请检查必填字段是否已填写");
            return false;
        }
        var param = "cmd=handleError&error_id="+error_id+"&error_counter="+error_counter+"&handle_person="+handle_person+"&handle_detail="+handle_detail;
        //console.log(param);
        ajaxFun(param);
    }

    //取消错误处理表单
    function cancelForm() {
        var listTable = document.getElementById("listTable");
        listTable.show();
        var handleForm = document.getElementById("handleForm");
        handleForm.hide();
    }
    //通过js执行ajax请求
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
                    alert("操作失败，请稍后重试");
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
<script type="text/javascript" src="./js/calendar.js"></script>
<?php
require_once dirname(__FILE__).'/include/page_footer.php';
