<?php

require __DIR__."/bootstrap.php";

use QueueTask\Load\Load;
use QueueTask\Daemon\SingleWorkDaemon;

$config = include './config.php';

Load::Queue($config);

$queueConfig = [
    'queueName' => 'testQueue', //队列名称
];

$processConfig = [
    // master 进程配置
    'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
    'maxWorkerNum' => 2,            //2个进程

    // worker 进程配置
    'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
    'limitSeconds' => 86400,    // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制)

    'executeUSleep' => 3000000,   // 3秒执行一次
];


// 监听命令
try{
    SingleWorkDaemon::getInstance(
        //(new PushWork($queueConfig))->setProcessConfig($processConfig)
        (new PushDelayWork($queueConfig))->setProcessConfig($processConfig)
    )->listenCommand();
} catch (\ProcessManage\Exception\Exception $e) {
}
