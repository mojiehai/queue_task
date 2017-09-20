<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Job".DS."Job.php";
require_once TASK_ROOT_PATH.DS."Handler".DS."JobHandler.php";

/**
 * 队列
 * Class Queue
 */
abstract class Queue {


    /**
     * 出队列
     * @param String $queueName 队列名称
     * @return Job
     */
    abstract public function pop($queueName);


    /**
     * 入队列
     * @param Job $job
     * @return boolean
     */
    abstract public function push(Job $job);


    /**
     * 入队列  (对外)
     * @param JobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    abstract public function pushOn(JobHandler $handler , $func , array $param , $queueName);



    /**
     * 延迟入队列 (对外)
     * @param Int $delay 延迟时间/秒
     * @param JobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    abstract public function laterOn($delay,JobHandler $handler , $func , array $param , $queueName);


} 