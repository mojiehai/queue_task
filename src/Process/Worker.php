<?php

namespace QueueTask\Process;

use QueueTask\Exception\ProcessException;
use QueueTask\Helpers\Log;

/**
 * 工作进程
 * Class Worker
 * @package QueueTask\Process
 */
class Worker extends Process
{
    /**
     * 工作进程最大执行时长 单位：秒 0为不限制
     * @var int
     */
    protected $limitSeconds = 0;

    /**
     * 工作进程最大工作次数(即工作回调最大回调次数) 0为无限循环执行
     * @var int
     */
    protected $executeTimes = 1;

    /**
     * 工作进程每次执行后睡眠时间 单位：微秒数  0为不睡眠
     * @var int
     */
    protected $executeUSleep = 200000;  // 0.2s



    /**
     * 工作进程当前执行次数
     * @var int
     */
    protected $currentExecuteTimes = 0;

    /**
     * 预计退出工作的时间戳 0为不退出
     * @var int
     */
    protected $preExitTime = 0;

    /**
     * 允许配置的变量
     * @var array
     */
    protected $configNameList = ['executeTimes', 'executeUSleep', 'limitSeconds'];

    /**
     * Worker constructor.
     * @param int $pid
     */
    protected function __construct($pid = 0)
    {
        parent::__construct($pid);
    }

    /**
     * 配置初始化
     */
    protected function configure()
    {
        // 如果设置了最大执行时长，则初始化预计退出时间
        if ($this->limitSeconds > 0) {
            $this->preExitTime = time() + $this->limitSeconds;
        }
    }


    /**
     * 工作开始
     * @throws ProcessException
     */
    protected function runHandler()
    {
        while (true) {
            $this->execute();

            // 如果设置了预计退出时间，则检测是否需要退出
            if ($this->preExitTime > 0) {
                if (time() >= $this->preExitTime) {
                    $this->setWorkStop();
                }
            }

            // 如果限定了工作进程最大工作次数,则判断是否超出最大工作次数
            if ($this->executeTimes > 0) {
                if ($this->currentExecuteTimes >= $this->executeTimes) {
                    $this->setWorkStop();
                }
            }

            // 检测信号
            pcntl_signal_dispatch();

            // 检测主进程是否存在，不存在则退出自己进程(补救操作)
            if (
                (!$this->isWorkExpectStop()) &&
                (!static::isAlive(Master::getPidByFile()))
            ) {
                //$this->setWorkStop();
            }

            // 检测是否退出进程
            if ($this->isWorkExpectStop()) {
                Log::info('run stop');
                exit();
            }

            // 睡眠
            if ($this->executeUSleep > 0) {
                usleep($this->executeUSleep);
            }
        }
    }

    /**
     * 执行工作回调
     */
    protected function execute()
    {
        if (is_callable($this->closure)) {
            $closure = $this->closure;
            $closure($this);
            $this->currentExecuteTimes++;
        } else {
            $this->setWorkStop();
        }
    }

    /**
     * 获取当前执行次数
     * @return int
     */
    public function getExecuteTimes()
    {
        return $this->currentExecuteTimes;
    }

    /**
     * 添加信号
     */
    protected function setSignal()
    {
        // 1、停止信号
        // SIGTERM 程序结束(terminate、信号, 与SIGKILL不同的是该信号可以被阻塞和处理.
        // 通常用来要求程序自己正常退出. shell命令kill缺省产生这个信号.
        pcntl_signal(SIGTERM, [$this, 'stopHandler'], false);
        // 程序终止(interrupt、信号, 在用户键入INTR字符(通常是Ctrl-C、时发出
        pcntl_signal(SIGINT, [$this, 'stopHandler'], false);
        pcntl_signal(SIGUSR2, [$this, 'stopHandler'], false);

        Log::info('setSignal worker');
    }

    /**
     * 停止信号处理程序
     */
    protected function stopHandler()
    {
        Log::info('receive stop');
        $this->setWorkStop();
    }

}