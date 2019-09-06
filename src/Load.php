<?php

namespace QueueTask;

use QueueTask\Connection\ConnectionFactory;
use QueueTask\Helpers\Log;

/**
 * 启动加载模块类(类似于session_start())
 * Class Load
 * @package QueueTask\Load
 */
class Load
{

    /**
     * 加载queue模块依赖的配置
     * @param array $config
     */
    public static function Queue(array $config)
    {
        // 加载log
        if (isset($config['log'])) {
            Log::getInstance()->setConfig($config['log']);
        }

        // 加载链接列表
        if (isset($config['connectList'])) {
            ConnectionFactory::$connectList = $config['connectList'];
        }

        // 加载当前链接
        if (isset($config['currentConnect'])) {
            ConnectionFactory::$currentConnect = $config['currentConnect'];
        }
    }

}