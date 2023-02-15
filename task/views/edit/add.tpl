<div class="container-fluid wulaui m-t-sm">
    <form id="new-task-form" action="{'system/task/edit/add'|app}" data-ajax method="post" data-loading>
        <div class="form-group">
            <label for="task-select">请选择任务(<b class="text-danger">*</b>)</label>
            <select name="task" id="task-select" class="form-control">
                {foreach $tasks as $cls=>$name}
                    <option value="{$cls|escape}">{$name}</option>
                {/foreach}
            </select>
        </div>
    </form>
</div>