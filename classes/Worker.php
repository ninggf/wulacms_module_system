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

use system\task\TaskQueue;
use wulaphp\app\App;
use wulaphp\command\LoopScript;

class Worker extends LoopScript {
    /**@var \wulaphp\db\DatabaseConnection */
    private $db;
    private $sql;
    private $changeStatusSql2R;
    private $changeStatusSql2E;
    private $changeStatusSql2F;
    private $changeStatusSql2P;
    private $tqTableName;
    private $ctask = null;

    protected function setUp() {
        $ctime = time();
        if (isset($_SERVER['taskGroup'])) {
            $taskGroupDf = explode(',', trim($_SERVER['taskGroup']));
            if ($taskGroupDf) {
                $taskGroup = $taskGroupDf;
            } else {
                $taskGroup = ['0'];
            }
            if (count($taskGroup) == 1) {
                $gp = " AND `group` = '{$taskGroup[0]}'";
            } else {
                $gps = implode("','", $taskGroup);
                $gp  = " AND `group` IN ('{$gps}')";
            }
        } else {
            $gp = '';
        }
        $queue = $this->env('queue');
        //任务所有表
        if ($queue) {
            $tq = '{task_queue_' . $queue . '}';
        } else {
            $tq = '{task_queue}';
        }
        $this->tqTableName = $tq;
        //获取要执行的任务(100条)
        $this->sql = "SELECT name,id,task,options,retryCnt,retry,retryInt,`group` FROM {$tq} WHERE runat <= {$ctime} AND status = 'P' AND run_time = 0 {$gp} ORDER BY runat ASC LIMIT 0,100";
        // 正在运行
        $this->changeStatusSql2R = "UPDATE {$tq} SET status = %s, run_time = %d WHERE id = %s AND status = 'P' AND run_time = 0";
        //出错
        $this->changeStatusSql2E = "UPDATE {$tq} SET status = %s, finish_time = %d, msg = %s WHERE id = %s";
        //完成
        $this->changeStatusSql2F = "UPDATE {$tq} SET status = %s, finish_time = %d, progress = 100 WHERE id = %s";
        //重试
        $this->changeStatusSql2P = "UPDATE {$tq} SET run_time = 0, progress = 0, retry = retry + 1, status = %s, runat = %d, msg = %s WHERE id = %s";
        try {
            $this->db = App::db();
            register_shutdown_function(function () {
                if ($this->ctask) {
                    $rst = $this->ctask;
                    $sql = $this->changeStatusSql2E;
                    $this->db->cud($sql, 'E', time(), $rst['task'] . '中出现严重错误', $rst['id']);
                }
            });

            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return false;
        }
    }

    protected function run() {
        $tasks = $this->db->query($this->sql);
        if (!$tasks) {
            echo "no task to run";

            return self::DONE;//没有任务了
        }
        $db = $this->db;
        foreach ($tasks as $rst) {
            $sql = $this->changeStatusSql2R;
            $cnt = $db->cud($sql, 'R', time(), $rst['id']);
            if ($cnt !== 1) {
                continue;//没抢到，处理下一个
            }
            //处理任务
            $cls = $rst['task'];
            if (!is_subclass_of($cls, Task::class)) {
                $sql = $this->changeStatusSql2E;
                $db->cud($sql, 'E', time(), 'Task is not subclass of ' . Task::class, $rst['id']);
                continue;//处理下一个
            }
            $opts = @json_decode($rst['options'], true);
            if (!$opts) {
                $opts = [];
            }
            try {
                $this->ctask = $rst;
                /**@var \system\classes\Task $clz */
                $clz = new $cls($rst['id'], $db, $opts);
                $clz->setTableName($this->tqTableName);
                //运行任务
                $msg         = $clz->run();
                $this->ctask = null;
                unset($clz);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
            }

            if ($msg === true) {
                $sql = $this->changeStatusSql2F;
                $db->cud($sql, 'F', time(), $rst['id']);
                if (isset($opts['repeatInterval']) && $opts['repeatInterval']) {
                    if (is_numeric($opts['repeatInterval'])) {
                        $runat = time() + intval($opts['repeatInterval']);
                    } else {
                        $runat = time() + $rst['retryInt'];
                    }
                    $tq = new TaskQueue('', $db);
                    $tq->newTask($rst['name'], $cls, 'P', $rst['retryCnt'], $runat, $opts, $rst['retryInt'], $rst['group']);
                }
            } else {
                if ($rst['retry'] < $rst['retryCnt']) {
                    $intv = $rst['retryInt'] ? $rst['retryInt'] : 60;
                    $sql  = $this->changeStatusSql2P;
                    $db->cud($sql, 'P', time() + $intv, $msg ? $msg : '未知错误', $rst['id']);
                } else {
                    $sql = $this->changeStatusSql2E;
                    $db->cud($sql, 'E', time(), $msg ? $msg : '未知错误', $rst['id']);
                }
            }
        }
        echo "next run";

        return self::NEXT;
    }
}