/**
 * Created by zhezhao on 2016/10/11.
 */
//设置时间为当前时间
function setNow(tag) {
    //获取时间
    var date = new Date();
    var current_year = date.getFullYear();
    var current_month = date.getMonth();
    var current_day = date.getDate();
    var current_hours = date.getHours();
    var current_minutes = date.getMinutes();
    //设置时间
    document.getElementById(tag+"_year").value = current_year;
    document.getElementById(tag+"_month").value = parseInt(current_month)+1;
    document.getElementById(tag+"_day").value = current_day;
    document.getElementById(tag+"_hours").value = current_hours;
    document.getElementById(tag+"_minutes").value = current_minutes;
    //关闭日历
    document.getElementById("calendar").hide();
}
//确认时间的选择
function dateFinished(tag){
    //设置时间
    document.getElementById(tag+"_year").value = document.getElementById('display_year').innerText;
    document.getElementById(tag+"_month").value = document.getElementById('display_month').innerText;
    document.getElementById(tag+"_day").value = document.querySelector("td.selected").innerText;
    document.getElementById(tag+"_hours").value = document.getElementById('hour').value;
    document.getElementById(tag+"_minutes").value = document.getElementById('minute').value;
    //关闭日历
    document.getElementById("calendar").hide();
}
//显示日历控件
function showCalendar(obj,tag) {
    //设置“现在”按钮
    document.getElementById("now").setAttribute("onclick","javascript:setNow('"+tag+"')");
    //设置“完成”按钮
    document.getElementById("finished").setAttribute("onclick","javascript:dateFinished('"+tag+"')");
    var date = new Date();
    var current_year = date.getFullYear();
    var current_month = date.getMonth();
    var current_day = date.getDate();
    var current_hours = date.getHours();
    var current_minutes = date.getMinutes();
    //设置年份为当前年份
    document.getElementById('display_year').innerText = current_year;
    //设置月份为当前月份
    document.getElementById('display_month').innerText = current_month+parseInt(1);
    //设置为当前的小时、分钟
    document.getElementById("hour").value = current_hours;
    document.getElementById("minute").value = current_minutes;
    //绘制日历
    printCalendar();
    //高亮显示当前的天
    chooseDay(current_day);
    //显示日历
    var pos = getPosition(obj);
    pos.top += 10;
    pos.left += 16;
    var calendar = document.getElementById("calendar");
    calendar.style.top = pos.top+"px";
    calendar.style.left = pos.left+"px";
    calendar.show();
}

//根据年份和月份绘制日历
function printCalendar() {
    var year = document.getElementById('display_year').innerText;
    var month = document.getElementById('display_month').innerText;
    month = parseInt(month) - 1;
    //1. 判断是平年还是闰年
    var addDay = 0;    //平年2月，28天
    if((year%4 == 0 && year%100 !=0) || year%400 == 0){
        addDay = 1;    //闰年2月，29天
    }
    //2. 构建月份天数的数组
    var m_days = new Array(31,28+parseInt(addDay),31,30,31,31,30,31,30,31,30,31);
    //3. 计算日期显示的行数
    var tmpDate = new Date(year,month,1);
    var firstday = tmpDate.getDay();
    firstday =  (firstday==0)? 7 : firstday;    //这个月的第一天是星期几
    var trNums = Math.ceil((m_days[month] + firstday - 1)/7);
    //4. 绘制日历
    var tbody = '';
    for(var i=0;i<trNums;i++){
        tbody += "<tr>";
        for(var j=0;j<7;j++){
            var indent = parseInt(i*7) + parseInt(j);
            var day = parseInt(indent) - parseInt(firstday) + parseInt(2);
            if(day < 1){
                //第一行的灰色部分
                var preMonth = ((month-1+12)%12==0)? 12:(month-1+12)%12;
                day = parseInt(m_days[preMonth])+parseInt(day);
                tbody += "<td class='grey'>"+day+"</td>";
                continue;
            }
            if(day > m_days[month]){
                //最后一行的灰色部分
                var nextMonth = ((year+1+12)%12==0)? 12:(year+1+12)%12;
                day = parseInt(day) - m_days[month];
                tbody += "<td class='grey'>"+day+"</td>";
                continue;
            }
            tbody += "<td class='valid'>"+day+"</td>";
        }
        tbody += "</tr>";
    }
    //5. 将绘制好的日历加入到dom
    var calendar_body = document.createElement("tbody");
    calendar_body.innerHTML = tbody;
    calendar_body.setAttribute("id","calendar_body");
    var calendar_table = document.getElementById("calendar_table");
    //删除旧的日历
    var old_body = document.getElementById("calendar_body");
    calendar_table.removeChild(old_body);
    //插入新的日历
    calendar_table.insertBefore(calendar_body,null);

    var objs = document.getElementsByClassName("valid");
    for(var i=0;i<objs.length;i++){
        objs[i].onclick = function(){
            chooseDay(parseInt(this.innerText));
        }
    }
}

//点击具体的某一天
function chooseDay(index){
    index--;
    var objs = document.getElementsByClassName("valid");
    for(var i=0;i<objs.length;i++){
        if(i == index){
            objs[i].className = "valid selected";
        }else{
            objs[i].className = "valid";
        }
    }
}

//减小年份
function calendarYearDec() {
    var obj = document.getElementById('display_year');
    var year = obj.innerText;
    obj.innerText = parseInt(year)-1;
    printCalendar();
}

//增加年份
function calendarYearInc() {
    var obj = document.getElementById('display_year');
    var year = obj.innerText;
    obj.innerText = parseInt(year)+1;
    printCalendar();
}

//减小月份
function calendarMonthDec() {
    var obj = document.getElementById('display_month');
    var month = obj.innerText;
    if(month == 1){
        obj.innerText = 12;
        calendarYearDec();  //减少一年
    }else{
        obj.innerText = parseInt(month) - 1;
    }
    printCalendar();
}

//增加月份
function calendarMonthInc() {
    var obj = document.getElementById('display_month');
    var month = obj.innerText;
    if(month == 12){
        obj.innerText = 1;
        calendarYearInc();  //增加一年
    }else{
        obj.innerText = parseInt(month) + 1;
    }
    printCalendar();
}