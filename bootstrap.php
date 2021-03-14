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

use system\classes\AdminPassport;
use wulaphp\app\App;
use wulaphp\auth\Passport;
use wulaphp\cmf\CmfModule;
use wulaphp\io\Response;
use wulaphp\mvc\view\View;
use wulaphp\router\Router;

/**
 * 系统内核模块.
 *
 * @group kernel
 * @subEnabled
 */
class SystemModule extends CmfModule {

    public function getName(): string {
        return '内核';
    }

    public function getDescription(): string {
        return 'wualcms系统内核模块，提供用户、模块、日志、等基础功能。';
    }

    public function getHomePageURL(): string {
        return 'https://www.wulacms.com/modules/system';
    }

    public function getAuthor(): string {
        return 'Leo Ning';
    }

    public function getVersionList(): array {
        $v['1.0.0'] = '初始版本';

        return $v;
    }

    /**
     * 处理安装.
     *
     * @param Router $router
     * @param string $url
     *
     * @bind router\beforeDispatch
     * @return \wulaphp\mvc\view\View
     */
    public static function beforeDispatch(Router $router, string $url): ?View {
        if (defined('WULACMF_INSTALLED') && !WULACMF_INSTALLED) {
            if (PHP_RUNTIME_NAME == 'cli-server' && is_file(WWWROOT . $url)) {
                return null;//运行在开发服务器
            }
            Response::respond(503, "Please run 'php artisn install' first");
        }

        return null;
    }

    public function menu(): array {
        return [];
    }

    public function acl(): array {
        return [];
    }

    /**
     * @param Passport|null $passport
     *
     * @filter passport\newAdminPassport
     *
     * @return Passport
     */
    public static function createAdminPassport(?Passport $passport) {
        if ($passport instanceof Passport) {
            $passport = new AdminPassport();
        }

        return $passport;
    }
}

App::register(new SystemModule());
