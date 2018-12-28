<?php

namespace QueueTask\Daemon\Command;


use ProcessManage\Command\Template\Template;

/**
 * 队列守护进程命令模板
 * Class DaemonQueueTemplate
 * @package QueueTask\Daemon\Command
 */
class DaemonQueueTemplate extends Template
{

    /**
     * 命令映射的类
     * @var array
     */
    public $mapping = [
        'action' => [
            'start' => '\QueueTask\Daemon\Command\Start',
            'stop' => '\QueueTask\Daemon\Command\Stop',
            'restart' => '\QueueTask\Daemon\Command\ReStart',
            'status' => '\QueueTask\Daemon\Command\Status',
        ],
        'options' => [
            'd' => '\QueueTask\Daemon\Command\D',
        ],
    ];

    /**
     * 获取模板内容
     * @return string
     */
    public function getTemplateStr()
    {
        return '<start|stop|restart|status> -[d]';
    }
}