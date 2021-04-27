<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include __DIR__ . '/../../../bootstrap.php';

$taskModel = new \system\classes\model\TaskModel();
$ctime     = time();
$tasks     = $taskModel->select()->where(['status' => 'R', 'next_runtime <=' => $ctime])->asc('next_runtime');
$db        = $taskModel->db();

foreach ($tasks as $t) {
    $data['last_runtime'] = time();
    $rst                  = $taskModel->update()->set($data)->where([
        'id'           => $t['id'],
        'last_runtime' => $t['last_runtime']
    ])->affected();
    if ($rst === 1) {
        #更新第一次运行时间.
        if (!$t['first_runtime']) {
            $update['first_runtime'] = $data['last_runtime'];
        }
        # 检测crontab
        if ($t['crontab']) {
            $next_runtime           = CrontabHelper::next_runtime($t['crontab'], $data['last_runtime'] + 1);
            $update['next_runtime'] = $next_runtime;
        } else {
            $update['status'] = 'S';
        }
        $taskModel->update($update, $t['id']);
        # 写入运行队列
        $tq['task_id']     = $t['id'];
        $tq['create_time'] = $tq['start_time'] = $data['last_runtime'];
        $tq['status']      = 'P';
        $tq['progress']    = 0;
        $tq['retried']     = 0;
        $tq['end_time']    = 0;
        $tq['options']     = $t['options'];
        $db->insert($tq)->into('{task_queue}')->exec();
    }
}