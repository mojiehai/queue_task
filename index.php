<?php

require_once "./QueueAdapter.php";
require_once "./Handler/TestHandler.php";

$res = QueueAdapter::getQueue();

//$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'a');
$r = $res->laterOn(10,new TestHandler(),'test',['test'=>'test'],'a');
echo date("Y-m-d H:i:s",time());
var_dump($r);die;