<?php

namespace system\classes;

use backend\classes\layui\TableData;

/**
 * Base logger class
 * @package system\classes
 */
interface ILogger {
    /**
     * 日志器ID
     * @return string
     */
    function getId(): string;

    /**
     * 日志器名称
     * @return string
     */
    function getName(): string;

    /**
     * 获取视图.
     *
     * @return string
     */
    function getView(): ?string;

    /**
     * 获取数据.
     *
     * @param string $id
     * @param array  $args
     *
     * @return TableData
     */
    function getData(string $id, array $args): TableData;

    /**
     * 转换消息.
     *
     * @param string $msg
     *
     * @return string
     */
    function convertMessage(string $msg): string;

    /**
     * 图标
     * @return string|null
     */
    function getIconCls(): ?string;

    /**
     * 列
     * @return array
     */
    public function getCols(): array;

    /**
     * 在保存日志到数据库前对value1和value2进行处理.
     *
     * @param string|null $value1
     * @param string|null $value2
     */
    public function filter(?string &$value1 = null, ?string &$value2 = null);
}