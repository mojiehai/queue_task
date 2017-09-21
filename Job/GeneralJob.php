<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Job".DS."Job.php";
require_once TASK_ROOT_PATH.DS."Handler".DS."JobHandler.php";
require_once TASK_ROOT_PATH.DS."Exception".DS."TaskException.php";

class GeneralJob extends Job{

    protected $connect = null;       //连接对象            Connection
    public    $connectType = "";     //连接对象（类型）     String

    private $id       = 0;           //job id              Int
    private $handler  = null;        //job handler         JobHandler
    private $isexec   = false;       //是否执行成功         boolean
    private $attempts = 0;           //已经执行次数         Int
    private $func     = "";          //执行方法             String
    private $param    = [];          //执行参数             array

    public function __construct(Connection $connect , JobHandler $handler , $func , array $param){
        parent::__construct($connect);

        $this->handler = $handler;
        $this->func    = $func;
        $this->param   = $param;
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
     * 删除任务
     * @return boolean
     */
    public function delete()
    {
        return $this -> connect -> deleteJob($this);
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
        return $this -> connect -> releaseJob($this , $delay);
    }


}