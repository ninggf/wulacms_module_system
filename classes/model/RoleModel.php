<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes\model;

use wulaphp\db\Table;

class RoleModel extends Table {
    /**
     * @orm
     * @return array
     */
    public function users(): array {
        return $this->belongsToMany(new UserTable($this), 'user_role');
    }

    /**
     * @orm
     * @return array
     * @throws \wulaphp\db\DialectException
     */
    public function permissions(): array {
        return $this->hasMany(new RolePermission($this));
    }
}