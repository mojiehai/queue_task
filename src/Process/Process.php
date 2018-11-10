<?php

namespace QueueTask\Process;

use QueueTask\Helpers\Log;

/**
 * 进程抽象类
 * Class Process
 * @package QueueTask\src\Process
 */
abstract class Process
{

    /**
     * worker状态
     */
    const STATUS_PREPARE = 0;       // 准备
    const STATUS_RUN = 1;           // 运行
    const STATUS_SET_RESTART = 2;   // 需要重启
    const STATUS_SET_STOP = 3;      // 需要停止
    const STATUS_STOPPED = 4;       // 已经停止

    /**
     * 进程id
     *
     * @var int
     */
    public $pid = '';

    /**
     * 进程名称
     * @var string
     */
    public $title = '';

    /**
     * 进程名称前缀
     * @var string
     */
    public static $TITLE_PREFIX = 'queue_task:';

    /**
     * 进程名称后缀
     * @var string
     */
    protected $titleSuffix = '';

    /**
     * 进程的实际工作内容
     * @var \Closure
     */
    protected $closure = null;

    /**
     * worker当前状态
     * @var int
     */
    protected $status = self::STATUS_PREPARE;

    /**
     * 允许配置的变量名
     * @var array
     */
    protected $configNameList = [];

    /**
     * 允许配置的基础变量名
     * @var array
     */
    protected $baseConfigNameList = [];

    /**
     * 允许配置的基础静态变量名
     * @var array
     */
    protected $baseStaticConfigNameList = ['TITLE_PREFIX'];

    /**
     * 原始配置数组
     * @var array
     */
    protected $config = [];

    /**
     * Process constructor.
     * @param int $pid  进程pid
     */
    protected function __construct($pid = 0)
    {
        $this->status = self::STATUS_PREPARE;
        if (empty($pid)) {
            // 获取当前进程pid
            $this->pid = posix_getpid();
        } else {
            $this->pid = $pid;
        }
    }

    /**
     * 初始化进程数据
     */
    protected function init()
    {
        // 设置进程名称
        $this->setProcessTitle();
    }

    /**
     * 设置当前进程名称  queue_task:ClassName:suffix
     */
    protected function setProcessTitle()
    {
        $className = get_class($this);
        $classNameInfoArr = explode('\\', $className);
        $className = end($classNameInfoArr);
        $this->title = static::$TITLE_PREFIX . $className . $this->titleSuffix;
        cli_set_process_title($this->title);
    }

    /**
     * 实例化进程类
     * @param int $pid 进程pid
     * @return static
     */
    public static function Create($pid = 0)
    {
         $process = new static($pid);
         $process->init();
         Log::info('create ok !');
         return $process;
    }

    /**
     * 加载配置
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        $configList = array_merge($this->baseConfigNameList, $this->configNameList);
        $staticConfigList = $this->baseStaticConfigNameList;
        foreach ($config as $k => $v) {
            if (in_array($k, $configList)) {
                if (!is_null($v)) {
                    $this->$k = $v;
                }
            }
            if (in_array($k, $staticConfigList)) {
                if (!is_null($v)) {
                    static::$$k = $v;
                }
            }
        }
        return $this;
    }

    /**
     * 设置进程的工作内容
     * @param \Closure $closure
     * @return $this
     */
    public function setWork(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closure = $closure;
        }
        return $this;
    }

    ###################### 进程状态 #######################
    /**
     * 设置进程需要停止
     */
    public function setStop()
    {
        Log::info('stopping ... ');
        $this->status = self::STATUS_SET_STOP;
    }

    /**
     * 判断进程是否准备停止
     * @return bool
     */
    public function isExpectStop()
    {
        return $this->status == self::STATUS_SET_STOP;
    }

    /**
     * 停止当前进程
     */
    protected function stop()
    {
        Log::info('stopped !!!');
        exit();
    }

    /**
     * 设置进程重新启动
     */
    public function setRestart()
    {
        Log::info('restarting ... ');
        $this->status = self::STATUS_SET_RESTART;
    }

    /**
     * 判断进程是否准备重启
     * @return bool
     */
    public function isExpectRestart()
    {
        return $this->status == self::STATUS_SET_RESTART;
    }

    /**
     * 重启当前进程
     */
    protected function restart()
    {
        Log::info('restarted');
    }
    ###################### 进程状态 #######################

    /**
     * 进程start
     * @return void
     */
    public final function run()
    {
        // config 配置初始化
        $this->configure();
        // 设置添加信号处理
        $this->setSignal();
        // 设置运行状态
        $this->status = self::STATUS_RUN;
        Log::info('running ... ');
        // 工作开始
        $this->runHandler();
    }

    /**
     * 配置初始化
     */
    protected function configure()
    {
    }

    /**
     * 添加信号处理机制
     */
    protected function setSignal()
    {
    }

    /**
     * 工作开始
     */
    abstract protected function runHandler();


    /**
     * 检测进程是否存在
     * @param $pid
     * @return bool
     */
    public static function isAlive($pid)
    {
        if (empty($pid)) {
            return false;
        } else {
            return posix_kill($pid, 0);
        }
    }
}