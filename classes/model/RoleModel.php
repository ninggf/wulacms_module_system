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
use wulaphp\validator\Validator;

class RoleModel extends Table {
    use Validator;

    /**
     * @required 角色代码必填
     */
    public $name;
    /**
     * @required 角色名称必填
     */
    public $role;

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
    public function getRolesByTenantId(int $tid): array {
        $roleSql = 'SELECT R.* from {role} AS R  WHERE R.tenant_id = %d';

        return $this->dbconnection->query($roleSql, $tid);
    }

    /**
     * 添加角色
     * @param array $role
     *
     * @return bool|int
     * @Author LW 2021/3/16 15:29
     */
    public function addRole(array $role) {
        return $this->insert($role);
    }

    /**
     * 更新角色信息
     * @param array $role
     * @param int   $id
     *
     * @return bool|\wulaphp\db\sql\UpdateSQL
     * @Author LW 2021/3/16 16:15
     */
    public function updateRole(array $role,int $id){
        return $this->update($role, $id);
    }

    /**
     * 删除角色
     * @param array $ids
     *
     * @return bool|\wulaphp\db\sql\DeleteSQL
     * @Author LW 2021/3/16 17:07
     */
    public function delRole(array $ids){
        return $this->delete(['id IN' => $ids]);
    }
}