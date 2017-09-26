<?php

require_once "./QueueAdapter.php";
require_once "./Handler/TestHandler.php";

$res = QueueAdapter::getQueue();

$res->pushOn(new TestHandler(),'test',['test'=>'test'],'a');