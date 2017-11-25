# QueueTask
一个轻量级可拓展的队列任务、暂时支持mysql、redis等存储方式


# 目录结构
<pre>

|--Config/   配置文件目录

    |--Config.php    配置文件
  
  
|--Connection/   链接（mysql、redis等）目录

    |--ConnectAdapter.php    获取链接的工厂类
    
    |--Connection.php    链接类的基类
  
    |--Mysql/    mysql链接目录
        
        |--MySqlConnect.php    mysql链接类(继承Connection)
    
    |--Redis/    redis链接目录
  
        |--RedisConnect.php    redis链接类(继承Connection)
    
        |--RedisDrive.php    redis基础操作类
    
    |...    拓展链接方式，可在此创建目录/文件，继承Connection，在ConnectAdapter注册即可
  
  
|--Exception/    异常目录

    |--DBException.php    数据存储相关异常
  
    |--TaskException.php    队列任务执行异常类
  
  
|--Handler    处理程序目录(任务真正执行的位置)

    |--JobHandler.php   处理程序类基类
  
    |--TestHandler.php    (测试文件，可删除)
  
    |...    其他执行类，只需要继承JobHandler，即可使用
  

|--Helpers    辅助目录

    |--StringHelpers.php   字符串帮助类
    
  
|--Job    任务目录

    |--Job.php    任务基类
  
    |--GeneralJob.php   一般任务类(现在用的任务实体)
  
    |...    可拓展其他任务类，需要继承Job，
    

|--Queue    队列目录

    |--QueueInterface.php   队列接口
  
    |--Queue.php    队列实体
  
    |--MySqlQueue.php   MySql队列
  
    |--RedisQueue.php   Redis队列
  
    |...    可拓展其他队列，继承Queue，指定存储方式即可


|--Worker   工作目录

    |--Worker.php   任务监听类
  
  
|--QueueAdapter.php   获取队列的工厂类（获取队列入口）

|--listen.php    (测试文件，可删除)

|--index.php    (测试文件，可删除)

</pre>

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
