<?php


require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Connection.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."ConnectAdapter.php";

/**
 * 任务
 * Class Job
 */
abstract class Job {

    /**
     * 连接对象
     * @var Connection
     */
    protected $connect = null;      //连接对象
    public    $connectType = "";    //连接对象（类型）

    public    $queueName = "";      //队列名称

    public function __construct(Connection $connect , $queueName){
        $this->connect     = $connect;
        $this->connectType = $connect->getType();
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


    /**
     * 设置该任务的连接对象
     * @param $conn
     */
    public function setConnect($conn){
        $this->connect = $conn;
    }

    /**
    public function __sleep(){
    }

    public function __wakeup(){
    }
     */


    /**
     * 序列化
     * @return string
     */
    public function m_Serialize(){
        //$this->connect = null;
        return serialize($this);
    }

    /**
     * 反序列化
     * @param $objStr
     * @return Job
     */
    public static function m_UnSerialize($objStr){
        $obj = unserialize($objStr);
        if( $obj instanceof Job ){
            $obj -> setConnect = ConnectAdapter::getConnection($obj->connectType);
            return $obj;
        } else {
            return null;
        }
    }

} 