<?php

namespace QueueTask\Daemon\Command\SingleWork;


use ProcessManage\Command\Template\Template;

/**
 * 队列守护进程命令模板
 * Class DaemonSingleQueueTemplate
 * @package QueueTask\Daemon\Command\SingleWork
 */
class DaemonSingleQueueTemplate extends Template
{

    /**
     * 命令映射的类
     * @var array
     */
    public $mapping = [
        'action' => [
            'start' => '\QueueTask\Daemon\Command\SingleWork\Start',
            'stop' => '\QueueTask\Daemon\Command\SingleWork\Stop',
            'restart' => '\QueueTask\Daemon\Command\SingleWork\ReStart',
            'status' => '\QueueTask\Daemon\Command\SingleWork\Status',
            'wakeup' => '\QueueTask\Daemon\Command\SingleWork\WakeUp',
        ],
        'options' => [
            'd' => '\QueueTask\Daemon\Command\SingleWork\D',
        ],
    ];

    /**
     * 获取模板内容
     * @return string
     */
    public function getTemplateStr()
    {
        return '<start|stop|restart|status|wakeup> -[d]';
    }
}