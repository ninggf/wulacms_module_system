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

class IndexController extends Controller {
	public function beforeRun($action, $refMethod) {
		if (WULACMF_INSTALLED) {
			Response::respond(404);
		}
	}

	public function index() {
		return 'install';
	}
}