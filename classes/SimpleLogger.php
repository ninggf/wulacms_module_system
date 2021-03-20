<?php

namespace system\classes;

trait SimpleLogger {
    public function getView(): ?string {
        return null;
    }

    /**
     * 获取数据
     *
     * @param string $id
     * @param array  $args
     *
     * @return array
     */
    public function getData(string $id, array $args): array {
        return [];
    }

    public function convertMessage(string $msg): string {
        return $msg;
    }

    public function getIconCls(): ?string {
        return 'layui-icon-log';
    }
}