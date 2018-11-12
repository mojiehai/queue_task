<?php

require "./bootstrap.php";

use QueueTask\Worker\Worker;
use QueueTask\Queue\Queue;
use QueueTask\Process\Manage;
use QueueTask\Process\Process;
use QueueTask\Process\Worker as PWorker;
use QueueTask\Log\ProcessLog;
use QueueTask\Log\WorkLog;

$config = [
    'queueName' => 'a', //队列名称
    'attempt' => 3,     //队列任务失败尝试次数，0为不限制
    'memory' => 128,    //允许使用的最大内存  单位:M
    'sleep' => 1,       //每次检测的时间间隔
    'delay' => 3,       //失败后延迟的秒数重新入队列

    // 进程基础配置
    'titlePrefix' => 'queue_task',   // 进程前缀
    'baseTitle' => 'test',  // 进程基础名称

    // master 进程配置
    'pidFileDir' => null,           // pid文件根目录
    'checkWorkerInterval' => 10,    // 300秒检测一次进程
    'maxWorkerNum' => 2,    //3个进程

    // worker 进程配置
    'executeTimes' => 0,    // 任务的最大执行次数
    'executeUSleep' => 5000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us
    'limitSeconds' => 0,    // 工作进程最大执行时长(秒)
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
                    (new Worker(Queue::getInstance()))
                        ->setConfig($config)
                        ->listen(
                            // 设置队列处理完每次任务后的回调
                            function (Worker $w) use ($process){
                                if ($process->isWorkExpectStop()) {
                                    $w->setStop();
                                }
                            });
                })
            ->run();
        */

        (new Manage($config))
            ->setWork(
                function(PWorker $process) use ($file){
                    //file_put_contents($file.$process->pid.'.'.time(), $process->pid);
                    //$res = posix_kill(posix_getpid(), SIGUSR2);
                    //$res = pcntl_signal_get_handler(SIGUSR2);
                    WorkLog::info('run worker:'.$process->pid.', times: '.$process->getExecuteTimes());
                })
            ->run();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} else {
    exit('exit');
}
