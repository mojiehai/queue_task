<?php

namespace QueueTask\Worker;

use QueueTask\Log\WorkLog;
use QueueTask\Queue\Queue;
use QueueTask\Job\Job;

/**
 * 工作类
 * Class Worker
 */
class Worker
{

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
     * 每次检测的时间间隔(出队的时候没有任务，则等待该秒数后继续尝试出队)
     * @var int
     */
    public $sleep = 3;

    /**
     * 失败后延迟的秒数重新入队列
     * @var int
     */
    public $delay = 0;

    /**
     * 允许配置的变量名
     * @var array
     */
    protected $configNameList = ['queueName', 'attempt', 'memory', 'sleep', 'delay'];

    /**
     * Worker constructor.
     * @param Queue $queue 队列实例
     */
    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * 加载配置
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        foreach ($config as $k => $v) {
            if (in_array($k, $this->configNameList)) {
                if (!is_null($v)) {
                    $this->$k = $v;
                }
            }
        }
        return $this;
    }

    /**
     * 启用一个队列的监听任务
     */
    public function listen()
    {
        while (true) {

            //消费一次队列任务
            $this->runOnce();

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

        //弹出任务
        $job = $this->queue->pop($this->queueName);

        if($job instanceof Job) {

            // 执行任务
            $job->execute();

            // 判断任务是否执行成功
            if ($job->isExec()) {
                //任务成功，触发回调
                $job->success();
            } else {
                // 执行失败,判断当前是否超出执行次数
                if ($this->attempt > 0 && $job->getAttempts() >= $this->attempt) {
                    // 给定最大重试次数限制，且超过最大限制，则任务失败，触发回调
                    $job->failed();
                } else {
                    // 未给定最大重试次数限制，或者没有超过最大重试限制，则重新将任务放入队尾
                    $job->release($this->queue, $this->queueName, $this->delay);
                }
            }
        } else {
            // 如果队列没有任务，就等待指定间隔时间
            $this->sleep($this->sleep);
        }

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
     * 休眠
     * @param int $seconds
     */
    protected function sleep($seconds)
    {
        sleep($seconds);
    }

} 