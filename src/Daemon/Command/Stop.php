<?php

namespace QueueTask\Daemon\Command;

use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\Daemon;

/**
 * stop 命令动作
 * Class Stop
 * @package QueueTask\Daemon\Command
 */
class Stop extends Action
{

    /**
     * 执行该命令的程序
     * @return void
     * @throws \Exception
     */
    public function handler()
    {
        $daemon = Daemon::getInstance();
        (new Manage($daemon->getProcessConfig()))->stop();
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'stop';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'stop process';
    }
}