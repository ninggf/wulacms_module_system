<div class="container-fluid wulaui m-t-sm">
    <form id="core-role-form" name="RoleForm" data-validate="{$rules|escape}" action="{'system/account/role/save'|app}"
          data-ajax data-ajax-done="reload:#core-role-table" method="post" data-loading>
        <input type="hidden" name="id" id="id" value="{$id}"/>
        {$form|render}
    </form>
</div>