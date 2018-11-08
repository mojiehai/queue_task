<?php

namespace QueueTask\Process;

/**
 * 主进程类
 * Class Master
 * @package QueueTask\Process
 */
class Master extends Process
{

    /**
     * 子进程pid列表
     * @var array
     */
    protected $workers = [];


    public function addWorkerPid($pid)
    {
        if (!in_array($pid, $this->workers)) {
            $this->workers[] = $pid;
            $this->workers = array_values($this->workers);  // 重新排列下标，防止下标过大
        }
    }




    /**
     * 进程执行的内容
     * @return void
     */
    function run()
    {
        echo $this->title . ":" .$this->pid;
    }
}