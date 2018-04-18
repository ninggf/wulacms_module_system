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

use system\classes\cmd\TaskQueueCommand;
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
				$module              = $system->getMenu('module', __('Modules'), 999996);
				$module->icon        = '&#xe857;';
				$module->iconCls     = 'layui-icon';
				$module->data['url'] = App::url('system/module');
				$module->badge       = count(App::modules('upgradable'));
			}
			if ($passport->cando('m:system/account')) {
				$account              = $system->getMenu('account');
				$account->name        = __('Accounts');
				$account->data['url'] = App::url('system/account/users');
				$account->pos         = 1;
				$account->icon        = '&#xe672;';
			}
			if ($passport->cando('m:system/task')) {
				$task              = $system->getMenu('task');
				$task->name        = __('Tasks');
				$task->data['url'] = App::url('system/task');
				$task->pos         = 999995;
				$task->iconCls     = 'layui-icon';
				$task->icon        = '&#xe628;';
			}
			if ($passport->cando('m:system/log')) {
				$log              = $system->getMenu('logs');
				$log->name        = __('System Logs');
				$log->data['url'] = App::url('system/logs');
				$log->pos         = 999998;
				$log->icon        = '&#xe64a;';
			}
		}
		//应用
		$apps            = $ui->getMenu('apps', __('Applications'), 10);
		$apps->icon      = '&#xe637;';
		$apps->iconStyle = 'color:green';
		//活动管理
		$act            = $ui->getMenu('activity', __('Activity'), 20);
		$act->icon      = '&#xe756;';
		$act->iconCls   = 'layui-icon';
		$act->iconStyle = 'color:red';
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
		$manager->getResource('system/task', '任务', 'm');
		$manager->getResource('system/log', '日志', 'm');
	}

	/**
	 * 管理员表格.
	 *
	 * @param array $cols
	 *
	 * @filter  get_columns_of_core.admin.table
	 * @return array
	 */
	public static function adminTableColumns($cols) {
		$cols['roles']     = [
			'name'   => '角色',
			'show'   => true,
			'width'  => 120,
			'order'  => 10,
			'render' => function ($v) {
				$rs = [];
				foreach ($v as $r) {
					$rs[] = $r['name'];
				}

				return implode(',', $rs);
			}
		];
		$cols['phone']     = ['name' => '手机', 'show' => true, 'width' => 120, 'order' => 20];
		$cols['email']     = ['name' => '邮箱', 'show' => false, 'order' => 30];
		$cols['lastlogin'] = [
			'name'   => '最后登录',
			'show'   => true,
			'width'  => 150,
			'sort'   => 'lastlogin',
			'order'  => 50,
			'render' => function ($v) {
				return date('Y-m-d H:i:s', $v);
			}
		];
		$cols['status']    = [
			'name'   => '激活',
			'show'   => true,
			'width'  => 60,
			'order'  => 60,
			'sort'   => 'status',
			'align'  => 'center',
			'render' => function ($v) {
				if ($v) {
					return '<span class="active"><i class="fa fa-check text-success text-active"></i></span>';
				} else {
					return '<span><i class="fa fa-times text-danger text"></i></span>';
				}
			}
		];

		return $cols;
	}

	/**
	 * @param array $cmds
	 *
	 * @filter artisan\getCommands
	 * @return array
	 */
	public static function queueCmd($cmds) {
		if (WULACMF_INSTALLED) {
			$cmds[] = new TaskQueueCommand();
		}

		return $cmds;
	}
}
