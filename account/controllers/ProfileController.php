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
use system\classes\form\ChangePasswordForm;
use system\model\UserTable;
use wulaphp\app\App;
use wulaphp\auth\Passport;
use wulaphp\io\Ajax;
use wulaphp\io\LocaleUploader;
use wulaphp\mvc\view\JsonView;
use wulaphp\validator\JQueryValidatorController;

/**
 * @package system\account\controllers
 * @accept  system\model\UserTable
 */
class ProfileController extends IFramePageController {
	use JQueryValidatorController, Plupload;

	public function index() {
		$form        = new UserTable(!0);
		$data['uid'] = $this->passport->uid;
		$form->inflateByData($this->passport->info());
		$data['form']     = BootstrapFormRender::v($form);
		$data['rules']    = $form->encodeValidatorRule($this);
		$pwdForm          = new ChangePasswordForm(!0);
		$data['pwdform']  = BootstrapFormRender::v($pwdForm);
		$data['pwdrules'] = $pwdForm->encodeValidatorRule($this);
		$data ['avatar']  = $this->passport->avatar;

		return $this->render('profile', $data);
	}

	/**
	 * 更新头像.
	 *
	 * @param int $uid
	 *
	 * @return array|\wulaphp\mvc\view\JsonView
	 */
	public function updateAvatar($uid) {
		$rst = $this->upload(null, 128000, $uid == $this->passport->uid);
		if (isset($rst['error']) && $rst['error']['code'] == 422) {
			return new JsonView($rst, [], 422);
		}
		if ($rst['done']) {
			$url   = $rst['result']['url'];
			$table = new UserTable(!0);
			$table->updateAccount(['avatar' => $url, 'id' => $uid]);
			$this->passport->avatar = $url;
			$this->passport->store();
		}

		return $rst;
	}

	/**
	 * 删除头像.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public function delAvatar() {
		$table = new UserTable(!0);
		$table->updateAccount(['avatar' => '', 'id' => $this->passport->uid]);
		$avatar = $this->passport->avatar;
		//如果是存在本地的头像，将它删除
		if (!preg_match('#^(/|https?://).+#', $avatar)) {
			$locale = new LocaleUploader();
			$locale->delete($avatar);
		}
		$this->passport->avatar = App::assets('wula/jqadmin/images/avatar.jpg');
		$this->passport->store();

		return Ajax::success('');
	}

	/**
	 * 修改账户信息.
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public function indexPost() {
		$form = new UserTable(!0);
		$data = $form->inflate();
		$id   = intval($data['id']);
		if (empty($id)) {
			return Ajax::error('未知账户');
		} else if ($id != $this->passport->uid) {
			return Ajax::error('你无权修改此账户信息');
		}
		try {
			$rst = $form->updateAccount($data);
			if ($rst) {
				$this->passport->username = $data['username'];
				$this->passport->nickname = $data['nickname'];
				$this->passport->phone    = $data['phone'];
				$this->passport->email    = $data['email'];
				$this->passport->store();

				return Ajax::success('账户信息更新成功');
			}

			return Ajax::error($form->lastError());
		} catch (\PDOException $pe) {
			return Ajax::error($pe->getMessage());
		}
	}

	/**
	 * 修改密码
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public function chpwdPost() {
		$data = rqsts(['id', 'newpwd', 'newpwd1', 'oldpwd']);
		$user = new UserTable(!0);
		$rst  = $user->get($this->passport->uid);
		if (!Passport::verify($data['oldpwd'], $rst['hash'])) {
			return Ajax::validate('ChPwdForm', ['oldpwd' => '原密码不正确']);
		}
		$new_password = $data['newpwd'];
		if (!$new_password) {
			return Ajax::validate('ChPwdForm', ['newpwd' => '新的密码不能为空']);
		}
		if (strlen($new_password) < 6) {
			return Ajax::validate('ChPwdForm', ['newpwd' => '密码长度不足6位']);
		}
		$confirm_password = $data['newpwd1'];
		if ($new_password != $confirm_password) {
			return Ajax::validate('ChPwdForm', ['newpwd' => '二次密码不相同']);
		}
		try {
			if ($user->chagnePassword($this->passport->uid, $new_password)) {
				return Ajax::success('密码已修改');
			}

			return Ajax::error('密码修改失败');
		} catch (\Exception $e) {
			return Ajax::error($e->getMessage());
		}
	}

	//允许上传的图片类型
	protected function allowed($ext) {
		return in_array($ext, ['.jpg', '.png', '.gif', '.jpeg']);
	}
}