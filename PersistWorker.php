<?php


require_once dirname(__FILE__).DIRECTORY_SEPARATOR."Queue".DIRECTORY_SEPARATOR."PersistQueue.php";

/**
 * 持久化任务监听进程类
 * Class PersistWorker
 */
class PersistWorker {


    /**
     * 启用一个队列后台监听任务
     * @param PersistQueue $queue
     * @param string $queueName 监听队列的名称(在push的时候把任务推送到哪个队列，则需要监听相应的队列才能获取任务)
     * @param int $attempt 队列任务失败尝试次数，0为不限制
     * @param int $memory 允许使用的最大内存
     * @param int $sleep 每次检测的时间间隔
     * @param int $delay 失败后延迟的秒数重新入队列
     */
    public static function listen(PersistQueue $queue, $queueName = 'default', $attempt = 10, $memory = 128, $sleep = 3, $delay = 0){
        $job = null;
        while (true){

            $job = $queue->pop($queueName);

            if($job instanceof PersistJob){

                if($attempt > 0 && $job->getAttempts() > $attempt){
                    $job->delete();
                    $job->failed();
                }else{

                    $job->execute();

                    if (! $job->isDeleted()) {
                        $job->release($delay);
                    }
                }
            }

            self::sleep($sleep);


            if (self::memoryExceeded($memory)) {
                self::stop();
            }
        }
    }



    /**
     * 判断内存使用是否超出
     * @param  int   $memoryLimit
     * @return bool
     */
    public static function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * 停止队列监听
     */
    public static function stop(){
        die;
    }

    /**
     * 休眠
     */
    public static function sleep($seconds){
        sleep($seconds);
    }


} 