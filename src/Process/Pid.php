<?php


namespace QueueTask\Process;

use Swoole\Process;

/**
 * Class Pid
 * @package QueueTask\Process
 */
class Pid
{

    /**
     * 唯一标识
     * @var string
     */
    public $uniqueTag = '';

    protected $pid = 0;

    /**
     * Pid constructor.
     * @param string $uniqueTag
     */
    public function __construct($uniqueTag)
    {
        $this->uniqueTag = $uniqueTag;
    }

    /**
     * 获取pid
     * @return int
     */
    public function get()
    {
        if ($this->pid > 0) {
            return $this->pid;
        } else {
            $pid = @file_get_contents($this->pidPath());
            return intval($pid);
        }
    }

    /**
     * 设置pid
     * @param int $pid
     * @return bool
     */
    public function set(int $pid)
    {
        $result = @file_put_contents($this->pidPath(), $pid);
        $this->pid = $this->get();
        return $result ? true : false;
    }

    /**
     * 是否存活
     * @return bool
     */
    public function isAlive()
    {
        $pid = $this->get();
        if ($pid != 0 && Process::kill($pid, 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取pid文件
     * @return string
     */
    protected function pidPath()
    {
        return __DIR__.'/../../pid/manage_'.$this->uniqueTag.'.pid';
    }

}