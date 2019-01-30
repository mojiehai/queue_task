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

        $singleWork = $daemon->getSingleWork();

        $singleWork->commandRestart();

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