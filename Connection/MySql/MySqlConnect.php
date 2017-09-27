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
        self::$connect = @mysqli_connect(MYSQL_DB_HOST,MYSQL_DB_USERNAME,MYSQL_DB_PASSWORD,MYSQL_DB_DATABASE,MYSQL_DB_PORT);
        if(!self::$connect){
            throw new DBException("MySql Connection Error:".mysqli_connect_error(),mysqli_connect_errno());
        }
        mysqli_set_charset(self::$connect,MYSQL_DB_CHARSET);
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
        $date = date('Y-m-d H:i:s',time());
        $sql = 'SELECT * FROM `'.self::TABLE_NAME.'` WHERE `queueName` = "'.$queueName.'" AND `wantexectime` <= "'.$date.'" ORDER BY `wantexectime` ASC LIMIT 1';
        $res = mysqli_query(self::$connect,$sql);
        if($res){
            $result = mysqli_fetch_assoc($res);
            if($result){
                $job = unserialize($result['job']);

                //删除该任务
                $delsql = 'DELETE FROM `'.self::TABLE_NAME.'` WHERE `id` = '.$result['id'];
                $res2 = mysqli_query(self::$connect,$delsql);
                if($res2){
                    return $job;
                }else{
                    return null;
                }
            }else{
                return null;
            }
        }else{
            return null;
        }
    }

    /**
     * 压入队列
     * @param Job $job
     * @return boolean
     */
    public function push(Job $job)
    {
        $currtime = date('Y-m-d H:i:s',time());
        $queueName = $job->queueName;
        $createtime = $currtime;
        $wantexectime = $currtime;
        $jobstr = serialize($job);

        $sql = 'INSERT INTO `'.self::TABLE_NAME.'` (`queueName`,`createtime`,`job`,`wantexectime`) VALUES ("'.$queueName.'","'.$createtime.'",\''.$jobstr.'\',"'.$wantexectime.'");';
        return mysqli_query(self::$connect,$sql);
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @return boolean
     */
    public function laterOn($delay, Job $job)
    {
        $currtime = date('Y-m-d H:i:s',time());
        $queueName = $job->queueName;
        $createtime = $currtime;
        $wantexectime = date('Y-m-d H:i:s',time() + $delay);
        $jobstr = serialize($job);

        $sql = 'INSERT INTO `'.self::TABLE_NAME.'` (`queueName`,`createtime`,`job`,`wantexectime`) VALUES ("'.$queueName.'","'.$createtime.'",\''.$jobstr.'\',"'.$wantexectime.'");';
        return mysqli_query(self::$connect,$sql);
    }
}