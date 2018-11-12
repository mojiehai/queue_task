<?php

namespace QueueTask\Process;


use QueueTask\Exception\ProcessException;

class Manage
{

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 工作初始化
     * @var \Closure
     */
    protected $closureInit = null;

    /**
     * 工作回调
     * @var \Closure
     */
    protected $closure = null;

    /**
     * @param array $config
     * Manage constructor.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 设置进程的工作初始化
     * @param \Closure $closure
     * @return $this
     */
    public function setWorkInit(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closureInit = $closure;
        }
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
                //分离出子进程
                $pid = pcntl_fork();
                if($pid < 0){
                    $this->showRunErrors(new ProcessException('start error!'));
                    exit();
                }else if($pid > 0){
                    // 杀掉父进程
                    echo 'start ok !' . PHP_EOL;
                    exit;
                }
                //脱离当前终端(脱离死去的父进程的牵制)
                $sid = posix_setsid();
                if ($sid < 0) {
                    exit;
                }
                //将当前工作目录更改为根目录
                chdir('/');
                //关闭文件描述符
                fclose(STDIN);
                fclose(STDOUT);
                fclose(STDERR);
                //重定向输入输出
                global $STDOUT, $STDERR;
                $STDOUT = fopen('/dev/null', 'a');
                $STDERR = fopen('/dev/null', 'a');
                $this->start();
                break;
            case '':        // 直接启动
                $master = new Master($this->config);
                echo $master->pid . ' -- '. $master->title . ' -- starting !' . PHP_EOL;
                $master->setWorkInit($this->closureInit)->setWork($this->closure)->run();
                break;
            default:        // 命令错误
                $this->showCommandErrors($com2);
                break;
        }
    }

    /**
     * stop命令动作
     * @return void
     * @throws ProcessException
     */
    protected function stop()
    {
        $master = new Master($this->config, -1);
        $master->pid = $master->getPidByFile();
        if (Master::isMasterAlive($master)) {
            if (posix_kill($master->pid, SIGUSR2)) {
                echo 'stop'.PHP_EOL;
            } else {
                throw new ProcessException('stop failure');
            }
        } else {
            throw new ProcessException('process is not exists!');
        }
    }

    /**
     * restart命令动作
     * @return void
     * @throws ProcessException
     */
    protected function restart()
    {
        $master = new Master($this->config, -1);
        $master->pid = $master->getPidByFile();
        if (Master::isMasterAlive($master)) {
            if (posix_kill($master->pid, SIGUSR1)) {
                echo 'restart'.PHP_EOL;
            } else {
                throw new ProcessException('restart failure');
            }
        } else {
            throw new ProcessException('process is not exists!');
        }
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
        $str .= "Usage: ".$_SERVER['PHP_SELF']." <start|stop|restart> [-d]\n";
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