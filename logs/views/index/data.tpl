<tbody data-total="{$total}">
{foreach $items as $item}
    <tr>
        <td class="{$tdCls[$item.level]}"></td>
        <td>{$item.time|date_format:'Y年m月d日H点i分'}</td>
        <td>{$item.nickname}({$item.username})</td>
        <td>{$item.ip}</td>
        <td>{$item.log|escape}</td>
        <td>{$groups[$item.type]}</td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="6" class="text-center">暂无日志</td>
    </tr>
{/foreach}
</tbody>