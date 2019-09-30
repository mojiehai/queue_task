# queue_task
PHP队列任务管理器，支持多种存储方式

<br />

## 业务场景
在实际业务场景中，我们可能需要执行一些耗时操作，例如：发送邮件等。但这样的操作并不适合放在主流程中，则可以使用队列来异步处理任务。

<br />

## 简述
基于php-cli模式多进程的生产者-消费者模式，自定义存储介质，也可用系统内置存储：Redis/Mns/RabbitMq。
      
<br />
      
## 依赖
##### 必须
- php: `>=7.0`
- aliyun/aliyun-mns-php-sdk: `^1.1`
- php-amqplib/php-amqplib: `^2.10`
- ext-redis: `*`
- ext-json: `*`
##### 非必须(多进程模式必须)
- ext-pcntl: `*`
- ext-posix: `*`
- swoole: `>=1.10.3`
 
<br />
 
## 安装
> linux：`composer require mojiehai/queue_task`  

> windows：`composer require mojiehai/queue_task --ignore-platform-reqs`  (忽略环境检查)(windows不支持多进程)

<br />

## 使用
### 全局配置
```php
############################## 全局配置 ##############################
$config = [
    'log' => [
        'logRoot' => __DIR__ . '/../runtime/log',   // 日志文件根目录
        'fileName' => '\q\u\e\u\e_Y-m-d.\l\o\g',    // 日志文件分割规则(date()函数第一个参数)
    ],
  
    'connectList' => [
        'Redis' => [
            'class' => '\\QueueTask\\Connection\\Redis\\Redis',
            'config' => [   // 应用初始化的参数
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
            'config' => [   // 应用初始化的参数
                'popTimeout'        => 3,           // pop阻塞的超时时长 s
                'accessKeyID'       => '',          // Mns key id
                'accessKeySecret'   => '',          // Mns key secret
                'endpoint'          => '',          // Mns end point
            ],
        ],
        'RabbitMQ' => [
            'class' => '\\QueueTask\\Connection\\RabbitMQ\\RabbitMQ',
            'config' => [   // 应用初始化的参数
                // exchanges需要设置为direct，持久化存储，不自动确认消息
                'popTimeout'    => 3,               // pop阻塞的超时时长 s
                'host'          => '127.0.0.1',
                'port'          => 5672,
                'username'      => '',
                'password'      => '',
                'vhost'         => '/',           // 虚拟主机
                'exChanges'     => '',            // 直连交换机名称
            ],
        ],
    ],

    'currentConnect' => 'RabbitMQ',         // 当前使用的应用类型
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
$r = $queue->pushOn(new TestHandler(),'test',['test'=>'test'],'testQueue');

// 延迟5s压入队列(部分队列不支持延迟操作，例如rabbitmq)，参数：延迟秒数，handler对象，方法，自定义参数，队列名称
$r = $queue->laterOn(5,new TestHandler(),'test',['test'=>'test'],'testQueue');
 
```
### 监听任务
1. 以普通方式启动监听任务(单进程)
    ```php
    $config = [
        'queueName' => 'testQueue', //队列名称
        'attempt' => 3,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'maxRunTime' => 100,    // 最大运行时间 100s 0为不限制(单进程模式建议设置为0，否则需要手动定时拉取)
    ];
    
    try{
        (new Worker($config))->listen();
    }catch (Exception $e){
        echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
    }
    ```
    
    <br />
    
2. 以守护进程方式启动监听任务(多进程)
    ```php
    $config1 = [
        'queueName' => 'testQueue', //队列名称
        'attempt' => 3,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'maxRunTime' => 100,    // 最大运行时间 100s     (多进程模式建议设置为进程重启的间隔时间，例如，需要1个小时重启一次，则设置为3600)
    ];
    
    $config2 = [
        'queueName' => 'testQueue1', //队列名称
        'attempt' => 3,     //队列任务失败尝试次数，0为不限制
        'memory' => 128,    //允许使用的最大内存  单位:M
        'maxRunTime' => 100,    // 最大运行时间 100s
    ];
    
    
    try {
        (new MultiWorker('tag1'))          // tag1为唯一标识，不同任务组使用不同标识
                ->addWorker($config1, 1)   // 第二个参数为进程数
                ->addWorker($config2, 2)
                ->start();
    
    } catch (Exception $e) {
    }
    ```
    
<br />

