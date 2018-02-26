<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\task;

use wulaphp\db\View;

class TaskLog extends View {

	public function getLogs($id, $time = 0) {
		$where = ['task_id' => $id];
		if ($time) {
			$where['create_time >'] = $time;
		}
		$logs = $this->find($where)->desc('create_time')->limit(0, 200)->toArray();

		return $logs ? array_reverse($logs) : [];
	}
}