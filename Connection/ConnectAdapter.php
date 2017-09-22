<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."File".DS."FileConnect.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."Redis".DS."RedisConnect.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."MySql".DS."MySqlConnect.php";

/**
 * 获取任务存储方式
 * Class ConnectAdapter
 */
class ConnectAdapter {


    /**
     * 根据配置文件返回任务存储介质
     * @param string $type
     * @return Connection|null
     */
    public static function getConnection($type = ''){

        if(empty($type)){
            $type = STORAGE_TYPE;
        }

        switch($type){

            case STORAGE_MYSQL:
                return self::getMysql();
                break;

            case STORAGE_REDIS:
                return self::getRedis();
                break;

            case STORAGE_FILE:
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
    public static function getMysql(){
        return MySqlConnect::getInstance();
    }


    /**
     * @return RedisConnect
     */
    public static function getRedis(){
        return RedisConnect::getInstance();
    }


    /**
     * @return FileConnect
     */
    public static function getFile(){
        return FileConnect::getInstance();
    }

} 