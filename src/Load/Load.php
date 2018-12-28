<?php

namespace QueueTask\Load;

use ProcessManage\Config\LogConfig;
use ProcessManage\Config\ProcessConfig;
use QueueTask\Config\QueueConfig;

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
        QueueConfig::LoadConfig($config);
        LogConfig::LoadConfig(QueueConfig::$Log);
        ProcessConfig::LoadConfig(QueueConfig::$Process);
    }

}