<?php

/**
 * Redis配置
 */
if( !defined('REDIS_DB_HOST') ) {

    define('REDIS_DB_HOST'        ,   '127.0.0.1');       //数据库地址
    define('REDIS_DB_PORT'        ,   '6379');            //数据库端口
    define('REDIS_DB_DATABASE'    ,   '0');               //数据库名称
    define('REDIS_DB_PASSWORD'    ,   '');                //密码

}