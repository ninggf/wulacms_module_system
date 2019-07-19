<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes;

use wulaphp\app\App;
use wulaphp\command\LoopScript;
use wulaphp\util\RedisClient;

class RedisQueueWorker extends LoopScript {
    /**@var \wulaphp\db\DatabaseConnection */
    private $db;
    /**@var \Redis */
    private $redis;
    private $ctime;
    private $ctask   = null;
    private $queueId = 0;

    protected function setUp() {
        try {
            $this->db = App::db();
            //任务队列所在库
            $this->redis = RedisClient::getRedis(App::icfg('taskQueueDB', 13));
            $this->ctime = time();
            register_shutdown_function(function () {
                if ($this->ctask) {
                    $task = $this->ctask;
                    $this->db->cud('update {regular_task_queue} set fail=fail+1 where id = %d', $this->queueId);
                    $this->db->cud('update {regular_task} set status = %s,finish_time = %d , msg = %s where id = %s', 'E', time(), $task['clz'] . '中出现严重错误', $task['id']);
                }
            });

            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return false;
        }
    }

    protected function run() {
        $queue = $this->db->queryOne('SELECT id FROM {regular_task_queue} WHERE id <= %d AND finish_time = 0 ORDER BY id ASC LIMIT 1', $this->ctime);
        if (!$queue) {
            return self::DONE;
        }
        $this->queueId = $qid = $queue['id'];
        $count         = 500;
        $qkey          = date('Ymd_His', $qid);
        //队列状态改为R（运行中）
        $this->db->cud('update {regular_task_queue} set status=%s where id = %d and status <> \'R\'', 'R', $qid);
        for ($i = 1; $i <= 10; $i++) {
            $key = $qkey . '_' . $i;
            do {
                $task = $this->redis->rPop($key);
                if (!$task) {
                    break;
                }
                $count--;//弹出一个任务就要减一，说明队列中是有任务的
                $task = @json_decode($task, true);
                if ($task) {
                    $cls = $task['clz'];
                    if (!is_subclass_of($cls, RegularTask::class)) {
                        continue;//处理下一个
                    }
                    if ($task['opts']) {
                        $opts = $task['opts'];
                    } else {
                        $opts = [];
                    }
                    try {
                        $this->ctask = $task;
                        /**@var \system\classes\RegularTask $clz */
                        $clz = new $cls($task['id'], $this->db, $opts);
                        //运行任务
                        $msg = $clz->run();
                        unset($clz, $cls);
                        $this->ctask = null;
                    } catch (\Exception $e) {
                        $msg = $e->getMessage();
                    }
                    if ($msg === true) {//运行成功
                        $this->db->cud('update {regular_task_queue} set succ=succ+1 where id = %d', $qid);
                        $this->db->cud('update {regular_task} set status = %s,finish_time = %d where id = %s', 'F', time(), $task['id']);
                    } else {
                        $this->db->cud('update {regular_task_queue} set fail=fail+1 where id = %d', $qid);
                        $this->db->cud('update {regular_task} set status = %s,finish_time = %d , msg = %s where id = %s', 'E', time(), $msg ? $msg : '未知错误', $task['id']);
                    }
                }
            } while ($count > 0);
            if ($count == 0) {//已经完成500个任务，重启自己
                return self::NEXT;
            }
        }
        if ($count > 0) {//说明队列中的任务已经全部执行完了
            $this->db->cud('update {regular_task_queue} set status=%s,finish_time = %d where id = %d and finish_time = 0', 'F', time(), $qid);

            return self::DONE;
        }

        //立即开始下一轮
        return self::NEXT;
    }
}