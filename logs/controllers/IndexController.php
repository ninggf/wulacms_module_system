<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\logs\controllers;

use backend\classes\IFramePageController;
use system\model\SyslogTable;
use wulaphp\db\sql\Condition;

/**
 * Class IndexController
 * @package system\logs\controllers
 * @acl     m:system/logs
 */
class IndexController extends IFramePageController {
	public function index() {
		$groups         = ['system' => '系统日志', 'accesslog' => '登录日志'];
		$data['groups'] = apply_filter('system\logs', $groups);

		return $this->render($data);
	}

	public function data($q, $type, $level, $count) {
		$table = new SyslogTable();
		$query = $table->select('Syslog.*,U.nickname,U.username')->page()->sort();
		$query->join('{user} AS U', 'Syslog.user_id = U.id');
		$where = [];
		if ($type) {
			$where['type'] = $type;
		}
		if ($level) {
			$where['level'] = $level;
		}
		if ($q) {
			$qw = Condition::parseSearchExpression($q, ['日期 & 时间' => 'time', '用户' => 'nickname', 'IP' => 'ip']);
			if ($qw) {
				if (isset($qw['nickname']) && is_numeric($qw['nickname'])) {
					$qw['user_id'] = $qw['nickname'];
					unset($qw['nickname']);
				}
				$query->where($qw);
			} else {
				$where['nickname LIKE'] = "%$q%";
			}
		}
		$query->where($where);
		$data['items']  = $query->toArray();
		$data['total']  = $count ? $query->total('Syslog.id') : '';
		$groups         = ['system' => '系统日志', 'accesslog' => '登录日志'];
		$data['groups'] = apply_filter('system\logs', $groups);
		$data['tdCls']  = ['INFO' => 'info', 'WARN' => 'warning', 'ERROR' => 'danger'];

		return view($data);
	}
}