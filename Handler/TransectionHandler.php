<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/14
 * Time: 16:47
 */

namespace app\PersistenceTask\Handler;


use app\controllers\TransectionController;

class TransectionHandler extends PersistJobHandler {

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
     * 删除未使用订单
     */
    public function unUsedOfTimeOut($job,$data){
        $tid         = getVal($data['tid'] , '');

        $isrunok = TransectionController::UnUsedOfTimeOut($tid);

        if(!$isrunok){
            echo "unUsedOfDelete error\r\n";
            $this->throwOnceFailure('unUsedOfDelete run failed');
        }else{
            echo "unUsedOfDelete success\r\n";
        }
    }


} 