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

use system\model\SyslogTable;
use wulaphp\io\Request;

class Syslog {
	private static $ip = '127.0.0.1';
	/**
	 * @var SyslogTable
	 */
	private static $table = null;

	/**
	 * 信息日志
	 *
	 * @param string     $message
	 * @param int|string $uid
	 * @param string     $app
	 */
	public static function info($message, $uid = 0, $app = 'system') {
		self::_log('INFO', $uid, $app, $message);
	}

	/**
	 * 警告日志
	 *
	 * @param string     $message
	 * @param int|string $uid
	 * @param string     $app
	 */
	public static function warn($message, $uid = 0, $app = 'system') {
		self::_log('WARN', $uid, $app, $message);
	}

	/**
	 * 错误日志
	 *
	 * @param string     $message
	 * @param int|string $uid
	 * @param string     $app
	 */
	public static function error($message, $uid = 0, $app = 'system') {
		self::_log('ERROR', $uid, $app, $message);
	}

	private static function _log($level, $uid, $type, $message) {
		if (!self::$table) {
			self::$ip    = Request::getIp();
			self::$table = new SyslogTable();
		}
		if (self::$table) {
			self::$table->log($level, $type, $uid, self::$ip, $message);
		}
	}
}