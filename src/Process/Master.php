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

    /**
     * 停止子进程(给子进程发送停止信号SIGTERM)
     * @param int $workerPid 子进程pid，为0则为所有子进程
     * @return bool
     */
    protected function stopWorkers($workerPid = 0)
    {
        var_dump($this->workers);
        if ($workerPid == 0) {
            $isStop = true;
            foreach ($this->workers as $k => $v) {
                //if ($res = posix_kill($v, SIGTERM)) {
                //if ($res = posix_kill($v, SIGKILL)) {
                if ($res = posix_kill($v, SIGUSR2)) {
                    Log::info('master kill '.$v.' , return '.var_export($res, true));
                    pcntl_signal_dispatch();
                    unset($this->workers[$k]);
                } else {
                    // log
                    // kill child $v faild
                    $isStop = false;
                }
            }
            var_dump($this->workers, $isStop);
            return $isStop;
        } else {
            return posix_kill($workerPid, SIGTERM);
        }
    }
    ############################## 子进程操作 ###############################



    /**
     * 工作开始
     * @return void
     */
    protected function runHandler()
    {
        echo $this->title . ":" .$this->pid."\n";

        posix_kill($this->pid, SIGALRM);
        while (true) {
            echo 'aaa'.PHP_EOL;
            pcntl_signal_dispatch();
            echo 'bbb'.PHP_EOL;
            pcntl_wait($status, WUNTRACED);//不阻塞
            //pcntl_waitpid($this->pid, $status, WNOHANG);//不阻塞
            echo 'ccc'.PHP_EOL;
            if ($this->isWorkExpectStop()) {
                echo 'exit';
                exit();
            } else {
                echo 'status: '.$this->status.PHP_EOL;
            }
            //sleep(1);
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
        Log::info('setSignal');
        var_dump(\pcntl_signal_get_handler(SIGUSR2));die;
    }


    /**
     * 闹钟信号处理程序(检测子进程数)
     * @throws ProcessException
     */
    protected function checkHandler()
    {
        if ($this->status == self::STATUS_RUN) {
            pcntl_alarm($this->checkWorkerInterval);    // 设置下次轮询的闹钟
            // 循环开启子进程
            while ($this->isAddWorker()) {
                $workerPid = pcntl_fork();  // 开启子进程
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
     * restart信号
     */
    protected function restartHandler()
    {
        // 停止所有子进程
        $this->stopWorkers();
        // 5s后重启启动子进程
        sleep(5);
        // 启动检测子进程机制，重启子进程
        posix_kill($this->pid, SIGALRM);
    }

    /**
     * stop信号
     */
    protected function stopHandler()
    {
        echo 'stopMaster'.PHP_EOL;
        // 停止所有子进程
        $this->stopWorkers();
        // 停止当前进程
        $this->setWorkStop();
    }
    ########################## 信号处理程序 ##############################



}