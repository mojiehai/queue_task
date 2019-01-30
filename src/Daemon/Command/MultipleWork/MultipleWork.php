<?php

namespace QueueTask\Daemon\Command\MultipleWork;

use ProcessManage\Exception\Exception;
use QueueTask\Daemon\Work\Work;
use ProcessManage\Process\ManageMultiple;
use ProcessManage\Process\Manage;

/**
 * 多任务类
 * Class MultipleWork
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class MultipleWork
{

    /**
     * 是否后台运行
     * @var bool
     */
    public $background = false;

    /**
     * 指定的队列名称
     * @var string
     */
    public $queueName = null;

    /**
     * 获取多进程多任务管理器
     * @var ManageMultiple
     */
    protected $manageMultiple = null;

    /**
     * 工作列表
     * @var Work[]
     */
    protected $works = [];

    /**
     * 添加work
     * @param Work $work
     * @return $this
     */
    public function addWork(Work $work) {
        $this->works[$work->queueName] = $work;
        return $this;
    }

    ############################# manage ###############################
    /**
     * 获取多进程多任务管理器
     * @return ManageMultiple
     */
    protected function getManageMultiple()
    {
        if (!($this->manageMultiple instanceof ManageMultiple)) {
            $multipleManage = new ManageMultiple();
            foreach ($this->works as $work) {
                // 添加多个manage
                $multipleManage->addManage(
                    (new Manage($work->getProcessConfig()))
                        ->setWorkInit($work->getWorkInit())
                        ->setWork($work->getWork())
                );
            }
            $this->manageMultiple = $multipleManage;
        }
        return $this->manageMultiple;
    }


    /**
     * 获取多进程单任务管理器
     * @param string $queueName 队列名称
     * @return Manage
     */
    protected function getManage(string $queueName)
    {
        $work = $this->works[$queueName];
        return (new Manage($work->getProcessConfig()))
            ->setWorkInit($work->getWorkInit())
            ->setWork($work->getWork());
    }


    /**
     * 检测命令是否可用
     * @return bool
     * @throws Exception
     */
    public function checkCommand()
    {
        // 如果工作为空，则退出
        if (empty($this->works)) {
            throw new Exception('there is no queue configured to run');
        }

        // 如果队列名称不为空，且工作中没有该队列，则退出
        if (!empty($this->queueName) && !isset($this->works[$this->queueName])) {
            throw new Exception('queue does not exist');
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
        // 设置了队列名称
        if (!empty($this->queueName)) {
            // 执行单任务
            $manage = $this->getManage($this->queueName);

            if ($this->background) {
                // 后台运行
                $manage->setBackground();
            }
            $manage->start();

        } else {
            // 执行多任务
            $multipleManage = $this->getManageMultiple();

            $multipleManage->start();
        }
    }

    /**
     * 命令：stop
     * @throws Exception
     */
    public function commandStop()
    {
        $this->checkCommand();
        // 设置了队列名称
        if (!empty($this->queueName)) {
            // 执行单任务
            $manage = $this->getManage($this->queueName);

            $manage->stop();

        } else {
            // 执行多任务
            $multipleManage = $this->getManageMultiple();

            $multipleManage->stop();
        }
    }

    /**
     * 命令：restart
     * @throws Exception
     */
    public function commandRestart()
    {
        $this->checkCommand();
        // 设置了队列名称
        if (!empty($this->queueName)) {
            // 执行单任务
            $manage = $this->getManage($this->queueName);

            $manage->setBackground()->restart();

        } else {
            // 执行多任务
            $multipleManage = $this->getManageMultiple();

            $multipleManage->restart();
        }
    }

    /**
     * 命令： wakeup
     * @throws Exception
     */
    public function commandWakeup()
    {
        $this->checkCommand();
        // 设置了队列名称
        if (!empty($this->queueName)) {
            // 执行单任务
            $manage = $this->getManage($this->queueName);

            $manage->wakeup();

        } else {
            // 执行多任务
            $multipleManage = $this->getManageMultiple();

            $multipleManage->wakeup();
        }
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
        // 设置了队列名称
        if (!empty($this->queueName)) {
            $workList = [$this->works[$this->queueName]];
            // 执行单任务
            $manage = $this->getManage($this->queueName);

            $status = $manage->status();

        } else {
            $workList = $this->works;
            // 执行多任务
            $multipleManage = $this->getManageMultiple();

            $status = $multipleManage->status();
        }

        $queueConfig = ['QueueConfig' => []];
        // 转换queue_name
        foreach ($workList as $work) {
            $status = $work->formatQueueStatus($status);
            $queueConfig['QueueConfig'][] = $work->getQueueConfig();
        }

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