<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Handler".DIRECTORY_SEPARATOR."PersistJobHandler.php";

abstract class PersistQueue {


    /**
     * 出队列
     * @param String $queueName 队列名称
     * @return PersistJob
     */
    abstract public function pop($queueName);


    /**
     * 入队列
     * @param PersistJob $job
     * @return boolean
     */
    abstract public function push(PersistJob $job);


    /**
     * 入队列  (对外)
     * @param PersistJobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    abstract public function pushOn(PersistJobHandler $handler , $func , array $param , $queueName);



    /**
     * 延迟入队列 (对外)
     * @param Int $delay 延迟时间/秒
     * @param PersistJobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    abstract public function laterOn($delay,PersistJobHandler $handler , $func , array $param , $queueName);


} 