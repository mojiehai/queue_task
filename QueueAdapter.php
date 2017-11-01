<?php

namespace QueueTask;

use QueueTask\Config\Config;
use QueueTask\Queue\MySqlQueue;
use QueueTask\Queue\RedisQueue;
use QueueTask\Queue\FileQueue;


/**
 * 获取队列入口
 * Class QueueAdapter
 */
class QueueAdapter
{

    /**
     * 根据配置文件获取队列(存储方式)
     * @return FileQueue|MySqlQueue|null|RedisQueue
     */
    public static function getQueue()
    {

        switch (Config::getStorageType()) {

            case Config::STORAGE_MYSQL:
                return self::getMySqlQueue();
                break;

            case Config::STORAGE_REDIS:
                return self::getRedisQueue();
                break;

            case Config::STORAGE_FILE:
                return self::getFileQueue();
                break;

            default:
                return null;
                break;
        }

    }



    /**************** 手动强制获取队列 *********************/

    public static function getMySqlQueue()
    {
        return MySqlQueue::getInstance();
    }

    public static function getRedisQueue()
    {
        return RedisQueue::getInstance();
    }

    public static function getFileQueue()
    {
        return FileQueue::getInstance();
    }

} 