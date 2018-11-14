<?php

namespace QueueTask\Connection;


use QueueTask\Config\Config;
use QueueTask\Exception\Exception;

class ConnectionFactory
{

    /**
     * 获取链接对象
     * @param string $connectName 链接方式
     * @return Connection
     * @throws Exception
     */
    public static function getInstance($connectName)
    {
        $connect = Config::getConnection($connectName);
        if (empty($connect) || !isset($connect['class']) || empty($connect['class'])) {
            throw new Exception('There is no link available type', 102);
        } else {
            $class = $connect['class'];
            $config = isset($connect['config']) ? $connect['config'] : [];
            return $class::getInstance($config);
        }
    }
}