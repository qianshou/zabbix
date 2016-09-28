<?php
$redis = new Redis();
$res1 = $redis->connect('127.0.0.1', 6379);
$res2 = $redis->auth("dfgf");
var_dump($res1);
var_dump($res2);