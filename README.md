# QueueTask
易于拓展的队列任务、支持mysql、redis等存储方式


# 目录结构
<pre>

|--Config/   配置文件目录

    |--config.php    主配置文件
  
    |...    辅助配置文件，可拓展，主配置文件引入该文件
  
  
|--Connection/   链接（mysql、redis等）目录

    |--ConnectAdapter.php    获取链接的入口类
  
    |--Connection.php    链接类的基类
  
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
  
    |--require.php
  
    |--TestHandler.php    (测试文件，可删除)
  
    |...    其他执行类，只需要继承JobHandler，即可使用
  

|--Helpers    辅助目录

    |--function.php   自定义方法
  
  
|--Job    任务目录

    |--Job.php    任务基类
  
    |--GeneralJob.php   一般任务类(现在用的任务实体)
  
    |...    可拓展其他任务类，需要继承Job
  

|--Queue    队列目录

    |--QueueInterface.php   队列接口
  
    |--Queue.php    队列实体
  
    |--MySqlQueue.php   MySql队列
  
    |--RedisQueue.php   Redis队列
  
    |...    可拓展其他队列，继承Queue，指定存储方式即可


|--Worker   工作目录

    |--Worker.php   任务监听类
  
  
|--QueueAdapter.php   获取队列的统一入口类

|--job_queue.sql    MySql任务的表结构

|--listen.php    (测试文件，可删除)

|--index.php    (测试文件，可删除)

</pre>

# 使用示例
## Mysql存储
> * /Config/config.php中常量STORAGE_TYPE的值指定为STORAGE_MYSQL，/Config/mysql_conf.php中配置好Mysql的相关配置
> * Mysql添加job_queue表
> * 用PHP Cli模式运行listen.php，监听mysql任务
> * 执行index.php添加任务
> * index.php中指定的TestHandler中的test方法被执行

## Redis存储
> * /Config/config.php中常量STORAGE_TYPE的值指定为STORAGE_REDIS，/Config/redis_conf.php中配置好Redis的相关配置
> * 开启redis-server
> * 用PHP Cli模式运行listen.php，监听redis任务
> * 执行index.php添加任务
> * index.php中指定的TestHandler中的test方法被执行
> #### 注：listen.php、index.php、TestHandler.php都只是使用示例，可以直接修改，不影响功能

## 其他存储方式
> #### 实现Connection、Queue、在ConnectAdapter、QueueAdapter中注册、增加配置文件参数，即可使用
