<?php

require_once dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Connection.php";

/**
 * Redis 操作任务类
 * Class RedisConnect
 */
class RedisConnect extends Connection{

    protected static $instance = null;
    protected function __construct(){
        //TODO  初始化redis连接
    }
    public function __destruct(){
        $this->close();
        self::$instance = null;
    }
    public static function getInstance(){
        if( self::$instance == null ){
            self::$instance = new RedisConnect();
        }
        return self::$instance;
    }


    /**
     * 返回存储方式(mysql/redis/file...)
     * @return String
     */
    public function getType()
    {
        // TODO: Implement getType() method.
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job
     */
    public function pop($queueName)
    {
        // TODO: Implement pop() method.
    }

    /**
     * 压入队列
     * @param Job $job
     * @return boolean
     */
    public function push(Job $job)
    {
        // TODO: Implement push() method.
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @return boolean
     */
    public function laterOn($delay, Job $job)
    {
        // TODO: Implement laterOn() method.
    }
}