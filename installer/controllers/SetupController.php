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

use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\controller\SessionSupport;

class SetupController extends Controller {
    use SessionSupport;

    public function index() {
        return 'setup:' . $_SERVER['HTTP_REFERER'];
    }
}