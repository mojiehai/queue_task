<?php


abstract class PersistJob {


    /**
     * 该任务已经执行的次数
     * @return int
     */
    abstract public function getAttempts();


    /**
     * 删除任务
     * @return boolean
     */
    abstract public function delete();



    /**
     * 任务失败回调
     * @return void
     */
    abstract public function failed();


    /**
     * 执行任务
     * @return mixed
     */
    abstract public function execute();



    /**
     * 任务是否删除
     * @return boolean
     */
    abstract public function isDeleted();


    /**
     * 重试该任务
     * @param int $delay   延迟秒数
     * @return mixed
     */
    abstract public function release($delay = 0);

} 