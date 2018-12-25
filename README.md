# queue_task
PHP队列任务管理器，支持多种存储方式

<br />

## 业务场景
在实际业务场景中，我们可能需要执行一些耗时操作，例如：发送邮件等。但这样的操作并不适合放在主流程中，则可以使用任务队列来异步处理任务。

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
    
2. 以守护进程方式启动监听任务，命令模板： `<start|stop|restart|status> -[d]`
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
        - connectRegister 存储配置列表
			
	2. 非固定配置（通过manage构造函数加载进去，作用域为本次manage管理的进程）

		| 配置项              | 描述                                                                                                                         | 类型   | 是否必填 | 默认值               |
		| ------------------- | ---------------------------------------------------------------------------------------------------------------------------- | ------ | -------- | -------------------- |
		| titlePrefix         | 进程名称前缀，优先级大于固定配置                                                                                             | string | 否       | (默认读取固定配置值) |
		| baseTitle           | 进程基础名称，用来区分多个进程管理器                                                                                         | string | 否       | process              |
		| checkWorkerInterval | master：检查工作进程的时间间隔，单位：秒                                                                                     | int    | 否       | 300                  |
		| maxWorkerNum        | master：最大工作进程数                                                                                                       | int    | 否       | 4                    |
		| executeTimes        | worker：工作进程最大工作次数(即工作回调最大回调次数) 0为无限循环执行，(执行完指定次数后退出子进程，等待master进程重启子进程) | int    | 否       | 1                    |
		| executeUSleep       | worker：工作进程每次执行后睡眠时间 单位：微秒数  0为不睡眠                                                                   | int    | 否       | 200000               |
		| limitSeconds        | worker：工作进程最大执行时长 单位：秒 0为不限制(执行完指定次数后退出子进程，等待master进程重启子进程)                        | int    | 否       | 0                    |

<br />

2. 方法说明
	- Manage类
		
		| 方法名                                         | 参数说明           | 返回值 | 描述                                                                                                                                                                                                                                  |
		| ---------------------------------------------- | ------------------ | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
		| setBackground()                      | 无                 | Manage | 设置为后台运行，该方法执行完毕后，当前进程就会脱离终端，成为init进程的子进程。                                                                                                                                                        |
		| setWorkInit(\Closure $closure = null) | $closure：回调函数 | Manage | 设置工作进程初始化的回调方法，这个回调方法会在worker进程对象初始化完成后调用。一般该回调方法中初始化一些资源数据，例如数据库连接，给当前worker进程的工作回调使用。该回调方法接收一个参数，为当前的worker进程对象(Worker)。(示例见 [1.0](#s1.0) ) |          
		| setWork(\Closure $closure = null)     | $closure：回调函数 | Manage | 设置工作进程工作回调，该回调会在setWorkInit设置的初始化回调后调用。该回调方法接收两个参数：第一个为当前的worker进程对象(Worker)，第二个为工作进程初始化的回调方法的返回值。(示例见 [1.1](#s1.1) )          |
		| start()                                | 无                 | 无     | 启动任务                                                                                                                                                                                                                              |
		| stop()                                 | 无                 | 无     | 停止任务                                                                                                                                                                                                                              |
		| restart()                             | 无                 | 无     | 重启任务                                                                                                                                                                                                                              |
		| status()                              | 无                 | array  | 进程状态数组                                                                                                                                                                                                         |
		| showStatus()                          | 无                 | 无     | 格式化显示进程状态信息 (说明见 [1.2](#s1.2) )                                                                                                                                                                                                          |
	
			
	- Process类
	
        | 方法名                                          | 参数说明           | 返回值  | 描述                               |
        | ----------------------------------------------- | ------------------ | ------- | ---------------------------------- |
        | resetPid()                               | 无                 | 无      | 重设pid(不需要手动调用)            |
        | setWorkInit(\Closure $closure = null)  | $closure：回调函数 | Process | 设置工作初始化回调(不需要手动调用) |
        | setWork(\Closure $closure = null)      | $closure：回调函数 | Process | 设置工作回调(不需要手动调用)       |
        | setStop()                                 | 无                 | 无      |  给当前进程对象发送停止信号              |
        | isExpectStop()                            | 无                 | bool    | 判断当前进程是否准备停止           |
        | isRun()                 | 无                 | 无      | 判断当前进程是否为正在运行状态           |
        | run()                                     | 无                 | 无      | 开始运行(不需要手动调用)           |
        | isAlive()                              | 无                 | bool    | 检测当前进程对象是否存在               |
        | static CheckAlive(int $pid)                  | $pid：进程pid       | bool    | 检测进程是否存在                   |

	- Worker类(继承Process类)
	
		| 方法名                  | 参数说明 | 返回值 | 描述 |
        | ----------------------- | -------- | ------ | ---- |
        | getExecuteTimes()  | 无       | int    |  获取当前执行次数 |

	- Master类(继承Process类)
	
	    | 方法名 | 参数说明 | 返回值 | 描述 | 
        | ------- | -------- | ------ | ---- |
        | getAllStatus() | 无 | array |  获取所有进程状态信息 |

<br />

3. 示例或说明
	- <a name='s1.0'>1.0</a>  
		```php
		(new Manage($config))->setWorkInit(
		    // 工作内容初始化
		    function (Worker $process) {
			// init
			$link = mysqli_connect(...);
			...
			$redis = new Redis(...);
			...
			return ['mysql' => $link, 'redis' => $redis];
		    }
		 )
		```
	- <a name='s1.1'>1.1</a>  
		```php
		(new Manage($config))->setWork(
		    // 执行的工作内容
		    function(Worker $process, $result = []) {
			// work
			$mysqlLink = $result['mysql'];
			$redisLink = $result['redis'];
		    })
		 )
		```
	- <a name='s1.2'>1.2</a>  
		```
		[root@localhost command]# php cmd.php status
		Master
		  type      pid      title                    memory(m)         start                  run(s)    count
		  Master    29570    process_m:Master:test    0.661(693296b)    2018-12-23 17:29:01    6         2           

		Worker
		  type      pid      title                    memory(m)         start                  run(s)    work
		  Worker    29571    process_m:Worker:test    0.661(692760b)    2018-12-23 17:29:01    6         1         
		  Worker    29572    process_m:Worker:test    0.661(693608b)    2018-12-23 17:29:01    6         1         
		```
		字段说明：(Master表示主进程，Worker表示工作进程)
		- `type`：进程类型说明(Master/Worker)
		- `pid`：进程pid
		- `title`：进程名称
		- `memory`：内存消耗，单位：M，括号中的为字节数
		- `start`：进程开始时间
		- `run`：运行时长，单位：秒
		- `count`：(Master进程独有属性)当前子进程个数
		- `work`：(Worker进程独有属性)当前进程执行任务回调的次数


