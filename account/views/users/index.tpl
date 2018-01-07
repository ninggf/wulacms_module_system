<section class="hbox stretch" id="core-account-workset">
    <aside class="aside aside-md b-r">
        <section class="vbox">
            <header class="header bg-light b-b">
                <button class="btn btn-icon btn-default btn-sm pull-right visible-xs m-r-xs" data-toggle="class:show"
                        data-target="#core-role-wrap">
                    <i class="fa fa-reorder"></i>
                </button>
                <p class="h4">角色</p>
            </header>
            <section class="hidden-xs scrollable w-f m-t-xs" id="core-role-wrap">
                <div id="core-role-list" class="wulaui" data-load="{'system/account/role'|app}"
                     data-loading="#core-role-list">
                    {include '../role/index.tpl'}
                </div>
            </section>
            <footer class="footer b-t hidden-xs">
                <a class="btn btn-success btn-sm pull-right edit-role" data-ajax="dialog"
                   href="{'system/account/role/edit'|app}" data-area="400px,300px"
                   data-title="新的角色">
                    <i class="fa fa-plus"></i> 新角色
                </a>
            </footer>
        </section>
    </aside>

    <section>
        <section class="hbox stretch wulaui">
            <aside class="aside" id="admin-grid" data-load="{'system/account/users/grid'|app}">
                {include './grid.tpl'}
            </aside>
            <aside class="aside hidden" id="acl-space"></aside>
        </section>
    </section>
</section>
<script>
	layui.use(['jquery', 'wulaui'], ($, wui) => {
		$('#acl-space').on('click', '#acl-cancel', () => {
			$('#acl-space').addClass('hidden');
			$('#admin-grid').removeClass('hidden').show();
		}).on('click', '#acl-save', () => {
			$('#acl-form').submit();
		}).on('click', '#acl-save-c', function () {
			$('#acl-form').data('ajaxDone', 'hide:#acl-space;show:#admin-grid').submit();
		}).on('click', '#acl-reset', function () {
			$('#acl-form').get(0).reset();
		})
	})
</script>