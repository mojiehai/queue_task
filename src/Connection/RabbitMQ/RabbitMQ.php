<?php

namespace QueueTask\Connection\RabbitMQ;

use QueueTask\Helpers\Log;
use QueueTask\Connection\Connection;
use QueueTask\Job;

/**
 * Producer
 * Consumer
 * RabbitMQ链接驱动
 * Class RabbitMQ
 */
class RabbitMQ extends Connection
{
    /**
     * RabbitMQ单例对象
     * @var RabbitMQ
     */
    protected static $instance = null;

    /**
     * 生产者
     * @var Producer
     */
    protected $producer = null;

    /**
     * 消费者
     * @var Consumer
     */
    protected $consumer = null;

    /**
     * RabbitMQ constructor.
     * @param array $config
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * 获取生产者
     * @return Producer
     */
    protected function getProducer()
    {
        if (!($this->producer instanceof Producer)) {
            $this->producer = new Producer($this->config);
        }
        return $this->producer;
    }

    /**
     * 获取消费者
     * @return Consumer
     */
    protected function getConsumer()
    {
        if (!($this->consumer instanceof Consumer)) {
            $this->consumer = new Consumer($this->config, $this);
        }
        return $this->consumer;
    }


    /**
     * 执行pop出来的任务(阻塞方法)
     * @param string $queueName
     */
    public function popRun($queueName)
    {
        $this->pop($queueName);
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        if (!empty($this->producer)) {
            $this->producer->close();
        }
        if (!empty($this->consumer)) {
            $this->consumer->close();
        }
        return true;
    }

    /**
     * 弹出队头任务(blocking)
     * @param string $queueName 队列名称
     * @param array & $extends 额外需要传递给ack方法的参数
     * @return null
     */
    protected function pop($queueName, & $extends = [])
    {
        $this->getConsumer()->pop($queueName);
    }

    /**
     * 确认任务
     * @param string $queueName
     * @param Job $job
     * @param array $extends
     */
    protected function ack($queueName, Job $job = null, $extends = [])
    {
        // rabbitmq的ack在pop的回调函数内部，不在此声明
    }

    /**
     * 压入队列
     * @param Job $job
     * @param String $queueName 队列名
     * @return boolean
     */
    public function push(Job $job, $queueName)
    {
        return $this->getProducer()->push($job, $queueName);
    }

    /**
     * 添加一条延迟任务
     * @param int $delay
     * @param Job $job
     * @param String $queueName
     * @return bool
     */
    public function later($delay, Job $job, $queueName)
    {
        // mq不支持延迟队列
        Log::error('RabbitMQ Not Support Later Push,delay: '.$delay.',Job: '. Job::Encode($job));
        return false;
    }

}