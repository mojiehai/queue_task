<?php

namespace QueueTask\Connection;

use QueueTask\Config\Config;
use QueueTask\Connection\File\FileConnect;
use QueueTask\Connection\MySql\MySqlConnect;
use QueueTask\Connection\Redis\RedisConnect;

/**
 * 获取任务存储方式
 * Class ConnectAdapter
 */
class ConnectAdapter
{

    /**
     * 根据配置文件返回任务存储介质
     * @param string $type
     * @return Connection|null
     */
    public static function getConnection($type = '')
    {

        if(empty($type)) {
            $type = Config::getStorageType();
        }

        switch ($type) {

            case Config::STORAGE_MYSQL:
                return self::getMysql();
                break;

            case Config::STORAGE_REDIS:
                return self::getRedis();
                break;

            case Config::STORAGE_FILE:
                return self::getFile();
                break;

            default:
                return null;
                break;
        }

    }


    /**
     * @return MySqlConnect
     */
    public static function getMysql()
    {
        return MySqlConnect::getInstance();
    }


    /**
     * @return RedisConnect
     */
    public static function getRedis()
    {
        return RedisConnect::getInstance();
    }


    /**
     * @return FileConnect
     */
    public static function getFile()
    {
        return FileConnect::getInstance();
    }

} 