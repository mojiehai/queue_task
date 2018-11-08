<?php

namespace QueueTask\Process;


class Manage
{

    /**
     * 最大工作进程数
     * @var int
     */
    protected $maxWorker = 4;


    /**
     * 开始执行
     */
    public function run()
    {
        $command = $this->getCommand();

        switch ($command) {
            case "start":
                break;
            case "stop":
                break;
            case "restart":
                break;
            default:
                $this->showErrors($command);
                break;
        }

    }


    /**
     * 获取操作命令
     * @param int $key 指定获取用户输入的哪个命令
     * @return string
     */
    protected function getCommand($key = 1)
    {
        GLOBAL $argv;
        if (isset($argv[$key])) {
            return $argv[$key];
        } else {
            return '';
        }
    }

    /**
     * 显示提示信息
     */
    protected function showHelps()
    {
        $str = "";
        $str .= "Usage: queue_task <start|stop|restart>\n";
        echo $str;
    }

    /**
     * 显示错误信息
     * @param $errStr
     */
    protected function showErrors($errStr)
    {
        $str = '';
        if (empty($errStr)) {
            $str .= "Please enter the command";
        } else {
            $str .= "ERROR: command '".$errStr."' syntax error";
        }
        $str .= "\n";

        echo $str;

        $this->showHelps();
    }

}