<?php

require_once dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Redis".DS."RedisDrive.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Connection.php";

/**
 * Redis 操作任务类
 * Class RedisConnect
 */
class RedisConnect extends Connection{

    /**
     * redis驱动
     * @var null|RedisDrive
     */
    protected static $connect = null;

    /**
     * 本类单例
     * @var RedisConnect
     */
    protected static $instance = null;
    protected function __construct(){
        // 初始化redis连接
        $config = [
            'host' => REDIS_DB_HOST,
            'port' => REDIS_DB_PORT,
            'database' => REDIS_DB_DATABASE
        ];
        self::$connect = RedisDrive::getInstance($config);
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
        return STORAGE_REDIS;
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        self::$connect->close();
    }

    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job|null
     */
    public function pop($queueName){
        //从延迟集合中合并到主执行队列
        $this->migrateAllExpiredJobs($queueName);

        //弹出任务
        $jobstr = self::$connect->lpop($queueName);
        if(!is_null($jobstr)){
            return unserialize($jobstr);
        }else{
            return null;
        }

    }

    /**
     * 压入队列
     * @param Job $job
     * @return boolean
     *
     *
     * 直接压入主执行队列
     */
    public function push(Job $job)
    {
        //命令：rpush 队列名 任务
        $res = self::$connect->rpush($job->queueName , serialize($job));
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @return boolean
     *
     *
     * 放入等待执行任务的有序集合中
     */
    public function laterOn($delay, Job $job)
    {
        //命令：zadd  主队列名:delayed   当前时间戳+延迟秒数  任务
        $res = self::$connect->zadd($job->queueName.":delayed" , time() + $delay , serialize($job));
        if($res){
            return true;
        }else{
            return false;
        }
    }




    /**
     * 合并等待执行的任务
     * @param  string $queueName
     * @return void
     */
    protected function migrateAllExpiredJobs($queueName)
    {
        $this->migrateExpiredJobs($queueName . ':delayed', $queueName);
    }


    /**
     * 当延时任务到大执行时间时，将延时任务从延时任务集合中移动到主执行队列中
     * @param  string $from     集合名称
     * @param  string $to       队列名称
     * @return void
     */
    protected function migrateExpiredJobs($from, $to)
    {
        $time = time();
        $jobs = $this->getExpiredJobs($from, $time);
        if (count($jobs) > 0) {
            //开始redis事物
            self::$connect->watch($from);
            self::$connect->multi();

            $this->removeExpiredJobs( $from, $time);
            $this->pushExpiredJobsOntoNewQueue( $to, $jobs);

            self::$connect->exec();
        }
    }


    /**
     * 从指定集合中获取所有超时的任务
     * @param String $name     集合名称
     * @param int $time     超时时间(集合中小于该时间为超时)
     * @return mixed
     */
    public function getExpiredJobs($name , $time){
        return self::$connect->zrangebyscore($name , '-inf' , $time);
    }


    /**
     * 从指定集合删除过期任务
     * @param  string $from
     * @param  int $time
     * @return void
     */
    protected function removeExpiredJobs($from, $time){
        self::$connect->zremrangebyscore($from, '-inf', $time);
    }


    /**
     * 将多个任务从添加到队列
     *
     * 场景：将有序集合中的延迟任务入主队列
     * @param  string $to
     * @param  array $jobs
     * @return void
     */
    protected function pushExpiredJobsOntoNewQueue($to, $jobs){
        //等价于  self::$connect->rpush($to,$jobs[0],$jobs[1]... );
        call_user_func_array([self::$connect, 'rpush'], array_merge([$to], $jobs));
    }

}