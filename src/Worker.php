<?php

namespace QueueTask\Worker;

use QueueTask\Helpers\LoadConfig;
use QueueTask\Log\WorkLog;
use QueueTask\Queue;
use QueueTask\Job\Job;

/**
 * 工作类
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
    public $attempt = 10;

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
     * 允许配置的变量名
     * @var array
     */
    protected $configNameList = ['queueName', 'attempt', 'memory', 'maxRunTime'];

    /**
     * Worker constructor.
     * @param Queue $queue 队列实例
     */
    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
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

            //消费一次队列任务
            $this->runOnce();

            // 检查最大执行时间
            $this->checkMaxRunTime($endTime);

            // 退出监听
            if ($this->isStop()) {
                break;
            }
        }
    }

    /**
     * 消费一次队列任务
     */
    public function runOnce()
    {
        $job = null;

        $this->queue->popRun($this->queueName);

        // 内存超出
        if ($this->memoryExceeded()) {
            WorkLog::error('Memory out of range');
            $this->setStop();
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
     * 判断内存使用是否超出
     * @return bool
     */
    protected function memoryExceeded()
    {
        return (memory_get_usage() / 1024 / 1024) >= $this->memory;
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
    protected function setStop()
    {
        $this->close();
        $this->isStop = true;
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

} 