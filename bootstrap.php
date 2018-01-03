<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system;

use system\classes\SystemHookHandlers;
use wula\cms\CmfModule;
use wulaphp\app\App;

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

		return $v;
	}
}

App::register(new SystemModule());