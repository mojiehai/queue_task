<?php

namespace QueueTask\Config;

use ProcessManage\Config\Config;

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
     *
     */
    public static $connectRegister = [
        'MySql' => [
            'class' => '\\QueueTask\\Connection\\MySql\\MySql',
            'config' => [
                'DB_HOST'       => '127.0.0.1',     //数据库地址
                'DB_PORT'       => '3306',          //数据库端口
                'DB_USERNAME'   => 'root',          //用户名
                'DB_PASSWORD'   => 'root',          //密码
                'DB_CHARSET'    => 'utf8',          //数据库字符集
                'DB_DATABASE'   => 'queue_task',    //数据库名称
                'DB_TABLE_ASYNC'=> 'queue_task_delay',//延迟队列数据库名称
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
    ];

    /**
     * @var string 当前使用的链接
     */
    public static $currentConnect = 'Redis';

    /**
     * @var array 日志模块配置
     */
    public static $Log = [
        'LogBaseRoot' => __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'log', // 日志文件根目录

        // 日志级别对应的文件名前缀
        'Debug_FileNamePrefix' => '',
        'Info_FileNamePrefix' => '',
        'Notice_FileNamePrefix' => '',
        'Warning_FileNamePrefix' => '',
        'Error_FileNamePrefix' => 'error_',
        'Fatal_FileNamePrefix' => 'fatal_',

        // 普通日志文件默认文件名
        'LogFileName' => 'work',
        // 普通日志文件分隔规则
        'LogDeLimiterRule' => 'Y-m-d',
        // 进程日志文件默认文件名
        'ProcessLogFileName' => 'queue_task_run',
        // 进程日志文件分隔规则
        'ProcessLogDeLimiterRule' => 'Y-m-d',
    ];

    /**
     * @var array 守护进程模块配置
     */
    public static $Process = [
        'PidRoot' => '/tmp/queue_task/pid',
        'TitlePrefix' => 'queue_task',
        'StatusFileRoot' => '/tmp/queue_task/status',
    ];

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


    /**
     * 加载配置
     * @param array $config
     */
    public static function LoadConfig(array $config)
    {
        // 配置不完全覆盖，而是层级覆盖
        foreach ($config as $configField => $configValue) {
            if (isset(static::$$configField)) {
                $config[$configField] = static::fullConfig(static::$$configField, $configValue);
            }
        }
        parent::LoadConfig($config);
    }

    /**
     * 填充config
     * @param $template
     * @param $config
     * @return mixed
     */
    public static function fullConfig($template, $config)
    {
        if (is_array($config) && is_array($template)) {
            foreach ($config as $k => $v) {
                if (isset($template[$k])) {
                    if (is_array($v)) {
                        $template[$k] = static::fullConfig($template[$k], $v);
                    } else {
                        $template[$k] = $v;
                    }
                } else {
                    $template[$k] = $v;
                }
            }
        } else {
            $template = $config;
        }
        return $template;
    }

} 