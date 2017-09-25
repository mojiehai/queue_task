<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Connection".DS."ConnectAdapter.php";
require_once TASK_ROOT_PATH.DS."Job".DS."GeneralJob.php";
require_once TASK_ROOT_PATH.DS."Queue".DS."Queue.php";

class FileQueue extends Queue{

    /**
     * 连接对象
     * @var Connection
     */
    protected static $connection;

    /**
     * 单例对象
     * @var FileQueue
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
            self::$instance = new FileQueue(ConnectAdapter::getConnection(STORAGE_FILE));
        }
        return self::$instance;
    }

} 