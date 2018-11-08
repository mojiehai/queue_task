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
     * 进程执行的内容
     * @return void
     */
    function run()
    {
        echo $this->title.':'.$this->pid;
    }
}