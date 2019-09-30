<?php


namespace QueueTask;

use QueueTask\Exception\Exception;
use QueueTask\Process\ManageProcess;

/**
 * 工作类  多任务模式
 * Class MultiWorker
 * @package QueueTask
 */
class MultiWorker
{

    /**
     * @var ManageProcess
     */
    protected $manage = null;

    /**
     * MultiWorker constructor.
     * @param string $uniqueTag 唯一标识
     * @throws Exception
     */
    public function __construct($uniqueTag)
    {
        if (empty($uniqueTag)) {
            throw new Exception('require unique tag');
        }

        // 检测swoole扩展
        if (!extension_loaded('swoole')) {
            throw new Exception('missing swoole extensions');
        }

        // 检测版本号
        if (version_compare(swoole_version(), '1.10.3', '<')) {
            throw new Exception('required swoole version > 1.10.3');
        }

        // 初始化进程管理器
        $this->manage = new ManageProcess($uniqueTag);
    }

    /**
     * 添加worker
     * @param array $workerConfig
     * @param int $processNum
     * @return $this
     */
    public function addWorker(array $workerConfig, int $processNum = 1)
    {
        $processNum = $processNum < 1 ? 1 : $processNum;
        $this->manage->addWorker($workerConfig, $processNum);
        return $this;
    }

    /**
     * 开始运行
     */
    public function start()
    {
        $this->manage->start();
    }

    /**
     * 停止运行
     */
    public function stop()
    {
        $this->manage->stop();
    }

}