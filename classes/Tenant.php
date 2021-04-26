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
 * @property-read int    $id
 * @property-read string $name
 * @property-read string $username;
 * @property-read int    $status
 * @property-read string $domain
 */
class Tenant {
    /**@var Tenant */
    private static $tenant;
    protected      $id     = - 1;
    protected      $name   = '';
    protected      $status = 0;
    protected      $username;
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
        $ds       = explode('@', $domain);
        $username = trim($ds[0] ?? '');
        $domain   = trim($ds[1] ?? '');

        try {
            if (!self::$tenant) {
                self::$tenant = new self(- 1, $domain);
                self::$tenant->setUsername($username);
                fire('passport\TenantInfo', self::$tenant);
            } else if (self::$tenant->id == - 1) {
                self::$tenant->setDomain($domain);
                self::$tenant->setUsername($username);
                fire('passport\TenantInfo', self::$tenant);
            }
        } catch (\Exception $e) {
        }

        if (!defined('APP_TENANT_ID') && self::$tenant->id >= 0) {
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

    /**
     * 设置当前租户的用户名.
     *
     * @param string $username
     *
     * @return $this
     */
    public function setUsername(string $username): Tenant {
        $this->username = $username;

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
        return [
            'name'     => $this->name,
            'id'       => $this->id,
            'domain'   => $this->domain,
            'status'   => $this->status,
            'username' => $this->username
        ];
    }

    public function __get($field) {
        return property_exists($this, $field) ? $this->{$field} : null;
    }
}