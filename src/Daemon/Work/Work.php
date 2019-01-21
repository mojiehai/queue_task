<?php

namespace QueueTask\Daemon\Work;

use ProcessManage\Process\Worker as ProcessWorker;
use QueueTask\Worker\Worker;
use QueueTask\Queue\Queue;

/**
 * 工作描述类
 * Interface Work
 * @package QueueTask\Daemon\Work
 */
class Work
{
    /**
     * 队列名称(多队列轮询情况，则为|分隔的多队列名称拼接的字符串)
     * @var string
     */
    public $queueName = '';

    /**
     * 队列配置信息
     * @var array
     */
    protected $queueConfig = [
        'queueName' => 'default', //队列名称(同时默认为进程的基础名称)(字符串或者数组)
        'attempt' => 10,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'sleep' => 3,       //每次检测的时间间隔
        'delay' => 0,       //失败后延迟的秒数重新入队列
    ];


    /**
     * 进程配置信息
     * @var array
     */
    protected $processConfig = [
        'baseTitle' => '',      // 进程名称

        // master 进程配置
        'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
        'maxWorkerNum' => 1,            //1个进程

        // worker 进程配置
        'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
        'limitSeconds' => 86400, // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制) (默认1天重启一次)
        'executeUSleep' => 0,   // 每次工作任务中间睡眠时长(微秒)，0微秒执行一次
    ];

    /**
     * Work constructor.
     * @param array $queueConfig 队列配置信息
     */
    public function __construct(array $queueConfig)
    {
        $this->setQueueConfig($queueConfig);
    }

    /**
     * 设置配置
     * @param $variable
     * @param array $config
     */
    protected function setConfig($variable, array $config = [])
    {
        foreach ($config as $k => $v) {
            $this->$variable[$k] = $v;
        }
    }

    /**
     * 设置队列配置
     * @param array $config
     * @return $this
     */
    protected function setQueueConfig(array $config = [])
    {
        $this->setConfig('queueConfig', $config);
        if (is_array($this->queueConfig['queueName'])) {
            $this->queueName = implode('|', $this->queueConfig['queueName']);
        } else {
            $this->queueName = $this->queueConfig['queueName'];
        }
        return $this;
    }

    /**
     * 设置进程配置
     * @param array $config
     * @return $this
     */
    public function setProcessConfig(array $config = [])
    {
        $this->setConfig('processConfig', $config);
        ##################### 不允许修改的值 #####################
        if (empty($this->processConfig['baseTitle'])) {
            // 如果为空，队列基础名称修改成队列名
            $this->processConfig['baseTitle'] = $this->queueName;
        }
        // 进程前缀
        $this->processConfig['titlePrefix'] = 'queue_task';
        ##################### 不允许修改的值 #####################
        return $this;
    }

    /**
     * 获取进程配置
     * @return array
     */
    public function getProcessConfig()
    {
        return $this->processConfig;
    }

    /**
     * 工作进程的初始化
     * @return \Closure
     */
    public function getWorkInit()
    {
        $config = $this->queueConfig;
        // 初始化队列消费者
        return function (ProcessWorker $process) use ($config) {
            return (new Worker(Queue::getInstance()))->setConfig($config);
        };
    }

    /**
     * 工作进程的运行
     * @return \Closure
     */
    public function getWork()
    {
        // 执行的工作内容
        return function(ProcessWorker $process, Worker $worker) {
            $worker->runOnce();
            // 如果任务需要退出
            if ($worker->isStop()) {
                // 则调用进程的退出操作
                $process->setStop();
            }
        };
    }

}