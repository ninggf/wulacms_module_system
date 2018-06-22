<tbody data-total="{$total}">
{foreach $items as $item}
    <tr class="{$tdCls[$item.status]}" {if $item.msg && $item.status == 'E'}rel="{$item.id}"{/if}>
        <td></td>
        <td>
            <input type="checkbox" value="{$item.id}" class="grp"/>
        </td>
        <td class="st">{$groups[$item.status]}</td>
        <td>
            <p>[{$priorities[$item.priority]}]
                {if $item.status == 'D' || $item.status == 'E' || $item.runat > $ctime}
                    <a href="{'system/task/edit'|app}/{$item.id}" class="edit-task" data-title="编辑任务"
                       data-area="500px,auto" data-ajax="dialog">{$item.name}</a>
                {else}
                    {$item.name}
                {/if}
            </p>
            <p class="text-muted">
                {$item.id}
            </p>
        </td>
        <td>{$item.create_time|date_format:'Y-m-d H:i:s'}</td>
        <td>{if $item.runat}{$item.runat|date_format:'Y-m-d H:i:s'}{/if}</td>
        <td rel="{$item.id}" class="task-s-{$item.status}">
            {if $item.status != 'P' && $item.status != 'D'}
                <a href="{'system/task/log'|app}/{$item.id}" title="任务[{$item.id}]" data-tab="&#xe659;">{$item.progress}
                    %</a>
            {else}
                {$item.progress}%
            {/if}
        </td>
        <td class="rt">{if $item.retryCnt>0}{$item.retry}/{$item.retryCnt}/{$item.retryInt}s{/if}</td>
        <td>{if $item.run_time}{$item.run_time|date_format:'Y-m-d H:i:s'}{/if}</td>
        <td class="ft">{if $item.finish_time}{$item.finish_time|date_format:'Y-m-d H:i:s'}{/if}</td>
    </tr>
    {if $item.msg && $item.status == 'E'}
        <tr class="danger hidden">
            <td colspan="2"></td>
            <td colspan="8">{$item.msg}</td>
        </tr>
    {/if}
    {foreachelse}
    <tr>
        <td colspan="10" class="text-center">无数据</td>
    </tr>
{/foreach}
</tbody>