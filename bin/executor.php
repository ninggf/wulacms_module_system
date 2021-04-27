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

class TaskExecutorLoopScript extends \wulaphp\command\LoopScript {
    protected function run(): ?int {
        $db    = \wulaphp\app\App::db();
        $ctime = time();
        $tasks = $db->select('TQ.id,TQ.options,TQ.task_id,T.retry,T.interval,TQ.retried,T.task')->from('{task_queue} AS TQ')->left('{task} AS T', 'TQ.task_id', 'T.id')->where([
            'TQ.status'        => 'P',
            'TQ.start_time <=' => $ctime
        ])->asc('TQ.start_time')->limit(0, 10)->toArray();

        if (!$tasks) {
            return self::DONE; // 退出，然后睡指定时间后再拉起执行
        }
        $taskIns = \system\classes\BaseTask::getTasks();
        foreach ($tasks as $task) {
            $rst = $db->cud('UPDATE {task_queue} SET status = %s WHERE id=%d and status=%s', 'R', $task['id'], 'P');
            if ($rst === 1) {
                try {
                    if (isset($taskIns[ $task['task'] ])) {
                        $clz = $taskIns[ $task['task'] ];
                        $clz->setup($task['id'], $db, json_decode($task['options'], true));
                        $rst = $clz->execute();
                        if ($rst) {
                            $db->cud('UPDATE {task_queue} SET end_time = %d, status=%s, progress=100 WHERE id=%d', time(), 'F', $task['id']);
                        } else {
                            throw new Exception('执行失败', 2);
                        }
                    } else {
                        throw new Exception('任务类未注册', 1);
                    }
                } catch (Exception $e) {
                    $data['end_time'] = time();
                    $data['status']   = 'E';
                    $data['progress'] = 100;
                    if ($e->getCode() == 1) {
                        $data['msg'] = $e->getMessage();
                    }

                    $db->update('{task_queue}')->set($data)->where(['id' => $task['id']])->affected();

                    if ($e->getCode() == 2 && $task['retry'] && $task['retry'] > $task['retried']) {
                        # 添加一个重试任务.
                        $tq['task_id']     = $task['task_id'];
                        $tq['create_time'] = time();
                        $tq['start_time']  = $tq['create_time'] + $task['interval'] ?: 10;
                        $tq['status']      = 'P';
                        $tq['progress']    = 0;
                        $tq['retried']     = intval($task['retried']) + 1;
                        $tq['end_time']    = 0;
                        $tq['options']     = $task['options'];
                        $db->insert($tq)->into('{task_queue}')->exec();
                    }
                }
            }
        }

        return self::NEXT; // 退出,然后立即拉起执行
    }
}

(new TaskExecutorLoopScript())->start();