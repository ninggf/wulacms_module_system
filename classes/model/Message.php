<?php

namespace system\classes\model;

use wulaphp\db\Table;

class Message extends Table {

    public function readlogs() {
        return $this->hasMany('message_read_log');
    }

    public function deleteMsg(int $id, int $uid): int {
        $data['update_time'] = time();
        $data['update_uid']  = $uid;
        $data['status']      = 2;
        $data['deleted']     = 1;

        return $this->update()->set($data)->where(['id' => $id])->affected();
    }

    public function publishMsg(int $id, int $uid): int {
        $data['publish_time'] = $data['update_time'] = time();
        $data['publish_uid']  = $data['update_uid'] = $uid;

        $data['status'] = 1;

        return $this->update()->set($data)->where(['id' => $id])->affected();
    }
}