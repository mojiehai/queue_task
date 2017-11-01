<?php

namespace QueueTask\Job;

/**
 * 任务
 * Class Job
 */
abstract class Job
{

    public    $connectType = "";    //连接对象（类型）

    public    $queueName = "";      //队列名称

    public    $checkid = '';        //随机字符串(防止队列唯一)

    public function __construct($connectType , $queueName)
    {
        $this->connectType = $connectType;
        $this->$queueName  = $queueName;
        $this->checkid     = uniqid(rand(0,9999),true);
    }

    /**
     * 该任务已经执行的次数
     * @return int
     */
    abstract public function getAttempts();



    /**
     * 任务失败回调
     * @return void
     */
    abstract public function failed();

    /**
     * 任务成功回调
     * @return void
     */
    abstract public function success();

    /**
     * 执行任务
     * @return mixed
     */
    abstract public function execute();


    /**
     * 任务是否执行成功
     * @return boolean
     */
    abstract public function isExec();


    /**
     * 重试该任务
     * @param int $delay   延迟秒数
     * @return mixed
     */
    abstract public function release($delay = 0);


    /**
     * 序列化Job对象，用于持久化存储
     * @param Job $job   Job
     * @return string
     */
    public static function EncodeJob(Job $job)
    {
        return base64_encode(serialize($job));
    }


    /**
     * 反序列化Job对象
     * @param String $objStr
     * @return Job
     */
    public static function DecodeJob($objStr)
    {
        return unserialize(base64_decode($objStr));
    }

}