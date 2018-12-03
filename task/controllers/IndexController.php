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

use backend\classes\IFramePageController;
use system\task\TaskLog;
use system\task\TaskQueue;
use wulaphp\db\sql\Condition;
use wulaphp\io\Ajax;
use wulaphp\io\Response;

/**
 * Class IndexController
 * @package system\task\controllers
 * @acl     m:system/task
 */
class IndexController extends IFramePageController {
    private $groups   = ['D' => '新建', 'P' => '等待', 'R' => '运行', 'F' => '完成', 'E' => '出错'];
    private $priority = ['I' => '中', 'H' => '高', 'L' => '低'];

    public function index() {

        $data['groups'] = $this->groups;

        return $this->render($data);
    }

    /**
     * 再次执行
     *
     * @param string $ids
     *
     * @return \wulaphp\mvc\view\JsonView
     */
    public function restart($ids) {
        if (empty($ids)) {
            return Ajax::error('未提供任务编号');
        }

        $ids   = explode(',', $ids);
        $table = new TaskQueue();
        $table->restartTask($ids);

        return Ajax::reload('#table', '任务已经重新启动');
    }

    /**
     * 删除
     *
     * @param string $ids
     *
     * @return \wulaphp\mvc\view\JsonView
     */
    public function del($ids) {
        if (empty($ids)) {
            return Ajax::error('未提供任务编号');
        }
        $ids   = explode(',', $ids);
        $table = new TaskQueue();
        $table->deleteTask($ids);

        return Ajax::reload('#table', '任务已经删除');
    }

    public function clear() {
        $table = new TaskQueue();
        $table->clearTask();

        return Ajax::reload('#table', '完成的任务已清空');
    }

    public function status($id, $time = 0) {
        if (empty($id)) {
            return Ajax::success();
        }
        $table  = new TaskQueue();
        $status = $table->findAll(['id' => $id], 'id,progress,finish_time,retryCnt,retry,status')->get();

        if ($status['finish_time']) {
            $status['finish_time'] = date('Y-m-d H:i:s', $status['finish_time']);
        }
        $status['retrys'] = "{$status['retry']}/{$status['retryCnt']}";
        if ($status) {
            $tl   = new TaskLog();
            $logs = $tl->getLogs($id, $time);
        } else {
            $logs = [];
        }
        $lg = [];
        if ($logs) {
            foreach ($logs as $l) {
                $lg[] = '<p data-time="' . $l['create_time'] . '">' . date('Y-m-d H:i:s', $l['create_time']) . ' ' . $l['content'] . '</p>';
            }
        }

        return ['progress' => $status, 'logs' => implode('', $lg)];
    }

    public function data($q, $type, $runat, $count) {
        $table = new TaskQueue();
        $query = $table->select()->page()->sort();

        $where = [];
        if ($type) {
            $where['status'] = $type;
        } else {
            $where['status IN'] = ['D', 'P', 'R'];
        }
        if ($runat == '1') {
            $where['runat'] = 0;
        } else if ($runat == '2') {
            $where['runat >'] = 0;
        }
        if ($q) {
            $qw = Condition::parseSearchExpression($q, [
                '定时'   => '@runat',
                '任务'   => 'task',
                'id'   => 'id',
                'ID'   => 'id',
                'task' => 'task'
            ]);
            if ($qw) {
                $query->where($qw);
            } else {
                $where['task LIKE'] = "%$q%";
            }
        }
        $query->where($where);
        $data['items']      = $query->toArray();
        $data['total']      = $count ? $query->total('id') : '';
        $data['groups']     = $this->groups;
        $data['priorities'] = $this->priority;
        $data['tdCls']      = ['P' => '', 'F' => 'success', 'E' => 'danger', 'R' => 'info'];
        $data['ctime']      = time() + 180;

        return view($data);
    }

    public function log($id) {
        if (empty($id)) {
            Response::respond(404, $id . '为空');
        }

        $tq   = new TaskQueue();
        $task = $tq->get(['id' => $id])->ary();
        if (!$task) {
            Response::error('任务不存在');
        }

        $tl           = new TaskLog();
        $data['task'] = $task;
        $data['logs'] = $tl->getLogs($id);

        return $this->render($data);
    }
}