<?php

namespace QueueTask\Connection;
use QueueTask\Job\Job;
use ProcessManage\Exception\Exception;

/**
 * 连接类
 * 增加新的存储介质，需要继承该类
 * Class Connection
 */
abstract class Connection
{
    protected $config = [];

    /**
     * Connection constructor.
     * @param array $config 配置参数
     */
    protected function __construct(array $config = []){
        $this->config = $config;
    }

    /**
     * Connection destruct.
     */
    public function __destruct()
    {
        $this->close();
        static::$instance = null;
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
     * 获取单例
     * @param array $config 配置参数
     * @return Connection|null
     */
    public static function getInstance($config = [])
    {
        if(!(static::$instance instanceof Connection)) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }

    /**
     * 关闭连接
     * @return boolean
     */
    abstract public function close();


    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job|null
     */
    abstract public function pop($queueName);


    /**
     * 压入队列
     * @param Job $job
     * @param String $queueName 队列名
     * @return boolean
     */
    abstract public function push(Job $job, $queueName);


    /**
     * 添加一条延迟任务
     * @param int $delay    延迟的秒数
     * @param Job $job  任务
     * @param String $queueName 队列名
     * @return boolean
     */
    abstract public function laterOn($delay , Job $job, $queueName);

} 