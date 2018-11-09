<?php

namespace QueueTask\Process;
use QueueTask\Exception\ProcessException;

/**
 * 主进程类
 * Class Master
 * @package QueueTask\Process
 */
class Master extends Process
{

    /**
     * master进程存放pid文件的目录
     * @var string
     */
    protected static $pidFileDir = '';
    /**
     * master进程pid文件的完整目录
     * @var string
     */
    protected static $pidFilePath = '';
    /**
     * master进程pid文件的名称
     * @var string
     */
    protected static $pidFileName = 'queue_task.master.pid';

    /**
     * 最大工作进程数
     * @var int
     */
    protected $maxWorkerNum = 4;

    /**
     * 工作进程pid列表
     * @var array
     */
    protected $workers = [];

    /**
     * 检查工作进程时间间隔 单位：秒
     * @var int
     */
    protected $checkWorkerInterval = 300;

    /**
     * 允许配置的变量
     * @var array
     */
    protected $configNameList = ['checkWorkerInterval', 'maxWorkerNum'];

    /**
     * Master constructor.
     * @param int $pid
     * @throws ProcessException
     */
    protected function __construct($pid = 0)
    {
        parent::__construct($pid);
        $this->savePidToFile();
    }

    /**
     * 实例化进程类
     * @param int $pid  进程pid
     * @return Process
     * @throws ProcessException
     */
    public static function Create($pid = 0)
    {
        if (empty($pid)) {
            // pid为空，则会自动获取当前pid，检查pidFile的master进程是否存活，存活的话就不允许创建
            if (static::isAlive(static::getPidByFile())) {
                throw new ProcessException('process is already exists!');
            }
        }
        return parent::Create($pid);
    }

    ############################## pid file ###############################
    /**
     * 初始化pid文件
     * @throws ProcessException
     */
    protected static function initPidFile()
    {
        if (empty(static::$pidFileDir)) {
            static::$pidFileDir = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        }
        if (empty(static::$pidFilePath)) {
            static::$pidFilePath = static::$pidFileDir.static::$pidFileName;
        }
        if (!file_exists(static::$pidFilePath)) {
            if (!touch(static::$pidFilePath)) {
                throw new ProcessException('create file failure');
            }
        }
    }

    /**
     * 保存pid
     * @return bool
     * @throws ProcessException
     */
    protected function savePidToFile()
    {
        static::initPidFile();
        $return = file_put_contents(static::$pidFilePath, $this->pid);
        if ($return) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取master的pid
     * @return int pid
     * @throws ProcessException
     */
    protected static function getPidByFile()
    {
        static::initPidFile();
        return intval(file_get_contents(static::$pidFilePath));
    }
    ############################## pid file ###############################




    ############################## 子进程操作 ###############################
    /**
     * 是否需要添加工作进程
     * @return bool
     */
    protected function isAddWorker()
    {
        $this->checkWorkers();
        // 工作进程数量 小于 最大工作进程数
        if (count($this->workers) < $this->maxWorkerNum) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加子进程号
     * @param $pid
     */
    protected function addWorker($pid)
    {
        $this->workers[] = $pid;
        $this->workers = array_values($this->workers);  // 重新排列下标，防止下标过大
    }

    /**
     * 检查子进程，清理不存在的进程
     */
    protected function checkWorkers()
    {
        foreach ($this->workers as $k => $v) {
            if (!static::isAlive($v)) {
                unset($this->workers[$k]);
            }
        }
    }
    ############################## 子进程操作 ###############################



    /**
     * 进程执行的内容
     * @return void
     * @throws ProcessException
     */
    protected function runHandler()
    {
        echo $this->title . ":" .$this->pid."\n";

        posix_kill($this->pid, SIGALRM);
        while (true) {
            pcntl_signal_dispatch();
            pcntl_wait($status, WUNTRACED);//不阻塞
        }
    }

    /**
     * 添加信号
     */
    protected function setSignal()
    {
        // 1、闹钟信号
        pcntl_signal(SIGALRM, [$this, 'sigalrmHandler'], false);
    }




    ########################## 信号处理程序 ##############################
    /**
     * 闹钟信号处理程序(检测子进程数)
     * @throws ProcessException
     */
    protected function sigalrmHandler()
    {
        pcntl_alarm($this->checkWorkerInterval);    // 设置下次轮询的闹钟
        // 循环开启子进程
        while($this->isAddWorker()) {
            $workerPid = pcntl_fork();  // 开启子进程
            if ($workerPid > 0) {
                // 该分支为父进程
                $this->addWorker($workerPid);
            } else if ($workerPid == 0) {
                // 该分支为子进程
                $worker = Worker::Create();
                $worker->setConfig($this->config)->setWork($this->closure)->run();
                exit();
            } else {
                // fork失败
                throw new ProcessException('fork process error');
            }
        }
    }
    ########################## 信号处理程序 ##############################



}