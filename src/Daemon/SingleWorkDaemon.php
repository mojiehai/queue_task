<?php

namespace QueueTask\Daemon;

use ProcessManage\Command\Command;
use ProcessManage\Exception\Exception;
use QueueTask\Daemon\Command\SingleWork\DaemonSingleQueueTemplate;
use QueueTask\Daemon\Work\Work;


/**
 * 守护进程
 * Class SingleWorkDaemon
 * @package QueueTask\Daemon
 */
class SingleWorkDaemon
{

    /**
     * @var SingleWorkDaemon
     */
    protected static $instance = null;

    /**
     * @var Work
     */
    protected $work = null;

    /**
     * Daemon constructor.
     * @param Work $work 工作描述类
     */
    protected function __construct(Work $work)
    {
        $this->setWork($work);
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
     * @param Work $work 工作描述类
     * @return SingleWorkDaemon
     * @throws Exception
     */
    public static function getInstance(Work $work = null)
    {
        if (!(static::$instance instanceof static)) {
            if (!empty($work)) {
                static::$instance = new SingleWorkDaemon($work);
            } else {
                throw new Exception('There is no work');
            }
        } else {
            if (!empty($work)) {
                static::$instance->setWork($work);
            }
        }
        return static::$instance;
    }

    /**
     * 设置work
     * @param Work $work
     */
    private function setWork(Work $work) {
        $this->work = $work;
    }

    /**
     * 获取work
     * @return Work
     */
    public function getWork()
    {
        return $this->work;
    }

    /**
     * 监听命令
     */
    public function listenCommand()
    {
        (new Command(new DaemonSingleQueueTemplate()))->run();
    }

}