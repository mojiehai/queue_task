<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";

/**
 * 获取任务存储方式
 * Class ConnectAdapter
 */
class ConnectAdapter {


    /**
     * 根据配置文件返回任务存储介质
     * @return Connection|null
     */
    public static function getConnection(){

        switch(STORAGE_TYPE){

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



    public static function getMysql(){
        return null;
    }


    public static function getRedis(){
        return null;
    }


    public static function getFile(){
        return null;
    }

} 