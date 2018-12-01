<?php

namespace QueueTask\Load;

use QueueTask\Config\QueueConfig;

/**
 * 启动加载模块类(类似于session_start())
 * Class Load
 * @package QueueTask\Load
 */
class Load
{

    /**
     * 加载queue模块
     * @param array $config
     */
    public static function Queue(array $config)
    {
        QueueConfig::loadConfig($config);
    }

}