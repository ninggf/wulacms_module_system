<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\task\controllers;

use backend\classes\BackendController;
use backend\form\BootstrapFormRender;
use system\classes\form\TaskForm;
use system\task\TaskQueue;
use wulaphp\io\Ajax;
use wulaphp\io\Response;
use wulaphp\validator\JQueryValidatorController;
use wulaphp\validator\ValidateException;

/**
 * 任务编辑控制器.
 *
 * @package system\task\controllers
 * @acl     m:system/task
 */
class EditController extends BackendController {
	use JQueryValidatorController;

	public function index($id = '') {
		if (empty($id)) {
			Response::error('任务编号为空');
		}
		$tq   = new TaskQueue();
		$task = $tq->get(['id' => $id])->ary();
		if (!$task) {
			Response::error('任务不存在');
		}
		$tcls = $task['task'];
		if (!is_subclass_of($tcls, '\system\classes\Task')) {
			Response::error('任务类不存在');
		}

		$tform = new TaskForm(true);
		$tform->inflateByData($task);

		$options = @json_decode($task['options'], true);
		/**@var \system\classes\Task $tclz */
		$tclz = new $tcls($id);
		$form = $tclz->getForm();
		if ($options && $form) {
			$form->inflateByData($options);
		}
		if ($form) {
			$task['form'] = BootstrapFormRender::v($form);
			$tform->applyRules($form);
		}
		$task['tform'] = BootstrapFormRender::v($tform);
		$task['rules'] = $tform->encodeValidatorRule($this);

		return view($task);
	}

	public function savePost($id = '') {
		if (empty($id)) {
			Response::error('任务编号为空');
		}
		$tq   = new TaskQueue();
		$task = $tq->get(['id' => $id])->ary();
		if (!$task) {
			Response::error('任务不存在');
		}
		$tcls = $task['task'];
		if (!is_subclass_of($tcls, '\system\classes\Task')) {
			Response::error('任务类不存在');
		}

		$tform = new TaskForm(true);
		try {
			$data = $tform->inflate();
			$tform->validate($data);
			/**@var \system\classes\Task $tclz */
			$tclz = new $tcls($id);
			$form = $tclz->getForm();
			if ($form) {
				$opts = $form->inflate();
				$form->validate($opts);
				$data['options'] = json_encode($opts);
			} else {
				$data['options'] = '';
			}
			$tq = new TaskQueue();
			if ($tq->updataTask($data, $id)) {
				return Ajax::reload('#table', '任务编辑成功');
			} else {
				return Ajax::error('无法保存任务');
			}
		} catch (ValidateException $e) {
			return Ajax::validate('TaskEditForm', $e->getErrors());
		} catch (\Exception $ee) {
			return Ajax::error($ee->getMessage());
		}
	}

	public function add() {
		$data['tasks'] = TaskQueue::tasks();
		if (empty($data['tasks'])) {
			$data['tasks'][''] = '--无可有任务--';
		}

		return view($data);
	}

	public function addPost($task) {
		if (empty($task)) {
			Response::error('请选择任务');
		}
		$tasks = TaskQueue::tasks();

		if (!isset($tasks[ $task ])) {
			Response::error('任务已经不存在了，无法为你创建.');
		}

		$tq = new TaskQueue();
		$id = $tq->newTask($tasks[ $task ], $task, 'D');
		if ($id) {
			return Ajax::success(['message' => '创建任务成功', 'id' => $id]);
		} else {
			return Ajax::error('无法创建任务.');
		}
	}
}