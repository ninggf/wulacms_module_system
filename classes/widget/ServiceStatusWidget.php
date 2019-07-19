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
use backend\classes\WidgetSettingForm;

class ServiceStatusWidget extends Widget {
    public function render() {
        return 'Service Status<h1>adfasd</h1>';
    }

    public function name() {
        return __('Service Status');
    }

    public function minWidth() {
        return 6;
    }

    public function settingForm() {
        return new ServiceStatusWidgetForm(true);
    }
}

class ServiceStatusWidgetForm extends WidgetSettingForm {
    /**
     * 节点(一行一个)
     * @var \backend\form\TextareaField
     * @type string
     */
    public $nodes;
}