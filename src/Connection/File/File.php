<?php

namespace QueueTask\Connection\File;

use QueueTask\Connection\Connection;
use QueueTask\Job\Job;

/**
 * File 操作任务类
 * Class File
 */
class File extends Connection
{

    protected static $instance = null;

    /**
     * File constructor.
     * @param array $config 配置参数
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
    }


    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job
     */
    public function pop($queueName)
    {
        // TODO: Implement pop() method.
    }

    /**
     * 压入队列
     * @param Job $job
     * @param string $queueName 队列名称
     * @return boolean
     */
    public function push(Job $job, $queueName)
    {
        // TODO: Implement push() method.
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @param string $queueName 队列名称
     * @return boolean
     */
    public function laterOn($delay, Job $job, $queueName)
    {
        // TODO: Implement laterOn() method.
    }
}