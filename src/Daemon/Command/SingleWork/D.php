<?php

namespace QueueTask\Daemon\Command\SingleWork;

use ProcessManage\Command\Action\Action;
use ProcessManage\Command\Options\Options;
use ProcessManage\Exception\Exception;
use QueueTask\Daemon\SingleWorkDaemon;

/**
 * d 参数的动作
 * Class D
 * @package QueueTask\Daemon\Command\SingleWork
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
     * @throws Exception
     * @return mixed
     */
    public function impactAction(Action $action)
    {
        $daemon = SingleWorkDaemon::getInstance();
        $singleWork = $daemon->getSingleWork();
        $singleWork->background = true;
    }
}