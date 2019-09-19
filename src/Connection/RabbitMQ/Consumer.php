<?php


namespace QueueTask\Connection\RabbitMQ;

use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use QueueTask\Connection\Connection;
use QueueTask\Helpers\Log;
use QueueTask\Job;

/**
 * 消费者
 * Class Consumer
 * @package QueueTask\Connection\RabbitMQ
 */
class Consumer extends OpenConnect
{

    /**
     * 当前初始化的queueName
     * @var string
     */
    protected $initQueueName = '';

    /**
     * @var RabbitMQ
     */
    protected $rabbitMq = null;

    public function __construct($config, Connection $rabbitMq)
    {
        parent::__construct($config);
        $this->rabbitMq = $rabbitMq;
    }

    /**
     * 初始化队列
     * @param string $queueName
     * @param bool $force 是否强制初始化
     */
    protected function initQueue($queueName, $force = false)
    {
        if (
            $force ||                               // 强制初始化
            ($this->initQueueName != $queueName)    // 当前初始化的队列不是参数指定的队列，需要重新初始化
        ) {
            // 初始化队列
            parent::initQueue($queueName, $force);

            // rabbit Connection对象
            $rabbitMq = $this->rabbitMq;

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
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            };

            // 在处理并确认前一个消息之前，不要向消费者发送新消息
            $this->channel->basic_qos(null, 1, null);

            // 配置消费者 no_ack:false需要手动确认消息完成
            $this->channel->basic_consume($queueName, '', false, false,
                false, false, $callback);

            $this->initQueueName = $queueName;
        }

    }

    /**
     * 任务出队 阻塞
     * @param $queueName
     * @return void
     */
    public function pop($queueName)
    {
        // 初始化队列
        $this->initQueue($queueName);
        try {

            //等待mq弹出数据,超时时间为popTimeOut
            $this->channel->wait(null, false, $this->rabbitMq->popTimeOut);

        } catch (\ErrorException $error) {

            // 重新连接
            $this->initQueue($queueName, true);

            Log::error('RabbitMQ Pop Failed:'.$error->getMessage());

        } catch(AMQPRuntimeException $error) {

            // 重新连接
            $this->initQueue($queueName, true);

            Log::error('RabbitMQ AMQPRuntimeException:'.$error->getMessage());

        } catch(AMQPTimeoutException $error) {
            // 超时,说明当前队列的消费者中没有任务
        }
    }

}