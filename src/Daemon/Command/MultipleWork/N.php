<?php

namespace QueueTask\Daemon\Command\MultipleWork;

/**
 * n 参数的动作
 * Class N
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class N extends Name
{

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'n';
    }
}