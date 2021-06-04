<?php

namespace system\classes;

use system\classes\model\UserTable;
use wulaphp\util\Params;

class MessageDto extends Params {
    /**
     * @var int
     * @required<update>
     * @callback<update> (checkMessage) => {message not found}
     * @num
     * @min 1
     */
    public $id;
    /**
     * @var string
     * @required<new>
     */
    public $title;
    /**
     * @var int
     * @num
     * @min 1
     * @callback<new> (checkUid) => {user not found}
     */
    public $uid;
    /**
     * 摘要
     * @var string
     */
    public $desc;
    /**
     * @var string
     * @url
     */
    public $url;

    public $content;

    public function checkMessage(int $value, array $data, string $msg) {
        if (\system\classes\model\Message::sexist(['id' => $value])) {
            return true;
        }

        return $msg;
    }

    public function checkUid(int $value, array $data, string $msg) {
        if (UserTable::sexist(['id' => $value, 'status' => 1])) {
            return true;
        }

        return $msg;
    }
}