<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\model;

use wulaphp\db\Table;

class UserMetaModel extends Table {
    /**
     * 设置用户字符型元数据.
     *
     * @param string|int $uid
     * @param string     $name
     * @param string     $value
     *
     * @return bool|int|\wulaphp\db\sql\UpdateSQL
     */
    public function setStrMeta($uid, $name, $value) {
        return $this->updateMeta($uid, $name, 'value', $value);
    }

    /**
     * 设置用户整型元数据.
     *
     * @param int|string $uid
     * @param string     $name
     * @param int        $value
     *
     * @return bool
     */
    public function setIntMeta($uid, $name, $value) {
        return $this->updateMeta($uid, $name, 'ivalue', $value);
    }

    /**
     * 用户的小部件.
     *
     * @param string|int $uid
     *
     * @return array
     */
    public function myWidgets($uid) {
        return $this->json_decode(['user_id' => $uid, 'name' => 'widgets'], 'value');
    }

    /**
     * 取字符.
     *
     * @param string      $uid
     * @param string|null $name
     *
     * @return array|string
     */
    public function getStrMeta($uid, $name = null) {
        if ($name) {
            $values = $this->get(['user_id' => intval($uid), 'name' => $name])->get('value');
        } else {
            $values = $this->get(['user_id' => intval($uid)])->toArray('value', 'name');
        }

        return $values;
    }

    /**
     * 取数值
     *
     * @param string      $uid
     * @param string|null $name
     *
     * @return array|int
     */
    public function getIntMeta($uid, $name = null) {
        if ($name) {
            $values = $this->get(['user_id' => intval($uid), 'name' => $name])->get('ivalue');
        } else {
            $values = $this->get(['user_id' => intval($uid)])->toArray('ivalue', 'name');
        }

        return $values;
    }

    /**
     * 设置元数据.
     *
     * @param string|int $uid
     * @param string     $name
     * @param string     $field
     * @param string|int $value
     *
     * @return bool|int|\wulaphp\db\sql\UpdateSQL
     */
    private function updateMeta($uid, $name, $field, $value) {
        if ($field == 'value' || $field == 'ivalue') {
            $w['name']      = $name;
            $w['user_id']   = $uid;
            $data[ $field ] = $value;
            try {
                if ($this->exist($w)) {
                    return $this->update($data, $w);
                } else {
                    $w[ $field ] = $data[ $field ];

                    return $this->insert($w);
                }
            } catch (\Exception $e) {

            }
        }

        return false;
    }
}