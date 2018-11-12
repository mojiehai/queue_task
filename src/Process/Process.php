<?php

namespace QueueTask\Process;

use QueueTask\Exception\ProcessException;
use QueueTask\Log\ProcessLog;

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
    const STATUS_INIT = 1;          // 初始化
    const STATUS_RUN = 2;           // 运行
    const STATUS_SET_RESTART = 3;   // 需要重启
    const STATUS_SET_STOP = 4;      // 需要停止
    const STATUS_STOPPED = 5;       // 已经停止


    /**
     * 进程前缀、进程类型、进程基础名称的分隔符
     * @var string
     */
    const TITLE_DELIMITER = ':';

    /**
     * 进程id
     *
     * @var int
     */
    public $pid = '';

    /**
     * 进程名称 (前缀、类型、进程基础名称组成)
     * @var string
     */
    public $title = '';

    /**
     * 进程名称前缀
     * @var string
     */
    protected $titlePrefix = 'queue_task';

    /**
     * 进程基础名称(用来区分多个多进程任务)
     * @var string
     */
    protected $baseTitle = 'process';

    /**
     * 进程实际工作内容的初始化
     * @var \Closure
     */
    protected $closureInit = null;

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
    protected $baseConfigNameList = ['titlePrefix', 'baseTitle'];

    /**
     * 原始配置数组
     * @var array
     */
    protected $config = [];

    /**
     * Process constructor.
     * @param array $config
     * @param int $pid  进程pid
     */
    public function __construct(array $config = [], $pid = 0)
    {
        $this->status = self::STATUS_PREPARE;
        if (empty($pid)) {
            // 获取当前进程pid
            $this->pid = posix_getpid();
        } else {
            $this->pid = $pid;
        }
        $this->config = $config;
        // 加载配置
        $this->configure();
    }

    /**
     * 加载配置
     */
    protected function configure()
    {
        $configList = array_merge($this->baseConfigNameList, $this->configNameList);
        foreach ($this->config as $k => $v) {
            if (in_array($k, $configList)) {
                if (!is_null($v)) {
                    $this->$k = $v;
                }
            }
        }

        // 生成title
        $className = get_class($this);
        $classNameInfoArr = explode('\\', $className);
        $className = end($classNameInfoArr);
        $titleArr = [$this->titlePrefix, $className, $this->baseTitle];
        $this->title = implode(self::TITLE_DELIMITER, $titleArr);
    }

    /**
     * 初始化进程数据
     * @return $this
     */
    protected function init()
    {
        $this->status = self::STATUS_INIT;
        // 设置进程名称
        $this->setProcessTitle();
        ProcessLog::Record('info', $this, 'init ok !');
        return $this;
    }

    /**
     * 设置当前进程名称
     */
    protected function setProcessTitle()
    {
        cli_set_process_title($this->title);
    }

    /**
     * 设置进程的工作初始化
     * @param \Closure $closure
     * @return $this
     */
    public function setWorkInit(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closureInit = $closure;
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
        ProcessLog::Record('info', $this, 'stopping ... ');
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
        ProcessLog::Record('info', $this, 'stopped !!!');
        exit();
    }

    /**
     * 设置进程重新启动
     */
    public function setRestart()
    {
        ProcessLog::Record('info', $this, 'restarting ... ');
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
        ProcessLog::Record('info', $this, 'restarted');
    }
    ###################### 进程状态 #######################

    /**
     * 进程start
     * @return void
     * @throws ProcessException
     */
    public final function run()
    {
        $this->init();
        // 需要初始化才能运行
        if ($this->status == self::STATUS_INIT) {
            if (!is_callable($this->closure)) {
                // 如果没有工作任务，则退出进程
                $this->stop();
            }
            // 设置添加信号处理
            $this->setSignal();
            // 设置运行状态
            $this->status = self::STATUS_RUN;
            ProcessLog::Record('info', $this, 'running ... ');
            // 工作开始
            $this->runHandler();
        } else {
            throw new ProcessException('run after initialization');
        }
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