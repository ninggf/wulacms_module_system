<div class="wulaui">
    {if $roles}
        <ul class="nav nav-pills nav-stacked no-radius" data-pop-menu=".core-role-pop-menu">
            <li class="active">
                <a href="javascript:void(0);" class="role-li">全部</a>
            </li>
            {foreach $roles as $role}
                <li {if $role.id>1}data-id="{$role.id}"{/if} data-rid="{$role.id}">
                    <a href="javascript:void(0);" class="role-li">{$role.name}</a>
                </li>
            {/foreach}
        </ul>
    {/if}
    <div class="hidden">
        <p class="text-lg core-role-pop-menu">
            <a data-ajax="dialog" href="{"system/account/role/edit"|app}" data-title="编辑角色" data-area="400px,auto" class="text-warning edit-role"
               title="编辑"><i class="fa fa-pencil-square-o"></i></a>
            {if $canAcl}
                <a href="{'system/account/role/acl'|app}" data-ajax="update"
                   data-ajax-done="hide:#admin-grid;show:#acl-space" target="#acl-space" class="m-r-xs m-l-xs"
                   title="授权"><i class="fa fa-credit-card"></i></a>
            {/if}
            {if $canDel}
            <a data-ajax href="{"system/account/role/del"|app}" class="text-danger" title="删除"
               data-confirm="角色删除后将不可恢复!">
                <i class="fa fa-trash-o"></i>
            </a>
            {/if}
        </p>
    </div>
</div>