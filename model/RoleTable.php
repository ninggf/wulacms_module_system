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

class RoleTable extends Table {
	/**
	 * 拥有角色的用户.
	 * @orm
	 * @return array|null
	 */
	public function users() {
		return $this->belongsToMany(new UserTable($this), 'user_role');
	}

	/**
	 * 角色对应的访问控制列表.
	 * @orm
	 * @return array
	 */
	public function acls() {
		return $this->hasMany(new AclTable($this));
	}
}