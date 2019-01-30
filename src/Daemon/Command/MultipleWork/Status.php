<?php

namespace QueueTask\Daemon\Command\MultipleWork;


use ProcessManage\Command\Action\Action;
use QueueTask\Daemon\MultipleWorkDaemon;

/**
 * status 命令动作
 * Class Status
 * @package QueueTask\Daemon\Command\MultipleWork
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
        $daemon = MultipleWorkDaemon::getInstance();
        $multipleWork = $daemon->getMultipleWork();
        $multipleWork->commandStatus();
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