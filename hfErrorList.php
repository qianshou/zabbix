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

$page['title'] = _('错误信息列表');
$page['file'] = 'hfErrorList.php';

require_once dirname(__FILE__).'/include/page_header.php';

$themes = array_keys(Z::getThemes());
$themes[] = THEME_DEFAULT;

$mysqli = new mysqli("127.0.0.1","root","root","zabbix");
if($mysqli->connect_errno){ //连接成功errno应该为0
    $str = 'Connect Error:'.$mysqli->connect_error;
    echo $str;
}
$mysqli->set_charset('utf8');
$mysql_result = $mysqli->query("SELECT * FROM `hf_collected_error` ORDER BY `occur_time` DESC LIMIT 0,100");
$item = array();
while($row = $mysql_result->fetch_assoc()){
    $rows[] = $row;
}
$mysql_result->free();
$mysqli->close();
?>
<div class="header-title table">
    <div class="cell">
        <h1>错误信息列表</h1>
    </div>
</div>
<div>
    <table class="list-table">
        <thead>
        <tr>
            <th style="width: 6%">等级</th>
            <th style="width: 8%">发生时间</th>
            <th style="width: 6%">错误来源</th>
            <th>错误信息</th>
            <th style="width: 5%">已处理</th>
            <th style="width: 8%">处理时间</th>
            <th style="width: 10%">发送邮件</th>
            <th style="width: 10%">发送短信</th>
        </tr>
        </thead>
        <tbody>
        <?php
            foreach ($rows as $item){
                echo "<tr>";
                switch ($item['level']){
                    case 'error':
                        echo "<td class='high-bg'>错误</td>";
                        break;
                    case "warning":
                        echo "<td class='average-bg'>警告</td>";
                        break;
                    default:
                        echo "<td class='info-bg'>通知</td>";
                        break;
                }
                echo "<td>".$item['occur_time']."</td>";
                echo "<td>".$item['client_id']."</td>";
                echo "<td><pre>".$item['msg']."</pre></td>";
                echo "<td>".$item['finished']."</td>";
                echo "<td>".$item['handle_time']."</td>";
                echo "<td>".$item['mail']."</td>";
                echo "<td>".$item['sms']."</td>";
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
<?php
require_once dirname(__FILE__).'/include/page_footer.php';
