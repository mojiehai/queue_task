<?php

namespace QueueTask\Helpers;

/**
 * 加载配置到变量中
 * Trait LoadConfig
 * @package QueueTask\Helpers
 */
trait LoadConfig
{

    /**
     * 允许配置的变量名
     * @var array
     */
    protected $configNameList = [];

    /**
     * 加载配置
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        foreach ($config as $k => $v) {
            if (in_array($k, $this->configNameList)) {
                if (!is_null($v)) {
                    $this->$k = $v;
                }
            }
        }
        return $this;
    }

}