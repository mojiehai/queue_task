<?php

namespace QueueTask\Connection\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use QueueTask\Helpers\Log;
use QueueTask\Job;

/**
 * 生产者
 * Class Producer
 * @package QueueTask\Connection\RabbitMQ
 */
class Producer extends OpenConnect
{

    /**
     * 当前初始化的queueName
     * @var string
     */
    protected $initQueueName = '';

    /**
     * 入队最大尝试次数(发生错误会重试入队)
     * @var int
     */
    protected $pushMaxTryTimes = 3;

    /**
     * 初始化队列
     * @param string $queueName
     * @param bool $force
     */
    protected function initQueue($queueName, $force = false)
    {
        if (
            $force ||                               // 强制初始化
            ($this->initQueueName != $queueName)    // 当前初始化的队列不是参数指定的队列，需要重新初始化
        ) {
            parent::initQueue($queueName, $force);
            $this->initQueueName = $queueName;
        }
    }

    /**
     * 压入队列
     * @param Job $job
     * @param string $queueName 队列名
     * @param int $times 次数
     * @return boolean
     */
    public function push(Job $job, $queueName, $times = 1)
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
            if ($times < $this->pushMaxTryTimes) {
                // 异常则压入出错
                Log::warning('RabbitMQ SendMessage Failed(time:'.$times.'): '.$e);
                // 重新连接
                $this->initQueue($queueName, true);
                return $this->push($job, $queueName, $times + 1);
            } else {
                // 异常则压入出错
                Log::error('RabbitMQ SendMessage Failed(time:'.$times.'): '.$e);
                return false;
            }
        }
    }


}