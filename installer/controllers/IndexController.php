<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\installer\controllers;

use wulaphp\app\App;
use wulaphp\io\Response;
use wulaphp\mvc\controller\Controller;

/**
 * Wulacms安装控制器。
 *
 * @package system\installer\controllers
 * @since   3.0
 * @version 3.0
 */
class IndexController extends Controller {
    public function beforeRun($action, $refMethod) {
        if (WULACMF_INSTALLED) {
            //安装完成后不能再访问此控制器
            Response::respond(404);
        }

        return parent::beforeRun($action, $refMethod);
    }

    public function index() {
        if (!file_exists(TMP_PATH . 'install.txt')) {
            $uuid = md5(uniqid());
            if (file_put_contents(TMP_PATH . 'install.txt', $uuid)) {
                Response::redirect(App::url('system/installer/setup'));
            } else {
                Response::error('tmp dir: ' . TMP_DIR . ' cannot write!');
            }
        } else {
            Response::redirect(App::url('system/installer/setup'));
        }
    }
}