# queue_task
一个轻量级可拓展的队列任务、暂时支持mysql、redis等存储方式

# 自动加载
> * 该项目遵守psr-4自动加载规则，使用Composer下载后，引入autoload.php即可自动加载

# 使用示例
## Mysql、Redis存储
> * /Config/Config.php中方法getStorageType()的返回值指定为STORAGE_MYSQL/STORAGE_REDIS，并且配置好Mysql(getMySqlConfig())/Redis(getRedisConfig())的相关配置
> * 若用Mysql存储，则需要新增job_queue表
> * 用PHP Cli模式运行listen.php，监听mysql任务，(框架中使用则推荐把该段代码放入一个Controller中，然后由PHP Cli模式运行该控制的该方法，这样即可在Handler中使用框架的其他功能、方法)
<pre>

        $queueName = "queueName";   //队列名称
        $attempt   = 3;             //队列任务失败尝试次数，0为不限制
        $memory    = 128;           //允许使用的最大内存  单位:M
        $sleep     = 1;             //每次检测的时间间隔
        $delay     = 3;             //失败后延迟的秒数重新入队列
        
        try{
            Worker::listen(QueueAdapter::getQueue(),$queueName,$attempt,$memory,$sleep,$delay);
        }catch (Exception $e){
            echo $e->getCode()." -- ".$e->getFile() . " -- ". $e->getLine() . " : ".$e->getMessage();
        }
        
</pre>
> * 执行index.php进行任务入队
<pre>

        $res = QueueAdapter::getQueue();

        //$r = $res->pushOn(new TestHandler(),'test',['test'=>'test'],'queueName');     //及时入队
        $r = $res->laterOn(5,new TestHandler(),'test',['test'=>'test'],'queueName');    //延迟5s入队
        
</pre>
> * index.php中指定的TestHandler中的test方法被执行
> #### 注：listen.php、index.php、TestHandler.php都只是使用示例，可以直接修改，不影响功能

## 其他存储方式
> #### 实现Connection、Queue、在ConnectAdapter、QueueAdapter中注册、增加配置文件参数，即可使用
