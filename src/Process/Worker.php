<?php

namespace QueueTask\Process;

/**
 * 工作进程
 * Class Worker
 * @package QueueTask\Process
 */
class Worker extends Process
{

    /**
     * Worker constructor.
     * @param int $pid
     */
    protected function __construct($pid = 0)
    {
        parent::__construct($pid);
    }


}