<?php

namespace QueueTask\Config;

/**
 * 配置基类
 * Class Config
 * @package QueueTask\Config
 */
abstract class Config
{
    ###
    // config item (static variable)
    ###

    /**
     * 加载配置
     * @param array $config
     * @return void
     */
    public static function loadConfig(array $config)
    {
        foreach ($config as $configField => $configValue) {
            if (isset(static::$$configField)) {
                static::$$configField = $configValue;
            }
        }
    }
}