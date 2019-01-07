<?php

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
            'start' => 'Start',
            'stop' => 'Stop',
            'restart' => 'ReStart',
            'status' => 'Status',
        ],
        'options' => [
            'd' => 'D',
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