<?php

namespace system\classes;

use system\classes\model\MessageMetaTable;
use wulaphp\app\App;
use wulaphp\mvc\view\View;

abstract class Message {
    private static $messageTypes = null;

    public static function getAllNewCount(int $uid) {
        $sql            = <<<'SQL'
select count(*) as cnt from {message} M 
    where uid in (0,%d) 
    and status = 1
    and not exists(
        select user_id from {message_read_log}  where message_id = M.id and user_id = %d
    )
SQL;
        $db             = App::db();
        $cnt            = $db->query($sql, $uid, $uid)[0];

        return $cnt['cnt'] ?: 0;
    }

    public abstract function getType(): string;

    public abstract function getName(): string;

    /**
     * 元数据定义.
     *
     * @return array
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 12:34:55
     * @since  1.0.0
     */
    public function getMetas(): array {
        return [];
    }

    /**
     * 发送消息(其实就是将消息写到数据库里啦).
     *
     * @throws \wulaphp\validator\ValidateException
     */
    public function send(MessageDto $dto, int $uid, int $id = 0): bool {
        $data = $dto->getData($id ? 'update' : 'new');
        $meta = $data['meta'] ?? [];
        unset($data['meta']);
        $model = new \system\classes\model\Message();
        if ($id) {
            $data['update_time'] = time();
            $data['update_uid']  = 0;
            $data['status']      = 0;
            $data['uid']         = $data['uid'] ?? 0;
            $rst                 = $model->update()->set($data)->where(['id' => $id])->affected();
        } else {
            unset($data['uid'], $data['id']);
            $data['type']        = $this->getType();
            $data['tenant_id']   = 0;
            $data['create_time'] = $data['update_time'] = time();
            $data['create_uid']  = $data['update_uid'] = $uid;
            $id                  = $rst = $model->insert($data);
        }

        if ($id && $meta) {
            $myMetas = $this->getMetas();
            if (!$myMetas) {
                return $rst > 0;
            }
            # 只保存消息支持的元数据
            $meta      = array_filter($meta, function ($item, $key) use ($myMetas) {
                return isset($myMetas[ $key ]);
            });
            $metaModel = new MessageMetaTable();
            $metaModel->setMetas($id, $meta);
        }

        return $rst > 0;
    }

    /**
     * 获取数据.
     *
     * @param int      $uid   用户ID
     * @param int      $start 开始位置
     * @param int      $limit 分页大小
     * @param array    $where 条件
     * @param int|null $total 查询总数
     *
     * @return array
     * @throws \wulaphp\db\DialectException
     */
    public function getMessages(int $uid, int $start = 0, int $limit = 10, ?int &$total = null, array $where = []): array {
        $db = App::db();

        $sql  = <<<'SQL'
SELECT 
       {_fields_}
 FROM {message} AS M
LEFT JOIN {message_read_log} as RL ON RL.message_id = M.id and RL.user_id = %d
WHERE M.status = 1
and M.tenant_id =0
and M.uid in (0,%d)
{and cond}
ORDER BY RL.read_time ,id desc 
LIMIT %d,%d
SQL;
        $args = [$uid, $uid];
        if ($where) {
            $cond = $where[0];
            $sql  = str_replace('{and cond}', 'and ' . $cond, $sql);
            array_shift($where);
            if ($where) {
                $args = array_merge($args, $where);
            }
        } else {
            $sql = str_replace('{and cond}', '', $sql);
        }
        $sql1   = str_replace('{_fields_}', 'M.*,IFNULL(RL.read_time,0) as read_time', $sql);
        $args[] = $start;
        $args[] = $limit;

        $datas = $db->query($sql1, ...$args);
        if ($datas && !is_null($total)) {
            $sql2  = str_replace('{_fields_}', 'count(*) as total', $sql);
            $cnt   = $db->query($sql2, ...$args)[0];
            $total = $cnt['total'] ?: 0;
        }

        return $datas;
    }

    /**
     * 编辑视图.
     *
     * @return \wulaphp\mvc\view\View
     */
    public abstract function getEditView(): View;

    /**
     * 查看视图
     *
     * @param array $data
     *
     * @return \wulaphp\mvc\view\View
     */
    public abstract function getView(array $data): View;

    /**
     * 消息中心视图.
     *
     * @param int $uid
     * @param int $start
     * @param int $limit
     *
     * @return \wulaphp\mvc\view\View
     */
    public abstract function getNotifyView(int $uid, int $start = 0, int $limit = 30): View;

    /**
     * 获取系统支持的消息类型列表.
     *
     * @return \system\classes\Message[]
     */
    public static function messages(): array {
        if (self::$messageTypes === null) {
            self::$messageTypes = [];
            try {
                fire('system\messageRegister');
            } catch (\Exception $e) {
            }
        }

        return self::$messageTypes;
    }

    /**
     * 创建指定类型的消息实例.
     *
     * @param string $type
     *
     * @return \system\classes\Message|null
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:21:22
     * @since  1.0.0
     */
    public static function createMessage(string $type): ?Message {
        $messages = self::messages();

        return $messages[ $type ] ?? null;
    }

    /**
     * 注册一个消息类型.
     *
     * @param \system\classes\Message $message
     */
    public static function register(Message $message) {
        $type                        = $message->getType();
        self::$messageTypes[ $type ] = $message;
    }

    /**
     * 获取新消息
     */
    public function getNewCount(int $uid, int $tenant_id = 0): int {
        $sql = <<<'SQL'
select count(*) as cnt from {message} M 
    where uid in (0,%d) 
    and status = 1
    and tenant_id = 0
    and type = %s
    and not exists(
        select user_id from {message_read_log}  where message_id = M.id and user_id = %d
    )
SQL;
        $db  = App::db();
        $cnt = $db->query($sql, $uid, $this->getType(), $uid)[0];

        return $cnt['cnt'] ?: 0;
    }
}