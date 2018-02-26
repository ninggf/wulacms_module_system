<div class="hbox stretch wulaui layui-hide" id="task-list">
    <section class="vbox">
        <header class="header bg-light clearfix b-b">
            <div class="row m-t-sm">
                <div class="col-xs-4 m-b-xs">
                    <a href="{'system/task/edit/add'|app}" class="btn btn-sm btn-success new-task" data-ajax="dialog"
                       data-area="300px,auto" data-title="新建任务">
                        <i class="fa fa-plus"></i> {'New'|t:' '}
                    </a>
                    <a href="{'system/task/restart'|app}" data-ajax data-grp="#table tbody input.grp:checked"
                       data-confirm="你真的要启动这些任务吗？" data-warn="请选择要启动的任务" class="btn btn-sm btn-primary"><i
                                class="fa fa-play-circle-o"></i>
                        {'Start'|t}</a>
                    <a href="{'system/task/del'|app}" data-ajax data-grp="#table tbody input.grp:checked"
                       data-confirm="你真的要删除这些任务吗？" data-warn="请选择要删除的任务" class="btn btn-danger btn-sm"><i
                                class="fa fa-trash"></i> {'Delete'|t}</a>
                    <button class="btn btn-sm" id="btn-reload">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
                <div class="col-xs-8 text-right m-b-xs">
                    <form data-table-form="#table" id="search-form" class="form-inline">
                        <input type="hidden" id="type" name="type" value=""/>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-sm btn-default active">
                                <input type="radio" name="runat" value=""><i class="fa fa-check text-active"></i>
                                全部
                            </label>
                            <label class="btn btn-sm btn-info">
                                <input type="radio" name="runat" value="2"><i class="fa fa-check text-active"></i>
                                定时任务
                            </label>
                            <label class="btn btn-sm btn-warning">
                                <input type="radio" name="runat" value="1"><i class="fa fa-check text-active"></i>
                                普通任务
                            </label>
                        </div>
                        <div class="input-group input-group-sm">
                            <input id="search" data-expend="300" type="text" name="q" class="input-sm form-control"
                                   placeholder="{'Search'|t}" autocomplete="off"/>
                            <span class="input-group-btn">
                                <button class="btn btn-sm btn-info" id="btn-do-search" type="submit">Go!</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </header>
        <section class="w-f">
            <div class="table-responsive">
                <table id="table" data-auto data-table="{'system/task/data'|app}" data-sort="status,d"
                       style="min-width: 800px">
                    <thead>
                    <tr>
                        <th width="10"></th>
                        <th width="10">
                            <input type="checkbox" class="grp"/>
                        </th>
                        <th width="60" data-sort="status,d">状态</th>
                        <th data-sort="priority,a">任务</th>
                        <th width="100" data-sort="create_time,d">创建时间</th>
                        <th width="100" data-sort="runat,a">定时</th>
                        <th width="80" data-sort="progress,a">进度</th>
                        <th width="100" data-sort="retryCnt,a">重试</th>
                        <th width="100" data-sort="run_time,a">运行时间</th>
                        <th width="100" data-sort="finish_time,a">结束时间</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </section>
        <footer class="footer b-t">
            <div data-table-pager="#table" data-limit="10"></div>
        </footer>
    </section>
    <aside class="aside aside-xs b-l hidden-xs">
        <div class="vbox">
            <header class="bg-light dk header b-b">
                <p>状态</p>
            </header>
            <section class="hidden-xs scrollable m-t-xs">
                <ul class="nav nav-pills nav-stacked no-radius" id="task-status">
                    <li class="active">
                        <a href="javascript:;"> 全部 </a>
                    </li>
                    {foreach $groups as $gp=>$name}
                        <li>
                            <a href="javascript:;" rel="{$gp}" title="{$name}"> {$name}</a>
                        </li>
                    {/foreach}
                </ul>
            </section>
        </div>
    </aside>
    <a class="hidden edit-task" id="for-edit-task"></a>
</div>
<script>
	layui.use(['jquery', 'bootstrap', 'wulaui'], function ($, b, wui) {
		var group = $('#task-status'), table = $('#table');
		group.find('a').click(function () {
			var me = $(this), mp = me.closest('li');
			if (mp.hasClass('active')) {
				return;
			}
			group.find('li').not(mp).removeClass('active');
			mp.addClass('active');
			$('#type').val(me.attr('rel'));
			$('#search-form').submit();
			return false;
		});
		$('input[name="runat"]').change(function () {
			$('#search-form').submit();
		});

		$('#task-list').on('before.dialog', '.new-task', function (e) { // 增加编辑用户
			e.options.btn = ['创建', '取消'];
			e.options.yes = function () {
				if ($('#task-select').val()) {
					$('#new-task-form').data('dialogId', layer.index).submit();
				}
				return false;
			};
		}).on('before.dialog', '.edit-task', function (e) {
			e.options.btn = ['保存', '取消'];
			e.options.yes = function () {
				$('#edit-task-form').data('dialogId', layer.index).submit();
				return false;
			};
		}).removeClass('layui-hide');

		$('body').on('ajax.success', '#new-task-form', function (e, data) {
			layer.close($(this).data('dialogId'));
			if (group.find('li.active a[rel=D]').length > 0) {
				table.reload();
			} else {
				group.find('a[rel=D]').click();
			}
			wui.dialog({
				content: wui.app('system/task/edit/' + data.args.id),
				type   : 'ajax',
				area   : '500px,auto',
				title  : '编辑任务'
			}, $('#for-edit-task'));
		}).on('ajax.success', '#edit-task-form', function () {
			layer.closeAll();
			table.reload();
		});
		$('#btn-reload').click(function () {
			table.reload();
		});
	})
</script>