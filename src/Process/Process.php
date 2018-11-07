<?php

namespace QueueTask\src\Process;


/**
 * 进程抽象类
 * Class Process
 * @package QueueTask\src\Process
 */
abstract class Process
{

    /**
     * 进程id
     *
     * @var int
     */
    public $pid = '';




    /**
     * 管道名称
     *
     * @var string
     */
    protected $pipeName = '';

    /**
     * 管道文件权限
     *
     * @var integer
     */
    protected $pipeMode = 0777;

    /**
     * 管道名称前缀
     *
     * @var string
     */
    protected $pipeNamePrefix = 'queueTask.pipe';

    /**
     * 管道文件的根目录
     *
     * @var string
     */
    protected $pipeDir = '';

    /**
     * 管道文件的完整路径
     *
     * @var string
     */
    protected $pipePath = '';


    /**
     * Process constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (empty($this->pid)) {
            $this->pid = posix_getpid();    // 获取当前进程pid
        }

        $this->pipeDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;  // /tmp/
        $this->pipeName = $this->pipeNamePrefix . $this->pid;   // 前缀+pid
        $this->pipePath = $this->pipeDir . $this->pipeName;     // 完整路径
    }


    /**
     * 创建管道
     *
     * @return void
     */
    public function pipeMake()
    {
        if (!file_exists($this->pipePath)) {
            if (!posix_mkfifo($this->pipePath, $this->pipeMode)) {
                // todo error
                exit;
            }
            chmod($this->pipePath, $this->pipeMode);
        }
    }


    /**
     * 写入内容到管道中
     *
     * @param string $signal 内容
     * @return void
     */
    public function pipeWrite($signal = '')
    {
        $pipe = fopen($this->pipePath, 'w');
        if (!$pipe) {
            return;
        }

        $res = fwrite($pipe, $signal);
        if (! $res) {
            return;
        }

        if (!fclose($pipe)) {
            return;
        }

    }

}