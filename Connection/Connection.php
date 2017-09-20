<?php


/**
 * 连接类
 * Class Connection
 */
abstract class Connection {

    /**
     * 返回存储方式(mysql/redis/file...)
     * @return String
     */
    abstract public function getType();


    /**
     * 关闭连接
     * @return
     */
    abstract public function close();

} 