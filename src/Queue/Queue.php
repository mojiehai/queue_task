<?php

namespace QueueTask\Queue;

use QueueTask\Connection\Connection;
use QueueTask\Job\Job;
use QueueTask\Job\GeneralJob;
use QueueTask\Handler\JobHandler;

/**
 * 队列实体
 * Class Queue
 */
class Queue implements QueueInterface
{

    /**
     * @var Queue 单例对象
     */
    protected static $instance;

    /**
     * @var Connection 连接对象
     */
    protected static $connection;

    /**
     * Queue constructor.
     * @param Connection $connection
     */
    protected function __construct(Connection $connection)
    {
        static::$connection = $connection;
    }

    public function __destruct()
    {
        static::$instance = null;
    }

    public static function getInstance()
    {
        $confConnectClass = "";
        if( static::$instance == null ) {
            static::$instance = new static($confConnectClass::getInstance());
        }
        return static::$instance;
    }

    /**
     * 弹出队列(弹出后队列中就没有这个任务了)
     * @param String $queueName 队列名称
     * @return Job
     */
    public function pop($queueName)
    {
        return static::$connection->pop($queueName);
    }

    /**
     * 入队列
     * @param Job $job
     * @return boolean
     */
    protected function push(Job $job)
    {
        return static::$connection->push($job);
    }

    /**
     * 延迟入队列
     * @param $delay
     * @param Job $job
     * @return boolean
     */
    protected function laterPush($delay , Job $job)
    {
        return static::$connection->laterOn($delay,$job);
    }

    /**
     * 入队列  (对外)
     * @param JobHandler $handler 回调类
     * @param String $func 方法名
     * @param array $param 参数
     * @param String $queueName 队列名
     * @return boolean
     */
    public function pushOn(JobHandler $handler, $func, array $param, $queueName)
    {
        $job = new GeneralJob(static::$connection->getType(),$queueName,$handler,$func,$param);
        return $this->push($job);
    }

    /**
     * 延迟入队列 (对外)
     * @param Int $delay 延迟时间/秒
     * @param JobHandler $handler 回调类
     * @param String $func 方法名
     * @param array $param 参数
     * @param String $queueName 队列名
     * @return boolean
     */
    public function laterOn($delay, JobHandler $handler, $func, array $param, $queueName)
    {
        $job = new GeneralJob(static::$connection->getType(),$queueName,$handler,$func,$param);
        return $this->laterPush($delay,$job);
    }


    /**
     * 关闭数据库连接
     */
    public function close()
    {
        static::$connection->close();
    }


}