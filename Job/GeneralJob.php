<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Job".DS."Job.php";
require_once TASK_ROOT_PATH.DS."Handler".DS."JobHandler.php";
require_once TASK_ROOT_PATH.DS."Exception".DS."TaskException.php";

class GeneralJob extends Job{

    /**
     * 连接对象
     * @var Connection
     */
    protected $connect = null;       //连接对象            Connection
    public    $connectType = "";     //连接对象（类型）     String

    private $handler;           //job handler         JobHandler
    private $isexec;            //是否执行成功         boolean
    private $attempts;          //已经执行次数         Int
    private $func;              //执行方法             String
    private $param;             //执行参数             array

    public $queueName;          //队列名称              String

    /**
     * @param Connection $connect 连接对象
     * @param String $queueName   队列名称
     * @param JobHandler $handler 回调类
     * @param String $func        回调类中的回调方法名
     * @param array $param        该回调方法需要的参数数组
     */
    public function __construct(Connection $connect , $queueName , JobHandler $handler , $func , array $param){
        parent::__construct($connect,$queueName);

        $this->init();

        $this->handler = $handler;
        $this->func    = $func;
        $this->param   = $param;
        $this->queueName = $queueName;
    }

    /**
     * 初始化默认任务参数
     */
    public function init(){
        $this->isexec = false;
        $this->attempts = 0;
    }

    /**
     * 该任务已经执行的次数
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }


    /**
     * 任务失败回调
     * @return void
     */
    public function failed()
    {
        $this -> handler -> failed($this,$this->func,$this->param);
    }

    /**
     * 任务成功回调
     * @return void
     */
    public function success()
    {
        $this -> handler -> success($this,$this->func,$this->param);
    }

    /**
     * 执行任务
     * @return mixed
     */
    public function execute()
    {
        $this -> attempts ++;
        try{

            //执行handler回调
            $this->handler->handler($this,$this->func,$this->param);

            $this->isexec = true;

        }catch (TaskException $e){

            $this -> isexec = false;

        }

    }

    /**
     * 任务是否执行成功
     * @return boolean
     */
    public function isExec()
    {
        return $this->isexec;
    }


    /**
     * 重试该任务
     * @param int $delay 延迟秒数
     * @return mixed
     */
    public function release($delay = 0)
    {
        return $this -> connect -> laterOn($delay , $this);
    }


}