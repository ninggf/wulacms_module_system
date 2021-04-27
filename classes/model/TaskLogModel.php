<?php

namespace system\classes\model;

use wulaphp\db\Table;

/**
 * 任务运行日志.
 *
 * @package system\classes\model
 */
class TaskLogModel extends Table {
    /**
     * 属于一个任务实例.
     *
     * @return array
     */
    public function queue(): array {
        return $this->belongsTo(new TaskQueueModel($this->db()));
    }
}