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

use wulaphp\db\DatabaseConnection;
use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class RoleTable extends FormTable {
    use JQueryValidator;
    /**
     * @type int
     */
    public $id;
    /**
     * 角色名(<b class="text-danger">*</b>)
     * @var \backend\form\TextField
     * @type string
     * @required
     * @callback (checkName(id)) => 角色名不合法
     * @layout 2,col-xs-8
     */
    public $name;
    /**
     * 授权级别(<b class="text-danger">*</b>)
     * @var \backend\form\TextField
     * @type int
     * @required
     * @range (0,999)
     * @layout 2,col-xs-4
     * @note   0-999
     */
    public $level = 0;
    /**
     * 备注
     * @var \backend\form\TextareaField
     * @type string
     * @maxlength (250)
     */
    public $note;

    /**
     * 用户的角色
     *
     * @param int|string $uid
     * @param int|string $pid 拥有者ID
     *
     * @return \wulaphp\db\sql\Query
     */
    public function userRoles($uid, $pid) {
        $q = $this->select('Role.*')->right('{user_role} AS UR', 'UR.role_id', 'Role.id')->where([
            'UR.user_id' => $uid,
            'Role.uid'   => $pid
        ])->desc('level');

        return $q;
    }

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

    /**
     * 更新角色数据.
     *
     * @param array      $data
     * @param string|int $uid
     *
     * @return bool
     * @throws \PDOException
     */
    public function updateRole($data, $uid) {
        $id = unget($data, 'id');
        if (!$id) {
            return false;
        }
        if (isset($data['level'])) {
            return $this->trans(function (DatabaseConnection $db) use ($data, $id, $uid) {
                if (!$this->update($data, ['id' => $id, 'uid' => $uid])) {
                    return false;
                }

                //更新授权表中的优先级
                return $db->update('{acl}')->set(['priority' => 999 - intval($data['level'])])->where(['role_id' => $id])->exec();
            });
        } else {
            return $this->update($data, ['id' => $id, 'uid' => $uid]);
        }
    }

    /**
     * 创建一个角色.
     *
     * @param array $data
     *
     * @return bool|int
     * @throws \PDOException
     */
    public function createRole($data) {
        return $this->insert($data);
    }

    /**
     * 删除角色.
     *
     * @param int|string $id
     * @param int|string $uid
     *
     * @return bool|mixed|null
     */
    public function deleteRole($id, $uid) {
        if (empty($id)) {
            return true;
        }
        if ($id < 3) {
            $this->errors = '系统内置的角色不能删除';

            return false;
        }

        return $this->trans(function (DatabaseConnection $db) use ($id, $uid) {
            if (!$this->delete(['id' => $id, 'uid' => $uid])) {
                return false;
            }

            return $db->delete()->from('{acl}')->where(['role_id' => $id])->exec();
        });
    }

    public function alterFieldOptions($name, &$options) {
        if ($this->_tableData && $this->_tableData['id'] < 3 && ($name == 'name' || $name == 'level')) {
            $options['readonly'] = true;
        }
        if ($name == 'level') {
            $pass             = whoami('admin');
            $maxLevel         = $pass['maxRoleLevel'];
            $options['range'] = "(0,$maxLevel)";
            $options['note']  = "0-$maxLevel";
        }
    }

    public function checkName($value, $data, $msg) {
        $id            = unget($data, 'id');
        $pass          = whoami('admin');
        $where['name'] = $value;
        $where['uid']  = $pass['pid'];
        if ($id) {
            $where['id <>'] = $id;
        }
        if ($this->exist($where)) {
            return $msg;
        }

        return true;
    }
}