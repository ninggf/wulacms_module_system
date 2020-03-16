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

use wula\cms\CmfModule;
use wulaphp\app\App;
use wulaphp\io\Response;
use wulaphp\router\IURLDispatcher;
use wulaphp\router\Router;
use wulaphp\router\UrlParsedInfo;

/**
 * 系统内核模块.
 *
 * @group kernel
 * @subEnabled
 */
class SystemModule extends CmfModule {

    public function getName() {
        return '内核';
    }

    public function getDescription() {
        return 'wualcms系统内核模块，提供用户、模块、日志、等基础功能。';
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

    /**
     * 处理安装.
     *
     * @param Router $router
     * @param string $url
     *
     * @bind router\beforeDispatch
     */
    public static function beforeDispatch(Router $router, $url) {
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
     * @param Router $router
     *
     * @bind router\registerDispatcher
     */
    public static function regDispatcher(Router $router) {
        $router->register(new class implements IURLDispatcher {
            public function dispatch(string $url, Router $router, UrlParsedInfo $parsedInfo) {
                if (!$url || $url == 'index.html') {
                    Response::redirect(App::url('backend'));
                }

                return null;
            }
        }, 99999999);
    }
}

App::register(new SystemModule());
