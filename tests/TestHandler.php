<?php

namespace Tests;

use QueueTask\Handler\JobHandler;

class TestHandler extends JobHandler
{

    /**
     * 失败回调方法
     * @param $job      任务
     * @param $func     执行的方法
     * @param $data     参数
     * @return mixed
     */
    public function failed($job, $func, $data)
    {
        echo $func." failed [".date('Y-m-d H:i:s',time())."] no \r\n";
    }

    /**
     * 任务成功回调
     * @param $job      任务
     * @param $func     执行的方法
     * @param $data     参数
     * @return mixed
     */
    public function success($job, $func, $data)
    {
        echo $func." success [".date('Y-m-d H:i:s',time())."] yes \r\n";
    }


    public function test($job,$data)
    {
        $path = __DIR__.DIRECTORY_SEPARATOR."test.log";
        //file_put_contents($path,"[".date('Y-m-d H:i:s',time())."] test ok\r\n");
        if(!file_exists($path)) {
            touch($path);
        }
        $rs = fopen($path,"a+");
        fwrite($rs,"[".date('Y-m-d H:i:s',time())."] test ok : ".json_encode($data)."\r\n");
        fclose($rs);
        if(rand(0,2) == 0) {
            echo "[".date('Y-m-d H:i:s',time())."] ok \r\n";
        } else {
            echo "[".date('Y-m-d H:i:s',time())."] no \r\n";
            $this->throwOnceFailure('error ');
        }
    }


}