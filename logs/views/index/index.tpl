<div class="hbox stretch wulaui layui-hide" id="syslog-page">
    <section class="vbox">
        <header class="header bg-light lt clearfix b-b">
            <div class="row m-t-sm">
                <div class="col-xs-12 text-right m-b-xs">
                    <form data-table-form="#table" id="search-form" class="form-inline">
                        <input type="hidden" id="type" name="type" value=""/>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-sm btn-default active">
                                <input type="radio" name="level" value=""><i class="fa fa-check text-active"></i>
                                全部
                            </label>
                            <label class="btn btn-sm btn-info">
                                <input type="radio" name="level" value="INFO"><i class="fa fa-check text-active"></i>
                                提示
                            </label>
                            <label class="btn btn-sm btn-warning">
                                <input type="radio" name="level" value="WARN"><i class="fa fa-check text-active"></i>
                                警告
                            </label>
                            <label class="btn btn-sm btn-danger">
                                <input type="radio" name="level" value="ERROR"><i class="fa fa-check text-active"></i>
                                错误
                            </label>
                        </div>
                        <div class="input-group input-group-sm date">
                            <input type="text" class="form-control" autocomplete="off" name="date" id="date" style="width: 170px">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <div class="input-group input-group-sm">
                            <input id="search" data-expend="150" type="text" name="q" class="input-sm form-control" placeholder="{'Search'|t}" autocomplete="off"/>
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
                <table id="table" data-auto data-table="{'system/logs/data'|app}" data-sort="time,d"
                       style="min-width: 800px">
                    <thead>
                    <tr>
                        <th width="1"></th>
                        <th width="120" data-sort="time,d">日期 & 时间</th>
                        <th width="140" data-sort="user_id,a">用户</th>
                        <th width="130" data-sort="ip,a">IP</th>
                        <th>日志</th>
                        <th width="100" data-sort="type,a">组</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </section>
        <footer class="footer b-t">
            <div data-table-pager="#table" data-limit="30"></div>
        </footer>
    </section>
    <aside class="aside aside-sm b-l hidden-xs">
        <div class="vbox">
            <header class="bg-light lt header b-b">
                <p>日志分组</p>
            </header>
            <section class="hidden-xs scrollable m-t-xs">
                <ul class="nav nav-pills nav-stacked no-radius" id="syslog-apps">
                    <li class="active">
                        <a href="javascript:"> 全部 </a>
                    </li>
                    {foreach $groups as $gp=>$name}
                        <li>
                            <a href="javascript:" rel="{$gp}"> {$name}</a>
                        </li>
                    {/foreach}
                </ul>
            </section>
        </div>
    </aside>
</div>
<script>
	layui.use(['jquery','laydate', 'bootstrap', 'wulaui'], function ($,laydate) {
		var group = $('#syslog-apps');
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
		$('input[name="level"]').change(function () {
			$('#search-form').submit();
		});
        laydate.render({
            elem: '#date',
            range: '~',
            max: "{'Y-m-d'|date}"
        });
		$('#syslog-page').removeClass('layui-hide')
	})
</script>