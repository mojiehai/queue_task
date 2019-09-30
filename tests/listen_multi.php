<?php

require __DIR__."/bootstrap.php";

use QueueTask\Worker;
use QueueTask\Queue;
use QueueTask\Load;
use QueueTask\MultiWorker;

$config = include __DIR__.'/config.php';

Load::Queue($config);

$config1 = [
    'queueName' => 'testQueue', //队列名称
    'attempt' => 3,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'maxRunTime' => 100,    // 最大运行时间 100s
];

$config2 = [
    'queueName' => 'testQueue1', //队列名称
    'attempt' => 3,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'maxRunTime' => 100,    // 最大运行时间 100s
];

try{

    (new MultiWorker('tag1'))
        ->addWorker($config1, 1)
        ->addWorker($config2, 2)
        ->start();

}catch (Exception $e){
    echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
}
