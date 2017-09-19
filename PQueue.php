<?php

require_once dirname(__FILE__).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR.'config.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."Queue".DIRECTORY_SEPARATOR."MySqlQueue.php";


/**
 * 使用入口类，获取队列
 * Class PQueue
 */
class PQueue {



    /**************************** 对外 ********************************/
    /**
     * 根据配置文件获取队列
     * @return PersistQueue
     */
    public static function getQueue(){

        switch(PQUEUE_TYPE){

            case "mysql":
                return self::MySqlQueue();
                break;


            default:
                return null;
                break;

        }

    }
    /**************************** 对外 ********************************/





    /******************* 私有 *********************/
    /**
     * 获取MySql队列
     * @return MySqlQueue
     */
    private static function MySqlQueue(){
        return MySqlQueue::getInstance();
    }
    /******************* 私有 *********************/


} 