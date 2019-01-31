<?php

use \QueueTask\Daemon\Work\Work;

class PushDelayWork extends Work
{

    public function getWorkInit()
    {
        return function (\ProcessManage\Process\Worker $process) {
            return \QueueTask\Queue\Queue::getInstance();
        };
    }

    public function getWork()
    {
        return function (\ProcessManage\Process\Worker $process, \QueueTask\Queue\Queue $queue) {
            $queue->laterOn(5, new \Tests\TestHandler(), 'test', ['test' => '1'], $this->queueName);
        };
    }

}