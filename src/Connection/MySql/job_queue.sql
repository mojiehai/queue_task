CREATE TABLE `job_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queueName` varchar(255) NOT NULL COMMENT '队列名称',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  `job` text NOT NULL COMMENT '任务类(序列化)',
  `wantExecTime` datetime NOT NULL COMMENT '意向执行时间',
  `is_head` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为queueName队列的头节点，头结点为系统节点，不存任务，1为头节点，0为任务节点',
  PRIMARY KEY (`id`),
  KEY `queueName_key` (`queueName`),
  KEY `wantExecTime_key` (`wantExecTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
