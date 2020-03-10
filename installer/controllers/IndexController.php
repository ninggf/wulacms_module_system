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
        return 'install index page';
    }
}