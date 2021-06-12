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
use system\classes\model\UserToken;
use system\dto\UserTokenDto;
use wulaphp\app\App;
use wulaphp\auth\Passport;
use wulaphp\cache\Cache;
use wulaphp\db\sql\Query;
use wulaphp\io\Request;
use wulaphp\validator\ValidateException;

/**
 * 管理员通行证.
 */
class AdminPassport extends Passport {
    protected $tenantId = 0;

    public function is($roles): bool {
        $myroles = $this->data['roles'];
        if (empty($myroles)) {
            return false;
        }
        foreach ((array)$roles as $r) {
            if ($r[0] == '!') {
                if (in_array(mb_substr($r, 1), $myroles)) {
                    return false;
                }
            } else if (!in_array($r, $myroles)) {
                return false;
            }
        }

        return true;
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
        $opOnRes = $op . ':' . $res;
        if (isset($checked[ $opOnRes ])) {
            return $checked[ $opOnRes ];
        }

        if (isset($this->data['acls'][ $opOnRes ])) {
            $checked[ $opOnRes ] = true;

            return true;
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

        if ($this->verifyUserData($user, true)) {
            $this->userRoles($table);
            $this->data['acl_ver']       = $user['acl_ver'];
            $this->data['longtime']      = time();
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
        if ($this->isLogin) {
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
            if (!$user['id']) {
                $this->isLogin = false;
                $this->data    = [];
                $this->store();

                return;
            }
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
            $this->uid  = 0;
            $this->data = [];
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
     * @param bool  $isLogin
     *
     * @return bool
     */
    protected function verifyUserData(Query $user, bool $isLogin = false): bool {
        $status = $user['status'];
        if ($status == '0') {
            $this->error = __('Your account is locked.');

            return false;
        }

        $this->uid = $user['id'];
        $tenantId  = intval($user['tenant_id'] ?? 0);
        $tenant    = Tenant::getByDomain($user['name']);
        if (!$tenant->isEnabled() || $tenant->data()['id'] != $tenantId) {
            $user['status'] = 0;
            $this->error    = __('Your tenant account is locked.');

            return false;
        }

        //检测TOKEN
        if (!$this->checkToken($isLogin)) {
            $this->isLogin = false;
            $this->uid     = 0;

            return false;
        }

        if ($user['passwd_expire_at'] && time() >= $user['passwd_expire_at']) {
            $this->data['passwd_expired'] = true;
        } else {
            $this->data['passwd_expired'] = false;
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
        $this->meta     = array_merge($this->meta, $meta);
    }

    protected function userRoles(UserTable $userTable) {
        $this->data['acls']  = null;
        $this->data['roles'] = [];
        $roels               = $userTable->myRoles($this->uid);
        foreach ($roels as $r) {
            $rid                          = $r['id'];
            $this->data['roles'][ $rid ]  = $r['name'];//标识
            $this->data['rolens'][ $rid ] = $r['role'];//名称
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

    /**
     * @param bool $isNew 登录
     *
     * @return bool
     */
    protected function checkToken(bool $isNew = false): bool {
        $userToken = new UserToken();
        if ($isNew) {
            $allowLoginTwice = App::bcfg('allowLoginTwice@common', true);
            $tokenExpInt     = App::cfg('tokenExpInt@common', '+10 years');
            if (!$allowLoginTwice) {
                $userToken->update(['expire_time' => time()], ['user_id' => $this->uid, 'expire_time >' => time()]);
            }
            $token              = new UserTokenDto();
            $token->user_id     = $this->uid;
            $token->token       = md5(rand_str() . $this->uid . $this->username);
            $token->ip          = Request::getIp();
            $token->agent       = $_SERVER['HTTP_USER_AGENT'];
            $token->expire_time = strtotime($tokenExpInt, time());
            try {
                $userToken->newToken($token);
                $this->data['token'] = $token->token;

                return true;
            } catch (ValidateException $e) {
                $this->error = $e->getMessage();

                return false;
            }
        }
        if (isset($this->data['token'])) {
            $token = $userToken->findOne(['user_id' => $this->uid, 'token' => $this->data['token']], 'id,
            expire_time')->ary();
            if ($token['expire_time'] > time()) {
                $userToken->update(['expire_time' => $token['expire_time'] + 60], $token);

                return true;
            }
        }

        return false;
    }
}