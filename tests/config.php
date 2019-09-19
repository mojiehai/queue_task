<?php

return [
    'log' => [
        'logRoot' => __DIR__ . '/../runtime/log',
        'fileName' => '\q\u\e\u\e_Y-m-d.\l\o\g',
    ],

    'connectList' => [
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
            ],
        ],
        'RabbitMQ' => [
            'class' => '\\QueueTask\\Connection\\RabbitMQ\\RabbitMQ',
            'config' => [
                // exchanges需要设置为direct，持久化存储，不自动确认消息
                'popTimeout'    => 3,               // pop阻塞的超时时长 s
                'host'          => '127.0.0.1',
                'port'          => 5672,
                'username'      => '',
                'password'      => '',
                'vhost'         => '/',           // 虚拟主机
                'exChanges'     => '', // 直连交换机名称
            ],
        ],
    ],

    'currentConnect' => 'RabbitMQ',
];
