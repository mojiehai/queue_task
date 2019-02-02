<?php

namespace QueueTask\Handler;

use QueueTask\Exception\TaskException;
use QueueTask\Job\Job;
use Throwable;


/**
 * 任务回调
 * Class JobHandler
 */
abstract class JobHandler
{

    /**
     * 回调执行任务方法
     * @param Job $job      任务
     * @param String $func     执行的方法
     * @param array $data     参数
     * @return mixed
     * @throws TaskException
     */
    public function handler($job, $func, $data)
    {
        if (method_exists($this, $func)) {
            $this->$func($job, $data);
        } else {
            $this->throwOnceFailure('method "'.$func .'" does not exist');
        }
    }


    /**
     * 失败回调方法
     * @param Job $job 任务
     * @param string $func 执行的方法
     * @param array $data 参数
     * @return mixed
     */
    abstract public function failed($job, $func, $data);


    /**
     * 任务成功回调
     * @param Job $job 任务
     * @param string $func 执行的方法
     * @param array $data 参数
     * @return mixed
     */
    abstract public function success($job, $func, $data);


    /**
     * 回调方法
     * @param $job
     * @param $data
     */
    /**
     * public function func($job,$data){}
     */


    /**
     * 设置本次执行handler为失败(会重试到指定次数)
     * @param string $message 错误信息
     * @throws TaskException
     */
    public function throwOnceFailure($message = "")
    {
        throw new TaskException($message, 0);
    }

    /**
     * 设置本次执行handler为强制失败(不会重试)
     * @param string $message 错误信息
     * @throws TaskException
     */
    public function throwForceFailure($message = "")
    {
        throw new TaskException($message, TaskException::FORCE_FAILED);
    }

} 