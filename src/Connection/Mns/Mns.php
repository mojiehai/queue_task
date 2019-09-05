<?php

namespace app\common\queue\connect\mns;

// 加载aliyun sdk
require_once __DIR__.DIRECTORY_SEPARATOR.'ali_mns_sdk'.DIRECTORY_SEPARATOR.'mns-autoloader.php';

use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Queue;
use AliyunMNS\Responses\ReceiveMessageResponse;
use core\log\Log;
use QueueTask\Connection\Connection;
use QueueTask\Exception\DBException;
use QueueTask\Job\Job;
use AliyunMNS\Client;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Exception\MnsException;

/**
 * mns链接驱动
 * Class Mns
 * @package app\common\queue\connect\mns
 * @author pengshuhai
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
     * 入队最大尝试次数(第一次不算重试)
     * @var int
     */
    private $pushMaxTryTimes = 5;

    /**
     * 删除消息最大尝试次数(第一次不算重试)
     * @var int
     */
    private $deleteMaxTryTimes = 5;

    /**
     * Mns constructor.
     * @param array $config
     * @throws DBException
     */
    protected function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['AccessKeyID']) && !empty($config['AccessKeyID'])) {
            $this->accessKeyID = $config['AccessKeyID'];
        }
        if (isset($config['AccessKeySecret']) && !empty($config['AccessKeySecret'])) {
            $this->accessKeySecret = $config['AccessKeySecret'];
        }
        if (isset($config['Endpoint']) && !empty($config['Endpoint'])) {
            $this->endpoint = $config['Endpoint'];
        }
        if (isset($config['PushMaxTryTimes'])) {
            $this->pushMaxTryTimes = $config['PushMaxTryTimes'];
        }
        if (isset($config['DeleteMaxTryTimes'])) {
            $this->deleteMaxTryTimes = $config['DeleteMaxTryTimes'];
        }


        if (empty($this->accessKeyID)) {
            throw new DBException("Mns Init Error: config 'AccessKeyID' is empty");
        }
        if (empty($this->accessKeySecret)) {
            throw new DBException("Mns Init Error: config 'AccessKeySecret' is empty");
        }
        if (empty($this->endpoint)) {
            throw new DBException("Mns Init Error: config 'Endpoint' is empty");
        }

    }

    /**
     * 创建Mns实例
     * @author PengShuHai<pengshuhai@jybdshop.cn>
     * @date 2019/2/21 11:46:09
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
        if (!empty(self::$client)) {
            self::$client = null;
        }
        return true;
    }

    /**
     * 弹出队头任务(先删除后返回该任务)
     * @param $queueName
     * @return Job|null
     */
    public function pop($queueName)
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

        // 删除消息
        $this->deleteMessage($queue, $response);

        // 消息删除成功，生成Job返回
        return Job::Decode($messageBody);
    }

    /**
     * 获取消息响应对象
     * @param Queue $queue
     * @return ReceiveMessageResponse|null
     */
    private function receiveMessage(Queue $queue)
    {
        try {
            // 获取消息，等待时长设置为0，不用http long polling，
            // 如果使用http long polling，则该方法为阻塞方法，但是不想让此方法阻塞
            return $queue->receiveMessage(0);
        } catch (MessageNotExistException $e) {
            // 消息不存在，直接返回null
            return null;
        } catch (MnsException $e) {
            Log::warning('Mns ReceiveMessage Failed: '.$e, 'mns', 'pop');
            return null;
        }
    }

    /**
     * 删除消息
     * @param Queue $queue
     * @param ReceiveMessageResponse $response
     * @param int $retry 当前重新尝试次数
     * @return bool
     */
    private function deleteMessage(Queue $queue, ReceiveMessageResponse $response, $retry = 0)
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

            // 删除消息次数 = 重试次数+1
            $times = $retry + 1;
            if ($isReTry && $retry < $this->deleteMaxTryTimes) {
                // 重试
                Log::warning('Mns DeleteMessage Failed(times: '.$times.'): '.$e, 'mns', 'delete');
                $retry ++;
                return $this->deleteMessage($queue, $receiptHandle, $retry);
            } else {
                // 不需要重试的或者重试完成还是报错的错误直接记录
                Log::error('Mns DeleteMessage Failed(times: '.$times.',no longer try): '.$e, 'mns', 'delete');
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
        // 在这里不设置强制延迟时间0s,根据aliyun控制台配置决定
        return $this->pushMns($queueName, $job);
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
        return $this->pushMns($queueName, $job, $delay);
    }


    /**
     * 推送消息到Mns
     * @author PengShuHai<pengshuhai@jybdshop.cn>
     * @date 2019/2/21 12:51:03
     * @param string $queueName 队列名称
     * @param Job $job 任务对象
     * @param int|null $delay 消息的DelaySeconds参数(延迟消费时间),如果为null，则延迟时间由aliyun控制台决定
     * @param int $retry 当前重新尝试次数
     * @return bool
     */
    private function pushMns($queueName, Job $job, $delay = null, $retry = 0)
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

            // 压入队列次数 = 重试次数+1
            $times = $retry + 1;
            if ($isReTry && $retry < $this->pushMaxTryTimes) {
                // 重试
                Log::warning('Mns SendMessage Failed(times: '.$times.'): '.$e, 'mns', 'push');
                $retry ++;
                return $this->pushMns($queueName, $job, $delay, $retry);
            } else {
                // 不需要重试的或者重试完成还是报错的错误直接记录并报警
                Log::fatal('Mns SendMessage Failed(times: '.$times.',no longer try): '.$e, 'mns', 'push');
                return false;
            }
        }
    }
}