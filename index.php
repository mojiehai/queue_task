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

use QueueTask\Handler\TestHandler;
use QueueTask\QueueAdapter;

$res = QueueAdapter::getQueue();

//$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'a');
$r = $res->laterOn(5,new TestHandler(),'test',['test'=>'test'],'a');
echo date("Y-m-d H:i:s",time());
var_dump($r);die;