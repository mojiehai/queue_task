<?php

namespace QueueTask\Connection;

use QueueTask\Job;
use ProcessManage\Exception\Exception;

/**
 * 连接类
 * 增加新的存储介质，需要继承该类
 * Class Connection
 */
abstract class Connection
{
    /**
     * config
     * @var array
     */
    protected $config = [];

    /**
     * singleton
     * @var Connection
     */
    protected static $instance = null;

    /**
     * pop阻塞超时时长
     * @var int
     */
    protected $popTimeOut = 3;

    /**
     * 处理程序
     * @var \Closure
     */
    protected $handler = null;

    /**
     * Connection constructor.
     * @param array $config 配置参数
     */
    protected function __construct(array $config = []){
        $this->config = $config;
        if (isset($config['POP_TIME_OUT']) && $config['POP_TIME_OUT'] > 0) {
            $this->popTimeOut = $config['POP_TIME_OUT'];
        }
    }

    /**
     * 设置处理程序
     * @param \Closure $handler
     */
    public function setHandler(\Closure $handler)
    {
        $this->handler = $handler;
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
     * 执行pop出来的任务(阻塞方法)
     * @param string $queueName
     */
    public function popRun($queueName)
    {
        $extends = [];
        $job = $this->pop($queueName, $extends);
        if ($job instanceof Job) {
            // 执行任务
            $handler = $this->handler;
            $handler($job, $queueName);

            // 确认任务
            $this->ack($queueName, $extends);
        }
    }

    /**
     * 弹出队头任务(先删除后返回该任务)(blocking)
     * @param string $queueName 队列名称
     * @param array & $extends 额外需要传递给ack方法的参数
     * @return Job|null
     */
    abstract public function pop($queueName, & $extends = []);

    /**
     * 确认任务
     * @param string $queueName
     * @param array $extends
     */
    abstract public function ack($queueName, $extends);

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
    abstract public function later($delay , Job $job, $queueName);

} 