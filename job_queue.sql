CREATE TABLE `job_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ququeName` varchar(255) NOT NULL COMMENT '队列名称',
  `attempts` int(3) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `handlerclass` text NOT NULL COMMENT '回调类(序列化)',
  `func` varchar(255) NOT NULL COMMENT '回调函数名',
  `param` text NOT NULL COMMENT '参数  json',
  `createtime` datetime NOT NULL COMMENT '创建时间',
  `wantexectime` datetime NOT NULL COMMENT '意向执行时间',
  `isexec` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否执行完毕',
  `isdelete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除 0未删除 1已删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;

