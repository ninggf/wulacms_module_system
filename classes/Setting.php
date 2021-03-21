<?php

namespace system\classes;

use system\classes\model\SettingTable;

abstract class Setting {
    public abstract function getId(): string;

    public abstract function getName(): string;

    public abstract function getView(): string;

    public function getIconCls(): ?string {
        return 'layui-icon-util';
    }

    public function getData(): array {
        if (!defined('APP_TENANT_ID')) {
            return [];
        }
        $settingTable       = new SettingTable();
        $where['group']     = $this->getId();
        $where['tenant_id'] = APP_TENANT_ID;

        return $settingTable->select('name,value')->where($where)->toArray('value', 'name');
    }

    /**
     * 保存到数据库.
     *
     * @param array $settings
     *
     * @return bool
     */
    public function save(array $settings): bool {
        $id    = $this->getId();
        $datas = [];
        foreach ($settings as $name => $value) {
            $data              = [];
            $data['group']     = $id;
            $data['tenant_id'] = APP_TENANT_ID;
            $data['name']      = $name;
            $data['value']     = $value;
            $datas[]           = $data;
        }
        if ($datas) {
            $settingTable = new SettingTable();

            return $settingTable->upserts($datas, ['value' => imv('VALUES(value)')], 'PRIMARY');
        }

        return true;
    }

    /**
     * @return \system\classes\Setting[]
     */
    public final static function settings(): array {
        static $settings = null;

        if ($settings === null) {
            $settings = (array)apply_filter('system\Setting', []);
        }

        return $settings;
    }

    /**
     * 获取配置实例.
     *
     * @param string $id
     *
     * @return \system\classes\Setting|null
     */
    public final static function getSetting(string $id): ?Setting {
        if ($id) {
            $settings = self::settings();

            return $settings[ $id ] ?? null;
        }

        return null;
    }
}