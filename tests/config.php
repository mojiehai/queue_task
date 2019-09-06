<?php

return [
    'log' => [
        'logRoot' => __DIR__ . '/../runtime/log',
        'fileName' => '\q\u\e\u\e_Y-m-d.\l\o\g',
    ],

    'connectList' => [
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
    ],

    'currentConnect' => 'MySql',
];
