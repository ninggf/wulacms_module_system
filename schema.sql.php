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

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}module` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(32) NOT NULL COMMENT '模块ID',
    `version` VARCHAR(32) NOT NULL COMMENT '版本',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '0禁用1启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '安装时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后一次升级时间',
    `checkupdate` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否检测升级信息',
    `kernel` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否是内核内置模块',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_NAME` (`name` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='模块表'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group` VARCHAR(64) NOT NULL COMMENT '配置组',
    `name` VARCHAR(32) NOT NULL COMMENT '字段名',
    `value` TEXT NULL COMMENT '值',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_NAME` (`group` ASC , `name` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='配置表'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}role` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL COMMENT '角色名称',
    `level` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色的Level',
    `note` VARCHAR(256) NULL COMMENT '说明',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_NAME` (`name` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户角色'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `username` VARCHAR(32) NOT NULL COMMENT '用户名',
    `nickname` VARCHAR(32) NULL COMMENT '昵称',
    `phone` VARCHAR(16) NULL COMMENT '手机号',
    `email` VARCHAR(128) NULL COMMENT '邮箱地址',
    `lastip` VARCHAR(64) NOT NULL DEFAULT '127.0.0.1' COMMENT '上次登录IP',
    `lastlogin` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '上次登录时间',
    `status` SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1正常,0禁用,2密码过期',
    `hash` VARCHAR(255) NOT NULL COMMENT '密码HASH',
    `avatar` VARCHAR(512) NULL COMMENT '头像',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_USERNAME` (`username` ASC),
    INDEX `IDX_STATUS` (`status` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户表'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_role` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id` , `role_id`)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户角色表'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_meta` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户编号',
    `name` VARCHAR(32) NOT NULL COMMENT '数据名称',
    `value` TEXT NULL COMMENT '数据值',
    `ivalue` INT NOT NULL DEFAULT 0 COMMENT '数值型值',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_USERMETA` (`user_id` ASC , `name` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户元数据'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}acl` (
    `role_id` SMALLINT UNSIGNED NOT NULL COMMENT '角色ID',
    `res` VARCHAR(64) NOT NULL COMMENT '资源ID',
    `allowed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否允许',
    `priority` SMALLINT UNSIGNED NOT NULL DEFAULT 999 COMMENT '优先级，数值越大优先级越小',
    `extra` TEXT NULL COMMENT '额外配置的数据，JSON格式.',
    UNIQUE INDEX `UDX_ROLE_RES` (`role_id` ASC , `res` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='访问控制列表'";


$tables['1.0.0'][] = "INSERT INTO `role` (`id`,`name`,`note`) VALUES (2,'管理员','网站管理员'),(1,'站长','拥有所有权限')";

$tables['1.0.0'][] = "CREATE TABLE `{prefix}user_gridcfg` (
  `uid` int(10) unsigned NOT NULL COMMENT '用户编号',
  `grid` varchar(48) NOT NULL COMMENT '表格ID',
  `columns` text COMMENT '显示列表',
  PRIMARY KEY (`uid`,`grid`)
) ENGINE=InnoDB DEFAULT CHARACTER SET={encoding} COMMENT='用户表格列'";

$tables['1.1.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}task_queue` (
    `id` VARCHAR(32) NOT NULL COMMENT '任务编号',
    `create_time` INT UNSIGNED NOT NULL,
    `name` VARCHAR(256) NOT NULL COMMENT '任务名',
    `task` VARCHAR(256) NOT NULL COMMENT '任务类',
    `priority` ENUM('I', 'H', 'L') NOT NULL DEFAULT 'I' COMMENT '优先级',
    `progress` SMALLINT NOT NULL DEFAULT 0 COMMENT '进度',
    `status` ENUM('D','P', 'R', 'F', 'E') NOT NULL DEFAULT 'P' COMMENT '状态',
    `runat` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '指定运行时间',
    `retryCnt` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `retryInt` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试间隔',
    `retry` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败后尝试次数',
    `run_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '运行时间',
    `finish_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
    `options` TEXT NULL COMMENT '配置选项(json)',
    `msg` TEXT NULL COMMENT '错误信息',
    PRIMARY KEY (`id`),
    INDEX `IDX_STATUS` (`status` ASC , `runat` ASC , `retry` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='任务队列'";

$tables['1.1.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}task_log` (
    `task_id` VARCHAR(32) NOT NULL,
    `create_time` INT UNSIGNED NOT NULL,
    `content` TEXT NULL,
    INDEX `FK_TASK_ID` (`task_id` ASC , `create_time` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='任务执行日志'";

$tables['2.0.1'][] = "ALTER TABLE `{prefix}user`
ADD COLUMN `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主账户ID' AFTER `id`";