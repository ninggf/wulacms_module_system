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

use wulaphp\auth\Passport;
use wulaphp\db\DatabaseConnection;
use wulaphp\form\FormTable;
use wulaphp\io\Request;
use wulaphp\validator\JQueryValidator;
use wulaphp\validator\ValidateException;

class UserTable extends FormTable {
    use JQueryValidator;
    /**
     * @var \backend\form\HiddenField
     * @type int
     */
    public $id;
    /**
     * 登录账户(<b class="text-danger">*</b>)
     * @var \backend\form\TextField
     * @type string
     * @required
     * @callback (checkUsername(id)) => Account exist
     * @layout 2, col-xs-6
     */
    public $username;
    /**
     * 昵称(<b class="text-danger">*</b>)
     * @var \backend\form\TextField
     * @type string
     * @required
     * @layout 2, col-xs-6
     */
    public $nickname;
    /**
     * 手机号
     * @var \backend\form\TextField
     * @type string
     * @phone
     * @layout 3, col-xs-6
     */
    public $phone;
    /**
     * 邮件地址
     * @var \backend\form\TextField
     * @type string
     * @email
     * @layout 3,col-xs-6
     */
    public $email;

    public function roles() {
        return $this->belongsToMany(new RoleTable($this), 'user_role');
    }

    /**
     * 更新用户最后登录信息.
     *
     * @param int $uid
     * @param int $time
     *
     * @return bool
     */
    public function updateLoginInfo($uid, $time) {
        if ($uid) {
            try {
                return $this->update(['lastip' => Request::getIp(), 'lastlogin' => $time], ['id' => $uid]);
            } catch (ValidateException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * 更新账户信息.
     *
     * @param array      $data
     * @param string|int $uid
     *
     * @return bool 更新成功返回true,反之返回false.
     */
    public function updateAccount($data, $uid) {
        try {
            if (isset($data['roles'])) {
                $rst = $this->trans(function (DatabaseConnection $db) use ($data, $uid) {
                    $id    = $data['id'];
                    $roles = $data['roles'];
                    unset($data['roles']);
                    $w = ['id' => $id];
                    if ($uid != 1) {
                        $w['pid'] = $uid;
                    }

                    if (!$this->update($data, $w)) {
                        return false;
                    }
                    if (!$db->delete()->from('{user_role}')->where(['user_id' => $id])->exec()) {
                        return false;
                    }
                    if ($roles) {
                        $rs = [];
                        array_unique($roles);
                        foreach ($roles as $rid) {
                            $rs[] = ['user_id' => $id, 'role_id' => $rid];
                        }
                        if (!$db->insert($rs, true)->into('{user_role}')->exec()) {
                            return false;
                        }
                    }

                    return true;
                });

                return $rst;
            } else {
                $id = isset($data['id']) ? $data['id'] : 0;
                if (!$id) {
                    return false;
                }
                $w = ['id' => $id];
                if ($uid != 1) {
                    $w['pid'] = $uid;
                }

                return $this->update($data, $w);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function newAccount($data) {
        $id = $this->trans(function (DatabaseConnection $db) use ($data) {
            if (isset($data['roles'])) {
                $roles = $data['roles'];
                unset($data['roles']);
            } else {
                $roles = [];
            }
            $id = $this->insert($data);
            if (!$id) {
                return false;
            }
            if ($roles) {
                $rs = [];
                array_unique($roles);
                foreach ($roles as $rid) {
                    $rs[] = ['user_id' => $id, 'role_id' => $rid];
                }
                if (!$db->insert($rs, true)->into('{user_role}')->exec()) {
                    return false;
                }
            }
            //用户默认小部件
            $widgets = json_encode(['welcome' => ['id' => 'welcome', 'pos' => 1, 'width' => 12, 'name' => '欢迎']]);

            $this->db()->insert([
                'user_id' => $id,
                'name'    => 'widgets',
                'value'   => $widgets
            ])->into('{user_meta}')->exec();

            return $id;
        });

        return $id;
    }

    /**
     * 更新用户密码.
     *
     * @param int    $id       用户ID
     * @param string $password 密码(明文)
     *
     * @return bool
     * @throws  \Exception
     */
    public function chagnePassword($id, $password) {
        return $this->update(['hash' => Passport::passwd($password)], ['id' => $id]);
    }

    public function checkUsername($value, $data, $msg) {
        $id                = unget($data, 'id');
        $where['username'] = $value;
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
     * @return int|bool
     */
    public function hasRole($uid, $role) {
        $sql  = <<<SQL
SELECT UR.role_id FROM {user_role} AS UR
  INNER JOIN {role} AS R ON (UR.role_id = R.id AND R.name= %s)
  WHERE UR.user_id = %d 
SQL;
        $role = $this->db()->queryOne($sql, $role, $uid);
        if ($role) {
            return $role['role_id'];
        } else {
            return false;
        }
    }
}