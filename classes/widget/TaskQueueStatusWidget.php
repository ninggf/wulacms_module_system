<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes\widget;

use backend\classes\Widget;

class TaskQueueStatusWidget extends Widget {
    public function render() {
        return '任务队列状态';
    }

    public function name() {
        return __('Task Queue');
    }
}