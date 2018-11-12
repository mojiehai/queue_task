<?php

namespace QueueTask\Process;
use QueueTask\Exception\ProcessException;
use QueueTask\Log\ProcessLog;

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
    protected $pidFileDir = '';
    /**
     * master进程pid文件的完整目录
     * @var string
     */
    protected $pidFilePath = '';
    /**
     * master进程pid文件的名称
     * @var string
     */
    protected $pidFileName = '';

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
    protected $configNameList = ['pidFileDir', 'checkWorkerInterval', 'maxWorkerNum'];

    /**
     * 加载配置
     */
    protected function configure()
    {
        // 初始化根目录(此值为默认值，支持通过配置修改)
        $this->pidFileDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'pid';
        parent::configure();

        // 初始化pid文件名
        $this->pidFileName = $this->title;
        // 初始化pid完整文件路径
        $this->pidFilePath = $this->pidFileDir.DIRECTORY_SEPARATOR.$this->pidFileName;
    }

    /**
     * 初始化进程数据
     * @throws ProcessException
     */
    protected function init()
    {
        // 检查master是否已经启动
        if (static::isMasterAlive($this)) {
            throw new ProcessException('process is already exists!');
        }
        parent::init();
        $this->savePidToFile();

        return $this;
    }

    ############################## pid file ###############################
    /**
     * 初始化pid文件
     * @throws ProcessException
     */
    protected function initPidFile()
    {
        if (empty($this->pidFileDir) || empty($this->pidFileName)) {
            throw new ProcessException("pid file configure error");
        }
        if (!is_dir($this->pidFileDir)) {
            if (!mkdir($this->pidFileDir, 0644, true)) {
                throw new ProcessException('create master pid directory failure');
            }
            chmod($this->pidFileDir, 0644);
        }
        if (!file_exists($this->pidFilePath)) {
            if (!touch($this->pidFilePath)) {
                throw new ProcessException('create master pid file failure');
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
        $this->initPidFile();
        $return = file_put_contents($this->pidFilePath, $this->pid);
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
    public function getPidByFile()
    {
        $this->initPidFile();
        return intval(file_get_contents($this->pidFilePath));
    }

    /**
     * 判断master进程是否存活
     * @param Master $master master进程对象
     * @return bool
     * @throws ProcessException
     */
    public static function isMasterAlive(Master $master)
    {
        return static::isAlive($master->getPidByFile());
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
                    // kill worker $v failed
                    ProcessLog::Record('error', $this, 'kill worker ('.$v.') failed');
                    $isStop = false;
                }
            }
            return $isStop;
        } else {
            return posix_kill($workerPid, SIGTERM);
        }
    }

    /**
     * 强行停止子进程(给子进程发送停止信号SIGKILL)
     * @param int $workerPid 子进程pid，为0则为所有子进程
     * @return bool
     */
    protected function forceStopWorkers($workerPid = 0)
    {
        if ($workerPid == 0) {
            $isStop = true;
            foreach ($this->workers as $k => $v) {
                if ($res = posix_kill($v, SIGKILL)) {
                    unset($this->workers[$k]);
                    ProcessLog::Record('warning', $this, 'force kill worker ('.$v.')');
                } else {
                    // kill worker $v failed
                    ProcessLog::Record('error', $this, 'kill -9 worker ('.$v.') failed');
                    $isStop = false;
                }
            }
            return $isStop;
        } else {
            return posix_kill($workerPid, SIGKILL);
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

                        // 启动子进程任务
                        (new Worker($this->config))->setWorkInit($this->closureInit)->setWork($this->closure)->run();
                    } catch (\Exception $e){
                        ProcessLog::Record('error', $this, $e->getTraceAsString());
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
        $isStop = false;
        // 检测10次 共5s
        for ($i = 1; $i <= 5; $i++) {
            // 睡眠1s，等待子进程安全退出
            sleep(1);
            // 检测子进程状态
            $this->checkWorkers();
            // 如果子进程全部退出完成
            if (empty($this->workers)) {
                $isStop = true;
                break;
            }
        }
        if (!$isStop) {
            // 强制退出还未退出的子进程
            $this->forceStopWorkers();
        }
        // 设置状态
        $this->status = self::STATUS_STOPPED;
        // 停止主进程
        parent::stop();
    }

    /**
     * 重启当前进程
     */
    protected function restart()
    {
        // 停止所有子进程
        $this->stopWorkers();
        $isStop = false;
        // 检测5次
        for ($i = 1; $i <= 5; $i++) {
            // 睡眠1s，等待子进程安全退出
            sleep(1);
            // 检测子进程状态
            $this->checkWorkers();
            // 如果子进程全部退出完成
            if (empty($this->workers)) {
                $isStop = true;
                break;
            }
        }
        if (!$isStop) {
            // 强制退出还未退出的子进程
            $this->forceStopWorkers();
        }
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