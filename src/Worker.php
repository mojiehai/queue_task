<?php

namespace QueueTask;

use QueueTask\Helpers\LoadConfig;
use QueueTask\Helpers\Log;
use QueueTask\Process\ProcessObserver;

/**
 * 工作类  单任务模式
 * Class Worker
 */
class Worker
{
    use LoadConfig;

    /**
     * 队列实例
     * @var Queue
     */
    protected $queue = null;

    /**
     * 是否退出
     * @var bool
     */
    protected $isStop = false;

    /**
     * 监听队列的名称(在push的时候把任务推送到哪个队列，则需要监听相应的队列才能获取任务)
     * @var string
     */
    public $queueName = 'default';

    /**
     * 队列任务失败尝试次数，0为不限制
     * @var int
     */
    public $attempt = 3;

    /**
     * 允许使用的最大内存
     * @var int
     */
    public $memory = 128;

    /**
     * 最大运行时间,0为不限制
     * @var int 秒
     */
    public $maxRunTime = 0;

    /**
     * 进程观察者
     * @var ProcessObserver
     */
    protected $observer = null;

    /**
     * Worker constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configNameList = ['queueName', 'attempt', 'memory', 'maxRunTime'];
        $this->setConfig($config);

        $this->queue = Queue::getInstance();
    }

    /**
     * 进程观察者
     * @param ProcessObserver $observer
     */
    public function bindProcessObserver(ProcessObserver $observer)
    {
        $this->observer = $observer;
    }

    /**
     * 启用一个队列的监听任务
     */
    public function listen()
    {
        // 设置执行函数
        $this->queue->setHandler(function(Job $job, $queueName) {
            // 执行任务
            $job->execute();

            // 判断任务是否执行成功
            if ($job->isExec()) {
                //任务成功，触发回调
                $job->success();
            } else {
                // 是否需要重试该任务
                if ($job->isRetry($this->attempt)) {
                    // 需要重试，则重新将任务放入队尾
                    $job->reTry($this->queue, $queueName);
                } else {
                    // 不需要重试，则任务失败，触发回调
                    $job->failed();
                }
            }
        });

        $endTime = time() + $this->maxRunTime;
        while (true) {

            // 消费一次队列任务
            $this->queue->popRun($this->queueName);

            // 检查最大执行时间
            $this->checkMaxRunTime($endTime);

            // 检查内存超出
            $this->checkMemoryExceeded();

            // 进程观察者检查是否需要停止
            $this->observeCheckStop();

            // 退出监听
            if ($this->isStop()) {
                $this->reportStopInfo();
                break;
            }
        }
    }

    /**
     * 是否需要退出
     * @return bool
     */
    public function isStop()
    {
        return $this->isStop;
    }

    /**
     * 关闭相关资源
     */
    protected function close()
    {
        $this->queue->close();
    }

    /**
     * 设置退出监听
     */
    public function setStop()
    {
        $this->close();
        $this->isStop = true;
    }

    /**
     * 判断内存使用是否超出
     */
    protected function checkMemoryExceeded()
    {
        if ((memory_get_usage() / 1024 / 1024) >= $this->memory) {
            Log::error('Memory out of range');
            $this->setStop();
        }
    }

    /**
     * 检查最大运行时间
     * @param $endTime
     */
    protected function checkMaxRunTime($endTime)
    {
        if ($this->maxRunTime > 0 && time() > $endTime) {
            $this->setStop();
        }
    }

    /**************************** 多任务模式 *********************************/
    /**
     * 进程观察者检查是否需要停止
     */
    protected function observeCheckStop()
    {
        if ($this->observer instanceof ProcessObserver) {
            // 检测
            $this->observer->check();
            // 判断是否停止
            if ($this->observer->isStop) {
                $this->setStop();
            }
        }
    }

    /**
     * 让进程观察者上报进程停止信息
     */
    protected function reportStopInfo()
    {
        if ($this->observer instanceof ProcessObserver) {
            $this->observer->reportStopInfo();
        }
    }

} 