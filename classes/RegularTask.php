<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes;

use wulaphp\app\App;
use wulaphp\util\RedisClient;

/**
 * 定时任务.
 *
 * @package system\classes
 */
abstract class RegularTask {
    protected $id;
    /**
     * 数据库连接.
     * @var \wulaphp\db\DatabaseConnection
     */
    protected $db;
    /**
     * 任务选项
     * @var array
     */
    protected $options;
    protected $tqName = '{task_queue}';

    public function __construct($id, $db = null, $options = []) {
        $this->id      = $id;
        $this->db      = $db;
        $this->options = $options;
    }

    /**
     * 将定时任务加入队列
     *
     * @param string     $name  任务名
     * @param int        $runat 时间戳(不能比当前时间小)
     * @param null|array $options
     *
     * @return bool
     */
    public static function enqueue($name, $runat, $options = null) {
        $runat = intval($runat);
        if ($runat <= time()) {
            return false;
        }
        try {
            $task['clz']  = static::class;
            $task['opts'] = $options;
            //队列键名
            $queueKey = date('Ymd_His', $runat);
            $db       = App::db();

            $id          = uniqid();
            $create_time = time();

            $rtSQL = 'INSERT INTO {regular_task} (id,name,create_time,run_time) VALUE (%s,%s,%d,%d)';
            if (!$db->cudx($rtSQL, $id, $name, $create_time, $runat)) {
                return false;
            }
            $updateSQL = 'UPDATE {regular_task_queue} SET total=total+1 WHERE id = %d';
            if (!$db->cud($updateSQL, $runat)) {//未更新到则说明需要添加
                $rtqSQL = 'INSERT INTO {regular_task_queue} (id,schedule,total) VALUE (%d,%s,1)';
                if (!$db->cud($rtqSQL, $runat, date('Y-m-d H:i:s', $runat))) {
                    if (!$db->cud($updateSQL, $runat)) {
                        $db->cud('DELETE FROM {regular_task} WHERE id = %s', $id);//删除

                        return false;
                    }
                }
            }
            $task['id'] = $id;
            $redis      = RedisClient::getRedis(App::icfg('taskQueueDB', 13));
            if (!$redis->lPush($queueKey . '_' . strval(rand(1, 10)), json_encode($task))) {
                $db->cud('DELETE FROM {regular_task} WHERE id = %s', $id);//删除

                return false;
            }

            return $id;
        } catch (\Exception $e) {
            log_error($e->getMessage(), 'regular_task.err');
        }

        return false;
    }

    /**
     * 运行出错啦.
     *
     * @param string $msg
     */
    public function error($msg) {
        $sql = 'UPDATE {regular_task} SET msg = %s, status = %s WHERE id = %s';
        $this->db->cud($sql, $msg, 'E', $this->id);
    }

    /**
     * 运行.
     *
     * @return bool|string 成功返回true，失败返回原因.
     */
    public abstract function run();
}