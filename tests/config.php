<?php

return [
    'connectRegister' => [
        'MySql' => [
            'config' => [
                'DB_HOST'        => '127.0.0.1',     //数据库地址
                'DB_PORT'        => '3306',          //数据库端口
                'DB_USERNAME'    => 'root',          //用户名
                'DB_PASSWORD'    => 'root',//密码
                'DB_CHARSET'     => 'utf8',          //数据库字符集
                'DB_DATABASE'    => 'queue_task',    //数据库名称
                'DB_TABLE'       => 'job_queue',     //表名称
                'DB_TABLE_DELAY' => 'job_queue_delay', //异步表名称
            ],
        ],

        'Redis' => [
            'config' => [
                'DB_HOST'       => '127.0.0.1',     //数据库地址
                'DB_PORT'       => '6379',          //数据库端口
                'DB_DATABASE'   => '0',             //数据库名称
                'DB_PASSWORD'   => null,            //密码        无密码时，设置为null
            ],
        ],
    ],

    'currentConnect' => 'MySql',
];
