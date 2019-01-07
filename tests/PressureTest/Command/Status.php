<?php

use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;

/**
 * status 命令动作
 * Class Status
 * @package QueueTask\Daemon\Command
 */
class Status extends Action
{

    /**
     * 执行该命令的程序
     * @return void
     * @throws \Exception
     */
    public function handler()
    {
        $daemon = Daemon::getInstance();
        (new Manage($daemon->getProcessConfig()))->showStatus();
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'status';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'process status';
    }
}