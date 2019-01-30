<?php

namespace QueueTask\Daemon\Command\MultipleWork;


use ProcessManage\Command\Action\Action;
use QueueTask\Daemon\MultipleWorkDaemon;

/**
 * start 命令动作
 * Class Start
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class Start extends Action
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

        $multipleWork->commandStart();

    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'start';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'start process';
    }
}