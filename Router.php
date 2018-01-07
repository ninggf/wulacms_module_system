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

use wulaphp\mvc\controller\SubModuleRouter;
use wulaphp\mvc\view\JsonView;

class Router extends SubModuleRouter {
}

function a($view, $args) {
	return new JsonView([array_sum($args)]);
}