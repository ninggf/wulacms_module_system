<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes;

use system\classes\model\RolePermission;
use system\classes\model\UserMetaTable;
use system\classes\model\UserTable;
use wulaphp\auth\Passport;
use wulaphp\cache\Cache;
use wulaphp\db\sql\Query;

/**
 * 管理员通行证.
 */
class AdminPassport extends Passport {
    protected $tenantId = 0;

    public function is($roles): bool {
        if ($this->isSuperUser) {
            return true;
        }
        $myroles = $this->data['roles'];
        if (empty($myroles)) {
            return false;
        }

        return !empty(array_intersect($myroles, (array)$roles));
    }

    /**
     * 用户可以有多个角色，角色可以继承其它角色.
     *
     * @param string     $op    操作
     * @param string     $res   资源
     * @param array|null $extra 额外数据
     *
     * @return bool
     */
    protected function checkAcl(string $op, string $res, ?array $extra = null): bool {
        static $checked = [];
        //1号用户为超级管理员
        if ($this->isSuperUser) {
            return true;
        }
        if (!isset($this->data['acls'])) {
            $this->loadAcl();
        }
        //未找到对应的ACL
        if (!$this->data['acls']) {
            return false;
        }
        $resid = $op . ':' . $res;
        if (isset($checked[ $resid ])) {
            return $checked[ $resid ];
        }
        $reses[] = $op . ':' . $res;
        // 对资源的全部操作授权
        $reses[] = '*:' . $res;
        $reses[] = '*:*';
        $ress    = explode('/', $res);
        if (count($ress) > 1) {
            // 对上级资源的全部操作授权
            while ($ress) {
                array_pop($ress);
                $reses[] = '*:' . implode('/', $ress);
                if (isset($checked[ $resid ])) {
                    return $checked[ $resid ];
                }
            }
        }
        // 权限检测.
        foreach ($reses as $opres) {
            if (isset($this->data['acls'][ $opres ])) {
                $checked[ $resid ] = true;

                return $checked[ $resid ];
            }
        }

        return false;
    }

    /**
     * 认证
     *
     * @param array|int|string|null $data [0=>username,1=>password] or uid
     *
     * @return bool
     */
    protected function doAuth($data = null): bool {
        $table = new UserTable();

        if (is_numeric($data)) {//直接使用uid
            $user = $table->findOne(['id' => $data]);
        } else {
            [$username, $password] = $data;
            $user = $table->findOne(['name' => $username]);
            if ($user['name'] != $username) {
                $this->error = __('You entered an incorrect username or password.');

                return false;
            }
            $passwdCheck = Passport::verify($password, $user['passwd']);
            if (!$passwdCheck) {
                $this->error = __('You entered an incorrect username or password.');

                return false;
            }
        }

        if ($this->verifyUserData($user)) {
            $this->userRoles($table);
            $this->data['acl_ver']       = $user['acl_ver'];
            $this->data['logintime']     = time();
            $this->data['astoken']       = md5($user['passwd'] . $user['name'] . $_SERVER['HTTP_USER_AGENT']) . '/' . $user['id'];
            $this->data['passwd']        = $user['passwd'];
            $this->data['nextCheckTime'] = time() + 60;

            return true;
        }

        return false;
    }

    public function restore() {
        if (defined('NO_RESTORE_PASSPORT')) {
            return;
        }
        if ($this->uid) {
            if ($this->data['nextCheckTime'] > time()) {
                if (!defined('APP_TENANT_ID')) {
                    define('APP_TENANT_ID', $this->tenantId);
                }

                return;
            }
            $cache         = Cache::getCache();
            $userDataValid = $cache->get('adm_passport@' . $this->uid);
            if ($userDataValid) {
                if (!defined('APP_TENANT_ID')) {
                    define('APP_TENANT_ID', $this->tenantId);
                }
                $this->data['nextCheckTime'] = time() + 60;
                $this->store();

                return;
            }
            $table = new UserTable();
            $user  = $table->findOne($this->uid);
            if ($this->verifyUserData($user)) {
                $this->data['passwd'] = $user['passwd'];
                if ($user['acl_ver'] > $this->data['acl_ver']) { #权限版本不同时重新加载角色和权限
                    $this->data['acl_ver'] = $user['acl_ver'];
                    $this->userRoles($table);
                }
            } else {
                $this->data['acls']  = null;
                $this->data['roles'] = [];
            }
            $this->data['nextCheckTime'] = time() + 60;
        } else {
            $this->isLogin = false;
            $this->data    = [];
        }
        $this->store();
    }

    /**
     *
     * @param string $password
     *
     * @return bool
     */
    public function verifyPasswd(string $password): bool {
        return self::verify($password, $this->data['passwd']);
    }

    /**
     * @param Query $user
     *
     * @return bool
     */
    protected function verifyUserData(Query $user): bool {
        $status = $user['status'];
        if ($status == '0') {
            $this->error = __('Your account is locked.');

            return false;
        }
        if ($status == 1 && $user['passwd_expire_at'] && time() >= $user['passwd_expire_at']) {
            $user['status'] = $status = '3';
        }

        if ($status == '2' || $status == '3') {
            $_SESSION['resetPasswd'] = 1;//重设密码
        }

        $this->uid = $user['id'];
        $tenantId  = intval($user['tenant_id'] ?? 0);
        $tenant    = Tenant::getByDomain($user['name']);
        if (!$tenant->isEnabled() || $tenant->data()['id'] != $tenantId) {
            $user['status'] = 0;
            $this->error    = __('Your tenant account is locked.');

            return false;
        }

        $this->data['tenant_id'] = $tenantId;
        $this->data['tenant']    = $tenant->data();
        $this->userMeta($user);
        $this->username       = $user['name'];
        $this->tenantId       = $user['tenant_id'];
        $this->isSuperUser    = $user['is_super_user'] ?? '0' == '1';
        $this->data['status'] = $user['status'];

        return true;
    }

    protected function userMeta($user) {
        $mt             = new UserMetaTable();
        $meta           = $mt->getMeta($this->uid);
        $this->nickname = $meta['nickname'] ?? $user['name'];
        $this->phone    = $meta['phone'] ?? '';
        $this->email    = $meta['email'] ?? '';
        $this->avatar   = $meta['avatar'] ?? '';
        $this->data     = array_merge($meta, $this->data);
    }

    protected function userRoles(UserTable $userTable) {
        $this->data['acls']  = null;
        $this->data['roles'] = [];
        $roels               = $userTable->myRoles($this->uid);
        foreach ($roels as $r) {
            $rid                         = $r['id'];
            $this->data['roles'][ $rid ] = $r['name'];
        }
    }

    /**
     * 加载ACL.
     */
    protected function loadAcl() {
        $acls = [];
        if ($this->data['roles']) {
            $acl  = new RolePermission();
            $rids = array_keys($this->data['roles']);
            $ac   = $acl->findAll(['role_id IN' => $rids], 'uri AS res,op')->toArray();
            foreach ($ac as $a) {
                $res                      = $a['res'];
                $op                       = $a['op'];
                $acls[ $op . ':' . $res ] = 1;
            }
        }
        $this->data['acls'] = $acls;
        $this->store();
    }
}