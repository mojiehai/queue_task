<?php

namespace QueueTask\Connection\MySql;

use QueueTask\Connection\Connection;
use QueueTask\Exception\DBException;
use QueueTask\Exception\Exception;
use QueueTask\Job\Job;

/**
 * MySql 操作任务类
 * Class MySql
 */
class MySql extends Connection
{

    //数据表名
    public static $TABLE_NAME = "";

    //mysql连接
    protected static $connect = null;

    //单例对象
    protected static $instance = null;

    /**
     * 配置参数
     * MySql constructor.
     * @param array $config
     * @throws DBException
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['DB_TABLE']) && !empty($config['DB_TABLE'])) {
            static::$TABLE_NAME = $config['DB_TABLE'];
        }
        if (empty(static::$TABLE_NAME)) {
            throw new DBException("MySql Init Error: config 'DB_TABLE' is empty");
        }
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
        $config = $this->config;
        // 初始化mysql连接
        self::$connect = @mysqli_connect($config['DB_HOST'],$config['DB_USERNAME'],$config['DB_PASSWORD'],$config['DB_DATABASE'],$config['DB_PORT']);
        if(!self::$connect) {
            throw new DBException("MySql Connection Error:".mysqli_connect_error(),mysqli_connect_errno());
        }
        mysqli_set_charset(self::$connect,$config['DB_CHARSET']);
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
     * @throws Exception
     */
    public function pop($queueName)
    {
        try {
            $this->begin();
            $date = date('Y-m-d H:i:s',time());
            $sql = 'SELECT * FROM `'.static::$TABLE_NAME.'` WHERE `queueName` = "'.$queueName.'" AND `wantExecTime` <= "'.$date.'" ORDER BY `wantExecTime` ASC LIMIT 1 FOR UPDATE';
            $res = $this->executeSql($sql);
            if (!($res instanceof \mysqli_result)) {
                throw new DBException("MySql Error:".mysqli_error(self::$connect),mysqli_errno(self::$connect));
            }

            $job = null;
            $result = mysqli_fetch_assoc($res);
            if($result) {
                //删除该任务
                if(!($this->delete(['id' => $result['id']]))) {
                    throw new DBException("MySql Error:".mysqli_error(self::$connect),mysqli_errno(self::$connect));
                }
                $job = Job::Decode($result['job']);
            }

            $this->commit();
            return $job;

        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 压入队列
     * @param Job $job
     * @param string $queueName 队列名称
     * @return bool
     */
    public function push(Job $job, $queueName)
    {
        $timestamp = time();
        $currTime = date('Y-m-d H:i:s', $timestamp);
        $createTime = $currTime;
        $wantExecTime = $currTime;
        $jobStr = Job::Encode($job);

        try {
            $this->begin();
            $res = $this->insert([
                'queueName' => $queueName,
                'createTime' => $createTime,
                'job' => $jobStr,
                'wantExecTime' => $wantExecTime,
            ]);
            if (!$res) {
                throw new DBException("MySql Error:".mysqli_error(self::$connect),mysqli_errno(self::$connect));
            }
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @param string $queueName 队列名称
     * @return bool
     */
    public function laterOn($delay, Job $job, $queueName)
    {
        $timestamp = time();
        $currTime = date('Y-m-d H:i:s', $timestamp);
        $createTime = $currTime;
        $wantExecTime = date('Y-m-d H:i:s',$timestamp + $delay);
        $jobStr = Job::Encode($job);

        try {
            $this->begin();
            $res = $this->insert([
                'queueName' => $queueName,
                'createTime' => $createTime,
                'job' => $jobStr,
                'wantExecTime' => $wantExecTime,
            ]);
            if (!$res) {
                throw new DBException("MySql Error:".mysqli_error(self::$connect),mysqli_errno(self::$connect));
            }
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 开启事务
     * @throws DBException
     */
    protected function begin()
    {
        $this->executeSql('begin');
    }

    /**
     * 回滚事务
     * @throws DBException
     */
    protected function rollback()
    {
        $this->executeSql('rollback');
    }

    /**
     * 提交事务
     * @throws DBException
     */
    protected function commit()
    {
        $this->executeSql('commit');
    }

    /**
     * 往数据库添加任务记录
     * @param $data
     * @return bool
     * @throws DBException
     */
    protected function insert($data)
    {
        if (empty($data)) {
            return false;
        } else {
            $keys = [];
            $values = [];
            foreach ($data as $k => $v) {
                $keys[] = '`'.$k.'`';
                $values[] = '"'.$v.'"';
            }
            $sql = 'INSERT INTO `'.static::$TABLE_NAME.'` ('.implode(',', $keys).') VALUES ('.implode(',', $values).');';
            return $this->executeSql($sql);
        }
    }


    /**
     * 删除数据库任务
     * @param $where
     * @return bool
     * @throws DBException
     */
    protected function delete($where)
    {
        if (empty($where)) {
            return false;
        } else {
            $tempWhere = [];
            foreach ($where as $k => $v) {
                $tempWhere[] = '`'.$k.'` = "'.$v.'"';
            }
            $whereStr = implode(' AND ', $tempWhere);
            //删除该任务
            $delSql = 'DELETE FROM `'.static::$TABLE_NAME.'` WHERE '.$whereStr;
            return $this->executeSql($delSql);
        }
    }

    /**
     * 执行sql
     * @param $sql
     * @return bool|\mysqli_result
     * @throws DBException
     */
    protected function executeSql($sql)
    {
        $this->open();
        return mysqli_query(self::$connect,$sql);
    }


}