<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes\model;

use wulaphp\db\Table;
use wulaphp\io\Request;

/**
 * 系统日志表.
 *
 * @package system\classes\model
 */
class SyslogTable extends Table {
    protected $autoIncrement = false;//不需要获取自增主键值

    /**
     * @param string      $level
     * @param string      $logger
     * @param int         $uid
     * @param string      $action
     * @param string      $message
     * @param string|null $oldValue
     * @param string|null $newValue
     *
     * @return bool
     */
    public function log(string $level, string $logger, int $uid, string $action, string $message, ?string $oldValue = null, ?string $newValue = null): bool {
        static $tenantids = [];
        $log['create_time'] = time();
        $log['user_id']     = intval($uid);
        $log['tenant_id']   = defined('APP_TENANT_ID') ? APP_TENANT_ID : 0;
        $log['logger']      = $logger;
        $log['level']       = $level;
        $log['operation']   = $action;
        $log['ip']          = Request::getIp();
        $log['message']     = $message;
        $log['value1']      = $oldValue;
        $log['value2']      = $newValue;

        try {
            return $this->insert($log);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 警告日志.
     *
     * @param string      $type
     * @param int         $uid
     * @param string      $action
     * @param string      $message
     * @param string|null $oldValue
     * @param string|null $newValue
     *
     * @return bool
     */
    public function warn(string $type, int $uid, string $action, string $message, ?string $oldValue = null, ?string $newValue = null): bool {
        return $this->log('WARN', $type, $uid, $action, $message, $oldValue, $newValue);
    }

    /**
     * 信息日志.
     *
     * @param string      $type
     * @param int         $uid
     * @param string      $action
     * @param string      $message
     * @param string|null $oldValue
     * @param string|null $newValue
     *
     * @return bool
     */
    public function info(string $type, int $uid, string $action, string $message, ?string $oldValue = null, ?string $newValue = null): bool {
        return $this->log('INFO', $type, $uid, $action, $message, $oldValue, $newValue);
    }

    /**
     * 错误日志.
     *
     * @param string      $type
     * @param int         $uid
     * @param string      $action
     * @param string      $message
     * @param string|null $oldValue
     * @param string|null $newValue
     *
     * @return bool
     */
    public function error(string $type, int $uid, string $action, string $message, ?string $oldValue = null, ?string $newValue = null): bool {
        return $this->log('ERROR', $type, $uid, $action, $message, $oldValue, $newValue);
    }
}