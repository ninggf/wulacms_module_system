<?php

namespace system\classes\model;

use wulaphp\db\Table;

/**
 * 后台任务表.
 *
 * @package system\classes\model
 */
class TaskModel extends Table {
    /**
     * 队列实例.
     * @return array
     */
    public function queue(): array {
        return $this->hasMany(new TaskQueueModel($this->db()));
    }
}