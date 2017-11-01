<?php

require_once "./Config/Config.php";
require_once "./Helpers/StringHelpers.php";
require_once "./Exception/TaskException.php";
require_once "./Exception/DBException.php";
require_once "./QueueAdapter.php";
require_once "./Handler/JobHandler.php";
require_once "./Handler/TestHandler.php";
require_once "./Queue/QueueInterface.php";
require_once "./Queue/Queue.php";
require_once "./Queue/MySqlQueue.php";
require_once "./Queue/RedisQueue.php";
require_once "./Connection/Connection.php";
require_once "./Connection/Redis/RedisDrive.php";
require_once "./Connection/Redis/RedisConnect.php";
require_once "./Connection/MySql/MySqlConnect.php";
require_once "./Connection/ConnectAdapter.php";
require_once "./Job/Job.php";
require_once "./Job/GeneralJob.php";
require_once "./Worker/Worker.php";

use QueueTask\Worker\Worker;
use QueueTask\QueueAdapter;

$queueName = "a";        //队列名称
$attempt   = 3;          //队列任务失败尝试次数，0为不限制
$memory    = 128;        //允许使用的最大内存  单位:M
$sleep     = 1;          //每次检测的时间间隔
$delay     = 3;          //失败后延迟的秒数重新入队列

try{
    Worker::listen(QueueAdapter::getQueue(),$queueName,$attempt,$memory,$sleep,$delay);
}catch (Exception $e){
    echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
}
