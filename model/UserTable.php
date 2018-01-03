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
use wulaphp\io\Request;
use wulaphp\validator\ValidateException;

class UserTable extends Table {
	public function roles() {
		return $this->belongsToMany(new RoleTable($this), 'user_role');
	}

	/**
	 * 更新用户最后登录信息.
	 *
	 * @param int $uid
	 *
	 * @return bool
	 */
	public function updateLoginInfo($uid) {
		if ($uid) {
			try {
				return $this->update(['lastip' => Request::getIp(), 'lastlogin' => time()], ['id' => $uid]);
			} catch (ValidateException $e) {
				return false;
			}
		}

		return false;
	}
}