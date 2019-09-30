<?php

require __DIR__."/bootstrap.php";

use QueueTask\Worker;
use QueueTask\Queue;
use QueueTask\Load;
use QueueTask\MultiWorker;

$config = include __DIR__.'/config.php';

Load::Queue($config);

try{

    (new MultiWorker('tag1'))->stop();

}catch (Exception $e){
    echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
}
