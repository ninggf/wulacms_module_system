<?php

namespace system\hooks\passport;

use wulaphp\hook\Alter;

class TenantInfo extends Alter {
    protected $priority = 99999999;

    /**
     * @param mixed $value ['username'=>'','tenant_id'=>0]
     * @param mixed ...$args
     *
     * @return mixed|void
     */
    public function alter($value, ...$args) {
        if (!$value || !is_array($value) || (isset($value['status']) && !$value['status'])) {
            return null;
        }
        $tenant_id = intval($value['tenant_id'] ?? 0);
        $username  = $value['username'] ?? '';
        if (!$username) {
            return null;
        }
        if ($tenant_id && !strpos($username, '@')) {
            return null;
        }
        if (!$tenant_id && strpos($username, '@')) {
            $tenant_id       = $value['tenant_id'] = 1;
            $value['status'] = 1;
        }
        # !
        defined('APP_TENANT_ID') || define('APP_TENANT_ID', $tenant_id);
        define('APP_TENANT_INFO', $value);

        return $value;
    }
}