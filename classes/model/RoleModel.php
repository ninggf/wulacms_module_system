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

class RoleModel extends Table {
    /**
     * @orm
     * @return array
     */
    public function users(): array {
        return $this->belongsToMany(new UserTable($this), 'user_role');
    }

    /**
     * @orm
     * @return array
     */
    public function permissions(): array {
        return $this->hasMany(new RolePermission($this));
    }

    /**
     * 根据用户ID获取角色列表.
     *
     * @param int $uid
     *
     * @return array
     */
    public function getRolesByUserId(int $uid): array {
        $roleSql = 'SELECT R.* from {role} AS R INNER JOIN {user} AS U ON R.tenant_id = U.tenant_id WHERE U.id = %d';

        return $this->dbconnection->query($roleSql, $uid);
    }

    /**
     * 根据租户ID获取角色列表.
     *
     * @param int $tid
     *
     * @return array
     */
    public function getRolesByTanentId(int $tid): array {
        $roleSql = 'SELECT R.* from {role} AS R  WHERE R.tenant_id = %d';

        return $this->dbconnection->query($roleSql, $tid);
    }
}