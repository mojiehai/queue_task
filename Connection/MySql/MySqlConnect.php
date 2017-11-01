<?php

namespace QueueTask\Connection\MySql;

use QueueTask\Config\Config;
use QueueTask\Connection\Connection;
use QueueTask\Exception\DBException;
use QueueTask\Job\Job;

/**
 * MySql 操作任务类
 * Class MySqlConnect
 */
class MySqlConnect extends Connection
{

    //数据表名
    const TABLE_NAME = "job_queue";

    //mysql连接
    protected static $connect = null;

    //单例对象
    protected static $instance = null;
    protected function __construct(){}
    public function __destruct()
    {
        $this->close();
        self::$instance = null;
    }

    /**
     * 获取单例
     * @return MySqlConnect|null
     */
    public static function getInstance()
    {
        if( self::$instance == null ) {
            self::$instance = new MySqlConnect();
        }
        return self::$instance;
    }


    /**
     * 初始化连接
     * @throws DBException
     */
    private function open()
    {
        if(self::$connect != null) {
            return;
        }

        // 初始化mysql连接
        self::$connect = @mysqli_connect(Config::MYSQL_DB_HOST,Config::MYSQL_DB_USERNAME,Config::MYSQL_DB_PASSWORD,Config::MYSQL_DB_DATABASE,Config::MYSQL_DB_PORT);
        if(!self::$connect) {
            throw new DBException("MySql Connection Error:".mysqli_connect_error(),mysqli_connect_errno());
        }
        mysqli_set_charset(self::$connect,Config::MYSQL_DB_CHARSET);
    }


    /**
     * 返回存储方式(mysql/redis/file...)
     * @return String
     */
    public function getType()
    {
        return Config::STORAGE_MYSQL;
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        if(self::$connect != null) {
            return mysqli_close(self::$connect);
        } else {
            return true;
        }
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
        $res = $this->executeSql($sql);
        if($res) {
            $result = mysqli_fetch_assoc($res);
            if($result) {
                $job = Job::DecodeJob($result['job']);

                //删除该任务
                $delsql = 'DELETE FROM `'.self::TABLE_NAME.'` WHERE `id` = '.$result['id'];
                $res2 = $this->executeSql($delsql);
                if($res2) {
                    return $job;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
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
        $jobstr = Job::EncodeJob($job);

        $sql = 'INSERT INTO `'.self::TABLE_NAME.'` (`queueName`,`createtime`,`job`,`wantexectime`) VALUES ("'.$queueName.'","'.$createtime.'",\''.$jobstr.'\',"'.$wantexectime.'");';
        return $this->executeSql($sql);
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
        $jobstr = Job::EncodeJob($job);

        $sql = 'INSERT INTO `'.self::TABLE_NAME.'` (`queueName`,`createtime`,`job`,`wantexectime`) VALUES ("'.$queueName.'","'.$createtime.'",\''.$jobstr.'\',"'.$wantexectime.'");';
        return $this->executeSql($sql);
    }


    /**
     * 执行sql
     * @param $sql
     * @return bool|\mysqli_result
     */
    private function executeSql($sql)
    {
        $this->open();
        return mysqli_query(self::$connect,$sql);
    }


}