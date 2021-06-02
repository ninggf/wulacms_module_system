<?php

namespace system\classes;

use wulaphp\mvc\view\View;

abstract class Message {
    private static $messageTypes = null;

    public abstract function getType(): string;

    public abstract function getName(): string;

    /**
     * 发送消息(其实就是将消息写到数据库里啦).
     *
     * @throws \wulaphp\validator\ValidateException
     */
    public function send(MessageDto $dto, int $uid, int $id = 0): bool {
        $data  = $dto->getData($id ? 'update' : 'new');
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
            $rst                 = $model->insert($data);
        }

        return $rst > 0;
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
     * @param array $data
     *
     * @return \wulaphp\mvc\view\View
     */
    public abstract function getNotifyView(array $data): View;

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
}