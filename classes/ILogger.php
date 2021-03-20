<?php

namespace system\classes;

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
     * @return array
     */
    function getData(string $id, array $args): array;

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
}