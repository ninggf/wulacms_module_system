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

use wulaphp\db\DatabaseConnection;
use wulaphp\db\Table;
use wulaphp\util\TreeNode;
use wulaphp\util\TreeWalker;
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
     *
     * @param array $role
     *
     * @return bool|int
     * @Author LW 2021/3/16 15:29
     */
    public function addRole(array $role) {
        $role['create_time'] = $role['update_time'] = time();
        return $this->insert($role);
    }

    /**
     * 更新角色信息
     *
     * @param array $role
     * @param int   $id
     *
     * @return bool|\wulaphp\db\sql\UpdateSQL
     * @Author LW 2021/3/16 16:15
     */
    public function updateRole(array $role, int $id) {

        return $this->update($role, $id);
    }

    /**
     * 删除角色
     *
     * @param array $ids
     * @param int   $tenant_id
     *
     * @return bool|\wulaphp\db\sql\DeleteSQL
     * @Author LW 2021/3/16 17:07
     */
    public function delRole(array $ids, int $tenant_id) {
        $where = ['id IN' => $ids, 'tenant_id' => $tenant_id];
        $res   = $this->trans(function (DatabaseConnection $db) use ($ids, $tenant_id, $where) {
            //更新用户acl
            $sqlUser = 'UPDATE {user} SET acl_ver = acl_ver + 1 WHERE id IN (SELECT user_id FROM {user_role} WHERE role_id IN (%d))';
            if(!$db->cudx($sqlUser, implode(',',$ids))){
                return false;
            }

            //删除role
            if (!$this->delete($where)) {
                return false;
            }
            //删除 role权限
            $sqlRolePer = 'DELETE FROM {role_permission} WHERE role_id IN (%d)';
            if (!$db->cudx($sqlRolePer, implode(',', $ids))) {
                return false;
            }
            //删除 user_role
            $sqlUserRole = 'DELETE FROM {user_role} WHERE role_id IN (%d)';
            if (!$db->cudx($sqlUserRole, implode(',', $ids))) {
                return false;
            }
            return true;
        });

        return !empty($res);
    }

    /**
     * 检查当前角色的pid是否是我的子类id
     *
     * @param int $pid      当前pid
     * @param int $id       当前角色id
     * @param int $tenantId 租户id
     *
     * @return bool
     * @Author LW 2021/3/18 10:43
     */
    public function checkRoleIsMySubRole(int $pid, int $id, int $tenantId): bool {
        if ($pid == $id) {
            return false;
        }
        $nodes    = $this->roleNodes($tenantId);
        $role     = $nodes->get($id);
        $children = [];
        $role->allChildren($children);
        $childrenIds = array_keys($children);

        return in_array($pid, $childrenIds);
    }

    /**
     * 查询当前租户下所有的角色tree节点
     *
     * @param int $tenantId
     *
     * @return \wulaphp\util\TreeNode
     * @Author LW 2021/3/18 10:42
     */
    public function roleNodes(int $tenantId): TreeNode {
        $roles = $this->getRolesByTenantId($tenantId);

        return TreeWalker::build($roles);
    }
}