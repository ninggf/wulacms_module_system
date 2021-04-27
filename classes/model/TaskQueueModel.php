<?php

namespace system\classes\model;

use wulaphp\db\Table;

/**
 * 任务队列实例.
 *
 * @package system\classes\model
 */
class TaskQueueModel extends Table {
    /**
     * 拥有多条日志.
     * @return array
     */
    public function logs(): array {
        return $this->hasMany(new TaskLogModel($this->db()));
    }

    /**
     * 属于一个任务.
     * @return array
     */
    public function task(): array {
        return $this->belongsTo(new TaskModel($this->db()));
    }
}