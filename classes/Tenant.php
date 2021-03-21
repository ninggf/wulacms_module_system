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
/**
 * Tenant
 * @package system\classes
 */
class Tenant {
    /**@var Tenant */
    private static $tenant;
    protected      $id     = - 1;
    protected      $name;
    protected      $status = 0;
    protected      $domain = '';

    private function __construct(int $id, string $domain) {
        $this->id     = $id;
        $this->domain = $domain;
    }

    /**
     * @param string $domain
     *
     * @return \system\classes\Tenant|null
     */
    public static function getByDomain(string $domain): Tenant {
        $ds     = explode('@', $domain);
        $domain = trim($ds[1] ?? '');
        try {
            if (!self::$tenant) {
                self::$tenant = new self(- 1, $domain);
                fire('passport\TenantInfo', self::$tenant);
            } else if (self::$tenant->id >= 0 && self::$tenant->domain != $domain) {
                self::$tenant->domain = $domain;

                fire('passport\TenantInfo', self::$tenant);
            }
        } catch (\Exception $e) {
        }
        if (!defined('APP_TENANT_ID') && self::$tenant->isEnabled()) {
            define('APP_TENANT_ID', self::$tenant->id);
        }

        return self::$tenant;
    }

    public function setId(int $id): Tenant {
        $this->id = $id;

        return $this;
    }

    public function setDomain(string $domain): Tenant {
        $this->domain = $domain;

        return $this;
    }

    public function setName(string $name): Tenant {
        $this->name = $name;

        return $this;
    }

    public function setStatus(int $status): Tenant {
        $this->status = $status;

        return $this;
    }

    /**
     * 是否可用.
     *
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->status === 1 && $this->id >= 0;
    }

    public function data(): array {
        return get_object_vars($this);
    }
}