<div class="container-fluid m-t-md">
    <div class="row wulaui">
        <div class="col-sm-9">
            <form id="core-admin-form" name="AdminForm" data-validate="{$rules|escape}"
                  action="{'system/account/users/save'|app}" data-ajax data-ajax-done="reload:#core-admin-table"
                  method="post" data-loading>
                <input type="hidden" name="id" id="id" value="{$id}"/>
                {$form|render}
            </form>
        </div>
        <div class="col-sm-3">
            <label>头像</label>
            <div data-uploader="{'system/account/users/update-avatar'|app}/{$id}" id="user-avatar" data-width="120"
                 data-name="avatar" data-auto data-value="{$avatar}" data-max-file-size="512K"
                 data-resize="250,,70,1"></div>
        </div>
    </div>
</div>