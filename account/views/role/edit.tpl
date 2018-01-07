<div class="panel wulaui">
    <div class="panel-body">
        <form id="core-role-form" name="RoleForm" data-validate="{$rules|escape}"
              action="{'system/account/role/save'|app}" data-ajax data-ajax-done="reload:#core-role-table"
              method="post">
            <input type="hidden" name="id" id="id" value="{$id}"/>
            {$form|render}
        </form>
    </div>
</div>