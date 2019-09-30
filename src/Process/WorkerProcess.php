<?php


namespace QueueTask\Process;

use QueueTask\Helpers\Log;
use QueueTask\Worker;
use Swoole\Process;

/**
 * 工作进程
 * Class WorkerProcess
 * @package QueueTask\Process
 */
class WorkerProcess
{

    /**
     * pid
     * @var int
     */
    public $pid = 0;

    /**
     * @var Process
     */
    public $process = null;

    /**
     * 是否停止
     * @var bool
     */
    public $isStop = false;

    /**
     * workerConfig
     * @var array
     */
    public $workerConfig = [];

    public function __construct(array $workerConfig)
    {
        $this->workerConfig = $workerConfig;
    }

    /**
     * start
     * @param int $parentPid 父进程pid
     * @param string $uniqueTag 父进程的唯一标识
     * @return int
     */
    public function start($parentPid, $uniqueTag)
    {
        $workerConfig = $this->workerConfig;
        $this->process = new Process(function (Process $process) use ($workerConfig, $parentPid, $uniqueTag) {
            // 子进程

            // 初始化worker
            $worker = new Worker($workerConfig);

            // 设置进程名称
            @swoole_set_process_name('queue_task_'.$uniqueTag.':'.$worker->queueName);

            // 初始化进程观察者
            $observer = new ProcessObserver($process, $parentPid);

            // 注册信号
            $observer->registerSignal();

            // 绑定观察者
            $worker->bindProcessObserver($observer);

            // 启动worker
            $worker->listen();

        }, false, SOCK_DGRAM);

        // 设置管道通讯非阻塞模式
        $this->process->setBlocking(false);

        $this->isStop = false;

        $this->pid = $this->process->start();

        Log::info('process start, pid: ' . $this->pid);

        return $this->pid;
    }

    /**
     * 读取命令
     */
    public function read()
    {
        $command = @$this->process->read();
        switch ($command) {
            case 'exit':
                $this->isStop = true;
                break;
        }
    }

    /**
     * 进程是否存活
     * @return bool
     */
    public function isAlive()
    {
        if ($this->pid != 0 && Process::kill($this->pid, 0)) {
            return true;
        } else {
            return false;
        }
    }

}