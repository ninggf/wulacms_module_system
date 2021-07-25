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
use wulaphp\validator\Validator;

class RolePermission extends Table {
    use Validator;

    /**
     * @required 角色id必填
     */
    public $role_id;
    /**
     * @required 资源URI必填
     */
    public $uri;

    /**
     * @required 操作op必填
     */
    public $op;

    /**
     * 获取角色的所有权限
     *
     * @param int $rid
     *
     * @return array
     * @Author LW 2021/3/22 17:38
     */
    public function getPermissionByRoleId(int $rid): array {
        $sql = 'SELECT uri,op from {role_permission}  WHERE role_id = %d';
        $res = $this->dbconnection->query($sql, $rid);
        if (!empty($res)) {
            foreach ($res as &$value) {
                $value['resId'] = $value['op'] . ':' . $value['uri'];
            }
        }

        return $res;
    }

    /**
     * 更新角色权限
     *
     * @param int   $rid
     * @param array $permissions
     *
     * @return bool
     * @Author LW 2021/3/22 17:09
     */
    public function updatePermissionByRoleId(int $rid, array $permissions): bool {
        $res = $this->trans(function (DatabaseConnection $db) use ($rid, $permissions) {
            //删除角色当前权限
            if (!$db->cudx('DELETE FROM {role_permission} WHERE role_id = %d', $rid)) {
                return false;
            }
            //更新权限
            if ($permissions && !$db->inserts($permissions)->into('{role_permission}')->execute()) {
                return false;
            }
            //更新当前角色所有用户的权限版本
            if (!$this->updateRoleAclVer($rid, $db)) {
                return false;
            }

            try {
                fire('backend\grantPermission', $rid, $permissions);
            } catch (\Exception $e) {
                return false;
            }

            return true;
        });

        return !empty($res);
    }

    /**
     * 更新角色下所有用户的aclVer
     *
     * @param int                                 $rid
     * @param \wulaphp\db\DatabaseConnection|null $db
     *
     * @return bool
     * @Author LW 2021/3/22 17:21
     */
    private function updateRoleAclVer(int $rid, ?DatabaseConnection $db = null): bool {
        $db  = $db ?? $this->dbconnection;
        $sql = 'UPDATE {user} SET acl_ver = acl_ver + 1 WHERE id IN (SELECT user_id FROM {user_role} WHERE role_id = %d)';

        return $db->cudx($sql, $rid);
    }

}