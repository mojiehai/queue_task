<?php

namespace QueueTask\Daemon\Command\SingleWork;


use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\SingleWorkDaemon;

/**
 * status 命令动作
 * Class Status
 * @package QueueTask\Daemon\Command\SingleWork
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
        $daemon = SingleWorkDaemon::getInstance();
        $work = $daemon->getWork();
        Manage::showStatus(
            (new Manage($work->getProcessConfig()))->status()
        );
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