# zabbix
针对zabbix进行的二次开发

zabbix版本3.2

在zabbix基础上，开发错误收集并处理的接口

错误信息保存在本地数据库，在localhost安装mysql数据库，并为php安装mysqli扩展

为了提高接口的响应时间，在本机安装redis服务器，并为php安装mysqli扩展

错误信息发送到接口之后，存储在redis消息队列中。

然后定时执行php脚本，从队列中取出数据，进行邮件、短信提醒等操作，然后写入mysql数据库。
