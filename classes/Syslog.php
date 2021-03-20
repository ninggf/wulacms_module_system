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
     * @param string      $logger
     * @param string      $message
     * @param string      $action
     * @param int         $uid
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public static function info(string $logger, string $message, string $action = '', int $uid = 0, ?string $oldValue = null, ?string $newValue = null) {
        self::_log('INFO', $uid, $logger, $message, $action, $oldValue, $newValue);
    }

    /**
     * 警告.
     *
     * @param string      $logger
     * @param string      $message
     * @param string      $action
     * @param int         $uid
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public static function warn(string $logger, string $message, string $action = '', int $uid = 0, ?string $oldValue = null, ?string $newValue = null) {
        self::_log('WARN', $uid, $logger, $message, $action, $oldValue, $newValue);
    }

    /**
     * 错误日志.
     *
     * @param string      $logger
     * @param string      $message
     * @param string      $action
     * @param int         $uid
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public static function error(string $logger, string $message, string $action = '', int $uid = 0, ?string $oldValue = null, ?string $newValue = null) {
        self::_log('ERROR', $uid, $logger, $message, $action, $oldValue, $newValue);
    }

    /**
     * 日志器.
     * @return ILogger[]
     */
    public static function loggers(): array {
        static $loggers = null;

        if ($loggers === null) {
            $loggers = apply_filter('system\Logger', []);
        }

        return $loggers;
    }

    /**
     * 获取指定日志器.
     *
     * @param string $id
     *
     * @return ILogger|null
     */
    public static function logger(string $id): ?ILogger {
        if ($id) {
            $loggers = self::loggers();

            return $loggers[ $id ] ?? null;
        }

        return null;
    }

    private static function _log($level, $uid, $logger, $message, $action, $v1, $v2) {
        if (!self::$table) {
            self::$table = new SyslogTable();
        }
        if (($log = self::logger($logger)) instanceof ILogger) {
            self::$table->log($level, $logger, $uid, $action, $log->convertMessage($message), $v1, $v2);
        }
    }
}