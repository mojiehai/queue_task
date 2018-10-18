<?php

namespace QueueTask\src\Connection;


use QueueTask\Config\Config;
use QueueTask\Connection\Connection;

class ConnectionFactory
{

    /**
     * 获取链接对象
     * @return Connection
     * @throws \Exception
     */
    public static function getInstance()
    {
        $connect = Config::getConnection();
        if (empty($connect) || !isset($connect['class']) || empty($connect['class'])) {
            throw new \Exception('There is no link available type', 102);
        } else {
            $class = $connect['class'];
            $config = isset($connect['config']) ? $connect['config'] : [];
            return $class::getInstance($config);
        }
    }
}