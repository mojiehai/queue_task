<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Handler".DS."JobHandler.php";

class TestHandler extends JobHandler{

    /**
     * 失败回调方法
     * @param $job      任务
     * @param $func     执行的方法
     * @param $data     参数
     * @return mixed
     */
    public function failed($job, $func, $data)
    {
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
    }


    public function test($job,$data){
        $path = TASK_ROOT_PATH.DS."test.log";
        //file_put_contents($path,"[".date('Y-m-d H:i:s',time())."] test ok\r\n");
        if(!file_exists($path)){
            touch($path);
        }
        $rs = fopen($path,"a+");
        fwrite($rs,"[".date('Y-m-d H:i:s',time())."] test ok : ".json_encode($data)."\r\n");
        fclose($rs);
    }


}