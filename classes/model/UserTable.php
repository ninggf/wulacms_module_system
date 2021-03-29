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

use wulaphp\app\App;
use wulaphp\auth\Passport;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\Table;
use wulaphp\util\TreeWalker;
use wulaphp\validator\Validator;

class UserTable extends Table {
    use Validator;

    const USER_STATUS = ['锁定', '正常'];
    /**
     * @required<update>
     * @num
     */
    public $id;
    /**
     * 账户.
     * @required<new>
     * @callback (checkUsername) => {account already exists}
     */
    public $name;
    /**
     * @required<new> => {password is required}
     */
    public $passwd;

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
     * @throws \wulaphp\validator\ValidateException
     */
    public function updateAccount(array $data, int $uid): bool {
        $data['id']          = $uid;
        $data['update_time'] = time();
        $w                   = ['id' => $uid];
        $this->validate($data, 'update');
        if (isset($data['passwd'])) {
            $data['passwd'] = Passport::passwd($data['passwd']);
        }

        return $this->update($data, $w);
    }

    /**
     * 添加账户
     *
     * @param array $data
     *
     * @return int
     * @Author LW 2021/3/18 17:03
     * @throws \wulaphp\validator\ValidateException
     */
    public function newAccount(array $data): int {
        $data['create_time'] = time();
        $data['update_time'] = time();
        $expireInt           = App::cfg('passwordExpInt@common');
        if(!isset($data['passwd_expire_at'])){
            if ($expireInt && $expireInt != '0') {
                $data['passwd_expire_at'] = strtotime($expireInt, time());
            } else {
                $data['passwd_expire_at'] = 0;
            }
        }
        unset($data['id']);
        $this->validate($data, 'new');
        if (isset($data['passwd'])) {
            $data['passwd'] = Passport::passwd($data['passwd']);
        }

        return $this->insert($data);
    }

    /**
     * @param array $uids
     * @param int $updateUid
     * @return bool
     * @Author LW 2021/3/18 19:37
     */
    public function delAccount(array $uids,int $updateUid): bool {
        //不能删除超级管理员
        return $this->recycle(['id IN' => $uids, 'is_super_user <>' => 1],$updateUid);
    }

    /**
     * 更新用户密码.
     *
     * @param int    $id       用户ID
     * @param string $password 密码(明文)
     *
     * @return array|null
     * @throws \wulaphp\validator\ValidateException
     */
    public function changePassword(int $id, string $password): ?array {
        $data = ['passwd' => $password];
        $this->validate($data, 'passwd');
        $expireInt = App::cfg('passwordExpInt@common');
        if ($expireInt && $expireInt != '0') {
            $data['passwd_expire_at'] = strtotime($expireInt, time());
        } else {
            $data['passwd_expire_at'] = 0;
        }
        $data['passwd'] = Passport::passwd($password);
        if ($this->update($data, ['id' => $id])) {
            return $data;
        }

        return null;
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

    protected function onValidatorInited() {
        $passwdStrength = App::cfg('passwdStrength', '1');
        $this->addRule('passwd', ["passwd($passwdStrength)"]);
    }
}