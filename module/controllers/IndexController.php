<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\module\controllers;

use backend\classes\IFramePageController;
use Michelf\MarkdownExtra;
use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\io\Ajax;
use wulaphp\util\ArrayCompare;

/**
 * Class IndexController
 * @package system\module\controllers
 * @acl     m:system/module
 */
class IndexController extends IFramePageController {
	public function index($type = 'installed') {
		$groups          = [];
		$modules         = App::modules($type);
		$data['modules'] = [];
		foreach ($modules as $m) {
			$gp                = $m->group;
			$groups[ $gp ]     = $gp;
			$data['modules'][] = $m->info();
		}
		usort($data['modules'], ArrayCompare::compare('status', 'd'));
		$data['groups']   = $groups;
		$data['type']     = $type;
		$data['upCnt']    = count(App::modules('upgradable'));
		$data['insCnt']   = count(App::modules());
		$data['uninsCnt'] = count(App::modules('uninstalled'));

		return $this->render($data);
	}

	public function stop($module) {
		$m = App::getModuleById($module);
		if ($m) {
			if ($m->isKernel) {
				return Ajax::error('无法停用内核模块');
			}
			$m->stop();
			RtCache::delete('modules@cmf');

			return Ajax::reload('top', '停用成功');
		} else {
			return Ajax::error('要停用的模块不存在');
		}
	}

	public function start($module) {
		$m = App::getModuleById($module);
		if ($m) {
			$m->start();
			RtCache::delete('modules@cmf');

			return Ajax::reload('top', '启用成功');
		} else {
			return Ajax::error('要启用的模块不存在');
		}
	}

	public function install($module) {
		$m = App::getModuleById($module);
		if ($m) {
			try {
				if ($m->install(App::db())) {
					RtCache::delete('modules@cmf');

					return Ajax::reload('top', '『' . $m->getName() . '』安装成功');
				}

				return Ajax::error('无法安装『' . $m->getName() . '』');
			} catch (\Exception $e) {
				return Ajax::error($e->getMessage());
			}
		} else {
			return Ajax::error('要安装的模块不存在');
		}
	}

	public function uninstall($module) {
		$m = App::getModuleById($module);
		if ($m) {
			if ($m->isKernel) {
				return Ajax::error('无法卸载内核模块');
			}
			try {
				if ($m->uninstall()) {
					RtCache::delete('modules@cmf');

					return Ajax::reload('top', '『' . $m->getName() . '』卸载成功');
				}

				return Ajax::error('无法卸载『' . $m->getName() . '』');
			} catch (\PDOException $e) {
				return Ajax::error($e->getMessage());
			}
		} else {
			return Ajax::error('要卸载的模块不存在');
		}
	}

	public function upgrade($module) {
		$m = App::getModuleById($module);
		if ($m) {
			try {
				if ($m->upgrade(App::db(), $m->getCurrentVersion(), $m->installedVersion)) {
					RtCache::delete('modules@cmf');

					return Ajax::reload('top', '『' . $m->getName() . '』升级成功');
				}

				return Ajax::error('无法升级『' . $m->getName() . '』');
			} catch (\Exception $e) {
				return Ajax::error($e->getMessage());
			}
		} else {
			return Ajax::error('要升级的模块不存在');
		}
	}

	public function detail($module) {
		$m = App::getModuleById($module);
		if ($m) {
			$data['module']     = $m->info();
			$data['changelogs'] = array_reverse($m->getVersionList(), true);
			$path               = $m->getPath('README.md');
			if (is_file($path)) {
				$content               = @file_get_contents($path);
				$content               = preg_replace_callback('/\]\(#(?P<hash>[^\)]+)\)/', function ($ms) {
					return '](' . App::url($ms['hash']) . ')';
				}, $content);
				$data['module']['doc'] = MarkdownExtra::defaultTransform($content);
			} else {
				$data['module']['doc'] = '此模块作者很懒，未提供任何文档，使用它全靠您蒙！';
			}

			return $this->render($data);
		}

		return Ajax::fatal('模块不存在', 404);
	}
}