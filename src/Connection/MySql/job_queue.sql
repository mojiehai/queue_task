CREATE TABLE `job_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queueName` varchar(255) NOT NULL COMMENT '队列名称',
  `job` text NOT NULL COMMENT '任务类(序列化)',
  PRIMARY KEY (`id`),
  KEY `queueName_key` (`queueName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `job_queue_delay` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queueName` varchar(255) NOT NULL COMMENT '队列名称',
  `job` text NOT NULL COMMENT '任务类(序列化)',
  `wantExecTime` datetime NOT NULL COMMENT '意向执行时间',
  PRIMARY KEY (`id`),
  KEY `queueName_key` (`queueName`),
  KEY `wantExecTime_key` (`wantExecTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;