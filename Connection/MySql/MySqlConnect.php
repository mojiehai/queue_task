<?php
require_once dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Connection.php";
require_once TASK_ROOT_PATH.DS."Exception".DS."DBException.php";


/**
 * MySql 操作任务类
 * Class MySqlConnect
 */
class MySqlConnect extends Connection{

    //数据表名
    const TABLE_NAME = "job_queue";

    //mysql连接
    protected static $connect = null;

    //单例对象
    protected static $instance = null;
    protected function __construct(){
        $this->init();
    }
    public function __destruct(){
        $this->close();
        self::$instance = null;
    }

    /**
     * 获取单例
     * @return MySqlConnect|null
     */
    public static function getInstance(){
        if( self::$instance == null ){
            self::$instance = new MySqlConnect();
        }
        return self::$instance;
    }


    /**
     * 初始化连接
     * @throws DBException
     */
    private function init(){
        // 初始化mysql连接
        self::$connect = mysqli_connect(QT_DB_HOST,QT_DB_USERNAME,QT_DB_PASSWORD,QT_DB_DATABASE,QT_DB_PORT);
        if(!self::$connect){
            throw new DBException("MySql Connection Error:".mysqli_connect_error(),mysqli_connect_errno());
        }
        mysqli_set_charset(self::$connect,QT_DB_CHARSET);
    }


    /**
     * 返回存储方式(mysql/redis/file...)
     * @return String
     */
    public function getType()
    {
        return STORAGE_MYSQL;
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        return mysqli_close(self::$connect);
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