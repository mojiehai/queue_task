<?php

namespace QueueTask\Daemon;

use ProcessManage\Command\Command;
use ProcessManage\Exception\Exception;
use QueueTask\Daemon\Command\MultipleWork\DaemonMultipleQueueTemplate;
use QueueTask\Daemon\Command\MultipleWork\MultipleWork;


/**
 * 守护进程
 * Class MultipleWorkDaemon
 * @package QueueTask\Daemon
 */
class MultipleWorkDaemon
{

    /**
     * @var MultipleWorkDaemon
     */
    protected static $instance = null;

    /**
     * @var MultipleWork
     */
    protected $multipleWork = null;

    /**
     * Daemon constructor.
     */
    protected function __construct()
    {
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
     */
    public static function getInstance()
    {
        if (!(static::$instance instanceof static)) {
            static::$instance = new MultipleWorkDaemon();
        }
        return static::$instance;
    }

    /**
     * 设置工作集合
     * @param MultipleWork $multipleWork
     * @return $this
     */
    public function setMultipleWork(MultipleWork $multipleWork)
    {
        $this->multipleWork = $multipleWork;
        return $this;
    }

    /**
     * 获取工作集合
     * @return MultipleWork
     */
    public function getMultipleWork()
    {
        return $this->multipleWork;
    }

    /**
     * 监听命令
     */
    public function listenCommand()
    {
        (new Command(new DaemonMultipleQueueTemplate()))->run();
    }

}