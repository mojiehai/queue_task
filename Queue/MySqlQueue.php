<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."ConnectAdapter.php";
require_once TASK_ROOT_PATH.DS."Job".DS."GeneralJob.php";
require_once TASK_ROOT_PATH.DS."Queue".DS."Queue.php";

class MySqlQueue extends Queue{

    /**
     * 连接对象
     * @var Connection
     */
    protected static $connection;

    /**
     * 单例对象
     * @var MySqlQueue
     */
    private static $instance;

    protected function __construct(Connection $connection){
        parent::__construct($connection);
    }

    public function __destruct(){
        self::$instance = null;
    }

    public static function getInstance(){
        if( self::$instance == null ){
            self::$instance = new MySqlQueue(ConnectAdapter::getConnection(STORAGE_MYSQL));
        }
        return self::$instance;
    }



} 