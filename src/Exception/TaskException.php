<?php

namespace QueueTask\Exception;

/**
 * 队列任务执行异常类
 * Class TaskException
 */
class TaskException extends Exception
{

    /**
     * 强制任务失败，不重试
     */
    const FORCE_FAILED = 1;

} 