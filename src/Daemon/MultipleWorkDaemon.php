<?php

namespace QueueTask\Daemon;

use ProcessManage\Command\Command;
use ProcessManage\Exception\Exception;
use QueueTask\Daemon\Command\MultipleWork\DaemonMultipleQueueTemplate;
use QueueTask\Daemon\Work\Work;


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
     * 工作列表
     * @var Work[]
     */
    protected $works = [];

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
     * 添加work
     * @param Work $work
     * @return $this
     */
    public function addWork(Work $work) {
        $this->works[$work->queueName] = $work;
        return $this;
    }

    /**
     * 获取所有work
     * @return Work[]
     */
    public function getWorks()
    {
        return $this->works;
    }

    /**
     * 获取单个work
     * @param $queueName
     * @return Work|null
     */
    public function getWork($queueName)
    {
        if (isset($this->works[$queueName])) {
            return $this->works[$queueName];
        } else {
            return null;
        }
    }

    /**
     * 监听命令
     * @throws Exception
     */
    public function listenCommand()
    {
        if (!empty($this->works)) {
            (new Command(new DaemonMultipleQueueTemplate()))->run();
        } else {
            throw new Exception('There is no work');
        }
    }

}