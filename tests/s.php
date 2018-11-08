<?php
define('PROC_MAX', 10);
define('PROC_MIN', 5);
 
$cmd = $argv[1];
$aPid = [];
$pidFile = __DIR__ . '/pid.pid';
$pid = file_get_contents($pidFile);
 
switch($cmd){
    case 'start' :
        if(posix_kill($pid, 0)){
            echo "gamelog process is already exsits!\n";
            return false;
        }
        //设置默认文件权限
        umask(022);
        //fork
        $pid = pcntl_fork();
        if($pid < 0){
            exit('fork error!');
        }else if($pid > 0){
            exit;
        }
        //脱离当前终端
        posix_setsid();
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
         
        cli_set_process_title('gamelog:master');
        $pid = posix_getpid();
        file_put_contents($pidFile, $pid);
        //闹钟信号
        pcntl_signal(SIGALRM, function() use (&$aPid) {
            pcntl_alarm(300);
            $workerNum = mt_rand(1, 20);//此处检测你需要的进程数
            $daemonNum = count($aPid);
             
            ($workerNum > PROC_MAX) && ($workerNum = PROC_MAX);
            if($daemonNum < $workerNum){
                $procNum = $workerNum - $daemonNum;
                $procNum = max(PROC_MIN, $procNum);
                for($p = 1; $p <= $procNum; $p++){
                    $pid = pcntl_fork();
                    if ($pid < 0) {
                        exit('fork error!');
                    } else if ($pid == 0) {
                        cli_set_process_title('gamelog:worker');
                        $i = 0;
                        while (true) {
                            //do your work
                            if ($i == 3) {
                                break;
                            }
                            sleep(1);
                            $i ++;
                        }
                        exit();
                    } else {
                        $aPid[] = $pid;
                    }
                }
            }else if($daemonNum > $workerNum){
                $wokerNum = max($wokerNum, PROC_MIN);
                $killNum = $daemonNum - $workerNum;
                foreach($aPid as $key=>$pid){
                    if(posix_kill($pid, SIGKILL)){
                        unset($aPid[$key]);
                        if(--$killNum <= 0){
                            break;
                        }
                    }
                }
            }
        }, false);
         
        pcntl_signal(SIGUSR1, function() use (&$aPid, $pid){
            foreach($aPid as $key=>$chpid){
                if(!posix_kill($chpid, SIGKILL)){
                    echo "kill child $chpid faild\n";
                }
            }
            posix_kill($pid, SIGKILL);
        }, false);
         
        pcntl_signal(SIGUSR2, function() use (&$aPid, $pid){
           foreach($aPid as $key=>$chpid){
                if(!posix_kill($chpid, SIGKILL)){
                    echo "kill child $chpid faild\n";
                }
            }
            if(!posix_kill($pid, SIGALRM)){
                echo "restart gamelog faild\n";
            }
        }, false);
         
        posix_kill($pid, SIGALRM);
        while (true) {
            pcntl_signal_dispatch();
            $pid = pcntl_wait($status, WUNTRACED);//不阻塞
        }
        break;
     
    case 'stop' :
        if(!posix_kill($pid, SIGUSR1)){
            exit('stop gamelog process error!');
        }
        break;
    case 'reload' :
        if(!posix_kill($pid, SIGUSR2)){
            exit('restop gamelog process error!');
        }
        break;
    default :
        echo "Useage php signal.php start|stop|reload\n";
}

