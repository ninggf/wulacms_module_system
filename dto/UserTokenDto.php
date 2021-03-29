<?php

namespace system\dto;

use wulaphp\util\Params;

class UserTokenDto extends Params {
    /**
     * 用户ID
     * @required<new>
     * @num
     */
    public $user_id;
    /**
     * 设备
     * @required<new>
     * @num
     */
    public $device = 0;
    /**
     * Token
     * @required<new>
     * @length (32) => {the length of token is %s}
     */
    public $token;
    /**
     * 操作系统
     */
    public $os = '';
    /**
     * IP
     * @required<new>
     * @ip
     */
    public $ip;
    /**
     * 客户端
     */
    public $agent = '';
    /**
     * @required<new>
     * @num
     */
    public $expire_time;
}