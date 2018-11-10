<?php

namespace QueueTask\Process;
use QueueTask\Exception\ProcessException;
use QueueTask\Helpers\Log;

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
     * 现在是否需要检测工作进程数
     * @var bool
     */
    protected $isCheckWorker = false;

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
    public static function getPidByFile()
    {
        static::initPidFile();
        return intval(file_get_contents(static::$pidFilePath));
    }
    ############################## pid file ###############################




    ############################## 子进程操作 ###############################
    /**
     * 检查工作进程，清理不存在的进程
     */
    protected function checkWorkers()
    {
        foreach ($this->workers as $k => $v) {
            if (!static::isAlive($v)) {
                unset($this->workers[$k]);
            }
        }
    }

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
     * 删除子进程
     * @param $pid
     */
    protected function removeWorker($pid)
    {
        foreach ($this->workers as $k => $v) {
            if ($pid == $v) {
                unset($this->workers[$k]);
            }
        }
    }

    /**
     * 停止子进程(给子进程发送停止信号SIGTERM)
     * @param int $workerPid 子进程pid，为0则为所有子进程
     * @return bool
     */
    protected function stopWorkers($workerPid = 0)
    {
        if ($workerPid == 0) {
            $isStop = true;
            foreach ($this->workers as $k => $v) {
                if ($res = posix_kill($v, SIGTERM)) {
                    unset($this->workers[$k]);
                } else {
                    // log
                    // kill child $v faild
                    $isStop = false;
                }
            }
            return $isStop;
        } else {
            return posix_kill($workerPid, SIGTERM);
        }
    }
    ############################## 子进程操作 ###############################


    ############################## 当前进程操作 ###############################
    /**
     * 根据当前子进程数，检查并fork出worker进程
     * @throws \Exception
     */
    protected function fork()
    {
        // 当前进程在运行状态
        if ($this->status == self::STATUS_RUN) {
            // 已经检查，当前不需要再次检查
            $this->isCheckWorker = false;
            // 设置下次轮询的闹钟
            pcntl_alarm($this->checkWorkerInterval);
            // 循环开启子进程
            while ($this->isAddWorker()) {
                $workerPid = pcntl_fork();  // fork出子进程
                if ($workerPid > 0) {
                    // 该分支为父进程
                    $this->addWorker($workerPid);
                } else if ($workerPid == 0) {
                    try {
                        // 该分支为子进程

                        //设置默认文件权限
                        umask(022);
                        //将当前工作目录更改为根目录
                        chdir('/');
                        //关闭文件描述符
                        fclose(STDIN);
                        fclose(STDOUT);
                        fclose(STDERR);
                        //重定向输入输出
                        global $STDOUT, $STDERR;
                        $STDOUT = fopen('/dev/null', 'a');
                        $STDERR = fopen('/dev/null', 'a');

                        $worker = Worker::Create();
                        $worker->setConfig($this->config)->setWork($this->closure)->run();
                    } catch (\Exception $e){
                        Log::error($e->getTraceAsString());
                    } finally {
                        exit();
                    }
                } else {
                    // fork失败
                    throw new ProcessException('fork process error');
                }
            }
        }
    }

    /**
     * 停止当前进程
     */
    protected function stop()
    {
        // 停止所有子进程
        $this->stopWorkers();
        // 设置状态
        $this->status = self::STATUS_STOPPED;
        parent::stop();
    }

    /**
     * 重启当前进程
     */
    protected function restart()
    {
        // 停止所有子进程
        $this->stopWorkers();
        // 睡眠5s
        sleep(5);
        // 设置成运行状态
        $this->status = self::STATUS_RUN;
        // 触发检测子进程机制
        posix_kill($this->pid, SIGALRM);
        parent::restart();
    }
    ############################## 当前进程操作 ###############################


    /**
     * 工作开始
     * @return void
     * @throws \Exception
     */
    protected function runHandler()
    {
        echo $this->title . ":" .$this->pid."\n";

        posix_kill($this->pid, SIGALRM);
        while (true) {
            // 调用信号处理程序
            pcntl_signal_dispatch();

            // 是否需要检测子进程
            if ($this->isCheckWorker) {
                $this->fork();
            }

            // 子进程退出或者有信号过来，则返回，否则阻塞
            $workerPid = pcntl_wait($status, WUNTRACED);//不阻塞
            if ($workerPid > 0) {
                $this->removeWorker($workerPid);
            }

            // 调用信号处理程序
            pcntl_signal_dispatch();

            // 是否需要重启
            if ($this->isExpectRestart()) {
                $this->restart();
            }

            // 是否需要停止
            if ($this->isExpectStop()) {
                $this->stop();
            }
        }
    }

    ########################## 信号处理程序 ##############################
    /**
     * 添加信号
     */
    protected function setSignal()
    {
        // 1、闹钟信号(检测子进程,进程数不足则启动子进程)
        pcntl_signal(SIGALRM, [$this, 'checkHandler'], false);

        // 2、重启信号
        pcntl_signal(SIGUSR1, [$this, 'restartHandler'], false);

        // 3、停止信号
        pcntl_signal(SIGUSR2, [$this, 'stopHandler'], false);
        // SIGTERM 程序结束(terminate、信号, 与SIGKILL不同的是该信号可以被阻塞和处理.
        // 通常用来要求程序自己正常退出. shell命令kill缺省产生这个信号.
        pcntl_signal(SIGTERM, [$this, 'stopHandler'], false);
        // 程序终止(interrupt、信号, 在用户键入INTR字符(通常是Ctrl-C、时发出
        pcntl_signal(SIGINT, [$this, 'stopHandler'], false);
    }


    /**
     * 闹钟信号处理程序(设置当前需要检测子进程数)
     */
    protected function checkHandler()
    {
        $this->isCheckWorker = true;
    }

    /**
     * restart信号
     */
    protected function restartHandler()
    {
        // 设置当前进程为需要重启状态
        $this->setRestart();
    }

    /**
     * stop信号
     */
    protected function stopHandler()
    {
        // 设置当前进程为需要停止状态
        $this->setStop();
    }
    ########################## 信号处理程序 ##############################



}