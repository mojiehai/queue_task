<?php

namespace QueueTask\Daemon\Command\MultipleWork;

use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\MultipleWorkDaemon;
use ProcessManage\Exception\Exception;
use ProcessManage\Process\ManageMultiple;

/**
 * stop 命令动作
 * Class Stop
 * @package QueueTask\Daemon\Command\MultipleWork
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

        $daemon = MultipleWorkDaemon::getInstance();
        $multipleWork = $daemon->getMultipleWork();
        $multipleWork->commandStop();

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