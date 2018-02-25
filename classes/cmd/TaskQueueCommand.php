<?php
declare(ticks=10);
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes\cmd;

use system\classes\Task;
use wulaphp\app\App;
use wulaphp\artisan\ArtisanMonitoredTask;
use wulaphp\db\DatabaseConnection;

class TaskQueueCommand extends ArtisanMonitoredTask {
	/**
	 * @var \wulaphp\db\DatabaseConnection
	 */
	private $db;
	private $num;
	private $interval;

	public function cmd() {
		return 'task:queue';
	}

	public function desc() {
		return 'run task in background';
	}

	protected function setUp(&$options) {
		$this->workerCount = aryget('c', $options, 2);
		$this->num         = aryget('n', $options, 10);
		$this->interval    = aryget('i', $options, 60);
		$maxM              = aryget('m', $options, '128M');
		$this->setMaxMemory($maxM);
	}

	protected function getOpts() {
		return [
			'c::count'    => 'The number of child processes.[2]',
			'n::num'      => 'The number of tasks each child process should execute before respawning.[10]',
			'm::memory'   => 'Maximum amount of memory a child process may consume.[128M]',
			'i::interval' => 'The interval(second) of retry.[60]'
		];
	}

	protected function argValid($options) {
		if (isset($options['c']) && !preg_match('/^[1-9]\d*$/', $options['c'])) {
			return false;
		}

		if (isset($options['n']) && !preg_match('/^[1-9]\d*$/', $options['n'])) {
			return false;
		}

		if (isset($options['m']) && !preg_match('/^[1-9]\d*(m|k|g)$/i', $options['m'])) {
			return false;
		}

		if (isset($options['i']) && !preg_match('/^[1-9]\d*$/', $options['i'])) {
			return false;
		}

		return true;
	}

	/**
	 * 从数据库中取出任务执行.
	 *
	 * @param array $options
	 *
	 * @return bool
	 * @throws
	 */
	protected function loop($options) {
		$cnt      = $this->num;
		$this->db = App::db();
		while ($cnt > 0 && !$this->shutdown) {
			$q = $this->db->select('id,task,options,retryCnt,retry,retryInt');
			$q->from('{task_queue}')->where([
				'status'   => 'P',
				'run_time' => 0,
				'runat <=' => time()
			])->desc('priority')->desc('runat')->asc('create_time')->limit(0, 1);
			$rst = $this->db->trans(function (DatabaseConnection $db, $data) {
				//更新任务状态
				$sql = 'UPDATE {task_queue} SET status = %s, run_time = %d WHERE id = %s AND status = \'P\' AND run_time = 0';
				$rst = $db->cud($sql, 'R', time(), $data['id']);

				return $rst ? $data : false;
			}, $error, $q->locker());
			$q   = null;
			//处理任务
			if ($rst) {
				$this->logi('start task: ' . $rst['id']);
				$cls = $rst['task'];
				if (!is_subclass_of($cls, Task::class)) {
					$sql = 'UPDATE {task_queue} SET status = %s, finish_time = %d, msg = %s WHERE id = %s';
					$this->db->cud($sql, 'E', time(), 'Task is not subclass of ' . Task::class, $rst['id']);
					$this->loge('error task: ' . $rst['id'] . ' [404]');
					continue;
				}
				$opts = @json_decode($rst['options'], true);
				/**@var \system\classes\Task $clz */
				$clz = new $cls($rst['id'], $this->db, $opts);
				try {
					$msg = $clz->run();
				} catch (\Exception $e) {
					$msg = $e->getMessage();
				}

				if ($msg === true) {
					$sql = 'UPDATE {task_queue} SET status = %s, finish_time = %d, progress = 100 WHERE id = %s';
					$this->db->cud($sql, 'F', time(), $rst['id']);
					$this->logi('finish task: ' . $rst['id']);
				} else {
					if ($rst['retry'] < $rst['retryCnt']) {
						$intv = $rst['retryInt'] ? $rst['retryInt'] : $this->interval;
						$sql  = 'UPDATE {task_queue} SET run_time = 0, retry = retry + 1, status = %s,runat = %d, msg = %s WHERE id = %s';
						$this->db->cud($sql, 'P', time() + $intv, $msg, $rst['id']);
						$this->loge('retry task: ' . $rst['id'] . ' [' . ($rst['retry'] + 1) . ']');
					} else {
						$sql = 'UPDATE {task_queue} SET status = %s, finish_time = %d, msg = %s WHERE id = %s';
						$this->db->cud($sql, 'E', time(), $msg, $rst['id']);
						$this->loge('error task: ' . $rst['id'] . ' (' . $msg . ')');
					}
				}
				$cnt--;//此时才可以算完成一个任务.
			} else {
				sleep(1);
			}
		}
		$this->db->close();

		return false;
	}
}