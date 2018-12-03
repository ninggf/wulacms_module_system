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

/**
 * 后台任务.
 *
 * @package system\classes
 */
abstract class Task {
    protected $id;
    /**
     * 数据库连接.
     * @var \wulaphp\db\DatabaseConnection
     */
    protected $db;
    /**
     * 任务选项
     * @var array
     */
    protected $options;

    public function __construct($id, $db = null, $options = []) {
        $this->id      = $id;
        $this->db      = $db;
        $this->options = $options;
    }

    /**
     * 更新状态。
     *
     * @param int        $progress
     * @param null|array $options
     */
    public final function update($progress, $options = null) {
        $sql = 'UPDATE {task_queue} SET progress = %d';
        if ($options) {
            $options = json_encode($options);
            $sql     .= ', options = %s WHERE id = %s';
            $this->db->cud($sql, $progress, $options, $this->id);
        } else {
            $this->db->cud($sql . ' WHERE id = %s', $progress, $this->id);
        }
    }

    public function error($msg) {
        $sql = 'UPDATE {task_queue} SET msg = %s, status = %s WHERE id = %s';
        $this->db->cud($sql, $msg, 'E', $this->id);
    }

    /**
     * 记录日志.
     *
     * @param string $text 日志信息
     */
    public final function log($text) {
        $sql = 'INSERT INTO task_log VALUES(%s,%d,%s)';
        $this->db->cud($sql, $this->id, time(), $text);
    }

    /**
     * 配置表单.
     *
     * @return \wulaphp\form\FormTable|null 表单实例.
     */
    public function getForm() {
        return null;
    }

    /**
     * 运行.
     *
     * @return bool|string 成功返回true，失败返回原因.
     */
    public abstract function run();

    /**
     * @param string     $name     任务名
     * @param null|array $options  选项
     * @param int        $retryCnt 重试次数
     * @param int        $interval 重试间隔
     * @param int        $runat    定时运行
     */
    public static function enqueue($name, $options = null, $retryCnt = 0, $interval = 0, $runat = 0) {
        $tq = new TaskQueue();
        return $tq->newTask($name, static::class, 'P', $retryCnt, $runat, $options, $interval);
    }

    /**
     * 获取一个任务在进度
     *
     * @param int|string $id
     *
     * @return int
     */
    public static function progress($id) {
        try {
            $db = App::db();
            $pg = $db->queryOne('SELECT progress FROM {task_queue} WHERE id = %d', intval($id));
            if ($pg) {
                return intval($pg['progress']);
            }
        } catch (\Exception $e) {

        }

        return 0;
    }
}