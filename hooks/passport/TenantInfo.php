<?php

namespace system\hooks\passport;

use system\classes\Tenant;
use wulaphp\hook\Handler;

class TenantInfo extends Handler {
    protected $priority = 99999999;

    public function handle(...$args) {
        $this->alter($args[0]);
    }

    private function alter(Tenant $tenant) {
        $td = $tenant->data();
        if (!strpos($td['domain'], '@')) {
            $tenant->setId(0)->setStatus(1)->setName('Owner');
        }
    }
}