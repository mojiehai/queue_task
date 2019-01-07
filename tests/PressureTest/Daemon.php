<?php

use ProcessManage\Command\Command;
use ProcessManage\Process\Worker as ProcessWorker;
use QueueTask\Queue\Queue;
use Tests\TestHandler;

/**
 * 守护进程
 * Class Daemon
 * @package QueueTask\Daemon
 */
class Daemon
{

    /**
     * @var Daemon
     */
    protected static $instance = null;

    /**
     * 队列配置信息
     * @var array
     */
    protected $queueConfig = [
        'queueName' => 'default', //队列名称(同时为进程的基础名称)
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
        // master 进程配置
        'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
        'maxWorkerNum' => 2,            //2个进程

        // worker 进程配置
        'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
        'limitSeconds' => 0,    // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制)

        'executeUSleep' => 3000000, // 每次执行任务睡眠时间(微秒)
    ];

    /**
     * Daemon constructor.
     * @param array $config
     */
    protected function __construct(array $config = [])
    {
        $this->setQueueConfig($config);
    }


    /**
     * clone
     * @throws Exception
     */
    public function __clone()
    {
        throw new Exception("This class cannot be cloned" , -101);
    }

    /**
     * 单例
     * @param array $queueConfig 队列配置信息
     * @return Daemon
     */
    public static function getInstance(array $queueConfig = [])
    {
        if (!(static::$instance instanceof static)) {
            static::$instance = new Daemon($queueConfig);
        } else {
            static::$instance->setQueueConfig($queueConfig);
        }
        return static::$instance;
    }

    /**
     * 监听命令
     */
    public function listenCommand()
    {
        (new Command(new DaemonQueueTemplate()))->run();
    }

    /**
     * 设置队列配置
     * @param array $config
     * @return $this
     */
    public function setQueueConfig(array $config = [])
    {
        $this->setConfig('queueConfig', $config);
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
        // 队列基础名称修改成队列名
        $this->processConfig['baseTitle'] = $this->queueConfig['queueName'].'_push';
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
     * 设置配置
     * @param $variable
     * @param array $config
     */
    private function setConfig($variable, array $config = [])
    {
        foreach ($config as $k => $v) {
            $this->$variable[$k] = $v;
        }
    }

    /**
     * 工作进程的初始化
     * @return \Closure
     */
    final public function getWorkInit()
    {
        $config = $this->queueConfig;
        // 初始化队列消费者
        return function (ProcessWorker $process) use ($config) {
            return Queue::getInstance();
        };
    }

    /**
     * 工作进程的运行
     * @return \Closure
     */
    final public function getWork()
    {
        // 执行的工作内容
        return function(ProcessWorker $process, Queue $queue) {
            if (rand(0, 1) == 0) {
                $r = $queue->pushOn(new TestHandler(),'test',['test'=>'test'],'testQueue');
                $msg = 'push';
            } else {
                $r = $queue->laterOn(5, new TestHandler(),'test',['test'=>'test'],'testQueue');
                $msg = 'laterOn';
            }
            if ($r) {
                \QueueTask\Log\WorkLog::info($msg. ' ok');
            } else {
                \QueueTask\Log\WorkLog::error($msg. ' failed');
            }
        };
    }

}