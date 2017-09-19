<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/8
 * Time: 12:50
 */

namespace app\PersistenceTask\Handler;

use app\controllers\DeviceController;

class DeviceHandler extends PersistJobHandler{

    /**
     * 失败回调方法
     * @param $job
     * @param $func
     * @param $data
     * @return mixed
     */
    public function failed($job, $func, $data)
    {
    }


    /**
     * 停止
     * @param $job
     * @param $data
     */
    public function stop($job,$data){
        $tid         = getVal($data['tid'] , '');
        $spotLightSn = getVal($data['spotLightSn'] , '');
        $curtainSn   = getVal($data['curtainSn'] , '');
        $chairSn     = getVal($data['chairSn'] , '');

        $isStop = DeviceController::Stop($tid,$spotLightSn,$curtainSn,$chairSn);

        if(!$isStop){
            echo "stop error\r\n";
            $this->throwOnceFailure('transection stop failed');
        }else{
            echo "stop success\r\n";
        }

    }

    /**
     * 停止设备
     * @param $job
     * @param $data
     */
    public function stopDevice($job,$data){
        $tid         = getVal($data['tid'] , '');
        $spotLightSn = getVal($data['spotLightSn'] , '');
        $curtainSn   = getVal($data['curtainSn'] , '');

        $isStop = DeviceController::StopDevice($tid,$spotLightSn,$curtainSn);

        if(!$isStop){
            echo "stopDevice error\r\n";
            $this->throwOnceFailure('device stop failed');
        }else{
            echo "stopDevice success\r\n";
        }

    }

}