<?php

namespace app\common\queue\connect\rabbit_mq;

use core\log\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use QueueTask\Connection\Connection;
use QueueTask\Job\Job;
use QueueTask\Exception\DBException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;

/**
 * RabbitMQ链接驱动
 * Class RabbitMQ
 * @package app\common\queue\connect\rabbit_mq
 * @author pengshuhai
 */
class RabbitMQ extends Connection
{
    /**
     * RabbitMQ单例对象
     * @var RabbitMQ
     */
    protected static $instance = null;

    /**
     * 服务器链接对象
     * @var AMQPStreamConnection
     */
    protected $connect = null;

    /**
     * 通道对象
     * @var AMQPChannel
     */
    protected $channel = null;

    /**
     * 主机ip或名称
     * @var string
     */
    protected $host = '';

    /**
     * 端口号
     * @var string
     */
    protected $port = '';

    /**
     * 用户名
     * @var string
     */
    protected $userName = '';

    /**
     * 密码
     * @var string
     */
    protected $password = '';

    /**
     * 虚拟主机
     * @var string
     */
    protected $vhost = '';

    /**
     * 直连交换机名称
     * @var string
     */
    protected $exChanges = '';

    /**
     * 队列pop弹出的任务
     * @var Job
     */
    private $job = null;

    /**
     * 消费者是否初始化完成
     * @var bool
     */
    protected $isInitConsumer = false;

    /**
     * 消费者当前监听的队列
     * @var string
     */
    protected $consumerQueueName = '';

    /**
     * 当前队列路由关键字
     * @var string
     */
    protected $routingKey = '';

    /**
     * RabbitMQ constructor.
     * @param array $config
     * @throws DBException
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['HOST']) && !empty($config['HOST'])) {
            $this->host = $config['HOST'];
        }
        if (isset($config['PORT']) && !empty($config['PORT'])) {
            $this->port = $config['PORT'];
        }
        if (isset($config['USERNAME']) && !empty($config['USERNAME'])) {
            $this->userName = $config['USERNAME'];
        }
        if (isset($config['PASSWORD']) && !empty($config['PASSWORD'])) {
            $this->password = $config['PASSWORD'];
        }
        if (isset($config['VHOST']) && !empty($config['VHOST'])) {
            $this->vhost = $config['VHOST'];
        }
        if (isset($config['EX_CHANGES']) && !empty($config['EX_CHANGES'])) {
            $this->exChanges = $config['EX_CHANGES'];
        }

        if (empty($this->host)) {
            throw new DBException("RabbitMQ Init Error: config 'HOST' is empty");
        }
        if (empty($this->port)) {
            throw new DBException("RabbitMQ Init Error: config 'PORT' is empty");
        }
        if (empty($this->userName)) {
            throw new DBException("RabbitMQ Init Error: config 'USERNAME' is empty");
        }
        if (empty($this->password)) {
            throw new DBException("RabbitMQ Init Error: config 'PASSWORD' is empty");
        }
        if (empty($this->vhost)) {
            throw new DBException("RabbitMQ Init Error: config 'VHOST' is empty");
        }
        if (empty($this->exChanges)) {
            throw new DBException("RabbitMQ Init Error: config 'EX_CHANGES' is empty");
        }
    }

    /**
     * 惰性链接
     */
    private function open()
    {
        if (empty($this->connect)) {
            // 初始化链接
            $this->connect = new AMQPStreamConnection($this->host, $this->port, $this->userName,
                $this->password, $this->vhost);
            // 创建通道
            $this->channel = $this->connect->channel();
        }
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        if (!empty($this->channel)) {
            $this->channel->close();
        }
        if (!empty($this->connect)) {
            $this->connect->close();
        }
        return true;
    }

    /**
     * 初始化队列
     * @param $queueName
     */
    private function initQueue($queueName)
    {
        // 关键字使用队列名称
        $this->routingKey = $this->exChanges.'.'.$queueName;

        $this->open();

        // 声明初始化交换机
        $this->channel->exchange_declare($this->exChanges, 'direct', false, true, false);

        // 声明队列
        $this->channel->queue_declare($queueName, false, true, false, false);

        // 将队列与某个交换机进行绑定，并使用路由关键字
        $this->channel->queue_bind($queueName, $this->exChanges, $this->routingKey);
    }

    /**
     * 初始化消费者
     * @param string $queueName
     */
    private function initConsumer($queueName)
    {
        if (!$this->isInitConsumer || $this->consumerQueueName != $queueName) {
            // 初始化队列
            $this->initQueue($queueName);

            // 当前对象
            $rabbitMq = $this;

            // 消费回调
            $callback = function (AMQPMessage $message) use ($rabbitMq) {
                // 消息内容
                $body = $message->getBody();
                if (empty($body)) {
                    // 任务为空
                    $rabbitMq->job = null;
                    Log::error('RabbitMQ Pop Message Is Empty', 'rabbit_mq', 'pop');
                } else {
                    // 解析成任务
                    $rabbitMq->job = Job::Decode($body);
                    if (!($rabbitMq->job instanceof Job)) {
                        // 消息没有解析成任务
                        Log::error('RabbitMQ Pop Message Not Job, Message: '.$body, 'rabbit_mq', 'pop');
                        $rabbitMq->job = null;
                    }
                }
                // 手动确认消息
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            };

            // 在处理并确认前一个消息之前，不要向消费者发送新消息
            $this->channel->basic_qos(null, 1, null);

            // 配置消费者 no_ack:false需要手动确认消息完成
            $this->channel->basic_consume($queueName, '', false, false,
                false, false, $callback);

            $this->isInitConsumer = true;
            $this->consumerQueueName = $queueName;
        }
    }

    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job|null
     */
    public function pop($queueName)
    {
        // 初始化消费者
        $this->initConsumer($queueName);

        try {

            //等待mq弹出数据,超时时间为0.01s
            $this->channel->wait(null, false, 0.01);

        } catch (\ErrorException $error) {
            Log::error('RabbitMQ Pop Failed:'.$error->getMessage(), 'rabbit_mq', 'pop');
            return null;
        } catch(AMQPRuntimeException $error) {
            Log::error('RabbitMQ AMQPRuntimeException:'.$error->getMessage(), 'rabbit_mq', 'pop');
            return null;
        } catch(AMQPTimeoutException $error) {
            // 超时,说明当前队列的消费者中没有任务
            return null;
        }

        return $this->job;
    }

    /**
     * 压入队列
     * @param Job $job
     * @param String $queueName 队列名
     * @return boolean
     */
    public function push(Job $job, $queueName)
    {
        try {
            // 初始化队列
            $this->initQueue($queueName);

            // 发送消息 声明持久消息
            $message = new AMQPMessage(
                Job::Encode($job),
                ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );
            $this->channel->basic_publish($message, $this->exChanges, $this->routingKey);

            return true;

        } catch (\Exception $e) {
            // 异常则压入出错
            Log::fatal('RabbitMQ SendMessage Failed: '.$e, 'rabbit_mq', 'push');
            return false;
        }
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @param String $queueName 队列名
     * @return boolean
     */
    public function laterOn($delay, Job $job, $queueName)
    {
        // 不支持延迟队列
        Log::error('RabbitMQ Not Support Later Push,delay: '.$delay.',Job: '. Job::Encode($job), 'rabbit_mq', 'laterOn');
        return $this->push($job, $queueName);
    }
}