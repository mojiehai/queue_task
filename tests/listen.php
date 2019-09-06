<?php

require __DIR__."/bootstrap.php";

use QueueTask\Worker;
use QueueTask\Queue;
use QueueTask\Load;

$config = include './config.php';

Load::Queue($config);

$config = [
    'queueName' => 'testQueue', //队列名称
    'attempt' => 3,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'maxRunTime' => 100,    // 最大运行时间 100s
];

try{
    $start = microtime(true);

    (new Worker(Queue::getInstance()))->setConfig($config)->listen();

    echo 'end, run: '.(microtime(true) - $start).PHP_EOL;
}catch (Exception $e){
    echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
}
