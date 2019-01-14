<?php

namespace QueueTask\Daemon\Command\MultipleWork;

use ProcessManage\Command\Action\Action;
use ProcessManage\Command\Options\Options;

/**
 * name 参数的动作
 * Class Name
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class Name extends Options
{

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'name';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'specify queue name';
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
        $action->setParam('queueName', $this->param);
    }
}