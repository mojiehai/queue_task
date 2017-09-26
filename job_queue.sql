CREATE TABLE `job_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queueName` varchar(255) NOT NULL COMMENT '队列名称',
  `createtime` datetime NOT NULL COMMENT '创建时间',
  `job` text NOT NULL COMMENT '任务类(序列化)',
  `wantexectime` datetime NOT NULL COMMENT '意向执行时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
