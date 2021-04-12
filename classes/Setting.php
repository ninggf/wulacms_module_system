<?php

namespace system\classes;

use system\classes\model\SettingTable;

abstract class Setting {

    /**
     * 获取id前缀
     * @return string
     * @Author LW 2021/4/9 9:56
     */
    public abstract function getPrefix(): string;

    /**
     * 获取配置ID
     * @return string
     * @Author LW 2021/4/9 9:52
     */
    public abstract function getId(): string;

    /**
     * 获取配置名
     * @return string
     * @Author LW 2021/4/9 9:52
     */
    public abstract function getName(): string;

    /**
     * 获取配置页
     * @return string
     * @Author LW 2021/4/9 9:52
     */
    public abstract function getView(): string;

    /**
     * 获取icon
     * @return string|null
     * @Author LW 2021/4/9 9:52
     */
    public function getIconCls(): ?string {
        return 'layui-icon-util';
    }

    /**
     * 读取配置对应数据
     * @return array
     * @Author LW 2021/4/9 9:53
     */
    public function getData(): array {
        if (!defined('APP_TENANT_ID')) {
            return [];
        }
        $id                 = $this->getId();
        $prefix             = $this->getPrefix();
        $settingTable       = new SettingTable();
        $group              = empty($prefix) ? $id : $prefix . ucfirst($id);
        $where['group']     = trim($group);
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
        $id     = $this->getId();
        $prefix = $this->getPrefix();
        $datas  = [];
        $group  = empty($prefix) ? $id : $prefix . ucfirst($id);
        foreach ($settings as $name => $value) {
            $data              = [];
            $data['group']     = trim($group);
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