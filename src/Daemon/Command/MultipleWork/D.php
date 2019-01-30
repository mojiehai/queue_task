<?php

namespace QueueTask\Daemon\Command\MultipleWork;

use ProcessManage\Command\Action\Action;
use ProcessManage\Command\Options\Options;
use QueueTask\Daemon\MultipleWorkDaemon;

/**
 * d 参数的动作
 * Class D
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class D extends Options
{

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'd';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'background running process';
    }

    /**
     * 影响action的行为
     *
     * 后台运行程序(守护进程运行)
     *
     * @param Action $action
     * @return mixed
     */
    public function impactAction(Action $action)
    {
        $multipleDaemon = MultipleWorkDaemon::getInstance();
        $multipleWork = $multipleDaemon->getMultipleWork();
        $multipleWork->background = true;
    }
}