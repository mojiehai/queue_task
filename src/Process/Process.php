<?php

namespace QueueTask\Process;


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
     * 进程名称
     * @var string
     */
    public $title = '';

    /**
     * 进程名称前缀
     * @var string
     */
    protected $titlePrefix = 'QueueTask:';

    /**
     * Process constructor.
     */
    public function __construct()
    {
        // 获取当前进程pid
        $this->pid = posix_getpid();

        // 设置当前进程名称  QueueTask:ClassName
        if (empty($this->title)) {
            $className = get_class($this);
            $className = end(explode('\\', $className));
            $this->title = $this->titlePrefix . $className;
        }
        cli_set_process_title($this->title);

    }


    /**
     * 进程执行的内容
     * @return void
     */
    abstract function run();

}