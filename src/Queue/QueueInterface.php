<?php

namespace QueueTask\Queue;

use QueueTask\Job\Job;
use QueueTask\Handler\JobHandler;
/**
 * 队列接口
 * Class Queue
 */
interface QueueInterface
{


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