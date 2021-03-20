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

use wulaphp\auth\Passport;
use wulaphp\db\DatabaseConnection;
use wulaphp\form\FormTable;
use wulaphp\util\TreeWalker;

class UserTable extends FormTable {
    const USER_STATUS = ['锁定','正常','重设密码','密码过期'];
    /**
     * @type int
     */
    public $id;
    /**
     * 账户.
     *
     * @type string
     * @required (id)
     * @callback (checkUsername) => {account allready exists}
     */
    public $name;

    /**
     * @return array
     */
    public function roles(): array {
        return $this->belongsToMany(new RoleModel($this), 'user_role');
    }

    /**
     * @return array
     */
    public function meta(): array {
        return $this->hasMany(new UserMetaTable());
    }

    /**
     * 更新账户信息.
     *
     * @param array $data
     * @param int   $uid
     *
     * @return bool 更新成功返回true,反之返回false.
     */
    public function updateAccount(array $data, int $uid): bool {
        if (!isset($data['id']) || $data['id'] != $uid) {
            $data['id'] = $uid;
        }
        $w = ['id' => $uid];
        return $this->update($data, $w);
    }

    /**
     * 添加账户
     * @param array $data
     *
     * @return int
     * @Author LW 2021/3/18 17:03
     */
    public function newAccount(array $data): int {
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $this->insert($data);
    }

    /**
     * @param array $uids
     *
     * @return bool
     * @Author LW 2021/3/18 19:37
     */
    public function delAccount(array $uids):bool{
        return $this->delete(['id IN' => $uids]);
    }


    /**
     * 更新用户密码.
     *
     * @param int    $id       用户ID
     * @param string $password 密码(明文)
     *
     * @return string
     */
    public function changePassword(int $id, string $password): string {
        $data = ['passwd' => Passport::passwd($password)];
        if ($this->update($data, ['id' => $id])) {
            return $data['passwd'];
        }

        return '';
    }

    /**
     * 验证name是否合法.
     *
     * @param string $value
     * @param array  $data
     * @param string $msg
     *
     * @return bool|string
     */
    public function checkUsername(string $value, array $data, string $msg) {
        $id            = unget($data, 'id');
        $where['name'] = $value;
        if ($id) {
            $where['id <>'] = $id;
        }

        if ($this->exist($where)) {
            return $msg;
        }

        return true;
    }

    /**
     * 用户是否拥有角色.
     *
     * @param string|int $uid
     * @param string     $role
     *
     * @return int
     */
    public function hasRole($uid, string $role): int {
        $sql  = <<<SQL
SELECT UR.role_id FROM {user_role} AS UR
  INNER JOIN {role} AS R ON (UR.role_id = R.id AND R.name= %s)
  WHERE UR.user_id = %d 
SQL;
        $role = $this->db()->queryOne($sql, $role, $uid);
        if ($role) {
            return $role['role_id'];
        } else {
            return 0;
        }
    }

    /**
     * 设置用户角色.
     *
     * @param int   $uid
     * @param array $rids
     *
     * @return bool
     */
    public function setRoles(int $uid, array $rids): bool {
        $data = [];
        if ($rids) {
            $rm    = new RoleModel($this->dbconnection);
            $roles = $rm->getRolesByUserId($uid);
            foreach ($roles as $r) {
                if (in_array($r['id'], $rids)) {
                    $d['user_id'] = $uid;
                    $d['role_id'] = $r['id'];
                    $data[]       = $d;
                }
            }
        }

        $rst = $this->trans(function (DatabaseConnection $db) use ($uid, $data, $rids) {
            # 删除已有的角色
            if (!$db->cudx('DELETE FROM {user_role} WHERE user_id = %d', $uid)) {
                return false;
            }
            # 更新权限版本
            if (!$this->updateAclVer($uid, $db)) {
                return false;
            }
            # 更新角色
            if ($data && !$db->inserts($data)->into('{user_role}')->execute()) {
                return false;
            }

            return true;
        });

        return !empty($rst);
    }

    /**
     * 用户拥有的角色.
     *
     * @param int $uid
     *
     * @return array
     */
    public function myRoles(int $uid): array {
        $rm      = new RoleModel($this->dbconnection);
        $roles   = $rm->getRolesByUserId($uid);
        $nodes   = TreeWalker::build($roles);
        $sql     = 'SELECT R.* FROM {user_role} AS UR INNER JOIN {role} AS R ON (UR.role_id = R.id) WHERE UR.user_id = %d ORDER BY UR.role_id ASC';
        $roles   = $this->dbconnection->query($sql, $uid);
        $myRoles = [];
        foreach ($roles as $r) {
            $rid = $r['id'];
            $rn  = $nodes->get($rid);
            if ($rn) {
                foreach ($rn->parents() as $p) {
                    $myRoles[ $p['id'] ] = $p;
                }
            }
        }

        return $myRoles;
    }

    /**
     * 更新用户权限版本.
     *
     * @param int                                 $uid
     * @param \wulaphp\db\DatabaseConnection|null $db
     *
     * @return bool
     */
    public function updateAclVer(int $uid, ?DatabaseConnection $db = null): bool {
        $db = $db ?? $this->dbconnection;

        return $db->cudx('UPDATE {user} SET acl_ver = acl_ver + 1 WHERE id = %d', $uid);
    }
}