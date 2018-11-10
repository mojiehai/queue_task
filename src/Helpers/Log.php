<?php

namespace QueueTask\Helpers;


use QueueTask\Process\Process;

class Log
{
    /**
     * 获取当前日志路径
     * @param string $fileName
     * @return string
     */
    public static function getLogFilePath($fileName = 'run')
    {
        $logRoot = dirname(__DIR__).DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR;
        if (!is_dir($logRoot)) {
            mkdir($logRoot, 0644, true);
            chmod($logRoot, 0644);
        }

        $logFile = $logRoot . $fileName . '.' . date('Y-m-d', time()).'.log';

        if (!file_exists($logFile)) {
            touch($logFile);
        }
        return $logFile;
    }

    /**
     * @param $type
     * @param $msg
     * @return bool
     */
    protected static function write($type, $msg)
    {
        $logFile = static::getLogFilePath();
        $name = cli_get_process_title();
        $name = str_replace(Process::$TITLE_PREFIX, '', $name);
        $log = '['.$type.']['.$name.' '.date('H:i:s', time()).']：'.
            $msg . PHP_EOL;
        return file_put_contents($logFile, $log, FILE_APPEND);
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool
     */
    public static function __callStatic($name, $arguments)
    {
        $msg = isset($arguments[0]) ? $arguments[0] : '';
        return static::write($name, $msg);
    }
}