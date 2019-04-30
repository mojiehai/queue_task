<?php

namespace QueueTask\Helpers\Lock;

/**
 * 文件锁
 * Class FileLock
 * @package QueueTask\Helpers
 */
class FileLock
{
    // 阻塞模式
    const BLOCK = LOCK_EX;

    // 非阻塞模式
    const UN_BLOCK = LOCK_EX + LOCK_NB;

    /**
     * 锁文件路径
     * @var string
     */
    private $fileLockPath = __DIR__.DIRECTORY_SEPARATOR.'fileLock';

    /**
     * 阻塞模式，默认阻塞
     * @var int
     */
    private $type = self::BLOCK;

    /**
     * 文件资源
     * @var resource
     */
    private $resource = null;

    /**
     * FileLock constructor.
     * @param int $type 阻塞模式
     */
    public function __construct(int $type = null)
    {
        if (empty($type)) {
            $type = self::BLOCK;
        }
        $this->type = $type;
        $this->resource = fopen($this->fileLockPath, 'w+');
    }

    /**
     * 锁
     * 阻塞模式：该方法阻塞
     * 非阻塞模式
     * @return bool 加锁 成功(true) 或者 失败(false)
     */
    public function lock()
    {
        return flock($this->resource, $this->type);
    }

    /**
     * 解锁
     * @return bool
     */
    public function unlock()
    {
        return flock($this->resource, LOCK_UN);
    }


    public function __destruct()
    {
        fclose($this->resource);
    }
}