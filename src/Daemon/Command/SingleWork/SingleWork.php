<?php

namespace QueueTask\Daemon\Command\SingleWork;

use QueueTask\Daemon\Work\Work;
use ProcessManage\Exception\Exception;
use ProcessManage\Process\Manage;

/**
 * 单任务类
 * Class SingleWork
 * @package QueueTask\Daemon\Command\SingleWork
 */
class SingleWork
{

    /**
     * 是否后台运行
     * @var bool
     */
    public $background = false;

    /**
     * @var Work
     */
    protected $work = null;

    /**
     * SingleWork constructor.
     * @param Work $work
     */
    public function __construct(Work $work)
    {
        $this->work = $work;
    }

    /**
     * 检测命令是否可用
     * @return bool
     * @throws Exception
     */
    public function checkCommand()
    {
        // 如果工作为空，则退出
        if (empty($this->work)) {
            throw new Exception('there is no queue configured to run');
        }
        return true;
    }

    ############################# command ###############################
    /**
     * 命令：start
     * @throws Exception
     */
    public function commandStart()
    {
        $this->checkCommand();

        $manage = new Manage($this->work->getProcessConfig());

        $manage->setWorkInit($this->work->getWorkInit())->setWork($this->work->getWork());

        if ($this->background) {
            // 后台运行
            $manage->setBackground();
        }
        $manage->start();

    }

    /**
     * 命令：stop
     * @throws Exception
     */
    public function commandStop()
    {
        $this->checkCommand();

        (new Manage($this->work->getProcessConfig()))->stop();
    }

    /**
     * 命令：restart
     * @throws Exception
     */
    public function commandRestart()
    {
        $this->checkCommand();

        (new Manage($this->work->getProcessConfig()))
            ->setWorkInit($this->work->getWorkInit())       // 设置初始化
            ->setWork($this->work->getWork())               // 设置任务
            ->setBackground()                           // 后台执行
            ->restart();                                // restart

    }

    /**
     * 命令： wakeup
     * @throws Exception
     */
    public function commandWakeup()
    {
        $this->checkCommand();

        (new Manage($this->work->getProcessConfig()))->wakeup();

    }


    /**
     * 命令： status
     * @param bool $isReturn 是否返回数组(不返回数组则直接显示)
     * @return array|null
     * @throws Exception
     */
    public function commandStatus($isReturn = false)
    {
        $this->checkCommand();

        $status = (new Manage($this->work->getProcessConfig()))->status();

        $queueConfig = ['QueueConfig' => []];

        $status = $this->work->formatQueueStatus($status);
        $queueConfig['QueueConfig'] = $this->work->getQueueConfig();
        $status = array_merge($queueConfig, $status);

        if ($isReturn) {
            return $status;
        } else {
            // 格式化显示
            echo "########################################### status ###########################################\n";
            Manage::showStatus($status);
            echo "########################################### status ###########################################\n";
            echo "queue process running ok !\n";
            echo "\n";
        }
    }

}