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
        'Redis' => [
            'class' => '\\QueueTask\\Connection\\Redis\\Redis',
            'config' => [
                'popTimeout'    => 3,               // pop阻塞的超时时长 s
                'host'          => '127.0.0.1',     // 数据库地址
                'port'          => 6379,            // 数据库端口
                'db'            => 0,               // 库
                'password'      => null,            // 密码
                'connTimeout'   => 1,               // 链接超时
            ],
        ],

        'Mns' => [
            'class' => '\\QueueTask\\Connection\\Mns\\Mns',
            'config' => [
                'popTimeout'        => 3,           // pop阻塞的超时时长 s
                'accessKeyID'       => '',          // Mns key id
                'accessKeySecret'   => '',          // Mns key secret
                'endpoint'          => '',          // Mns end point
                'pushMaxTryTimes'   => 3,           // 入队最大尝试次数(发生错误会重试入队)
                'deleteMaxTryTimes' => 3,           // 删除消息最大尝试次数(发生错误会重试删除)
            ],
        ],

        'RabbitMQ' => [
            'class' => '\\QueueTask\\Connection\\RabbitMQ\\RabbitMQ',
            'config' => [
                // exchanges需要设置为direct，持久化存储，不自动确认消息
                'popTimeout'    => 3,                   // pop阻塞的超时时长 s
                'host'          => '127.0.0.1',         // 主机
                'port'          => 5672,                // 端口
                'username'      => '',                  // 用户名
                'password'      => '',                  // 密码
                'vhost'         => '/',                 // 虚拟主机
                'exChanges'     => '',                  // 直连交换机名称
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