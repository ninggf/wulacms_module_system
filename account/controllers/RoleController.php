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

use backend\classes\BackendController;
use backend\form\BootstrapFormRender;
use system\model\AclTable;
use system\model\RoleTable;
use wulaphp\auth\AclResourceManager;
use wulaphp\io\Ajax;
use wulaphp\io\Response;
use wulaphp\validator\JQueryValidatorController;
use wulaphp\validator\ValidateException;

/**
 * Class RoleController
 * @package    system\account\controllers
 * @acl        m:system/account
 * @accept     system\model\RoleTable
 */
class RoleController extends BackendController {
    use JQueryValidatorController;

    public function index() {
        $data  = [];
        $roleM = new RoleTable();
        if ($this->passport->uid != $this->passport['pid']) {
            $q = $roleM->userRoles($this->passport->uid, $this->passport['uid']);
        } else {
            $q = $roleM->findAll(['uid' => $this->passport['pid']], 'id,name')->limit(0, 500)->desc('level');
        }
        $data['roles']   = $q;
        $data['canEdit'] = $this->passport->cando('er:system/account');
        $data['canAcl']  = $this->passport->cando('acl:system/account');
        $data['canDel']  = $this->passport->cando('dr:system/account');

        return view('role/index', $data);
    }

    /**
     * @param string $id
     *
     * @return \wulaphp\mvc\view\View
     * @acl e:system/account
     */
    public function edit($id = '') {
        $data = ['id' => $id];
        $form = new RoleTable(true);

        if ($id) {
            $role = $form->findOne(['id' => $id, 'uid' => $this->passport['pid']])->ary();
            if (!$role) {
                Response::respond(404);
            }
            $form->inflateByData($role);
        }
        $data['rules'] = $form->encodeValidatorRule($this);
        $data['form']  = BootstrapFormRender::v($form);

        return view($data);
    }

    /**
     * @param $id
     *
     * @return \wulaphp\mvc\view\JsonView
     * @acl e:system/account
     */
    public function savePost($id) {
        $form = new RoleTable(true);
        try {
            $role = $form->inflate();
            $form->validate($role);
            $role['uid'] = $this->passport['pid'];
            if ($id) {
                $rst = $form->updateRole($role, $role['uid']);
            } else {
                unset($role['id']);
                $rst = $form->createRole($role);
            }
            if ($rst) {
                return Ajax::reload('#core-role-list', $id ? '角色已经更新' : '新的角色已经添加');
            }
        } catch (ValidateException $ve) {
            return Ajax::validate('RoleForm', $ve->getErrors());
        } catch (\PDOException $pe) {
            return Ajax::error('数据库出错:' . $pe->getMessage());
        }

        return Ajax::error('保存角色数据时出错了，请联系管理员');
    }

    /**
     * @param $id
     *
     * @return \wulaphp\mvc\view\JsonView
     * @acl d:system/account
     */
    public function del($id) {
        if (empty($id)) {
            return Ajax::error('未指定要删除的角色');
        }
        if ($id < 3) {
            return Ajax::error('系统内置的角色不能删除');
        }
        $roleM = new RoleTable();
        $rst   = $roleM->deleteRole($id, $this->passport['pid']);
        if ($rst) {
            return Ajax::reload('#core-role-list', '角色已经删除');
        } else {
            return Ajax::error('无法删除角色:' . $roleM->lastError());
        }
    }

    /**
     * @param $id
     *
     * @acl acl:system/account
     * @return \wulaphp\mvc\view\SmartyView
     */
    public function acl($id) {
        if (empty($id)) {
            Ajax::fatal('角色ID为空');
        }
        $roleM = new RoleTable();
        $role  = $roleM->findOne(['id' => $id, 'uid' => $this->passport['pid']]);
        if (!$role['id']) {
            Ajax::fatal('角色不存在');
        }
        $data['role']      = $role;
        $aclResources      = AclResourceManager::getInstance('admin');
        $node              = $aclResources->getResource();
        $nodes             = $node->getNodes();
        $data ['ops']      = $node->getOperations();
        $data ['nodes']    = $nodes;
        $data ['acl']      = $role->acls()->toArray('allowed', 'res');
        $data ['parent']   = '/';
        $data ['debuging'] = APP_MODE != 'pro';

        return view($data);
    }

    /**
     * @param string $id
     * @param string $parentId
     *
     * @acl acl:system/account
     * @return \wulaphp\mvc\view\SmartyView
     */
    public function acldata($id, $parentId = '') {
        if (empty($id)) {
            Ajax::fatal('角色ID为空');
        }
        $roleM = new RoleTable();
        $role  = $roleM->findOne(['id' => $id, 'uid' => $this->passport['pid']]);
        if (!$role['id']) {
            Ajax::fatal('角色不存在');
        }
        $aclResources      = AclResourceManager::getInstance('admin');
        $node              = $aclResources->getResource($parentId);
        $nodes             = $node->getNodes();
        $data ['ops']      = $node->getOperations();
        $data ['nodes']    = $nodes;
        $data ['acl']      = $role->acls()->toArray('allowed', 'res');
        $data ['parent']   = $parentId;
        $data ['debuging'] = APP_MODE != 'pro';
        if ($this->passport->uid != $this->passport['pid']) {
            $data ['isMyRole'] = $this->passport->uid == 1 ? false : $this->passport->is($role['name']);
        }

        return view($data);
    }

    /**
     * @param $role_id
     * @param $acl
     *
     * @acl acl:system/account
     * @return \wulaphp\mvc\view\JsonView
     */
    public function setAcl($role_id, $acl) {
        $id = intval($role_id);
        if (empty($id)) {
            Ajax::fatal('角色ID为空');
        }
        $roleM = new RoleTable();
        $role  = $roleM->findOne(['id' => $id, 'uid' => $this->passport['pid']]);
        if (!$role['id']) {
            Ajax::fatal('角色不存在');
        }
        $priority       = 999 - intval($role['level']);
        $rst            = true;
        $beDeleteingIds = [];
        if (is_array($acl) && !empty ($acl)) {
            $acls     = [];
            $aclTable = new AclTable();
            $db       = $aclTable->db();
            foreach ($acl as $key => $v) {
                if (!is_numeric($v)) {
                    $beDeleteingIds [] = $key;
                    continue;
                }
                $v       = $v === '1' ? 1 : 0;
                $data    = ['role_id' => $id, 'res' => $key];
                $allowed = $aclTable->findOne($data, 'allowed')['allowed'];
                if (is_numeric($allowed)) {
                    if ($allowed != $v) {
                        $db->update('{acl}')->set(['allowed' => $v])->where($data)->exec();
                    }
                } else {
                    $data ['allowed']  = $v;
                    $data ['priority'] = $priority;
                    $acls []           = $data;
                }
            }
            if (!empty ($beDeleteingIds)) {
                $db->delete()->from('{acl}')->where(['role_id' => $role_id, 'res IN' => $beDeleteingIds])->exec();
            }
            if ($acls) {
                $rst = $db->insert($acls, true)->into('{acl}')->exec();
            }
        }
        if ($rst) {
            return Ajax::success('『' . $role['name'] . '』已授权成功');
        }

        return Ajax::success('『' . $role['name'] . '』授权失败');
    }
}