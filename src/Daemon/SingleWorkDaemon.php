<?php

namespace QueueTask\Daemon;

use ProcessManage\Command\Command;
use ProcessManage\Exception\Exception;
use QueueTask\Daemon\Command\SingleWork\DaemonSingleQueueTemplate;
use QueueTask\Daemon\Command\SingleWork\SingleWork;


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
     * @var SingleWork
     */
    protected $singleWork = null;

    /**
     * Daemon constructor.
     * @param SingleWork $singleWork 单工作类
     */
    protected function __construct(SingleWork $singleWork)
    {
        $this->setSingleWork($singleWork);
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
     * @param SingleWork $singleWork 单工作类
     * @return SingleWorkDaemon
     * @throws Exception
     */
    public static function getInstance(SingleWork $singleWork = null)
    {
        if (!(static::$instance instanceof static)) {
            if (!empty($singleWork)) {
                static::$instance = new SingleWorkDaemon($singleWork);
            } else {
                throw new Exception('There is no work');
            }
        } else {
            if (!empty($singleWork)) {
                static::$instance->setSingleWork($singleWork);
            }
        }
        return static::$instance;
    }

    /**
     * @param SingleWork $singleWork
     */
    private function setSingleWork(SingleWork $singleWork)
    {
        $this->singleWork = $singleWork;
    }

    /**
     * @return SingleWork
     */
    public function getSingleWork()
    {
        return $this->singleWork;
    }

    /**
     * 监听命令
     */
    public function listenCommand()
    {
        (new Command(new DaemonSingleQueueTemplate()))->run();
    }

}