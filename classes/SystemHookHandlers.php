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

use wulaphp\app\App;
use wulaphp\io\Response;
use wulaphp\router\Router;

/**
 * 系统内核模块事件处理器.
 *
 * @package system\classes
 */
trait SystemHookHandlers {
	/**
	 * 处理安装.
	 *
	 * @param Router $router
	 * @param string $url
	 *
	 * @bind router\beforeDispatch
	 */
	public static function beforeDispatch($router, $url) {
		if (!WULACMF_INSTALLED) {
			if (PHP_RUNTIME_NAME == 'cli-server' && is_file(WWWROOT . $url)) {
				return;//运行在开发服务器
			}
			$installURL = App::url('system/installer');
			if (WWWROOT_DIR != '/') {
				$regURL = substr($installURL, strlen(WWWROOT_DIR) - 1);
			} else {
				$regURL = $installURL;
			}
			$regURL = ltrim($regURL, '/');
			if (!Router::is($regURL . '(/.*)?', true)) {
				Response::redirect($installURL);
			}
		}
	}

	/**
	 * 注册导航菜单.
	 *
	 * @bind dashboard\initUI
	 *
	 * @param \backend\classes\DashboardUI $ui
	 */
	public static function initMenu($ui) {
		$passport = whoami('admin');
		if ($passport->cando('m:system')) {
			$system = $ui->getMenu('system');
			if ($passport->cando('m:system/module')) {
				$module              = $system->getMenu('module', '模块管理', 999990);
				$module->icon        = '&#xe857;';
				$module->iconCls     = 'layui-icon';
				$module->data['url'] = App::url('system/module');
				$module->badge       = count(App::modules('upgradable'));
			}
			if ($passport->cando('m:system/account')) {
				$account              = $system->getMenu('account');
				$account->name        = '管理员';
				$account->data['url'] = App::url('system/users');
				$account->pos         = 1;
				$account->icon        = '&#xe630;';
			}
			if ($passport->cando('m:system/log')) {
				$account              = $system->getMenu('logs');
				$account->name        = '日志';
				$account->data['url'] = App::url('system/logs');
				$account->pos         = 999999;
				$account->icon        = '&#xe64a;';
			}
		}
	}

	/**
	 * @param \wulaphp\auth\AclResourceManager $manager
	 *
	 * @bind rbac\initAdminManager
	 */
	public static function aclRes($manager) {
		$manager->getResource('system', '系统管理', 'm');
		$res = $manager->getResource('system/setting', '设置', 'm');
		$res->addOperate('default', '通用设置');
		$res = $manager->getResource('system/account', '管理员', 'm');
		$res->addOperate('acl', '授权');
		$manager->getResource('system/module', '模块', 'm');
		$manager->getResource('system/log', '日志', 'm');
	}
}