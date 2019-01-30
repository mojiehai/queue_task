<?php

namespace QueueTask\Daemon\Command\MultipleWork;

use ProcessManage\Command\Action\Action;
use QueueTask\Daemon\MultipleWorkDaemon;

/**
 * wakeup 命令动作
 * Class Stop
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class WakeUp extends Action
{

    /**
     * 执行该命令的程序
     * @return void
     * @throws \Exception
     */
    public function handler()
    {

        $daemon = MultipleWorkDaemon::getInstance();
        $multipleWork = $daemon->getMultipleWork();
        $multipleWork->commandWakeup();

    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'wakeup';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'wakeup worker process';
    }
}