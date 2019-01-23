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

    'currentConnect' => 'Redis',
];
Load::Queue($config);
############################## 全局配置 ##############################
```


### 压入任务
1. 首先定义处理类，例如：TestHandler继承JobHandler，并定义任务test方法
2. test方法接收两个参数，第一个为Job对象，第二个为自定义参数
```php
class TestHandler extends JobHandler
{

    /**
     * 失败回调方法
     * @param Job $job      任务
     * @param string $func     执行的方法
     * @param array $data     参数
     * @return mixed
     */
    public function failed(Job $job, $func, $data)
    {
        \QueueTask\Log\WorkLog::info('failed run handler -- func: '.$func.' -- params: '.json_encode($data));
    }

    /**
     * 任务成功回调
     * @param Job $job      任务
     * @param string $func     执行的方法
     * @param array $data     参数
     * @return mixed
     */
    public function success(Job $job, $func, $data)
    {
        \QueueTask\Log\WorkLog::info('success run handler -- func: '.$func.' -- params: '.json_encode($data));
    }


    public function test(Job $job,$data)
    {
        \QueueTask\Log\WorkLog::info('run handler -- func: test -- params: '.json_encode($data). '; result : '.var_export($res, true));
    }

}
```
3. 压入
```php
// 获取队列对象
$queue = Queue::getInstance();

// 直接压入队列，参数：handler对象，方法，自定义参数，队列名称
$r = $queue->pushOn(new TestHandler(),'test',['test'=>'test'],'queue_name_1');

// 延迟5s压入队列，参数：延迟秒数，handler对象，方法，自定义参数，队列名称
$r = $queue->laterOn(5,new TestHandler(),'test',['test'=>'test'],'queue_name_1');
 
```
### 监听任务
1. 以普通方式启动监听任务
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
    
2. 以守护进程方式启动监听单任务，命令格式： `<start|stop|restart|status> -[d]` 对应 (`开启`/`停止`/`平滑重启`/`状态`)(`-d`为后台运行)
    ```php
    $queueConfig = [
        'queueName' => 'queue_name_1', //队列名称(字符串或者数组，如果是数组，则每次随机从数组中取一个)
        'attempt' => 3,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'sleep' => 3,       //每次检测的时间间隔
        'delay' => 1,       //失败后延迟的秒数重新入队列
    ];
    
    $processConfig = [
        // master 进程配置
        'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
        'maxWorkerNum' => 1,            //2个进程
    
        // worker 进程配置
        'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
        'limitSeconds' => 86400,    // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制)
    ];
    
    
    try {
        // 监听命令
        (SingleWorkDaemon::getInstance(
            (new Work($queueConfig))->setProcessConfig($processConfig)
        ))->listenCommand();
    
    } catch (\ProcessManage\Exception\Exception $e) {
    }
    ```
    
3. 以守护进程方式启动监听多任务，命令格式：`<start|stop|restart|status> -[n|name] -[d]` 对应 (`开启`/`停止`/`平滑重启`/`状态`)(`-d`为后台运行, `-n`可以通过队列名称手动指定操作哪个队列,如果队列参数queueName为数组，队列名称则为`implode('|', $queueNameArr)`)
    ```php
    $config = [
        'work1' => [
            'queueConfig' => [
                'queueName' => 'testQueue1', //队列名称
                'attempt' => 3,     //队列任务失败尝试次数，0为不限制
                'memory' => 128,    //允许使用的最大内存  单位:M
                'sleep' => 3,       //每次检测的时间间隔
                'delay' => 1,       //失败后延迟的秒数重新入队列
            ],
            'processConfig' => [
                // master 进程配置
                'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
                'maxWorkerNum' => 1,            //1个进程
    
                // worker 进程配置
                'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
                'limitSeconds' => 86400, // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制) (默认1天重启一次)
            ]
        ],
        'work2' => [
            'queueConfig' => [
                'queueName' => 'testQueue2', //队列名称
                'attempt' => 3,     //队列任务失败尝试次数，0为不限制
                'memory' => 128,    //允许使用的最大内存  单位:M
                'sleep' => 3,       //每次检测的时间间隔
                'delay' => 1,       //失败后延迟的秒数重新入队列
            ],
            'processConfig' => [
                // master 进程配置
                'checkWorkerInterval' => 600,   // 10分钟检测一次进程数量
                'maxWorkerNum' => 1,            //1个进程
    
                // worker 进程配置
                'executeTimes' => 0,    // 任务的最大执行次数(到次数后停止，master进程重新启动)(0为不限制)
                'limitSeconds' => 86400, // 工作进程最大执行时长(秒)(到时间后停止，master进程重新启动)(0为不限制) (默认1天重启一次)
            ]
        ]
    ];
    
    
    try {
        // 监听命令
        $multiple = MultipleWorkDaemon::getInstance();
        $multiple->addWork(
            (new Work($config['work1']['queueConfig']))->setProcessConfig($config['work1']['processConfig'])
        );
        $multiple->addWork(
            (new Work($config['work2']['queueConfig']))->setProcessConfig($config['work2']['processConfig'])
        );
        $multiple->listenCommand();
    
    } catch (\ProcessManage\Exception\Exception $e) {
    }
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
             ],
        
            ######################### 当前存储方式 ###########################
            'currentConnect' => 'Redis',
     
            ######################### 日志配置 ###########################
            'Log' => [
                'LogBaseRoot' => __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'log', 
        
                'Debug_FileNamePrefix' => '',
                'Info_FileNamePrefix' => '',
                'Notice_FileNamePrefix' => '',
                'Warning_FileNamePrefix' => '',
                'Error_FileNamePrefix' => 'error_',
                'Fatal_FileNamePrefix' => 'fatal_',
        
                'LogFileName' => 'work',
                'LogDeLimiterRule' => 'Y-m-d',
                'ProcessLogFileName' => 'queue_task_run',
                'ProcessLogDeLimiterRule' => 'Y-m-d',
            ],
     
            ######################### 守护进程配置 ###########################
            'Process' => [
                'PidRoot' => '/tmp/queue_task/pid',
                'StatusFileRoot' => '/tmp/queue_task/status',
            ],
        ]
        ```
        > 注：上述所列都是各项配置的默认值，会局部覆盖原配置数组，而不是直接替换
        - `connectRegister`：存储配置列表，自定义存储结构可在这里添加
            - `class`：存储类名，需要继承`QueueTask\Connection\Connection`
            - `config`：`class`指定类的构造参数
        - `currentConnect`：当前存储方式(可选值为`connectRegister`中的键，默认`Redis`)
        - `Log`：日志配置模块
            - `LogBaseRoot`：日志文件根目录，默认为当前组件的runtime/log下
            - `xxx_FileNamePrefix`：日志级别对应的文件名前缀
            - `LogFileName`：普通日志文件默认文件名
            - `LogDeLimiterRule`：普通日志文件分隔规则，默认按天
            - `ProcessLogFileName`：进程日志文件默认文件名
            - `ProcessLogDeLimiterRule`：进程日志文件分隔规则，默认按天
        - `Process`：守护进程配置模块
            - `PidRoot`：进程pid文件的根目录
            - `TitlePrefix`：进程名称前缀
            - `StatusFileRoot`：进程状态文件的根目录
			
	2. 局部配置，当前进程所需要的配置
	    1. 队列配置
	        - `queueName`：队列名称，默认：`default`
	        - `attempt`：队列任务最大执行的次数(失败则重试，直到最大次数)，0为不限制（无限重试），默认：`10`
	        - `memory`：允许使用的最大内存，单位:M，默认：`128`
	        - `sleep`：每次检测的时间间隔(如果当前队列没有任务，则会等待该时长后再次尝试出队)，默认：`3`
	        - `delay`：失败后延迟的秒数重新加入队列(任务失败后，会压入一个延迟任务，如果该值设置成0，则会压入一个及时队列)，默认：`0`
		- `maxRunTime`：最大执行时间，超出该时间则退出监听，0为不限制，默认：`0`。`注：此参数只在非守护进程模式有效`
	        
	    2. 守护进程配置  
	        进程基础配置  
	        - `baseTitle`: 进程基础名称(不带前缀的部分)，默认为当前队列名称，推荐默认
	        
	        master进程
	        - `checkWorkerInterval`：n秒检测一次工作进程数量，默认：`600`
	        - `maxWorkerNum`：工作进程个数，默认：`1`
	        
	        worker进程
	        - `executeTimes`：任务的最大执行次数(到次数后退出进程，master进程重新启动)(0为不限制)，默认：`0`
	        - `limitSeconds`：工作进程最大执行时长(秒)(到时间后退出进程，master进程重新启动)(0为不限制)，默认：`86400`
	        - `executeUSleep`：每次工作任务中间睡眠时长(微秒)，默认：`0`

