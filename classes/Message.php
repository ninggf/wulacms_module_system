<?php

namespace system\classes;

use wulaphp\mvc\view\View;

abstract class Message {
    private static $messageTypes = null;

    public abstract function getType(): string;

    public abstract function getName(): string;

    /**
     * 发送消息.
     */
    public function send() {

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