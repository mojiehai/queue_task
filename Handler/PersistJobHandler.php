<?php

abstract class PersistJobHandler {

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
     * @throws PersistException
     */
    public function throwOnceFailure($msg = "" , $code = 0){
        throw new PersistException($msg,$code);
    }

} 