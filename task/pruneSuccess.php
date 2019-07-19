<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
include __DIR__ . '/../../../bootstrap.php';

try {
    $db = \wulaphp\app\App::db();
    $db->cud("DELETE FROM {task_queue} WHERE status = 'F'");
} catch (Exception $e) {
    exit(1);
}