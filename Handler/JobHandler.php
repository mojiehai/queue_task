<?php

require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."Config".DIRECTORY_SEPARATOR."config.php";
require_once TASK_ROOT_PATH.DS."Exception".DS."TaskException.php";


/**
 * 任务回调
 * Class JobHandler
 */
abstract class JobHandler {

    /**
     * 回调执行任务方法
     * @param $job
     * @param $func
     * @param $data
     * @return mixed
     */
    public function handler($job , $func , $data){
        $this -> $func($job , $data);
    }


    /**
     * 失败回调方法
     * @param $job
     * @param $func
     * @param $data
     * @return mixed
     */
    abstract public function failed($job , $func , $data);



    /**
     * 回调方法
     * @param $job
     * @param $data
     */
    /**
     * public function func($job,$data){}
     */


    /**
     * 设置本次执行handler为失败
     * @param string $msg
     * @param int $code
     * @throws TaskException
     */
    public function throwOnceFailure($msg = "" , $code = 0){
        throw new TaskException($msg,$code);
    }

} 