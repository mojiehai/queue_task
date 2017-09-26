<?php


require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Connection.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."ConnectAdapter.php";

/**
 * 任务
 * Class Job
 */
abstract class Job {

    public    $connectType = "";    //连接对象（类型）

    public    $queueName = "";      //队列名称

    public function __construct($connectType , $queueName){
        $this->connectType = $connectType;
        $this->$queueName  = $queueName;
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


}