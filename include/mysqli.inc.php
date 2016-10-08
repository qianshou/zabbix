<?php
/**
 * Created by PhpStorm.
 * User: zhezhao
 * Date: 2016/10/8
 * Time: 15:49
 */
class Zmysqli extends mysqli
{
    public function __construct()
    {
        parent::__construct("127.0.0.1","root","root","zabbix");
    }
}