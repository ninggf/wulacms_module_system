<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\model;

use wulaphp\db\Table;

class SyslogTable extends Table {
	public function log($level, $type, $uid, $ip, $message) {
		$log['type']    = $type;
		$log['level']   = $level;
		$log['user_id'] = intval($uid);
		$log['ip']      = $ip;
		$log['log']     = $message;
		$log['time']    = time();

		try {
			return $this->insert($log);
		} catch (\Exception $e) {
			return false;
		}
	}
}