<?php

require "./bootstrap.php";

use QueueTask\Queue\Queue;
use QueueTask\Process\Manage;
use QueueTask\Process\Process;
use Tests\TestHandler;
use QueueTask\Load\Load;

$baseConfig = include './config.php';
Load::Queue($baseConfig['queue']);

$config = [
    'queueName' => 'test_process', //队列名称
    'function' => 'writeTest',          // 执行的方法
    'params' => ['test'=>'test'],  // 执行的方法的参数

    // 进程基础配置
    'titlePrefix' => 'queue_task',   // 进程前缀
    'baseTitle' => 'push',  // 进程基础名称

    // master 进程配置
    'checkWorkerInterval' => 10,    // 10秒检测一次进程
    'maxWorkerNum' => 20,    //20个进程

    // worker 进程配置
    'executeTimes' => 0,    // 任务的最大执行次数
    'executeUSleep' => 1000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us (1s)
    'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
];

if(php_sapi_name() == 'cli') {
    try {
        // 创建进程管理器
        (new Manage($config))
            ->setWorkInit(
                // 初始化队列消费者
                function (Process $process) {
                    return [Queue::getInstance(), (new TestHandler())];
                }
            )
            ->setWork(
                // 执行的工作内容
                function(Process $process, array $arr) use ($config) {
                    $queue = $arr[0];   // Queue
                    $handler = $arr[1]; // TestHandler
                    // 直接压入队列
                    $res = $queue->pushOn($handler, $config['function'], $config['params'], $config['queueName']);
                    \QueueTask\Log\WorkLog::info('pushOn : '.var_export($res, true), 'push');
                    return ;
                    if (rand(0,1) == 1) {
                        // 直接压入队列
                        $res = $queue->pushOn($handler, $config['function'], $config['params'], $config['queueName']);
                        \QueueTask\Log\WorkLog::info('pushOn : '.var_export($res, true), 'push');
                    } else {
                        // 延迟压入队列
                        $res = $queue->laterOn(10, $handler, $config['function'], $config['params'], $config['queueName']);
                        \QueueTask\Log\WorkLog::info('laterOn : '.var_export($res, true), 'push');
                    }
                })
            ->run();
    } catch (QueueTask\Exception\Exception $e) {
        echo $e->getExceptionAsString();
    }
} else {
    exit('exit');
}
