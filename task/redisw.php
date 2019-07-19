<?php
/*
 *
 * 运行基于redis的定时（延时）队列中的任务，可以多开.
 *
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include __DIR__ . '/../../../bootstrap.php';

$worker = new \system\classes\RedisQueueWorker();
$worker->start();