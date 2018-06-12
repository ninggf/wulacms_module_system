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

use system\classes\Task;
use wulaphp\form\FormTable;

class ScriptTask extends Task {
	public function run() {
		$script = aryget('script', $this->options);
		if (!$script) {
			return '未指定要运行的脚本';
		}
		$rs = APPROOT . 'scripts' . DS . $script;
		if (!is_file($rs) || preg_match('\.php$', $script)) {
			return $script . '文件不存在';
		}
		try {
			$cmd = escapeshellcmd(PHP_BINARY);
			$arg = escapeshellarg($rs);
			@exec($cmd . ' ' . $arg . '  2>&1', $output, $rtn);
			if ($rtn === 0) {
				return true;
			}
			if ($rtn && $output) {
				return implode("\n\t", $output);
			} else if ($rtn) {
				return '脚本返回:' . $rtn;
			}
		} catch (\Exception $e) {
			return $e->getMessage();
		}

		return true;
	}

	public function getForm() {
		return new ScriptTaskForm(true);
	}

	/**
	 * 运行新的脚本.
	 *
	 * @param string $script   要执行的脚本
	 * @param string $name     任务名称
	 * @param int    $tryCnt   重试次数
	 * @param int    $runat    定时
	 * @param int    $interval 重试间隔
	 *
	 * @return bool|string
	 */
	public static function runScript($script, $name, $tryCnt = 0, $runat = 0, $interval = 0) {
		$rs = APPROOT . 'scripts' . DS . $script;
		if (!is_file($rs) || preg_match('\.php$', $script)) {
			return false;
		}
		$tq = new TaskQueue();

		return $tq->newTask($name, 'system\task\ScriptTask', 'P', $tryCnt, $runat, ['script' => $script], $interval);
	}
}

class ScriptTaskForm extends FormTable {
	public $table = null;

	/**
	 * 脚本
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 */
	public $script;
}