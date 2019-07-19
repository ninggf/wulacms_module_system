<div class="container-fluid wulaui m-t-sm">
    <form id="edit-task-form" name="TaskEditForm" data-validate="{$rules|escape}" action="{'system/task/edit/save'|app}"
          data-ajax method="post" data-loading>
        <input type="hidden" name="tq" value="{$tq}"/>
        {$tform|render}
        {if $form}
            <div class="line line-dashed line-lg pull-in"></div>
            <p class="text-muted m-t-n-md">任务自定义配置</p>
            {$form|render}
        {/if}
    </form>
</div>