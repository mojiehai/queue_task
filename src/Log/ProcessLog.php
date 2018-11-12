<?php

namespace QueueTask\Log;

use QueueTask\Process\Manage;
use QueueTask\Process\Master;
use QueueTask\Process\Worker;

/**
 * 进程日志类
 * Class ProcessLog
 * @package QueueTask\Log
 */
class ProcessLog extends Log
{

    /**
     * 调用日志的对象
     * @var \stdClass
     */
    public static $obj = null;

    /**
     * 获取默认日志文件名
     * @return string
     */
    public static function getDefaultLogFileName()
    {
        return 'process';
    }

    /**
     * 获取每行日志记录前缀
     * @param $level
     * @return string
     */
    protected static function getRowLogPrefix($level)
    {
        $levelArr = static::$LEVELS[$level];

        switch (true) {
            case (static::$obj instanceof Master):
                $prefix = '['.$levelArr['name'].'][ '.(static::$obj)->title.' '.(static::$obj)->pid.' '.date('Y-m-d H:i:s', time()).']: ';
                break;
            case (static::$obj instanceof Worker):
                $prefix = '['.$levelArr['name'].'][ ---- '.(static::$obj)->title.' '.(static::$obj)->pid.' '.date('Y-m-d H:i:s', time()).']: ';
                break;
            case (static::$obj instanceof Manage):
                $prefix = '['.$levelArr['name'].'][Manage '.date('Y-m-d H:i:s', time()).']: ';
                break;
            default:
                $prefix = parent::getRowLogPrefix($level);
                break;
        }
        return $prefix;
    }

    /**
     * process记录日志
     * @param string $type 日志级别
     * @param \stdClass $obj 进程对象
     * @param string $content 内容
     * @param string $fileName 日志文件名
     * @return mixed
     */
    public static function Record($type, $obj, $content, $fileName = '')
    {
        static::$obj = $obj;
        $return = static::$type($content, $fileName);
        static::$obj = null;
        return $return;
    }

}