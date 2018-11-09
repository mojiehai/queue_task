<?php

require "./bootstrap.php";

use QueueTask\Worker\Worker;
use QueueTask\Queue\Queue;
use QueueTask\Process\Manage;
use QueueTask\Process\Process;

$config = [
    'queueName' => 'a', //队列名称
    'attempt' => 3,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'sleep' => 1,       //每次检测的时间间隔
    'delay' => 3,       //失败后延迟的秒数重新入队列

    'checkWorkerInterval' => 10,    // 10秒检测一次进程
    'maxWorkerNum' => 2,    //2个进程
];
$file = __DIR__.DIRECTORY_SEPARATOR.'test.log.';

if(php_sapi_name() == 'cli') {
    try {
        // 创建进程管理器
        /*
        (new Manage())
            ->setConfig($config)
            ->setWork(
                // 设置进程工作内容
                function(Process $process) use ($config){
                    $worker = new Worker(Queue::getInstance());
                    $worker->setConfig($config);
                    $worker->listen(
                        // 设置队列处理完每次任务后的回调
                        function (Worker $w) use ($process){
                            if ($process->isWorkExpectStop()) {
                                $w->setStop();
                            }
                        }
                    );
                })
            ->run();
        */
        (new Manage())
            ->setConfig($config)
            ->setWork(
                function(Process $process) use ($file){
                    file_put_contents($file.$process->pid.'.'.time(), $process->pid);
                })
            ->run();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} else {
    exit('exit');
}
