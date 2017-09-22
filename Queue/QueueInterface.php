<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Job".DS."Job.php";
require_once TASK_ROOT_PATH.DS."Handler".DS."JobHandler.php";

/**
 * 队列接口
 * Class Queue
 */
interface QueueInterface{


    /**
     * 弹出队列(弹出后队列中就没有这个任务了)
     * @param String $queueName 队列名称
     * @return Job
     */
     public function pop($queueName);


    /**
     * 入队列(内部)
     * @param Job $job
     * @param String $queueName    队列名称
     * @return boolean
     */
    //public function push(Job $job , $queueName);


    /**
     * 入队列  (对外)
     * @param JobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    public function pushOn(JobHandler $handler , $func , array $param , $queueName);



    /**
     * 延迟入队列 (对外)
     * @param Int $delay 延迟时间/秒
     * @param JobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    public function laterOn($delay,JobHandler $handler , $func , array $param , $queueName);


} 