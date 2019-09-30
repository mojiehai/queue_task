<?php

namespace QueueTask\Helpers;

/**
 * Class Log
 * @package QueueTask\Helpers
 */
class Log
{
    use LoadConfig;

    /**
     * 文件路径
     * @var string
     */
    protected $logRoot = __DIR__.'/../../runtime/log';

    /**
     * 文件名
     * @var string
     */
    protected $fileName = '\q\u\e\u\e_Y-m-d.\l\o\g';

    /**
     * @var self
     */
    protected static $instance = null;

    protected function __construct() {
        $this->configNameList = ['logRoot', 'fileName'];
    }

    /**
     * @return Log
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new Log();
        }
        return self::$instance;
    }

    /**
     * @param $level
     * @param string $message
     * @param array $extends
     * @return bool
     */
    protected function write($level, $message = '', $extends = [])
    {
        if (!is_dir($this->logRoot)) {
             $mkdir = mkdir($this->logRoot, 0777, true);
             if (!$mkdir) {
                 return false;
             }
        }

        $time = time();

        $filePath = rtrim($this->logRoot, '/') . '/' . date($this->fileName, $time);

        // 信息
        $string = sprintf(
            "[%s][%s]: %s; extends=%s \n",
            $level, date('Y-m-d m:d:s', $time), $message, json_encode($extends)
        );

        if (file_put_contents($filePath, $string, FILE_APPEND)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * notice
     * @param string $message
     * @param array $extends
     * @return bool
     */
    public static function notice($message = '', $extends = [])
    {
        return self::getInstance()->write('notice', $message, $extends);
    }

    /**
     * info
     * @param string $message
     * @param array $extends
     * @return bool
     */
    public static function info($message = '', $extends = [])
    {
        return self::getInstance()->write('info', $message, $extends);
    }

    /**
     * warning
     * @param string $message
     * @param array $extends
     * @return bool
     */
    public static function warning($message = '', $extends = [])
    {
        return self::getInstance()->write('warning', $message, $extends);
    }

    /**
     * error
     * @param string $message
     * @param array $extends
     * @return bool
     */
    public static function error($message = '', $extends = [])
    {
        return self::getInstance()->write('error', $message, $extends);
    }

    /**
     * fatal
     * @param string $message
     * @param array $extends
     * @return bool
     */
    public static function fatal($message = '', $extends = [])
    {
        return self::getInstance()->write('fatal', $message, $extends);
    }

}