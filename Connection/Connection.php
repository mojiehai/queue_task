<?php


/**
 * 连接类
 * Class Connection
 */
abstract class Connection {

    protected function __construct(){}

    /**
     * 返回存储方式(mysql/redis/file...)
     * @return String
     */
    abstract public function getType();


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
     * @return boolean
     */
    abstract public function push(Job $job);


    /**
     * 添加一条延迟任务
     * @param int $delay    延迟的秒数
     * @param Job $job  任务
     * @return boolean
     */
    abstract public function laterOn($delay , Job $job);

} 