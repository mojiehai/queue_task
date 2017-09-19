<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/8
 * Time: 11:26
 */



use app\models\JobQueue;
use app\PersistenceTask\Handler\PersistJobHandler;
use app\PersistenceTask\Job\MySqlJob;
use app\PersistenceTask\Job\PersistJob;

class MySqlQueue extends PersistQueue{


    private static $instance = null;
    private function __construct(){}
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new MySqlQueue();
        }
        return self::$instance;
    }
    public function __destruct(){
        self::$instance = null;
    }





    /**
     * 出队列
     * @param $queueName
     * @return MySqlJob/null
     */
    public function pop($queueName)
    {
        $currtime = date('Y-m-d H:i:s',time());
        $one = JobQueue::find()
            -> where(' `ququeName` = "'.$queueName.'" and `isexec` = 0 and `isdelete` = 0 and `wantexectime` <= "'.$currtime.'" ')
            -> orderBy(' `wantexectime` ASC ')
            -> one();
        if($one){
            return new MySqlJob($one);
        }else{
            return null;
        }
    }

    /**
     * 入队列
     * @param MySqlJob $job
     * @return boolean
     */
    public function push(PersistJob $job)
    {
        return $job -> bindModel() -> getModel() -> save();
    }


    /**
     * 入队列  (对外)
     * @param PersistJobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    public function pushOn(PersistJobHandler $handler, $func, array $param, $queueName)
    {
        $currtime  = date('Y-m-d H:i:s',time());
        $job_queue = new JobQueue();
        $job_queue -> ququeName     = $queueName;
        $job_queue -> attempts      = 0;
        $job_queue -> handlerclass  = serialize($handler);
        $job_queue -> func          = $func;
        $job_queue -> param         = json_encode($param);
        $job_queue -> createtime    = $currtime;
        $job_queue -> wantexectime  = $currtime;
        $job_queue -> isexec        = 0;
        $job_queue -> isdelete      = 0;

        $job = new MySqlJob($job_queue);
        return $this -> push($job);
    }




    /**
     * 延迟入队列 (对外)
     * @param Int $delay 延迟时间/秒
     * @param PersistJobHandler $handler 回调类
     * @param String $func  方法名
     * @param array $param  参数
     * @param String $queueName 队列名
     * @return boolean
     */
    public function laterOn($delay,PersistJobHandler $handler , $func , array $param , $queueName){

        $currtime  = date('Y-m-d H:i:s',time());
        $exectime  = date('Y-m-d H:i:s',time() + $delay);
        $job_queue = new JobQueue();
        $job_queue -> ququeName     = $queueName;
        $job_queue -> attempts      = 0;
        $job_queue -> handlerclass  = serialize($handler);
        $job_queue -> func          = $func;
        $job_queue -> param         = json_encode($param);
        $job_queue -> createtime    = $currtime;
        $job_queue -> wantexectime  = $exectime;
        $job_queue -> isexec        = 0;
        $job_queue -> isdelete      = 0;

        $job = new MySqlJob($job_queue);
        return $this -> push($job);

    }



}