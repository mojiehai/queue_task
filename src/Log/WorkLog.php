<?php

namespace QueueTask\Log;

use ProcessManage\Log\Log;
use QueueTask\Config\QueueConfig;

class WorkLog extends Log
{
    /**
     * 获取默认日志文件名
     * @return string
     */
    public static function getLogFileName()
    {
        return QueueConfig::$Log['LogFileName'];
    }
}