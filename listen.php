<?php


require_once "./Worker/Worker.php";
require_once "./QueueAdapter.php";

$queueName = "a";        //队列名称
$attempt   = 3;          //队列任务失败尝试次数，0为不限制
$memory    = 128;        //允许使用的最大内存  单位:M
$sleep     = 3;          //每次检测的时间间隔
$delay     = 3;          //失败后延迟的秒数重新入队列

try{
    Worker::listen(QueueAdapter::getQueue(),$queueName,$attempt,$memory,$sleep,$delay);
}catch (Exception $e){
    echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
}
