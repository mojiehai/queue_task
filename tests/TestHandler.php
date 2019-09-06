<?php

namespace Tests;

use QueueTask\Handler\JobHandler;
use QueueTask\Helpers\Log;
use QueueTask\Job;

class TestHandler extends JobHandler
{

    /**
     * 失败回调方法
     * @param Job $job      任务
     * @param string $func     执行的方法
     * @param array $data     参数
     * @return mixed
     */
    public function failed(Job $job, $func, $data)
    {
        Log::info('failed run handler -- func: '.$func.' -- params: '.json_encode($data).',error:'.json_encode($job->getErrors()));
    }

    /**
     * 任务成功回调
     * @param Job $job      任务
     * @param string $func     执行的方法
     * @param array $data     参数
     * @return mixed
     */
    public function success(Job $job, $func, $data)
    {
        Log::info('success run handler -- func: '.$func.' -- params: '.json_encode($data).',error:'.json_encode($job->getErrors()));
    }


    public function test(Job $job,$data)
    {
        // 1/2几率成功
        if(rand(0,1) == 0) {
            Log::info('run handler -- func: test -- params: '.json_encode($data). '; result : true');
        } else {
            Log::info('run handler -- func: test -- params: '.json_encode($data). '; result : false');
            $job->setOnceFailure('error info , times:'.$job->getAttempts());
        }
    }

}