<?php

namespace QueueTask\Connection;

use QueueTask\Helpers\Log;

/**
 * Class ConnectionFactory
 * @package QueueTask\Connection
 */
class ConnectionFactory
{

    /**
     * @var array 链接配置列表
     * example:
     *
     */
    public static $connectList = [
        'MySql' => [
            'class' => '\\QueueTask\\Connection\\MySql\\MySql',
            'config' => [
                'DB_HOST'       => '127.0.0.1',     //数据库地址
                'DB_PORT'       => '3306',          //数据库端口
                'DB_USERNAME'   => 'root',          //用户名
                'DB_PASSWORD'   => 'root',          //密码
                'DB_CHARSET'    => 'utf8',          //数据库字符集
                'DB_DATABASE'   => 'queue_task',    //数据库名称
                'DB_TABLE_DELAY'=> 'job_queue_delay',//延迟队列表名称
                'DB_TABLE'      => 'job_queue',     //表名称
                'POP_TIME_OUT'  => 3,               // pop阻塞的超时时长 s
            ],
        ],

        'Redis' => [
            'class' => '\\QueueTask\\Connection\\Redis\\Redis',
            'config' => [
                'DB_HOST'       => '127.0.0.1',     //数据库地址
                'DB_PORT'       => '6379',          //数据库端口
                'DB_DATABASE'   => '0',             //数据库名称
                'DB_PASSWORD'   => null,            //密码        无密码时，设置为null
                'POP_TIME_OUT'  => 3,               // pop阻塞的超时时长 s
            ],
        ],

    ];

    /**
     * @var string 当前默认使用的链接
     */
    public static $currentConnect = 'Redis';

    /**
     * 获取链接对象
     * @param string $currentName 当前链接方式
     * @return Connection
     */
    public static function getInstance($currentName)
    {
        $connect = isset(self::$connectList[$currentName]) ? self::$connectList[$currentName] : [];
        if (empty($connect) || !isset($connect['class']) || empty($connect['class'])) {
            Log::error('There is no connection available type');
            return null;
        } else {
            $class = $connect['class'];
            $config = isset($connect['config']) ? $connect['config'] : [];
            return $class::getInstance($config);
        }
    }
}