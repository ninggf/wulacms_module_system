<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes\form;

use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class TaskForm extends FormTable {
	use JQueryValidator;
	public $table = null;
	/**
	 * @var \backend\form\HiddenField
	 * @type string
	 */
	public $id;
	/**
	 * 任务名称
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 2, col-xs-8
	 */
	public $name;
	/**
	 * 优先级
	 * @var \backend\form\SelectField
	 * @type string
	 * @see    parse_str
	 * @data   H=高&I=中&L=低
	 * @layout 2, col-xs-4
	 */
	public $priority = 'N';
	/**
	 * 出错时重试次数（0为不重试）
	 * @var \backend\form\TextField
	 * @type int
	 * @digits
	 * @layout 3, col-xs-8
	 */
	public $retryCnt = 0;
	/**
	 * 重试间隔（单位秒）
	 * @var \backend\form\TextField
	 * @type int
	 * @digits
	 * @layout 3, col-xs-4
	 */
	public $retryInt = 0;
	/**
	 * 定时运行（格式'Y-m-d H:i:s'或'+7 days'等,留空为立即执行）
	 * @var \backend\form\TextField
	 * @type datetime
	 */
	public $runat;

}