<?php


namespace QueueTask\Process;

use QueueTask\Helpers\Log;
use Swoole\Process;

/**
 * 管理进程
 * Class ManageProcess
 * @package QueueTask\Process
 */
class ManageProcess
{
    /**
     * @var Pid
     */
    public $pidManage = null;

    /**
     * 进程池
     * @var WorkerProcess[]
     */
    protected $workerProcess = [];

    /**
     * 是否退出
     * @var bool
     */
    protected $isStop = false;

    /**
     * 是否检查worker进程，并拉起
     * @var bool
     */
    protected $isCheck = false;

    /**
     * 闹钟信号10s间隔
     * @var int
     */
    protected $alarmInterval = 10;

    /**
     * ManageProcess constructor.
     * @param string $uniqueTag
     */
    public function __construct($uniqueTag)
    {
        $this->pidManage = new Pid($uniqueTag);
    }

    /**
     * 设置进程任务
     * @param array $workerConfig
     * @param int $processNum
     */
    public function addWorker(array $workerConfig, int $processNum)
    {
        for ($i = 0; $i < $processNum; $i ++) {
            $this->workerProcess[] = new WorkerProcess($workerConfig);
        }
    }

    /**
     * start
     */
    public function start()
    {
        // 守护进程
        Process::daemon();

        // 重设pid
        if (!$this->resetPid()) {
            return;
        }

        // 设置进程名称
        @swoole_set_process_name('queue_task_'.$this->pidManage->uniqueTag);

        // 设置信号
        $this->registerSignal();

        // 检查worker是否存在(启动子进程)
        $this->isCheck = true;

        while (true) {

            // 阻塞等待子进程退出，并唤醒
            $this->waitProcess();

            // 读取信号/信息
            $this->readSignal();

            // 是否检查worker,并拉起已经死掉的进程
            if ($this->isCheck) {
                $this->isCheck = false;
                $this->checkCreateProcess();
                // 设置下次闹钟
                pcntl_alarm($this->alarmInterval);
            }

            if ($this->isStop) {
                Log::info('manage process stopping ...');
                $this->waitStop();
                Log::info('manage process exit,pid: ' . $this->pidManage->get());
                break;
            }

        }
    }

    /**
     * 重设pid
     * @return bool
     */
    protected function resetPid()
    {
        // 检查pid是否存在
        $pid = $this->pidManage->get();

        if ($this->pidManage->isAlive()) {
            // master还活着
            Log::error('the process already exists, pid: '. $pid);
            return false;
        } else {
            $this->pidManage->set(posix_getpid());
            return true;
        }

    }

    /**
     * 注册信号
     */
    protected function registerSignal()
    {
        // 添加15信号
        pcntl_signal(SIGTERM, [$this, 'signalCall'], false);
        // 添加闹钟信号，检查拉取停止了的worker进程
        pcntl_signal(SIGALRM, [$this, 'signalCall'], false);
    }

    /**
     * 检查创建子进程
     */
    protected function checkCreateProcess()
    {
        $pid = $this->pidManage->get();
        foreach ($this->workerProcess as $workerProcess) {
            if (!($workerProcess->isAlive())) {
                $workerProcess->start($pid, $this->pidManage->uniqueTag);
            }
        }
    }

    /**
     * 读取信号
     */
    protected function readSignal()
    {
        pcntl_signal_dispatch();
    }

    /**
     * 阻塞等待子进程退出并唤醒或者监听其他信号
     */
    protected function waitProcess()
    {
        $pid = pcntl_wait($status, WUNTRACED);
        if ($pid > 0) {
            // 子进程退出信号，调用子进程管理器清理退出的进程
            foreach ($this->workerProcess as $workerProcess) {
                if ($pid == $workerProcess->pid) {
                    // 读取信息
                    $workerProcess->read();
                    // 检查是否正常退出
                    if ($workerProcess->isStop) {
                        Log::info('process exit, pid: ' . $pid);
                        // 正常退出，重启
                        $workerProcess->start($this->pidManage->get(), $this->pidManage->uniqueTag);
                    } else {
                        // 非正常退出，警报
                        Log::error('abnormal exit of process, pid: '. $pid, $workerProcess->workerConfig);
                    }
                }
            }
        }
    }

    /**
     * 发送停止信号给子进程，并且等待停止
     */
    protected function waitStop()
    {
        // 发送停止信号
        foreach ($this->workerProcess as $workerProcess) {
            if ($workerProcess->isAlive()) {
                Process::kill($workerProcess->pid, SIGTERM);
            }
        }

        // 阻塞等待子进程停止
        for ($i = 0; $i < count($this->workerProcess); $i ++) {
            $result = Process::wait(true);
            if ($result === false) {
                break;
            }
        }

        // 检查子进程是否存在
        foreach ($this->workerProcess as $workerProcess) {
            if ($workerProcess->isAlive()) {
                Log::error('kill process failed, pid: '. $workerProcess->pid);
            }
        }
    }


    /**
     * 停止
     */
    public function stop()
    {
        if ($this->pidManage->isAlive()) {
            Process::kill($this->pidManage->get(), SIGTERM);
        }
    }


    /**
     * 信号回调
     * @param $signal
     */
    public function signalCall($signal)
    {
        switch ($signal) {
            case SIGALRM:
                $this->isCheck = true;
                break;
            case SIGTERM:
                $this->isStop = true;
                break;
        }
    }

}