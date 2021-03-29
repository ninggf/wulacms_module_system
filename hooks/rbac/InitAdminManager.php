<?php

namespace system\hooks\rbac;

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

        $role = $manager->getResource('system/account/role', __('Role'));
        $role->addOperate('r', $viewOpName);
        $role->addOperate('add', __('Add'));
        $role->addOperate('edit', __('Edit'));
        $role->addOperate('del', __('Delete'));
        $role->addOperate('grant', __('Grant'));

        $user = $manager->getResource('system/account/user', __('User'));
        $user->addOperate('r', $viewOpName);
        $user->addOperate('add', __('Add'));
        $user->addOperate('edit', __('Edit'));
        $user->addOperate('del', __('Delete'));

        $manager->getResource('system/settings', __('Settings'))->addOperate('r', $viewOpName);
        $settings = Setting::settings();
        foreach ($settings as $s) {
            $set = $manager->getResource('system/settings/' . $s->getId(), $s->getName());
            $set->addOperate('r', $viewOpName);
            $set->addOperate('save', __('Save'));
        }
        $manager->getResource('system/logger', __('Logs'))->addOperate('r', __('View'));
        $loggers = Syslog::loggers();
        foreach ($loggers as $logger) {
            $log = $manager->getResource('system/logger/' . $logger->getId(), $logger->getName());
            $log->addOperate('r', $viewOpName);
        }
    }
}