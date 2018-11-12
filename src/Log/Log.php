<?php

namespace QueueTask\Log;

/**
 * 基础日志类
 * Class Log
 * @package QueueTask\Log
 */
abstract class Log
{
    /**
     * 日志级别
     * @var array
     */
    protected static $LEVELS = [
        'debug' => [
            'name' => 'Debug',
            'level' => 1
        ],
        'info' => [
            'name' => 'Info',
            'level' => 2
        ],
        'notice' => [
            'name' => 'Notice',
            'level' => 3
        ],
        'warning' => [
            'name' => 'Warning',
            'level' => 4
        ],
        'error' => [
            'name' => 'Error',
            'level' => 5
        ],
        'fatal' => [
            'name' => 'Fatal',
            'level' => 6
        ],
    ];

    ######################## 日志文件相关 ##########################
    /**
     * 获取日志文件根目录
     * @return string
     */
    final public static function getLogBaseDir()
    {
        $logRoot = dirname(__DIR__).DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;
        if (!is_dir($logRoot)) {
            mkdir($logRoot, 0777, true);
            chmod($logRoot, 0777);
        }
        return $logRoot;
    }

    /**
     * 获取默认日志文件名
     * @return string
     */
    public static function getDefaultLogFileName()
    {
        return 'run';
    }

    /**
     * 获取日志文件分隔规则
     */
    public static function getLogFileDeLimiterRule()
    {
        return 'Y-m-d';     // 按天分隔
    }

    /**
     * 获取日志完整路径
     * @param string $fileName  日志文件名
     * @return string
     */
    public static function getLogFilePath($fileName)
    {
        // 获取日志根目录
        $logRoot = static::getLogBaseDir();
        $logFileDelimiterRule = static::getLogFileDeLimiterRule();
        if (empty($logFileDelimiterRule)) {
            $logFileDelimiterRule = self::getLogFileDeLimiterRule();
        }
        $logFile = $logRoot . $fileName . '.' . date($logFileDelimiterRule, time()) . '.log';

        if (!file_exists($logFile)) {
            touch($logFile);
        }
        return $logFile;
    }
    ######################## 日志文件相关 ##########################

    /**
     * 写入日志
     * @param string $level
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    protected static function write($level, $content, $fileName = '')
    {
        if (empty($fileName)) {
            $logFile = static::getLogFilePath(static::getDefaultLogFileName());
        } else {
            $logFile = static::getLogFilePath($fileName);
        }
        $logPrefix = static::getRowLogPrefix($level);
        $log = $logPrefix . $content . PHP_EOL;
        return file_put_contents($logFile, $log, FILE_APPEND);
    }

    ######################## 日志内容相关 ##########################

    /**
     * 获取每行日志记录前缀
     * @param $level
     * @return string
     */
    protected static function getRowLogPrefix($level)
    {
        $levelArr = static::$LEVELS[$level];
        $prefix = '['.$levelArr['name'].']['.date('Y-m-d H:i:s', time()).']: ';
        return $prefix;
    }
    ######################## 日志内容相关 ##########################

    /**
     * @param $name
     * @param $arguments
     * @return bool
     */
    public static function __callStatic($name, $arguments)
    {
        $content = isset($arguments[0]) ? $arguments[0] : '';
        $fileName = isset($arguments[1]) ? $arguments[1] : '';
        if (isset(static::$LEVELS[strtolower($name)])) {
            $name = strtolower($name);
            return static::$name($content, $fileName);
        } else {
            // 未定义的按照debug处理
            return static::debug($content, $fileName);
        }
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function debug($content, $fileName = '')
    {
        return static::write('debug', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function info($content, $fileName = '')
    {
        return static::write('info', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function notice($content, $fileName = '')
    {
        return static::write('notice', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function warning($content, $fileName = '')
    {
        return static::write('warning', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function error($content, $fileName = '')
    {
        return static::write('error', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function fatal($content, $fileName = '')
    {
        return static::write('fatal', $content, $fileName);
    }
}