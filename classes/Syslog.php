<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes;

use system\classes\model\SyslogTable;

/**
 * 审计日志协助类.
 *
 * @package system\classes
 */
class Syslog {
    private static $ip = '127.0.0.1';
    /**
     * @var SyslogTable
     */
    private static $table = null;

    /**
     * 信息.
     *
     * @param string      $type
     * @param string      $message
     * @param string      $action
     * @param int         $uid
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public static function info(string $type, string $message, string $action = '', int $uid = 0, ?string $oldValue = null, ?string $newValue = null) {
        self::_log('INFO', $uid, $type, $message, $action, $oldValue, $newValue);
    }

    /**
     * 警告.
     *
     * @param string      $type
     * @param string      $message
     * @param string      $action
     * @param int         $uid
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public static function warn(string $type, string $message, string $action = '', int $uid = 0, ?string $oldValue = null, ?string $newValue = null) {
        self::_log('WARN', $uid, $type, $message, $action, $oldValue, $newValue);
    }

    /**
     * 错误日志.
     *
     * @param string      $type
     * @param string      $message
     * @param string      $action
     * @param int         $uid
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public static function error(string $type, string $message, string $action = '', int $uid = 0, ?string $oldValue = null, ?string $newValue = null) {
        self::_log('ERROR', $uid, $type, $message, $action, $oldValue, $newValue);
    }

    private static function _log($level, $uid, $type, $message, $action, $v1, $v2) {
        if (!self::$table) {
            self::$table = new SyslogTable();
        }
        self::$table->log($level, $type, $uid, $action, $message, $v1, $v2);
    }
}