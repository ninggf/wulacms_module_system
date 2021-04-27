<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

defined('APPROOT') or header('Page Not Found', true, 404) || die();

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` INT UNSIGNED NOT NULL COMMENT '最后更新时间',
  `update_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作uid',
  `deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否删除：0:否；1是',
  `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID，默认为0，系统用户',
  `is_super_user` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否是超级管理员',
  `name` VARCHAR(32) NOT NULL COMMENT '用于登录',
  `status` TINYINT NOT NULL COMMENT '状态：0锁定；1正常',
  `passwd` VARCHAR(255) NOT NULL COMMENT '密码HASH',
  `passwd_expire_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '密码过期时间',
  `acl_ver` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '权限版本',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `UDX_NAME` (`name` ASC),
  INDEX `IDX_TENANT_ID` USING BTREE (`tenant_id` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET = {encoding} COMMENT = '系统管理员'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_meta` (
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否删除：0:否；1是',
    `name` VARCHAR(16) NOT NULL COMMENT '元数据Key',
    `value` TEXT NULL COMMENT '元数据值',
    PRIMARY KEY (user_id , name)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户元数据'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}syslog` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `create_time` INT UNSIGNED NOT NULL COMMENT '日志时间',
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `logger` VARCHAR(16) NOT NULL COMMENT '日志类型（解析器）',
    `level` ENUM('WARN', 'INFO', 'ERROR') NOT NULL COMMENT '日志级别',
    `operation` VARCHAR(16) NOT NULL COMMENT '操作',
    `ip` VARCHAR(64) NOT NULL COMMENT 'IP',
    `message` TEXT NULL COMMENT '日志内容',
    `value1` TEXT NULL COMMENT 'Old Value',
    `value2` TEXT NULL COMMENT 'New Value',
    PRIMARY KEY (id),
    INDEX IDX_LEVEL_TYPE (tenant_id ASC, logger ASC, level ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='系统日志'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}setting` (
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
    `group` VARCHAR(20) NOT NULL DEFAULT 'default' COMMENT '配置组',
    `name` VARCHAR(16) NOT NULL COMMENT '配置项名',
    `value` TEXT NULL COMMENT '配置项值',
    PRIMARY KEY (`tenant_id`, `group`, `name`)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='系统设置'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}role` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
    `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL COMMENT '最后更新时间',
    `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '继承自角色',
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
    `name` VARCHAR(32) NOT NULL COMMENT '角色代码',
    `role` varchar(32) NOT NULL COMMENT '角色名称',
    `remark` VARCHAR(256) NULL COMMENT '说明',
    PRIMARY KEY (id),
    UNIQUE INDEX UDX_TENANT_ROLE (tenant_id ASC , name ASC),
    INDEX IDX_PID (pid ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户角色'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_role` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否删除：0:否；1是',
    PRIMARY KEY (`user_id` , `role_id`),
    INDEX IDX_ROLE_ID (role_id ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户的角色'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_token` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
    `expire_time` INT UNSIGNED NOT NULL COMMENT '过期时间',
    `device` SMALLINT UNSIGNED NOT NULL COMMENT '设备: 0 - pc; 1...',
    `token` CHAR(32) NOT NULL COMMENT 'Token',
    `os` varchar(32) NULL COMMENT '系统',
    `ip` VARCHAR(64) NOT NULL COMMENT '登录IP',
    `agent` VARCHAR(1024) NOT NULL COMMENT '客户端',
    PRIMARY KEY (`id`),
    UNIQUE INDEX UDX_USE_TOKEN (`user_id` ASC , `token` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户会话'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}role_permission` (
    `id` CHAR(32) NOT NULL COMMENT 'md5(role_id+uri+op)',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `uri` VARCHAR(128) NOT NULL COMMENT '资源URI',
    `op` VARCHAR(12) NOT NULL COMMENT '操作',
    PRIMARY KEY (`id`),
    INDEX IDX_ROLE_ID (role_id ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='角色权限'";

$tables['1.1.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `create_time` INT NOT NULL COMMENT '创建时间',
  `create_uid` INT UNSIGNED NOT NULL COMMENT '创建用户',
  `update_time` INT UNSIGNED NOT NULL COMMENT '更新时间',
  `update_uid` INT UNSIGNED NOT NULL COMMENT '更新用户',
  `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
  `uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID，为0时发给所有用户',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态： 0-草稿；1-发布；2-删除',
  `type` VARCHAR(16) NOT NULL COMMENT '消息类型',
  `title` VARCHAR(128) NOT NULL COMMENT '消息标题',
  `desc` VARCHAR(256) NULL COMMENT '简单说明',
  `content` TEXT NULL COMMENT '内容',
  `url` VARCHAR(512) NULL COMMENT '详情URL',
  PRIMARY KEY (`id`),
  INDEX `IDX_STATUS` (`status` ASC),
  INDEX `IDX_UID` (`uid` ASC))
ENGINE = InnoDB DEFAULT CHARACTER SET={encoding} COMMENT = '消息'";

$tables['1.1.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}message_read_log` (
  `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
  `message_id` INT UNSIGNED NOT NULL COMMENT '消息ID',
  `read_time` INT UNSIGNED NOT NULL COMMENT '第一次阅读时间',
  `read_ip` VARCHAR(128) NOT NULL COMMENT '第一次读消息时的IP',
  `last_read_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后一次阅读时间',
  `last_read_ip` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '最后一次阅读时的IP',
  `read_count` INT UNSIGNED NOT NULL COMMENT '一共阅读次数',
  PRIMARY KEY (`user_id`, `message_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET={encoding} COMMENT = '消息阅读记录'";

$tables['1.1.0'][] = "ALTER TABLE `{prefix}role` ADD COLUMN `system` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内置角色' AFTER `update_time`";

$tables['1.2.0'][] = <<<SQL
CREATE TABLE IF NOT EXISTS `{prefix}task` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` INT UNSIGNED NOT NULL COMMENT '创建用户',
  `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
  `name` VARCHAR(64) NOT NULL COMMENT '任务名称',
  `task` VARCHAR(128) NOT NULL COMMENT '任务类全类名',
  `retry` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '出错时重试次数，0不重试',
  `interval` SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT '重试间隔，单位秒',
  `status` ENUM('S','R') NOT NULL DEFAULT 'R' COMMENT '任务状态： S-停止；R-运行中',
  `next_runtime` INT UNSIGNED NOT NULL COMMENT '下次执行时间',
  `first_runtime` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '第一次执行时间',
  `last_runtime` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后一次执行时间',
  `crontab` VARCHAR(256) NULL COMMENT 'crontab表达式',
  `options` LONGTEXT NULL COMMENT '配置信息（JSON格式）',
  `remark` TEXT NULL COMMENT '描述信息',
  PRIMARY KEY (`id`),
  INDEX `IDX_USER_ID` (`user_id` ASC),
  INDEX `IDX_STATUS_RT` (`status` ASC, `next_runtime` DESC)
) ENGINE = InnoDB DEFAULT CHARACTER SET={encoding} COMMENT = '任务列表'
SQL;

$tables['1.2.0'][] = <<<SQL
CREATE TABLE IF NOT EXISTS `{prefix}task_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT UNSIGNED NOT NULL COMMENT '任务ID',
  `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
  `status` ENUM('P', 'R', 'F', 'E') NOT NULL DEFAULT 'P' COMMENT '状态： P-待运行;R-运行中;F-完成;E-出错',
  `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '任务进度',
  `start_time` INT UNSIGNED NOT NULL COMMENT '下一次时间',
  `end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `retried` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已经重试次数',
  `options` LONGTEXT NULL COMMENT '配置与数据（JSON格式）',
  `msg` TEXT NULL COMMENT '出错信息',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `UDX_TASK_ID` (`task_id` ASC,`start_time` DESC),
  INDEX `IDX_STATUS_RT` (`status` ASC, `start_time` DESC)
)ENGINE = InnoDB DEFAULT CHARACTER SET={encoding} COMMENT = '任务队列'
SQL;

$tables['1.2.0'][] = <<<SQL
CREATE TABLE IF NOT EXISTS `{prefix}task_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_queue_id` INT UNSIGNED NOT NULL COMMENT '任务实例ID',
  `create_time` INT UNSIGNED NOT NULL COMMENT '记录时间',
  `content` TEXT NOT NULL COMMENT '日志内容',
  PRIMARY KEY (`id`),
  INDEX `FDX_TASK_QID` (`task_queue_id` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET={encoding} COMMENT = '任务执行日志'
SQL;

