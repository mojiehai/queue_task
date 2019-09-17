<?php

namespace QueueTask\Connection\Redis;

use QueueTask\Connection\Connection;
use QueueTask\Job;

/**
 * Redis 操作任务类
 * Class Redis
 */
class Redis extends Connection
{

    /**
     * redis单例对象
     * @var Redis
     */
    protected static $instance = null;

    /**
     * redis host
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * redis port
     * @var int
     */
    private $port = 6379;

    /**
     * redis database
     * @var int
     */
    private $database = 0;

    /**
     * redis password
     * @var string
     */
    private $password = '';

    /**
     * redis timeout
     * @var int
     */
    private $connTimeout = 0;

    /**
     * redis驱动
     * @var \Redis
     */
    private $connect = null;

    /**
     * 本类单例
     * @var Redis
     */
    /**
     * Connection constructor.
     * @param array $config 配置参数
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->host     = (isset($config['host']) && !empty($config['host'])) ? $config['host'] : $this->host;
        $this->port     = (isset($config['port']) && !empty($config['port'])) ? $config['port'] : $this->port;
        $this->database = (isset($config['db']) && !empty($config['db'])) ? $config['db'] : $this->database;
        $this->password = (isset($config['password']) && !empty($config['password'])) ? $config['password'] : $this->password;
        $this->connTimeout  = (isset($config['connTimeout']) && !empty($config['connTimeout'])) ? $config['connTimeout'] : $this->connTimeout;
    }

    /**
     * get connect
     * @return \Redis
     */
    private function getConnect()
    {
        if (empty($this->connect)) {
            $this->connect = new \Redis();
            $this->open();
        } else {
            if (@$this->connect->ping() !== '+PONG') {
                $this->open();
            }
        }
        return $this->connect;
    }

    /**
     * open redis connect
     */
    private function open()
    {
        $this->connect->connect($this->host, $this->port, $this->connTimeout);
        if (!empty($this->password)) {
            $this->connect->auth($this->password);
        }
        $this->connect->select($this->database);
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        if (!empty($this->connect)) {
            $this->connect->close();
        }
        return true;
    }

    /**
     * 弹出队头任务(blocking)
     * @param string $queueName 队列名称
     * @param array & $extends 额外需要传递给ack方法的参数
     * @return Job|null
     */
    public function pop($queueName, & $extends = [])
    {
        //从延迟集合中合并到主执行队列
        $this->migrateAllExpiredJobs($queueName);

        $jobStr = $this->getConnect()->blPop($queueName, $this->popTimeOut);
        if (empty($jobStr)) {
            return null;
        } else {
            if (isset($jobStr[1]) && !empty($jobStr[1])) {
                return Job::Decode($jobStr[1]);
            } else {
                return null;
            }
        }
    }

    /**
     * 确认任务
     * @param string $queueName
     * @param Job $job
     * @param array $extends
     */
    public function ack($queueName, Job $job = null, $extends = [])
    {
        // redis不需要确认任务
    }


    /**
     * 压入队列(直接压入主执行队列)
     *
     * @param Job $job
     * @param string $queueName 队列名称
     *
     * @return boolean
     */
    public function push(Job $job, $queueName)
    {
        //命令：rpush 队列名 任务
        $length = $this->getConnect()->rPush($queueName, Job::Encode($job));
        if ($length) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加一条延迟任务
     * 放入等待执行任务的有序集合中
     *
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @param string $queueName 队列名称
     *
     * @return boolean
     */
    public function later($delay, Job $job, $queueName)
    {
        //命令：zadd  主队列名:delayed   当前时间戳+延迟秒数  任务
        $result = $this->getConnect()->zAdd($queueName . ':delayed', time() + $delay, Job::Encode($job));
        if ($result) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 合并等待执行的任务
     * @param  string $queueName
     * @return void
     */
    protected function migrateAllExpiredJobs($queueName)
    {
        $this->migrateExpiredJobs($queueName . ':delayed', $queueName);
    }


    /**
     * 当延时任务到大执行时间时，将延时任务从延时任务集合中移动到主执行队列中
     * @param  string $from     集合名称
     * @param  string $to       队列名称
     * @return void
     */
    protected function migrateExpiredJobs($from, $to)
    {
        $time = time();
        $jobs = $this->getExpiredJobs($from, $time);
        if (count($jobs) > 0) {
            $connect = $this->getConnect();
            //开始redis事物
            $connect->watch($from);
            $connect->multi();

            $this->removeExpiredJobs($from, $time);
            $this->pushExpiredJobsOntoNewQueue($to, $jobs);

            $connect->exec();
        }
    }


    /**
     * 从指定集合中获取所有超时的任务
     * @param String $name     集合名称
     * @param int $time     超时时间(集合中小于该时间为超时)
     * @return mixed
     */
    public function getExpiredJobs($name , $time)
    {
        return $this->getConnect()->zRangeByScore($name, '-inf', $time);
    }


    /**
     * 从指定集合删除过期任务
     * @param  string $from
     * @param  int $time
     * @return void
     */
    protected function removeExpiredJobs($from, $time)
    {
        $this->getConnect()->zRemRangeByScore($from, '-inf', $time);
    }


    /**
     * 将多个任务从添加到队列
     *
     * 场景：将有序集合中的延迟任务入主队列
     * @param  string $to
     * @param  array $jobs
     * @return void
     */
    protected function pushExpiredJobsOntoNewQueue($to, $jobs)
    {
        //等价于  $connect->rPush($to,$jobs[0],$jobs[1]... );
        $connect = $this->getConnect();
        call_user_func_array([$connect, 'rPush'], array_merge([$to], $jobs));
    }

}