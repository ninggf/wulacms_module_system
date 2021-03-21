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

use backend\classes\layui\TableData;
use system\classes\model\SyslogTable;
use wulaphp\db\sql\QueryBuilder;

abstract class CommonLogger implements ILogger {
    public function getView(): ?string {
        return null;
    }

    /**
     * 获取数据
     *
     * @param string $id
     * @param array  $args
     *
     * @return TableData
     */
    public function getData(string $id, array $args): TableData {
        $sysLog = (new SyslogTable())->alias('L');

        $where['L.logger']    = $id;
        $where['L.tenant_id'] = APP_TENANT_ID;//租户ID

        $page  = intval($args['page'] ?? 1);
        $limit = intval($args['limit'] ?? 30);

        $sql = $sysLog->select('L.*,U.name as username')->left('{user} AS U', 'L.user_id', 'U.id')->page($page, $limit);

        $sort = $args['sort'] ?? [];
        if ($sort && $sort['username']) {
            $sql->sort(['L.user_id', $sort['dir']]);
        } else {
            $sql->sort('L.id', 'd');
        }

        if (($val = aryget('name', $args))) {
            $where['U.name'] = $val;
        }
        if (($val = aryget('level', $args))) {
            $where['L.level'] = $val;
        }
        if (($val = aryget('ip', $args))) {
            $where['L.ip'] = $val;
        }
        if (($val = aryget('action', $args))) {
            $where['L.operation'] = $val;
        }
        if (($val = aryget('date', $args))) {
            $dates                     = explode(' - ', $val);
            $where['L.create_time >='] = strtotime($dates[0]);
            $where['L.create_time <']  = strtotime($dates[1]) + 86400;
        }

        $sql->where($where);

        $this->buildSql($sql, $args);

        $total = $sql->total();
        $logs  = $sql->toArray();

        array_walk($logs, function (&$item) {
            $item['date']   = date('Y-m-d H:i:s', $item['create_time']);
            $item['action'] = __($item['operation']);
            $this->walk($item);
        });

        return new TableData($logs, $total);
    }

    public function convertMessage(string $msg): string {
        return $msg;
    }

    public function getIconCls(): ?string {
        return 'layui-icon-log';
    }

    public function getCols(): array {
        return [];
    }

    /**
     * 在保存日志到数据库前对value1和value2进行处理.
     *
     * @param string|null $value1
     * @param string|null $value2
     */
    public function filter(?string &$value1 = null, ?string &$value2 = null) {

    }

    protected function walk(array &$item) {

    }

    protected function buildSql(QueryBuilder $sql, array $args) {

    }
}