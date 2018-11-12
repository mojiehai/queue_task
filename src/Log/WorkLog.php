<?php

namespace QueueTask\Log;


class WorkLog extends Log
{
    /**
     * 获取默认日志文件名
     * @return string
     */
    public static function getDefaultLogFileName()
    {
        return 'work';
    }
}