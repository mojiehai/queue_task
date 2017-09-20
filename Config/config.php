<?php


if( !defined('DS') )                define('DS' , DIRECTORY_SEPARATOR );

//根目录
if( !defined('TASK_ROOT_PATH') )    define('TASK_ROOT_PATH' , dirname(dirname(__FILE__)) );


/****************************** 队列存储方式 ********************************/
/*********** mysql *************/
if( !defined('STORAGE_MYSQL') )     define('STORAGE_MYSQL' , 'mysql' );

/*********** redis *************/
if( !defined('STORAGE_REDIS') )     define('STORAGE_REDIS' , 'redis' );

/*********** file *************/
if( !defined('STORAGE_FILE') )      define('STORAGE_FILE' , 'file' );


if( !defined('STORAGE_TYPE') )      define('STORAGE_TYPE' , STORAGE_MYSQL );        //存储方式
/****************************** 队列存储方式 ********************************/
