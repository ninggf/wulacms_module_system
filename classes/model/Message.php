<?php

namespace system\classes\model;

use wulaphp\db\HasMeta;
use wulaphp\db\Table;

class Message extends Table {
    use HasMeta;

    /**
     * 读取日志.
     *
     * @return array
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:12:04
     * @since  1.0.0
     */
    public function readlogs(): array {
        return $this->hasMany('message_read_log');
    }

    /**
     * 元数据.
     *
     * @return array
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:12:01
     * @since  1.0.0
     */
    public function metas(): array {
        return $this->hasMany('message_meta');
    }

    /**
     * 删除消息.
     *
     * @param int $id
     * @param int $uid
     *
     * @return int
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:12:11
     * @since  1.0.0
     */
    public function deleteMsg(int $id, int $uid): int {
        $data['update_time'] = time();
        $data['update_uid']  = $uid;
        $data['status']      = 2;
        $data['deleted']     = 1;

        return $this->update()->set($data)->where(['id' => $id])->affected();
    }

    /**
     * 发布消息.
     *
     * @param int $id
     * @param int $uid
     *
     * @return int
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:12:22
     * @since  1.0.0
     */
    public function publishMsg(int $id, int $uid): int {
        $data['publish_time'] = $data['update_time'] = time();
        $data['publish_uid']  = $data['update_uid'] = $uid;

        $data['status'] = 1;

        return $this->update()->set($data)->where(['id' => $id])->affected();
    }
}