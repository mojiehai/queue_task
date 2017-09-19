<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/8
 * Time: 11:59
 */

namespace app\PersistenceTask\Job;


use app\PersistenceTask\Exception\PersistException;
use app\models\JobQueue;
use app\PersistenceTask\Handler\PersistJobHandler;

class MySqlJob extends PersistJob{


    private $model = null;      //job_queue model

    /****************** job_queue 字段 ***************/
    private $handler    = null;         //job handler   class
    private $isexec     = false;        //是否执行完毕  boolean
    private $id         = null;         //id            int
    private $ququeName  = null;         //队列名称      string
    private $attempts   = null;         //执行次数      int
    private $func       = null;         //执行方法      string
    private $param      = null;         //执行参数      array
    private $isdelete   = false;        //是否删除      boolean
    /****************** job_queue 字段 ***************/

    public function __construct(JobQueue $model){
        $this -> model  = $model;

        $this -> id        = $model -> id;
        $this -> ququeName = $model -> ququeName;
        $this -> attempts  = $model -> attempts;
        $this -> handler   = unserialize($model -> handlerclass) ;
        $this -> isexec    = ($model -> isexec == 0) ? false:true;
        $this -> func      = $model -> func;
        $this -> param     = json_decode($model -> param , true);
        $this -> isdelete  = ($model -> isdelete == 0) ? false:true;

        if( !($this -> handler instanceof PersistJobHandler) ){
            throw new \Exception('handler error');
        }

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
        $this -> isdelete = true;
        return $this->bindModel()->update();
    }

    /**
     * 任务失败回调
     * @return void
     */
    public function failed()
    {
        $this->handler->failed($this,$this->func,$this->param);
    }

    /**
     * 执行任务
     * @return mixed
     */
    public function execute()
    {
        try{

            //执行handler回调
            $this->handler->handler($this,$this->func,$this->param);

            $this->isexec = true;
            $this->isdelete = true;

        }catch (PersistException $e){

            $this -> isexec = false;

        } finally {

            $this -> attempts ++;
            $this -> bindModel() -> update();

        }
    }

    /**
     * 任务是否删除
     * @return boolean
     */
    public function isDeleted()
    {
        return $this -> isdelete;
    }

    /**
     * 重试该任务
     * @param int $delay 延迟秒数
     * @return mixed
     */
    public function release($delay = 0)
    {
    }


    /**
     * 绑定model
     * @return MySqlJob
     */
    public function bindModel(){

        $this -> model -> attempts      = $this->attempts;
        $this -> model -> isexec        = $this->isexec ? 1 : 0;
        $this -> model -> isdelete      = $this->isdelete ? 1 : 0;

        return $this;

    }


    /**
     * 获取model
     * @return JobQueue
     */
    public function getModel(){
        return $this->model;
    }


    /**
     * 更新modle
     * @return bool|int
     * @throws \Exception
     */
    private function update(){
        return $this -> model -> update();
    }

}