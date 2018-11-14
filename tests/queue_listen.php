<?php

require "./bootstrap.php";

use QueueTask\Worker\Worker;
use QueueTask\Queue\Queue;
use QueueTask\Process\Manage;
use QueueTask\Process\Process;

$config = [
    'queueName' => 'test_process', //队列名称
    'attempt' => 2,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'sleep' => 3,       //每次检测的时间间隔
    'delay' => 1,       //失败后延迟的秒数重新入队列

    // 进程基础配置
    'titlePrefix' => 'queue_task',   // 进程前缀
    'baseTitle' => 'listen',  // 进程基础名称

    // master 进程配置
    'checkWorkerInterval' => 300,    // 300秒检测一次进程
    'maxWorkerNum' => 5,    //5个进程

    // worker 进程配置
    'executeTimes' => 0,    // 任务的最大执行次数
    'executeUSleep' => 0,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us
    'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
];

if(php_sapi_name() == 'cli') {
    try {
        // 创建进程管理器
        (new Manage($config))
            ->setWorkInit(
                // 初始化队列消费者
                function (Process $process) use ($config) {
                    return (new Worker(Queue::getInstance()))->setConfig($config);
                }
            )
            ->setWork(
                // 执行的工作内容
                function(Process $process, Worker $worker){
                    $worker->runOnce();
                    // 如果任务需要退出
                    if ($worker->isStop()) {
                        // 则调用进程的退出操作
                        $process->setStop();
                    }
                })
            ->run();
    } catch (QueueTask\Exception\Exception $e) {
        echo $e->getExceptionAsString();
    }
} else {
    exit('exit');
}
