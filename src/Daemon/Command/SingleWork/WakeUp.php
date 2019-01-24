<?php

namespace QueueTask\Daemon\Command\SingleWork;

use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\SingleWorkDaemon;

/**
 * wakeup 命令动作
 * Class Stop
 * @package QueueTask\Daemon\Command\SingleWork
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
        $daemon = SingleWorkDaemon::getInstance();
        $work = $daemon->getWork();
        (new Manage($work->getProcessConfig()))->wakeup();
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