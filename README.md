# queue_task
PHP队列任务管理器，支持多种存储方式

<br />

## 业务场景
在实际业务场景中，我们可能需要执行一些耗时操作，例如：发送邮件等。但这样的操作并不适合放在主流程中，则可以使用队列来异步处理任务。

<br />

## 简述
基于php-cli模式的生产者-消费者模式，自定义存储介质，也可用系统内置存储：Mysql、Redis。并且提供了一套支持命令管理的守护进程启动方式。
      
<br />
      
## 依赖
- php: >=7.0
- ext-mysqli: *
- ext-json: *
- mojiehai/process_manage: ~1.0.0
 
<br />
 
## 安装
> linux：`composer require mojiehai/queue_task`  

> windows：`composer require mojiehai/queue_task --ignore-platform-reqs`  (忽略环境检查)(windows不支持守护进程)

<br />

## 使用
### 全局配置
建议在全局的入口文件加载该配置信息
```php
############################## 全局配置 ##############################
$config = [
    'connectRegister' => [
        'MySql' => [
            'config' => [
                'DB_HOST'       => '127.0.0.1',     //数据库地址
                'DB_PORT'       => '3306',          //数据库端口
                'DB_USERNAME'   => 'root',          //用户名
                'DB_PASSWORD'   => 'root',          //密码
                'DB_CHARSET'    => 'utf8',          //数据库字符集
                'DB_DATABASE'   => 'queue_task',    //数据库名称
                'DB_TABLE'      => 'job_queue',     //表名称
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
Load::Queue($config);
############################## 全局配置 ##############################
```


### 压入任务
```php
$queue = Queue::getInstance();

// 直接压入队列
$r = $queue->pushOn(new TestHandler(),'test',['test'=>'test'],'queue_name_1');
// 延迟5s压入队列
$r = $queue->laterOn(5,new TestHandler(),'test',['test'=>'test'],'queue_name_1');
 
```
### 监听任务
1. 启动监听任务
    ```php
    $config = [
        'queueName' => 'queue_name_1', //队列名称
        'attempt' => 3,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'sleep' => 1,       //每次检测的时间间隔
        'delay' => 3,       //失败后延迟的秒数重新入队列
    ];
    
    try{
        (new Worker(Queue::getInstance()))->setConfig($config)->listen();
    }catch (Exception $e){
        echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
    }
    ```
    
    <br />
    
2. 以守护进程方式启动监听任务，命令格式： `<start|stop|restart|status> -[d]`
    ```php
    $queueConfig = [
        'queueName' => 'queue_name_1', //队列名称
        'attempt' => 3,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'sleep' => 3,       //每次检测的时间间隔
        'delay' => 1,       //失败后延迟的秒数重新入队列
    ];
    
    $processConfig = [
        // master 进程配置
        'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
        'maxWorkerNum' => 2,            //2个进程
    
        // worker 进程配置
        'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
        'limitSeconds' => 0,    // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制)
    ];
    
    
    // 监听命令
    (Daemon::getInstance($queueConfig))->setProcessConfig($processConfig)->listenCommand();
     
    ```
<br />

## 说明
1. 参数说明
	1. 全局配置，通过`Load::Queue($config)`加载，建议放在入口文件加载，默认配置如下：
        ```php
        $config = [
            ######################### 存储配置列表 ###########################
            'connectRegister' => [
                 'MySql' => [
                     'class' => '\\QueueTask\\Connection\\MySql\\MySql',
                     'config' => [
                         'DB_HOST'       => '127.0.0.1',     //数据库地址
                         'DB_PORT'       => '3306',          //数据库端口
                         'DB_USERNAME'   => 'root',          //用户名
                         'DB_PASSWORD'   => 'root',          //密码
                         'DB_CHARSET'    => 'utf8',          //数据库字符集
                         'DB_DATABASE'   => 'queue_task',    //数据库名称
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
             ],
        
            ######################### 当前存储方式 ###########################
            'currentConnect' => 'Redis',
     
            ######################### 日志配置 ###########################
            'Log' = [
                'LogBaseRoot' => /queue_task/runtime/log/', // 日志文件根目录(当前项目的runtime/log下)
        
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
            ],
     
            ######################### 守护进程配置 ###########################
            Process = [
                'PidRoot' => '/tmp/queue_task/pid',
                'TitlePrefix' => 'queue_task',
                'StatusFileRoot' => '/tmp/queue_task/status',
            ],
        ]
        ```
        - `connectRegister` 存储配置列表
        - `currentConnect` 当前存储方式
        - `Log` 日志配置模块
        - `Process` 守护进程配置模块
			
	2. 局部配置
