<?php

namespace system\classes;

abstract class Setting {
    public abstract function getId(): string;

    public abstract function getName(): string;

    public abstract function getView(): string;

    public function getIconCls(): ?string {
        return 'layui-icon-util';
    }

    public function save(): bool {
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