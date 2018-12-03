<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\task;

use wulaphp\db\Table;

class TaskQueue extends Table {
    protected $autoIncrement = false;

    /**
     * 启动任务.
     *
     * @param array $ids 要重启的任务ID数组.
     *
     * @return bool
     */
    public function restartTask($ids) {
        if (empty($ids)) {
            return true;
        }
        $data['status']      = 'P';
        $data['progress']    = 0;
        $data['retry']       = 0;
        $data['run_time']    = 0;
        $data['finish_time'] = 0;
        $data['msg']         = '';
        $where['id IN']      = $ids;
        $where['status IN']  = ['F', 'E', 'D'];

        return $this->update($data, $where);
    }

    /**
     * 删除任务
     *
     * @param array $ids
     *
     * @return bool|\wulaphp\db\sql\DeleteSQL
     */
    public function deleteTask($ids) {
        if (empty($ids)) {
            return true;
        }
        $where['id IN']     = $ids;
        $where['status IN'] = ['F', 'E', 'P', 'D'];
        $idx                = $this->find($where, 'id')->toArray('id');
        if ($idx) {
            return $this->delete(['id IN' => $idx]);
        } else {
            return true;
        }
    }

    public function clearTask() {
        $where['status'] = 'F';

        return $this->delete($where);
    }

    /**
     * 新建一个任务.
     *
     * @param string     $name     任务名.
     * @param string     $task     任务类.
     * @param string     $status   状态.
     * @param int        $retryCnt [optional] 重试.
     * @param int|string $runat    [optional] 定时.
     * @param null|array $options  [optional] 参数.
     * @param int        $interval 重试间隔
     *
     * @return string|bool 任务ID或false
     */
    public function newTask($name, $task, $status = 'P', $retryCnt = 0, $runat = 0, $options = null, $interval = 0) {
        if (empty($name)) {
            return false;
        }
        if (!is_subclass_of($task, '\system\classes\Task')) {
            return false;
        }
        $data['id']          = uniqid();
        $data['create_time'] = time();
        $data['name']        = (string)$name;
        $data['task']        = $task;
        $data['retryCnt']    = intval($retryCnt);
        $data['retryInt']    = abs(intval($interval));
        $data['runat']       = intval(is_string($runat) ? @strtotime($runat) : $runat);

        if ($options && is_array($options)) {
            $data['options'] = @json_encode($options);
            if (isset($data['options']['crontab'])) {
                $runat = \CrontabHelper::next_runtime($data['options']['crontab']);
                if ($runat) {
                    $data['runat'] = $runat;
                }
            }
        }

        if (in_array($status, ['D', 'P'])) {
            $data['status'] = $status;
        }

        $rst = $this->insert($data);
        if ($rst) {
            return $data['id'];
        }

        return false;
    }

    /**
     * 更新.
     *
     * @param array $data
     * @param       $id
     *
     * @return bool|\wulaphp\db\sql\UpdateSQL
     */
    public function updataTask($data, $id) {
        unset($data['id']);
        $where['id']        = $id;
        $where['status IN'] = ['D', 'F', 'E'];

        return $this->update($data, $where);
    }

    /**
     * 获取所有可以手动创建的任务.
     * @use filter:system\registerTask([])
     * @return array
     */
    public static function tasks() {
        $tasks = apply_filter('system\registerTask', [
            'system\task\ScriptTask' => '脚本任务'
        ]);

        return $tasks;
    }
}