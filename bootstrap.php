<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system {

	use system\classes\SystemHookHandlers;
	use wula\cms\CmfModule;
	use wulaphp\app\App;
	use wulaphp\db\DatabaseConnection;

	/**
	 * 系统内核模块.
	 *
	 * @group kernel
	 */
	class SystemModule extends CmfModule {
		use SystemHookHandlers;

		public function getName() {
			return '系统内核';
		}

		public function getDescription() {
			return 'wualcms系统内核模块，提供用户、模块、日志、多媒体等基础功能。';
		}

		public function getHomePageURL() {
			return 'https://www.wulacms.com/modules/system';
		}

		public function getAuthor() {
			return 'Leo Ning';
		}

		public function getVersionList() {
			$v['1.0.0'] = '初始版本';
			$v['1.1.0'] = '添加任务队列支持(php artisan task:queue)';
			$v['2.0.0'] = '不再支持 php 5.6.x版本';

			return $v;
		}

		/**
		 * 升级到1.0.0版本时执行
		 *
		 * @param \wulaphp\db\DatabaseConnection $db
		 *
		 * @return bool
		 */
		protected function upgradeTo1_0_0(DatabaseConnection $db) {
			$widgets = json_encode(['welcome' => ['id' => 'welcome', 'pos' => 1, 'width' => 12, 'name' => '欢迎']]);

			return $db->insert([
				'user_id' => 1,
				'name'    => 'widgets',
				'value'   => $widgets
			])->into('{user_meta}')->exec(true);
		}
	}

	App::register(new SystemModule());
}

namespace {
	function smarty_modifiercompiler_tableset($params, $compiler) {
		$id     = $params [0];
		$reload = isset($params[1]) ? $params[1] : "''";

		return 'system\model\UserGridcfgModel::echoSetButton(' . $id . ',' . $reload . ')';
	}

	function smarty_modifiercompiler_tablehead($params, $compiler) {
		$id = $params [0];

		return 'system\model\UserGridcfgModel::echoHead(' . $id . ')';
	}

	function smarty_modifiercompiler_tablerow($params, $compiler) {
		$id     = $params [0];
		$data   = isset($params[1]) ? $params[1] : '[]';
		$extras = isset($params[2]) ? $params[2] : '[]';

		return 'system\model\UserGridcfgModel::echoRow(' . $id . ',' . $data . ',' . $extras . ')';
	}

	function smarty_modifiercompiler_tablespan($params, $compiler) {
		$id   = $params [0];
		$data = isset($params[1]) ? $params[1] : 0;

		return 'system\model\UserGridcfgModel::colspan(' . $id . ',' . $data . ')';
	}
}