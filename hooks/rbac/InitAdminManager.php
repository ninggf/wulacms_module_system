<?php

namespace system\hooks\rbac;

use wulaphp\hook\Handler;

class InitAdminManager extends Handler {
    public function handle(...$args) {
        /**@var \wulaphp\auth\AclResourceManager $manager */
        $manager = $args[0];
        $manager->getResource('account', __('Account'));
        $role = $manager->getResource('account/role', __('Role'));

        $role->addOperate('add', __('Add Role'));

        $user = $manager->getResource('account/user', __('User'));
        $user->addOperate('add', __('Add User'));
    }
}