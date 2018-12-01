<?php

namespace QueueTask\Config;


/**
 * 队列配置文件类
 * Class QueueConfig
 * @package QueueTask\Config
 */
class QueueConfig extends Config
{

    /**
     * @var array 链接配置列表
     * example:
     * [
            'MySql' => [
                'class' => '\\QueueTask\\Connection\\MySql\\MySql',
                'config' => [
                    'DB_HOST'       => '127.0.0.1',     //数据库地址
                    'DB_PORT'       => '3306',          //数据库端口
                    'DB_USERNAME'   => 'root',          //用户名
                    'DB_PASSWORD'   => 'Merror.2017',          //密码
                    'DB_CHARSET'    => 'utf8',          //数据库字符集
                    'DB_DATABASE'   => 'test',          //数据库名称
                    'DB_TABLE'      => 'job_queue',     //表名称
                ],
            ],

            'Redis' => [
                'class' => '\\QueueTask\\Connection\\Redis\\Redis',
                'config' => [
                    'DB_HOST'       => '127.0.0.1',     //数据库地址
                    'DB_PORT'       => '6379',          //数据库端口
                    'DB_DATABASE'   => '0',             //数据库名称
                    'DB_PASSWORD'   => null,            //密码        无密码时，设置为null
                ],
             ],

            'File' => [
                'class' => '\\QueueTask\\Connection\\File\\File',
                'config' => [],
            ],
            ...
        ]
     */
    protected static $connectRegister = [];

    /**
     * @var string 当前使用的链接
     * example:
     *      Redis
     */
    public static $currentConnect = '';


    /**
     * 当前存储方式
     * @param string $currentConnect 当前存储方式
     * @return array
     */
    public static function getConnection($currentConnect)
    {
        if (isset(static::$connectRegister[$currentConnect])) {
            return static::$connectRegister[$currentConnect];
        } else {
            return [];
        }
    }


} 