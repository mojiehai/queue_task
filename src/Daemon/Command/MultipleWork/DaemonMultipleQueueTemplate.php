<?php

namespace QueueTask\Daemon\Command\MultipleWork;


use ProcessManage\Command\Template\Template;

/**
 * 队列守护进程命令模板
 * Class DaemonSingleQueueTemplate
 * @package QueueTask\Daemon\Command\MultipleWork
 */
class DaemonMultipleQueueTemplate extends Template
{

    /**
     * 命令映射的类
     * @var array
     */
    public $mapping = [
        'action' => [
            'start' => '\QueueTask\Daemon\Command\MultipleWork\Start',
            'stop' => '\QueueTask\Daemon\Command\MultipleWork\Stop',
            'restart' => '\QueueTask\Daemon\Command\MultipleWork\ReStart',
            'status' => '\QueueTask\Daemon\Command\MultipleWork\Status',
            'wakeup' => '\QueueTask\Daemon\Command\MultipleWork\WakeUp',
        ],
        'options' => [
            'd' => '\QueueTask\Daemon\Command\MultipleWork\D',
            'n' => '\QueueTask\Daemon\Command\MultipleWork\N',
            'name' => '\QueueTask\Daemon\Command\MultipleWork\Name',
        ],
    ];

    /**
     * 获取模板内容
     * @return string
     */
    public function getTemplateStr()
    {
        return '<start|stop|restart|status|wakeup> -[n|name] -[d]';
    }
}