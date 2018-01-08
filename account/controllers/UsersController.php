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
use system\classes\form\AdminForm;
use system\model\RoleTable;
use system\model\UserTable;
use wulaphp\validator\JQueryValidatorController;

/**
 * @package    system\account\controllers
 * @acl        m:system/account
 */
class UsersController extends IFramePageController {
	use JQueryValidatorController;

	public function index() {
		$data           = [];
		$roleM          = new RoleTable();
		$data['roles']  = $roleM->findAll(null, 'id,name')->limit(0, 500)->asc('id');
		$data['canAcl'] = $this->passport->cando('acl:system/account');

		return $this->render($data);
	}

	public function edit($id = '') {
		$form = new AdminForm(true);
		if ($id) {
			$admin = $form->get($id);
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

	public function grid() {
		return view();
	}

	public function data($status = '', $q = '', $rid = '', $count = '') {
		$model = new UserTable();
		$where = ['id >' => 1];
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