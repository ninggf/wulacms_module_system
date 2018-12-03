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
while (true) {
    try {
        $db = \wulaphp\app\App::db();
    } catch (Exception $e) {
        exit(2);
    }
    try {
        $q   = $db->select('name,id,task,options,retryCnt,retry,retryInt');
        $rst = $q->from('{task_queue}')->where([
            'status'   => 'P',
            'run_time' => 0,
            'runat <=' => time()
        ])->desc('priority')->desc('runat')->asc('create_time')->get(0);

        if ($rst) {
            $sql = 'UPDATE {task_queue} SET status = %s, run_time = %d WHERE id = %s AND status = \'P\' AND run_time = 0';
            $cnt = $db->cud($sql, 'R', time(), $rst['id']);
            if ($cnt !== 1) {
                continue;
            }
        } else {
            $db->close();
            exit(0);
        }
        //处理任务
        if ($rst) {
            $cls = $rst['task'];
            if (!is_subclass_of($cls, \system\classes\Task::class)) {
                $sql = 'UPDATE {task_queue} SET status = %s, finish_time = %d, msg = %s WHERE id = %s';
                $db->cud($sql, 'E', time(), 'Task is not subclass of ' . \system\classes\Task::class, $rst['id']);
                $db->close();
                exit(0);
            }
            $opts = @json_decode($rst['options'], true);
            /**@var \system\classes\Task $clz */
            $clz = new $cls($rst['id'], $db, $opts);
            try {
                $msg = $clz->run();
            } catch (\Exception $e) {
                $msg = $e->getMessage();
            }

            if ($msg === true) {
                $sql = 'UPDATE {task_queue} SET status = %s, finish_time = %d, progress = 100 WHERE id = %s';
                $db->cud($sql, 'F', time(), $rst['id']);
                if (isset($opts['repeatInterval']) && $opts['repeatInterval']) {
                    if (is_numeric($opts['repeatInterval'])) {
                        $runat = time() + intval($opts['repeatInterval']);
                    } else {
                        $runat = time() + $rst['retryInt'];
                    }
                    $tq = new \system\task\TaskQueue($db);
                    $id = $tq->newTask($rst['name'], $cls, 'P', $rst['retryCnt'], $runat, $opts, $rst['retryInt']);
                }
            } else {
                if ($rst['retry'] < $rst['retryCnt']) {
                    $intv = $rst['retryInt'] ? $rst['retryInt'] : 60;
                    $sql  = 'UPDATE {task_queue} SET run_time = 0,progress = 0, retry = retry + 1, status = %s,runat = %d, msg = %s WHERE id = %s';
                    $db->cud($sql, 'P', time() + $intv, $msg ? $msg : '未知错误', $rst['id']);
                } else {
                    $sql = 'UPDATE {task_queue} SET status = %s, finish_time = %d, msg = %s WHERE id = %s';
                    $db->cud($sql, 'E', time(), $msg ? $msg : '未知错误', $rst['id']);
                }
            }
        }
        $db->close();
        exit(0);
    } catch (Exception $e) {
        //估计有问题，让worker多睡觉一会
        $db->close();
        exit(2);
    }
}