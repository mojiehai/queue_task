<?php

require_once dirname(__FILE__).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Queue".DS."MySqlQueue.php";
require_once TASK_ROOT_PATH.DS."Queue".DS."RedisQueue.php";
require_once TASK_ROOT_PATH.DS."Queue".DS."FileQueue.php";


/**
 * 获取队列入口
 * Class QueueAdapter
 */
class QueueAdapter {

    /**
     * 根据配置文件获取队列(存储方式)
     * @return FileQueue|MySqlQueue|null|RedisQueue
     */
    public static function getQueue(){

        switch(STORAGE_TYPE){

            case STORAGE_MYSQL:
                return self::getMySqlQueue();
                break;

            case STORAGE_REDIS:
                return self::getRedisQueue();
                break;

            case STORAGE_FILE:
                return self::getFileQueue();
                break;

            default:
                return null;
                break;
        }

    }



    /**************** 手动强制获取队列 *********************/

    public static function getMySqlQueue(){
        return MySqlQueue::getInstance();
    }

    public static function getRedisQueue(){
        return RedisQueue::getInstance();
    }

    public static function getFileQueue(){
        return FileQueue::getInstance();
    }

} 