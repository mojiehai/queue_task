<?php

use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;

/**
 * restart 命令动作
 * Class ReStart
 * @package QueueTask\Daemon\Command
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
        $daemon = Daemon::getInstance();

        (new Manage($daemon->getProcessConfig()))
            ->setWorkInit($daemon->getWorkInit())       // 设置初始化
            ->setWork($daemon->getWork())               // 设置任务
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