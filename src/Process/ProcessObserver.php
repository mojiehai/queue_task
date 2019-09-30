<?php


namespace QueueTask\Process;

use Swoole\Process;

/**
 * 进程观察者
 * Class ProcessObserver
 * @package QueueTask\Process
 */
class ProcessObserver
{
    /**
     * 父进程pid
     * @var int
     */
    protected $parentPid = 0;

    /**
     * @var Process
     */
    public $process = null;

    /**
     * 是否需要停止
     * @var bool
     */
    public $isStop = false;

    /**
     * ProcessObserver constructor.
     * @param Process $process
     * @param $parentPid
     */
    public function __construct(Process $process, $parentPid)
    {
        $this->process = $process;
        $this->parentPid = $parentPid;
    }

    /**
     * 注册信号
     */
    public function registerSignal()
    {
        pcntl_signal(SIGTERM, [$this, 'signalCall'], false);
    }

    /**
     * 进程检测
     * @return void
     */
    public function check()
    {
        // 判断父进程是否存在
        if (!Process::kill($this->parentPid, 0)) {
            $this->isStop = true;
            return;
        }

        // 监听信号,执行回调
        pcntl_signal_dispatch();
    }

    /**
     * 上报进程停止信息给主进程
     */
    public function reportStopInfo()
    {
        $this->process->write('exit');
    }

    /**
     * 信号回调
     * @param $signal
     */
    public function signalCall($signal)
    {
        switch ($signal) {
            case SIGTERM:
                $this->isStop = true;
                break;
        }
    }

}