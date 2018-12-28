<?php

require __DIR__."/bootstrap.php";

use QueueTask\Worker\Worker;
use QueueTask\Queue\Queue;
use QueueTask\Load\Load;

$config = include './config.php';

Load::Queue($config);

$config = [
    'queueName' => 'queue_name_1', //队列名称
    'attempt' => 3,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'sleep' => 1,       //每次检测的时间间隔
    'delay' => 3,       //失败后延迟的秒数重新入队列
];

try{
    (new Worker(Queue::getInstance()))->setConfig($config)->listen();
}catch (Exception $e){
    echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
}
