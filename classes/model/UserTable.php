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
use wulaphp\form\FormTable;
use wulaphp\util\TreeWalker;

class UserTable extends FormTable {
    /**
     * @type int
     * @required
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
     * @throws \wulaphp\db\DialectException
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

    public function newAccount($data) {
        return false;
    }

    /**
     * 更新用户密码.
     *
     * @param int    $id       用户ID
     * @param string $password 密码(明文)
     *
     * @return string
     */
    public function chagnePassword(int $id, string $password): string {
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
     * 用户拥有的角色.
     *
     * @param int $uid
     *
     * @return array
     */
    public function myRoles(int $uid): array {
        $roleSql = 'SELECT R.* from {role} AS R INNER JOIN {user} AS U ON R.tenant_id = U.tenant_id WHERE U.id = %d';
        $roles   = $this->dbconnection->query($roleSql, $uid);
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
}