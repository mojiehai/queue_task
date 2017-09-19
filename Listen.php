<?php

/**
 * 监听入口文件，cli模式下直接执行该文件
 */

require_once dirname(__FILE__).DIRECTORY_SEPARATOR."PersistWorker.php";
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."PQueue.php";


/**
 * 监听参数
 */
$queueName  = "default";        //队列名称
$attempt    = 3;                //队列任务失败后重试次数
$memory     = 128;              //允许的最大使用内存  单位:M
$sleep      = 1;                //监听间隔时间
$delay      = 0;                //任务失败后延迟的秒数(延迟delay秒后再入队列)

/**
 * 开始监听
 */
PersistWorker::listen( PQueue::getQueue() , $queueName , $attempt , $memory , $sleep , $delay );