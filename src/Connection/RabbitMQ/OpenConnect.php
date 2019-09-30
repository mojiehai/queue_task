<?php


namespace QueueTask\Connection\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use QueueTask\Helpers\Log;

/**
 * 链接
 * class OpenConnect
 * @package QueueTask\Connection\RabbitMQ
 */
abstract class OpenConnect
{
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
     * 队列关键字
     * @var string
     */
    protected $routingKey = '';

    /**
     * 直连交换机名称
     * @var string
     */
    protected $exChanges = '';

    public function __construct($config)
    {
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
     * @param bool $force 是否强制重连
     */
    protected function open($force = false)
    {
        if ($force || empty($this->connect)) {
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
     * @param string $queueName
     * @param bool $force 是否强制初始化 (强制初始化还会初始化链接)
     */
    protected function initQueue($queueName, $force = false)
    {

        if (empty($queueName)) {
            return;
        }

        $this->open($force);

        // 关键字使用队列名称
        $this->routingKey = $this->exChanges . '.' . $queueName;

        // 声明初始化交换机
        $this->channel->exchange_declare($this->exChanges, 'direct', false, true, false);

        // 声明队列
        $this->channel->queue_declare($queueName, false, true, false, false);

        // 将队列与某个交换机进行绑定，并使用路由关键字
        $this->channel->queue_bind($queueName, $this->exChanges, $this->routingKey);

    }

}