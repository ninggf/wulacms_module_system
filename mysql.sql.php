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
  `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID，默认为0，系统用户',
  `is_super_user` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否是超级管理员',
  `name` VARCHAR(32) NOT NULL COMMENT '用于登录',
  `status` TINYINT NOT NULL COMMENT '状态：0锁定；1正常；2重设密码；3密码过期',
  `passwd` VARCHAR(255) NOT NULL COMMENT '密码HASH',
  `passwd_expire_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '密码过期时间',
  `acl_ver` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '权限版本',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `UDX_NAME` (`name` ASC),
  INDEX `IDX_TENANT_ID` USING BTREE (`tenant_id` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET = {encoding} COMMENT = '系统管理员'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_meta` (
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    name VARCHAR(16) NOT NULL COMMENT '元数据Key',
    value TEXT NULL COMMENT '元数据值',
    PRIMARY KEY (user_id , name)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户元数据'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}syslog` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    create_time INT UNSIGNED NOT NULL COMMENT '日志时间',
    tenant_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    logger VARCHAR(16) NOT NULL COMMENT '日志类型（解析器）',
    level ENUM('WARN', 'INFO', 'ERROR') NOT NULL COMMENT '日志级别',
    operation VARCHAR(16) NOT NULL COMMENT '操作',
    ip VARCHAR(64) NOT NULL COMMENT 'IP',
    message TEXT NULL COMMENT '日志内容',
    value1 TEXT NULL COMMENT 'Old Value',
    value2 TEXT NULL COMMENT 'New Value',
    PRIMARY KEY (id),
    INDEX IDX_LEVEL_TYPE (tenant_id ASC, logger ASC, level ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='系统日志'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}setting` (
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
    `group` VARCHAR(16) NOT NULL DEFAULT 'default' COMMENT '配置组',
    `name` VARCHAR(16) NOT NULL COMMENT '配置项名',
    `value` TEXT NULL COMMENT '配置项值',
    PRIMARY KEY (`tenant_id`, `group`, `name`)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='系统设置'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}role` (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
    pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '继承自角色',
    tenant_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
    name VARCHAR(32) NOT NULL COMMENT '角色代码',
    role varchar(32) NOT NULL COMMENT '角色名称',
    remark VARCHAR(256) NULL COMMENT '说明',
    PRIMARY KEY (id),
    UNIQUE INDEX UDX_TENANT_ROLE (tenant_id ASC , name ASC),
    INDEX IDX_PID (pid ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='用户角色'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}user_role` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
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
    `agent` VARCHAR(64) NOT NULL COMMENT '客户端',
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