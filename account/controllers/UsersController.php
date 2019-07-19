<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\account\controllers;

use backend\classes\IFramePageController;
use backend\form\BootstrapFormRender;
use backend\form\Plupload;
use system\classes\form\AdminForm;
use system\model\RoleTable;
use system\model\UserTable;
use wulaphp\auth\Passport;
use wulaphp\db\DatabaseConnection;
use wulaphp\io\Ajax;
use wulaphp\io\LocaleUploader;
use wulaphp\mvc\view\JsonView;
use wulaphp\validator\JQueryValidatorController;
use wulaphp\validator\ValidateException;

/**
 * @package    system\account\controllers
 * @acl        m:system/account
 * @accept     system\classes\form\AdminForm
 */
class UsersController extends IFramePageController {
    use JQueryValidatorController, Plupload;

    //首页
    public function index() {
        $data  = [];
        $roleM = new RoleTable();
        if ($this->passport->uid != $this->passport['pid']) {
            $q = $roleM->userRoles($this->passport->uid, $this->passport['pid']);
        } else {
            $q = $roleM->findAll(['uid' => $this->passport['pid']], 'id,name')->limit(0, 500)->desc('level');
        }
        $data['roles']   = $q;
        $data['canEdit'] = $this->passport->cando('er:system/account');
        $data['canAcl']  = $this->passport->cando('acl:system/account');
        $data['canDel']  = $this->passport->cando('dr:system/account');

        return $this->render($data);
    }

    //编辑、新增
    public function edit($id = '') {
        $form = new AdminForm(true);
        if ($id) {
            $admin = $form->findOne(['id' => $id, 'pid' => $this->passport['pid']]);
            $user  = $admin->get(0);
            if ($id != 1) {
                $user['roles'] = $admin->roles()->toArray('id');
            }
            $data['avatar'] = $user['avatar'];
            $form->inflateByData($user);
            $form->removeRule('password', 'required');
        }
        $data['form']  = BootstrapFormRender::v($form);
        $data['id']    = $id;
        $data['rules'] = $form->encodeValidatorRule($this);

        return view($data);
    }

    //保存数据
    public function savePost($id) {
        $form = new AdminForm(true);
        $user = $form->inflate();
        try {
            if ($id) {
                $form->removeRule('password', 'required');
            }
            if ($id == '1') {
                $form->removeRule('roles');
                unset($user['roles'], $user['status']);
            }
            $form->validate($user);
            if (($id && $user['password']) || !$id) {
                $user['hash'] = Passport::passwd($user['password']);
            }
            unset($user['password'], $user['password1']);

            if ($id) {
                $rst = $form->updateAccount($user, $user['pid']);
            } else {
                unset($user['id']);
                if ($this->passport->uid != 1) {
                    $user['pid'] = $this->passport['pid'];
                } else {
                    $user['pid'] = 0;
                }
                $avatar = sess_del('uploaded_avatar');
                if ($avatar) {
                    $user['avatar'] = $avatar;
                }
                $rst = $form->newAccount($user);
            }
            if (!$rst) {
                return Ajax::error($form->lastError());
            }
        } catch (ValidateException $ve) {
            return Ajax::validate('AdminForm', $ve->getErrors());
        } catch (\PDOException $pe) {
            return Ajax::error($pe->getMessage());
        }

        return Ajax::reload('#core-admin-table', $id ? '用户修改成功' : '新用户已经成功创建');
    }

    //修改用户状态
    public function setStatus($status, $ids = '') {
        $ids = safe_ids2($ids);
        if ($ids) {
            $status = $status === '1' ? 1 : 0;
            $idkey  = array_search('1', $ids);
            if ($idkey !== false) {
                unset($ids[ $idkey ]);
            }
            if ($ids) {
                $w = ['id IN' => $ids];
                if ($this->passport->uid != 1) {
                    $w['pid'] = $this->passport['pid'];
                }
                (new UserTable())->db()->update('{user}')->set(['status' => $status])->where($w)->exec();
            }

            return Ajax::reload('#core-admin-table', $status == '1' ? '所选用户已激活' : '所选用户已禁用');
        } else {
            return Ajax::error('未指定用户');
        }
    }

    /**
     * 删除用户.
     *
     * @param string $ids
     *
     * @acl d:system/account
     * @return \wulaphp\mvc\view\JsonView
     */
    public function del($ids = '') {
        $ids = safe_ids2($ids);
        if ($ids) {
            $idkey = array_search('1', $ids);
            if ($idkey !== false) {
                unset($ids[ $idkey ]);
            }
            if ($ids) {
                $error = '';
                $rst   = (new UserTable())->db()->trans(function (DatabaseConnection $db) use ($ids) {
                    $w = ['id IN' => $ids];
                    if ($this->passport->uid != 1) {
                        $w['pid'] = $this->passport['pid'];
                    }
                    if (!$db->delete()->from('{user}')->where($w)->exec()) {
                        return false;
                    }

                    return true;
                }, $error);
                if ($rst) {
                    return Ajax::reload('#core-admin-table', '所选用户已删除');
                } else {
                    return Ajax::error($error ? $error : '删除用户出错，请找系统管理员');
                }
            }
        }

        return Ajax::error('未指定用户');
    }

    /**
     * 更新头像.
     *
     * @param int $uid
     *
     * @return array|\wulaphp\mvc\view\JsonView
     */
    public function updateAvatar($uid = 0) {
        $rst = $this->upload(null, 512000);
        if (isset($rst['error']) && $rst['error']['code'] == 422) {
            return new JsonView($rst, [], 422);
        }
        if ($rst['done']) {
            $url = $rst['result']['url'];
            if ($uid) {
                $table  = new UserTable();
                $avatar = $table->get(['id' => $uid])['avatar'];
                $table->updateAccount(['avatar' => $url, 'id' => $uid], $this->passport['pid']);
            } else {
                $avatar                      = sess_get('uploaded_avatar');
                $_SESSION['uploaded_avatar'] = $url;
            }
            if ($avatar && !preg_match('#^(/|https?://).+#', $avatar)) {
                $locale = new LocaleUploader();
                $locale->delete($avatar);
            }
        }

        return $rst;
    }

    //删除头像
    public function delAvatar($uid = '') {
        if ($uid) {
            $table  = new UserTable();
            $avatar = $table->get(['id' => $uid])['avatar'];
            $table->updateAccount(['avatar' => '', 'id' => $uid], $this->passport['pid']);
        } else {
            $avatar = sess_del('uploaded_avatar');
        }
        if ($avatar && !preg_match('#^(/|https?://).+#', $avatar)) {
            $locale = new LocaleUploader();
            $locale->delete($avatar);
        }

        return Ajax::success();
    }

    //用户表格
    public function grid() {
        return view();
    }

    //用户表格数据
    public function data($status = '', $q = '', $rid = '', $count = '') {
        $model = new UserTable();
        $where = ['id >' => 1];

        if ($this->passport['pid'] != 1) {
            $where['pid'] = $this->passport['pid'];
        } else {
            $where ['pid'] = 0;
        }

        if ($status == '0') {
            $where['status'] = $status;
        } else {
            $where['status'] = 1;
        }
        if ($q) {
            $where1['username LIKE']   = '%' . $q . '%';
            $where1['||nickname LIKE'] = '%' . $q . '%';
            $where[]                   = $where1;
        }
        $users = $model->select('User.*');
        if ($rid) {
            $users->join('{user_role} AS UR', 'User.id = UR.user_id');
            $where['role_id'] = $rid;
        }
        $users->where($where)->page()->sort();
        $total = '';
        if ($count) {
            $total = $users->total('id');
        }
        $data['items'] = $users;
        $data['total'] = $total;

        return view($data);
    }
}