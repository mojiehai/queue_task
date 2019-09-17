<?php

namespace QueueTask\Connection\RabbitMQ;

use QueueTask\Helpers\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use QueueTask\Connection\Connection;
use QueueTask\Job;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;

/**
 * RabbitMQ链接驱动
 * Class RabbitMQ
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
    protected $host = '127.0.0.1';

    /**
     * 端口号
     * @var int
     */
    protected $port = 5672;

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
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['host']) && !empty($config['host'])) {
            $this->host = $config['host'];
        }
        if (isset($config['port']) && !empty($config['port'])) {
            $this->port = $config['port'];
        }
        if (isset($config['username']) && !empty($config['username'])) {
            $this->userName = $config['username'];
        }
        if (isset($config['password']) && !empty($config['password'])) {
            $this->password = $config['password'];
        }
        if (isset($config['vhost']) && !empty($config['vhost'])) {
            $this->vhost = $config['vhost'];
        }
        if (isset($config['exChanges']) && !empty($config['exChanges'])) {
            $this->exChanges = $config['exChanges'];
        }

    }

    /**
     * 链接
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
        try {
            if (!empty($this->channel)) {
                $this->channel->close();
            }
            if (!empty($this->connect)) {
                $this->connect->close();
            }
        } catch (\Exception $e) {
            Log::error('RabbitMQ close failed, reason: ' . $e->getMessage());
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
            $callback = function (AMQPMessage $message) use ($rabbitMq, $queueName) {
                // 任务
                $job = null;

                // 消息
                $body = $message->getBody();
                if (empty($body)) {
                    // 任务为空
                    Log::warning('RabbitMQ Pop Message Is Empty');
                } else {
                    // 解析成任务
                    $job = Job::Decode($body);
                    if ($job instanceof Job) {
                        $rabbitMq->runJob($job, $queueName);
                    } else {
                        // 消息没有解析成任务
                        Log::error('RabbitMQ Pop Message Not Job, Message: '.$body);
                    }
                }

                // 确认任务
                $rabbitMq->ack($queueName, $job, ['message' => $message]);
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
     * 确认任务
     * @param string $queueName
     * @param Job $job
     * @param array $extends
     */
    public function ack($queueName, Job $job = null, $extends = [])
    {
        /**
         * @var AMQPMessage $message
         */
        $message = $extends['message'];

        // 确认消息
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

    /**
     * 执行pop出来的任务(阻塞方法)
     * @param string $queueName
     */
    public function popRun($queueName)
    {
        $this->pop($queueName);
    }

    /**
     * 任务出队
     * @param string $queueName
     * @param array $extends
     * @return Job|void|null
     */
    public function pop($queueName, & $extends = [])
    {
        // 初始化消费者
        $this->initConsumer($queueName);
        try {

            //等待mq弹出数据,超时时间为popTimeOut
            echo '['.date('Y-m-d H:i:s').']wait...'.PHP_EOL;
            $this->channel->wait(null, false, $this->popTimeOut);
            echo '['.date('Y-m-d H:i:s').']run ok'.PHP_EOL;

        } catch (\ErrorException $error) {
            Log::error('RabbitMQ Pop Failed:'.$error->getMessage());
        } catch(AMQPRuntimeException $error) {
            Log::error('RabbitMQ AMQPRuntimeException:'.$error->getMessage());
        } catch(AMQPTimeoutException $error) {
            // 超时,说明当前队列的消费者中没有任务
        }
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
            Log::error('RabbitMQ SendMessage Failed: '.$e);
            return false;
        }
    }

    /**
     * 添加一条延迟任务
     * @param int $delay
     * @param Job $job
     * @param String $queueName
     * @return bool
     */
    public function later($delay, Job $job, $queueName)
    {
        // mq不支持延迟队列
        Log::error('RabbitMQ Not Support Later Push,delay: '.$delay.',Job: '. Job::Encode($job));
        return false;
    }

}