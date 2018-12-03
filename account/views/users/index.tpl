<section class="hbox stretch wulaui" id="core-account-workset">
    <aside class="aside aside-md b-r">
        <section class="vbox">
            <header class="header bg-light lt b-b">
                <button class="btn btn-icon btn-default btn-sm pull-right visible-xs m-r-xs" data-toggle="class:show"
                        data-target="#core-role-wrap">
                    <i class="fa fa-reorder"></i>
                </button>
                <p class="h4">角色</p>
            </header>
            <section class="hidden-xs scrollable w-f m-t-xs" id="core-role-wrap">
                <div id="core-role-list" data-load="{'system/account/role'|app}" data-loading="#core-role-list">
                    {include '../role/index.tpl'}
                </div>
            </section>
            <footer class="footer b-t hidden-xs">
                <a class="btn btn-success btn-sm pull-right edit-role" data-ajax="dialog"
                   href="{'system/account/role/edit'|app}" data-area="400px,auto" data-title="新的角色">
                    <i class="fa fa-plus"></i> 新角色
                </a>
            </footer>
        </section>
    </aside>

    <section>
        <section class="hbox stretch">
            <aside class="aside" id="admin-grid" data-load="{'system/account/users/grid'|app}">
                {include './grid.tpl'}
            </aside>
            <aside class="aside hidden" id="acl-space"></aside>
        </section>
    </section>
</section>
<script>
	layui.use(['jquery', 'layer', 'wulaui'], ($, layer, wui) => {
		//授权
		$('#acl-space').on('click', '#acl-cancel', () => {
			$('#acl-space').addClass('hidden');
			$('#admin-grid').removeClass('hidden').show();
		}).on('click', '#acl-save', () => {
			$('#acl-form').submit();
		}).on('click', '#acl-save-c', function () {
			$('#acl-form').data('ajaxDone', 'hide:#acl-space;show:#admin-grid').submit();
		}).on('click', '#acl-reset', function () {
			$('#acl-form').get(0).reset();
		});
		//对话框处理
		$('#core-account-workset').on('before.dialog', '.edit-admin', function (e) { // 增加编辑用户
			e.options.btn = ['保存', '取消'];
			e.options.yes = function () {
				$('#core-admin-form').on('ajax.success', function () {
					layer.closeAll()
				}).submit();
				return false;
			};
		}).on('before.dialog', '.edit-role', function (e) { //增加，编辑角色
			e.options.btn = ['保存', '取消'];
			e.options.yes = function () {
				$('#core-role-form').on('ajax.success', function () {
					layer.closeAll()
				}).submit();
				return false;
			};
		}).on('click', 'a.role-li', function () { //分角色查看用户
			var me = $(this), mp = me.closest('li'), rid = mp.data('rid'), group = me.closest('ul');
			if (mp.hasClass('active')) {
				return;
			}
			group.find('li').not(mp).removeClass('active');
			mp.addClass('active');
			$('#admin-role-id').val(rid ? rid : '');
			$('[data-table-form="#core-admin-table"]').submit();
			return false;
		}).on('change', '#ustatus', function () { //按状态查看用户
			$('#btn-do-search').click();
		});
		$('body').on('uploader.remove', '#user-avatar', function () {
			if (confirm('你真的要删除当前头像吗?')) {
				$.get("{'system/account/users/del-avatar'|app}/" + $('#id').val())
			} else {
				return false;
			}
		});
	})
</script>