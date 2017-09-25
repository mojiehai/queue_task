CREATE TABLE `job_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ququeName` varchar(255) NOT NULL COMMENT '队列名称',
  `createtime` datetime NOT NULL COMMENT '创建时间',
  `job` text NOT NULL COMMENT '任务类(序列化)',
  `wantexectime` datetime NOT NULL COMMENT '意向执行时间',
  `isexec` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否执行完毕 0未执行 1已执行',
  `isdelete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除 0未删除 1已删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
