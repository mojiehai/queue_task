<?php

namespace QueueTask\Daemon\Command\SingleWork;


use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\SingleWorkDaemon;

/**
 * restart 命令动作
 * Class ReStart
 * @package QueueTask\Daemon\Command\SingleWork
 */
class ReStart extends Action
{

    /**
     * 执行该命令的程序
     * @return void
     * @throws \Exception
     */
    public function handler()
    {
        $daemon = SingleWorkDaemon::getInstance();

        $work = $daemon->getWork();

        (new Manage($work->getProcessConfig()))
            ->setWorkInit($work->getWorkInit())       // 设置初始化
            ->setWork($work->getWork())               // 设置任务
            ->setBackground()                           // 后台执行
            ->restart();                                // restart
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'restart';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'restart process';
    }
}