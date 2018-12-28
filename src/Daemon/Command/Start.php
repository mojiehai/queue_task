<?php

namespace QueueTask\Daemon\Command;


use ProcessManage\Command\Action\Action;
use ProcessManage\Process\Manage;
use QueueTask\Daemon\Daemon;

/**
 * start 命令动作
 * Class Start
 * @package QueueTask\Daemon\Command
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
        $daemon = Daemon::getInstance();

        $manage = new Manage($daemon->getProcessConfig());

        $manage->setWorkInit($daemon->getWorkInit())->setWork($daemon->getWork());

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