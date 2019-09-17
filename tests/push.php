<?php

require __DIR__."/bootstrap.php";

use Tests\TestHandler;
use QueueTask\Load;
use QueueTask\Queue;

$config = include __DIR__.'/config.php';

Load::Queue($config);

$res = Queue::getInstance();

$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'testQueue');
//$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'testQueue1');
//$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'testQueue2');
//$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'testQueue3');
//$r = $res->laterOn(5,new TestHandler(),'test',['test'=>'test'],'testQueue');
echo date("Y-m-d H:i:s",time());
var_dump($r);die;