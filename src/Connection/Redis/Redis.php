<?php

namespace QueueTask\Connection\Redis;

use QueueTask\Config\Config;
use QueueTask\Connection\Connection;
use QueueTask\Job\Job;

/**
 * Redis 操作任务类
 * Class Redis
 */
class Redis extends Connection
{

    /**
     * redis驱动
     * @var null|RedisDrive
     */
    protected static $connect = null;

    /**
     * 本类单例
     * @var Redis
     */
    protected static $instance = null;
    /**
     * Connection constructor.
     * @param array $config 配置参数
     */
    protected function __construct($config = [])
    {
        self::$connect = RedisDrive::getInstance($config);
    }


    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        self::$connect->close();
    }

    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job|null
     */
    public function pop($queueName)
    {
        //从延迟集合中合并到主执行队列
        $this->migrateAllExpiredJobs($queueName);

        //弹出任务
        $jobstr = self::$connect->lpop($queueName);
        if(!is_null($jobstr)) {
            return Job::Decode($jobstr);
        } else {
            return null;
        }

    }

    /**
     * 压入队列
     * @param Job $job
     * @param string $queueName 队列名称
     * @return boolean
     *
     *
     * 直接压入主执行队列
     */
    public function push(Job $job, $queueName)
    {
        //命令：rpush 队列名 任务
        $res = self::$connect->rpush($queueName , Job::Encode($job));
        if($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @param string $queueName 队列名称
     * @return boolean
     *
     *
     * 放入等待执行任务的有序集合中
     */
    public function laterOn($delay, Job $job, $queueName)
    {
        //命令：zadd  主队列名:delayed   当前时间戳+延迟秒数  任务
        $res = self::$connect->zadd($queueName.":delayed" , time() + $delay , Job::Encode($job));
        if($res) {
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
            //开始redis事物
            self::$connect->watch($from);
            self::$connect->multi();

            $this->removeExpiredJobs( $from, $time);
            $this->pushExpiredJobsOntoNewQueue( $to, $jobs);

            self::$connect->exec();
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
        return self::$connect->zrangebyscore($name , '-inf' , $time);
    }


    /**
     * 从指定集合删除过期任务
     * @param  string $from
     * @param  int $time
     * @return void
     */
    protected function removeExpiredJobs($from, $time)
    {
        self::$connect->zremrangebyscore($from, '-inf', $time);
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
        //等价于  self::$connect->rpush($to,$jobs[0],$jobs[1]... );
        call_user_func_array([self::$connect, 'rpush'], array_merge([$to], $jobs));
    }

}