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
     * @var string|array
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
     * 最大运行时间,0为不限制
     * @var int 秒
     */
    public $maxRunTime = 0;

    /**
     * 允许配置的变量名
     * @var array
     */
    protected $configNameList = ['queueName', 'attempt', 'memory', 'sleep', 'delay', 'maxRunTime'];

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
        $startTime = time();
        while (true) {

            //消费一次队列任务
            $this->runOnce();

            // 检查最大执行时间
            $this->checkMoreThanRunTime($startTime);

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

        // job任务
        list($job, $queueName) = $this->getJobAndQueueName($this->queue);

        if($job instanceof Job) {

            // 执行任务
            $job->execute();

            // 判断任务是否执行成功
            if ($job->isExec()) {
                //任务成功，触发回调
                $job->success();
            } else {
                // 是否需要重试该任务
                if ($job->isRelease($this->attempt)) {
                    // 需要重试，则重新将任务放入队尾
                    $job->release($this->queue, $queueName, $this->delay);
                } else {
                    // 不需要重试，则任务失败，触发回调
                    $job->failed();
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
     * 获取出队job和队列名称
     * @param Queue $queue
     * @return array
     *      [Job, QueueName]
     */
    protected function getJobAndQueueName(Queue $queue)
    {
        $queueName = '';
        $job = null;
        if (is_array($this->queueName)) {
            $tmpQueueNames = $this->queueName;
            shuffle($tmpQueueNames);
            foreach ($tmpQueueNames as $name) {
                $job = $queue->pop($name);
                if ($job) {
                    $queueName = $name;
                    break;
                }
            }
        } else {
            $queueName = $this->queueName;
            $job = $queue->pop($queueName);
        }
        return [$job, $queueName];
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
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    /**
     * 检查最大运行时间
     * @param $startTime
     */
    protected function checkMoreThanRunTime($startTime)
    {
        if ($this->maxRunTime > 0 && time() > ($startTime + $this->maxRunTime)) {
            $this->setStop();
        }
    }

} 