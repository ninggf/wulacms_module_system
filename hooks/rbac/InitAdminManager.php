<?php

namespace system\hooks\rbac;

use system\classes\Message;
use system\classes\Setting;
use system\classes\Syslog;
use wulaphp\hook\Handler;

class InitAdminManager extends Handler {
    public function handle(...$args) {
        $viewOpName = __('View');
        /**@var \wulaphp\auth\AclResourceManager $manager */
        $manager = $args[0];
        $manager->getResource('system', __('System'))->addOperate('r', $viewOpName);
        $manager->getResource('system/account', __('Account'))->addOperate('r', $viewOpName);

        //角色
        $role = $manager->getResource('system/account/role', __('Role'));
        $role->addOperate('r', $viewOpName);
        $role->addOperate('add', __('Add'));
        $role->addOperate('edit', __('Edit'));
        $role->addOperate('del', __('Delete'));
        $role->addOperate('grant', __('Grant'));

        //用户
        $user = $manager->getResource('system/account/user', __('User'));
        $user->addOperate('r', $viewOpName);
        $user->addOperate('add', __('Add'));
        $user->addOperate('edit', __('Edit'));
        $user->addOperate('del', __('Delete'));

        $manager->getResource('system/message', __('Message'))->addOperate('r', $viewOpName);
        $messageTypes = Message::messages();
        foreach ($messageTypes as $type => $msg) {
            $msg = $manager->getResource('system/message/' . $type, $msg->getName());
            $msg->addOperate('r', $viewOpName);
            $msg->addOperate('add', __('Add'));
            $msg->addOperate('edit', __('Edit'));
            $msg->addOperate('del', __('Delete'));
            $msg->addOperate('pub', __('Publish'));
        }

        //设置
        $manager->getResource('system/settings', __('Settings'))->addOperate('r', $viewOpName);
        $settings = Setting::settings();
        foreach ($settings as $s) {
            if ($s instanceof Setting) {
                $set = $manager->getResource('system/settings/' . $s->getId(), $s->getName());
                $set->addOperate('r', $viewOpName);
                $set->addOperate('save', __('Save'));
            }
        }
        //日志
        $manager->getResource('system/logger', __('Logs'))->addOperate('r', __('View'));
        $loggers = Syslog::loggers();
        foreach ($loggers as $logger) {
            $log = $manager->getResource('system/logger/' . $logger->getId(), $logger->getName());
            $log->addOperate('r', $viewOpName);
        }
    }
}