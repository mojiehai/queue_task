<?php

require "./bootstrap.php";

use Tests\TestHandler;
use QueueTask\Queue\Queue;

$res = Queue::getInstance();

$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'a');
//$r = $res->laterOn(5,new TestHandler(),'test',['test'=>'test'],'a');
echo date("Y-m-d H:i:s",time());
var_dump($r);die;