<?php

namespace QueueTask\Connection\Mns;

use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Queue;
use AliyunMNS\Responses\ReceiveMessageResponse;
use QueueTask\Connection\Connection;
use QueueTask\Job;
use AliyunMNS\Client;
use QueueTask\Helpers\Log;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Exception\MnsException;

/**
 * mns链接驱动
 * Class Mns
 */
class Mns extends Connection
{

    /**
     * mns单例对象
     * @var Mns
     */
    protected static $instance = null;

    /**
     * Mns 客户端对象
     * @var Client
     */
    protected static $client = null;

    /**
     * Mns key id
     * @var string
     */
    protected $accessKeyID = '';

    /**
     * Mns key secret
     * @var string
     */
    protected $accessKeySecret = '';

    /**
     * Mns end point
     * @var string
     */
    protected $endpoint = '';

    /**
     * 入队最大尝试次数(发生错误会重试入队)
     * @var int
     */
    private $pushMaxTryTimes = 3;

    /**
     * 删除消息最大尝试次数(发生错误会重试删除)
     * @var int
     */
    private $deleteMaxTryTimes = 3;

    /**
     * Mns constructor.
     * @param array $config
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['accessKeyID']) && !empty($config['accessKeyID'])) {
            $this->accessKeyID = $config['accessKeyID'];
        }
        if (isset($config['accessKeySecret']) && !empty($config['accessKeySecret'])) {
            $this->accessKeySecret = $config['accessKeySecret'];
        }
        if (isset($config['endpoint']) && !empty($config['endpoint'])) {
            $this->endpoint = $config['endpoint'];
        }
        if (isset($config['pushMaxTryTimes'])) {
            $this->pushMaxTryTimes = intval($config['pushMaxTryTimes']);
        }
        if (isset($config['deleteMaxTryTimes'])) {
            $this->deleteMaxTryTimes = intval($config['deleteMaxTryTimes']);
        }
    }

    /**
     * 创建Mns实例
     */
    private function open()
    {
        if (empty(self::$client)) {
            self::$client = new Client($this->endpoint, $this->accessKeyID, $this->accessKeySecret);
        }
    }

    /**
     * 关闭连接
     * @return boolean
     */
    public function close()
    {
        self::$client = null;
        return true;
    }

    /**
     * 弹出队头任务(blocking)
     * @param string $queueName
     * @param array $extends
     * @return Job|null
     */
    public function pop($queueName, & $extends = [])
    {
        $this->open();

        // 不需要对消息做base64操作,Job自己会处理
        $queue = self::$client->getQueueRef($queueName, false);

        // 获取消息
        $response = $this->receiveMessage($queue);
        if (empty($response)) {
            return null;
        }

        // 消息内容
        $messageBody = $response->getMessageBody();

        // 给ack动作传递参数
        $extends = [
            'queue' => $queue,
            'response' => $response,
        ];

        // 消息删除成功，生成Job返回
        return Job::Decode($messageBody);

    }

    /**
     * 确认任务
     * @param string $queueName
     * @param Job $job
     * @param array $extends
     */
    public function ack($queueName, Job $job = null, $extends = [])
    {
        // 删除消息
        $this->deleteMessage($extends['queue'], $extends['response']);
    }


    /**
     * 获取消息响应对象
     * @param Queue $queue
     * @return ReceiveMessageResponse|null
     */
    private function receiveMessage(Queue $queue)
    {
        try {
            // 获取消息，参数为等待时长，设置为0就是立即返回，大于0该方法就为阻塞方法
            return $queue->receiveMessage($this->popTimeOut);
        } catch (MessageNotExistException $e) {
            // 消息不存在，直接返回null
            return null;
        } catch (MnsException $e) {
            Log::warning('Mns ReceiveMessage Failed: '.$e);
            return null;
        }
    }

    /**
     * 删除消息
     * @param Queue $queue
     * @param ReceiveMessageResponse $response
     * @param int $times 当前尝试的次数
     * @return bool
     */
    private function deleteMessage(Queue $queue, ReceiveMessageResponse $response, $times = 1)
    {
        try {
            // 获取ReceiptHandle，这是一个有时效性的Handle，可以用来设置Message的各种属性和删除Message。
            $receiptHandle = $response->getReceiptHandle();

            // 使用这个时效性的Handle删除Message
            $queue->deleteMessage($receiptHandle);
            return true;
        } catch (MnsException $e) {

            // 是否重试
            if ($e->getMnsErrorCode() == 'MessageNotExist') {
                // 如果是receiptHandle已经过期，那么ErrorCode是MessageNotExist，表示通过这个receiptHandle已经找不到对应的消息。
                // 所以此区间尝试也没有用,所以不用尝试
                $isReTry = false;
            } else {
                $isReTry = true;
            }

            if ($isReTry && $times < $this->deleteMaxTryTimes) {
                // 重试
                Log::warning('Mns DeleteMessage Failed(times: '.$times.'): '.$e);
                $times ++;
                return $this->deleteMessage($queue, $response, $times);
            } else {
                // 不需要重试的或者重试完成还是报错的错误直接记录
                Log::error('Mns DeleteMessage Failed(times: '.$times.',no longer try): '.$e);
                return false;
            }
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
        return $this->pushMns($queueName, $job, 0);
    }

    /**
     * 添加一条延迟任务
     * @param int $delay 延迟的秒数
     * @param Job $job 任务
     * @param String $queueName 队列名
     * @return boolean
     */
    public function later($delay, Job $job, $queueName)
    {
        return $this->pushMns($queueName, $job, $delay);
    }


    /**
     * 推送消息到Mns
     * @param string $queueName 队列名称
     * @param Job $job 任务对象
     * @param int|null $delay 消息的DelaySeconds参数(延迟消费时间),如果为null，则延迟时间由aliyun控制台决定
     * @param int $times 当前尝试的次数
     * @return bool
     */
    private function pushMns($queueName, Job $job, $delay = null, $times = 1)
    {
        $this->open();

        // 不需要对消息做base64操作,Job自己会处理
        $queue = self::$client->getQueueRef($queueName, false);

        // 消息体
        $messageBody = Job::Encode($job);

        // 发送消息对象
        $sendMessage = new SendMessageRequest($messageBody, $delay);

        try {

            // 消息发送成功
            $queue->sendMessage($sendMessage);
            return true;

        } catch (MnsException $e) {
            // 可能因为网络错误，或MessageBody过大等原因造成发送消息失败

            // 是否重试
            $isReTry = false;
            if ($e->getMnsErrorCode() == 'ServerError') {
                $isReTry = true;
            } else if (strpos($e->getMessage(), 'cURL error 28: Operation timed out') !== false) {
                $isReTry = true;
            }

            if ($isReTry && $times < $this->pushMaxTryTimes) {
                // 重试
                Log::warning('Mns SendMessage Failed(times: '.$times.'): '.$e);
                $times ++;
                return $this->pushMns($queueName, $job, $delay, $times);
            } else {
                // 不需要重试的或者重试完成还是报错的错误直接记录并报警
                Log::error('Mns SendMessage Failed(times: '.$times.',no longer try): '.$e);
                return false;
            }
        }
    }
}