<?php

namespace QueueTask\Daemon\Command\SingleWork;


use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\SingleWorkDaemon;

/**
 * start 命令动作
 * Class Start
 * @package QueueTask\Daemon\Command\SingleWork
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
        $daemon = SingleWorkDaemon::getInstance();

        $work = $daemon->getWork();

        $manage = new Manage($work->getProcessConfig());

        $manage->setWorkInit($work->getWorkInit())->setWork($work->getWork());

        if ($this->getParam('runInBackground')) {
            // 后台运行
            $manage->setBackground();
        }
        $manage->start();
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