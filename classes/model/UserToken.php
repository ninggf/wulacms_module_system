<?php

namespace system\classes\model;

use system\dto\UserTokenDto;
use wulaphp\db\Table;

/**
 * @dao
 * @package system\classes\model
 */
class UserToken extends Table {
    /**
     * @param \system\dto\UserTokenDto $token
     *
     * @return bool|int
     * @throws \wulaphp\validator\ValidateException
     */
    public function newToken(UserTokenDto $token) {
        $data                = $token->getData('new');
        $data['create_time'] = time();

        return $this->insert($data);
    }
}