<?php

namespace QueueTask\Queue;

use QueueTask\Config\QueueConfig;
use QueueTask\Connection\Connection;
use QueueTask\Job\Job;
use QueueTask\Handler\JobHandler;
use QueueTask\Connection\ConnectionFactory;
use ProcessManage\Exception\Exception;
use QueueTask\Log\WorkLog;

/**
 * 队列实体
 * Class Queue
 */
class Queue implements QueueInterface
{

    /**
     * @var array [Queue] 单例对象数组
     */
    protected static $instances;

    /**
     * @var Connection 连接对象
     */
    protected $connection;

    /**
     * Queue constructor.
     * @param Connection $connection
     */
    protected function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __destruct()
    {
        static::$instances = null;
    }

    /**
     * 不允许被克隆
     * @throws Exception
     */
    protected function __clone()
    {
        throw new Exception("This class cannot be cloned" , -101);
    }

    /**
     * @param string $connectName 链接类型(默认走配置)
     * @return Queue
     */
    public static function getInstance($connectName = '')
    {
        if (empty($connectName)) {
            $connectName = QueueConfig::$currentConnect;
        }
        if (!isset(static::$instances[$connectName]) || !(static::$instances[$connectName] instanceof Queue)) {
            try {
                static::$instances[$connectName] = new static(ConnectionFactory::getInstance($connectName));
            } catch (Exception $e) {
                WorkLog::error($e->getExceptionAsString());
            }
        }
        return static::$instances[$connectName];
    }

    /**
     * 弹出队列(弹出后队列中就没有这个任务了)
     * @param String $queueName 队列名称
     * @return Job
     */
    public function pop($queueName)
    {
        return $this->connection->pop($queueName);
    }

    /**
     * 入队列
     * @param Job $job
     * @param string $queueName 队列名
     * @return boolean
     */
    public function push(Job $job, $queueName)
    {
        return $this->connection->push($job, $queueName);
    }

    /**
     * 延迟入队列
     * @param int $delay 延迟的秒数
     * @param Job $job
     * @param string $queueName 队列名
     * @return boolean
     */
    public function laterPush($delay, Job $job, $queueName)
    {
        if ($delay <= 0) {
            return $this->push($job, $queueName);
        } else {
            return $this->connection->laterOn($delay, $job, $queueName);
        }
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
        $job = new Job($handler, $func, $param);
        return $this->push($job, $queueName);
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
        $job = new Job($handler, $func, $param);
        return $this->laterPush($delay,$job, $queueName);
    }


    /**
     * 关闭数据库连接
     */
    public function close()
    {
        $this->connection->close();
    }


}