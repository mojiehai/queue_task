<?php

namespace QueueTask\Process;


class Manage
{

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 工作回调
     * @var \Closure
     */
    protected $closure = null;

    /**
     * 设置配置
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * 设置进程的工作内容
     * @param \Closure $closure
     * @return $this
     */
    public function setWork(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closure = $closure;
        }
        return $this;
    }

    /**
     * 开始执行
     */
    public function run()
    {
        try {
            $command = $this->getCommand();

            //设置默认文件权限
            umask(022);
            switch ($command) {
                case "start":
                    $this->start($this->getCommand(2));
                    break;
                case "stop":
                    $this->stop();
                    break;
                case "restart":
                    $this->restart();
                    break;
                default:
                    $this->showCommandErrors($command);
                    break;
            }
            exit();
        } catch (\Exception $e) {
            $this->showRunErrors($e);
        }
    }


    ################################## command action ####################################
    /**
     * start命令动作
     * @param string $com2 第二个命令
     * @return void
     * @throws \Exception
     */
    protected function start($com2 = '')
    {
        switch ($com2) {
            case '-d':      // 守护进程方式启动
                break;
            case '':        // 直接启动
                $master = Master::Create();
                $master->setConfig($this->config)->setWork($this->closure)->run();
                break;
            default:        // 命令错误
                $this->showCommandErrors($com2);
                break;
        }
    }

    /**
     * stop命令动作
     * @return void
     */
    protected function stop()
    {
    }

    /**
     * restart命令动作
     * @return void
     */
    protected function restart()
    {
    }
    ################################## command action ####################################



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
        $str .= "Usage: queue_task <start|stop|restart> [-d]\n";
        echo $str;
    }

    /**
     * 显示错误信息
     * @param $errStr
     */
    protected function showCommandErrors($errStr)
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

    /**
     * 显示执行错误信息
     * @param \Exception $e
     */
    protected function showRunErrors(\Exception $e)
    {
        $str = "Error: ".$e->getMessage();

        $str .= "\n";
        echo $str;
    }

}