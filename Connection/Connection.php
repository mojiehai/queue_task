<?php


/**
 * 连接类
 * Class Connection
 */
abstract class Connection {

    /**
     * 返回存储方式(mysql/redis/file...)
     * @return String
     */
    abstract public function getType();


    /**
     * 关闭连接
     * @return
     */
    abstract public function close();


    /**
     * 删除该条任务
     * @param Job $job
     * @return boolean
     */
    abstract public function deleteJob(Job $job);


    /**
     * 重新添加这条任务
     * @param Job $job  任务
     * @param $delay    延迟的秒数
     * @return boolean
     */
    abstract public function releaseJob(Job $job , $delay);

} 