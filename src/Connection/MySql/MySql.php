<?php

namespace QueueTask\Connection\MySql;

use QueueTask\Connection\Connection;
use QueueTask\Exception\DBException;
use ProcessManage\Exception\Exception;
use QueueTask\Helpers\Lock\FileLock;
use QueueTask\Job\Job;

/**
 * MySql 操作任务类
 * Class MySql
 */
class MySql extends Connection
{

    //数据表名
    public static $TABLE_NAME = "";

    //延迟队列表名
    public static $DELAY_TABLE_NAME = "";

    //mysql连接
    protected static $connect = null;

    //单例对象
    protected static $instance = null;

    //是否初始化head
    protected $isInitHead = false;

    //最后一个sql
    protected $lastSql = '';

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
        if (isset($config['DB_TABLE_ASYNC']) && !empty($config['DB_TABLE_ASYNC'])) {
            static::$DELAY_TABLE_NAME = $config['DB_TABLE_ASYNC'];
        }
        if (empty(static::$DELAY_TABLE_NAME)) {
            die("DB_TABLE_ASYNC is empty");
            throw new DBException("MySql Init Error: config 'DB_TABLE_ASYNC' is empty");
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
            $lockObj = new FileLock;
            $lockObj->lock();

            $this->begin();

            $date = date('Y-m-d H:i:s');
            $delaySql = 'SELECT `id`,`job`,`queueName` FROM `'.static::$DELAY_TABLE_NAME.'` WHERE `queueName` = "'.$queueName.'" AND `wantExecTime` <= "'.$date.'" ORDER BY `wantExecTime`,`id` FOR UPDATE';
            $delayRes = $this->executeSql($delaySql);
            if (!($delayRes instanceof \mysqli_result)) {
                throw new DBException("MySql Error:".mysqli_error(self::$connect), mysqli_errno(self::$connect));
            }
            $delayData = mysqli_fetch_all($delayRes);
            if ($delayData) {
                $insertData = [];
                $ids        = [];
                foreach ($delayData as $k => $delay) {
                    $ids[]        = $delay[0];
                    $insertData[] = ['job' => $delay[1], 'queueName' => $delay[2]];
                }
                if (!($this->delete(['id' => ['in', $ids]], static::$DELAY_TABLE_NAME))) {
                    throw new DBException("MySql Error:".mysqli_error(self::$connect), mysqli_errno(self::$connect));
                }
                if (!$this->insertAll($insertData)) {
                    throw new DBException("MySql Error:".mysqli_error(self::$connect), mysqli_errno(self::$connect));
                }
            }

            $sql = 'SELECT `id`,`job` FROM `'.static::$TABLE_NAME.'` WHERE `queueName` = "'.$queueName.'" FOR UPDATE';
            $res = $this->executeSql($sql);
            if (!($res instanceof \mysqli_result)) {
                throw new DBException("MySql Error:".mysqli_error(self::$connect), mysqli_errno(self::$connect));
            }

            $job = null;

            $result = mysqli_fetch_assoc($res);

            if ($result) {
                // 任务节点，提取该任务，并删除该任务
                if(!($this->delete(['id' => $result['id']]))) {
                    throw new DBException("MySql Error:".mysqli_error(self::$connect), mysqli_errno(self::$connect));
                }
                $job = Job::Decode($result['job']);
            }

            $this->commit();

            $lockObj->unlock();

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
     * @throws DBException
     */
    public function push(Job $job, $queueName)
    {
        $jobStr = Job::Encode($job);

        try {
            $this->begin();
            $res = $this->insert([
                'queueName' => $queueName,
                'job' => $jobStr,
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
     * @throws DBException
     */
    public function laterOn($delay, Job $job, $queueName)
    {
        $timestamp = time();
        $wantExecTime = date('Y-m-d H:i:s',$timestamp + $delay);
        $jobStr = Job::Encode($job);

        try {
            $this->begin();
            $res = $this->insert([
                'queueName' => $queueName,
                'job' => $jobStr,
                'wantExecTime' => $wantExecTime,
            ], true);
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
     * @param $delay
     * @return bool
     * @throws DBException
     */
    protected function insert($data,$delay = false)
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
            $table = $delay? static::$DELAY_TABLE_NAME: static::$TABLE_NAME;
            $sql = 'INSERT INTO `'.$table.'` ('.implode(',', $keys).') VALUES ('.implode(',', $values).');';

            return $this->executeSql($sql);
        }
    }

    /**
     * 往数据库添加多条任务记录
     *
     * @param $dataSet
     * @return bool
     * @throws DBException
     */
    public function insertAll($dataSet)
    {
        $fields = array_map(function($v)
        {
            return "`{$v}`";
        }, array_keys($dataSet[0]));

        $values = [];
        foreach ($dataSet as $data) {
            $values[] = '("'.implode('","', $data).'")';
        }

        $sql = 'INSERT INTO '.static::$TABLE_NAME.' ('.implode(',', $fields).') VALUES '.implode(',', $values);

        return $this->executeSql($sql);
    }


    /**
     * 删除数据库任务
     * @param $where
     * @param $table
     * @return bool
     * @throws DBException
     */
    protected function delete($where,$table='')
    {
        if (empty($where)) {
            return false;
        } else {
            $tableName = $table? $table: static::$TABLE_NAME;

            $tempWhere = [];
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    if ('in' == strtolower($v[0])) {
                        $tempWhere[] = '`'.$k.'` IN ( "'.implode('","', $v[1]).'" )';
                    } else {
                        $action = [];
                        foreach ($v as $vv) {
                            $action[] = $vv;
                        }
                        $tempWhere[] = '`'.$k.'` '.implode(' ', $action);
                    }
                } else {
                    $tempWhere[] = '`'.$k.'` = "'.$v.'"';
                }
            }
            $whereStr = implode(' AND ', $tempWhere);
            //删除该任务
            $delSql = 'DELETE FROM `'.$tableName.'` WHERE '.$whereStr;

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
        $this->lastSql = $sql;
        return mysqli_query(self::$connect,$sql);
    }

    /**
     * 获取最后执行的sql
     * @return string
     */
    public function getLastSql()
    {
        return $this->lastSql;
    }

}