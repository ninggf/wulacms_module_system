<div class="hbox stretch wulaui layui-hide bg-white-only" id="module-page">
    <aside class="hidden-xs aside-sm b-r">
        <div class="vbox">
            <header class="bg-light lt header b-b">
                <p>模块分组</p>
            </header>
            <section class="hidden-xs scrollable m-t-xs">
                <ul class="nav nav-pills nav-stacked no-radius" id="core-module-groups">
                    <li class="active">
                        <a href="javascript:"> 全部 </a>
                    </li>
                    {foreach $groups as $gp}
                        <li>
                            <a href="javascript:" rel="{$gp}"> {$gp}</a>
                        </li>
                    {/foreach}
                </ul>
            </section>
        </div>
    </aside>
    <section>
        <div class="vbox">
            <header class="header bg-light lt clearfix p-l-none p-r-none">
                <div class="layui-tab layui-tab-brief caller-tab m-b-none">
                    <ul class="layui-tab-title m-b-none">
                        <li class="{if $type=='installed'}layui-this{/if}">
                            <a href="{'system/module/installed'|app}" class="text-primary">已安装（{$insCnt}）</a>
                        </li>
                        {if $upCnt>0}
                            <li class="{if $type=='upgradable'}layui-this{/if}">
                                <a href="{'system/module/upgradable'|app}">可升级（{$upCnt}）</a>
                            </li>
                        {/if}
                        {if $uninsCnt>0}
                            <li class="{if $type=='uninstalled'}layui-this{/if}">
                                <a href="{'system/module/uninstalled'|app}">未安装（{$uninsCnt}）</a>
                            </li>
                        {/if}
                    </ul>
                </div>
            </header>
            <section class="scrollable">
                <div class="tab-content" style="height: 100%">
                    <div class="tab-pane active" id="module-list" style="height: 100%">
                        <div class="table-responsive">
                            <table id="core-module-table" data-table style="min-width: 600px">
                                <thead>
                                <tr>
                                    <th width="160">名称</th>
                                    <th>描述</th>
                                    <th width="100">版本</th>
                                    <th width="120">作者</th>
                                    <th width="60"></th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach $modules as $m}
                                    <tr data-field-gp="{$m.group}" class="{if $m.status == 0}text-muted{/if}">
                                        <td>
                                            <a data-cls="layui-icon" data-tab="&#xe857;" data-title="模块:{$m.name}"
                                               href="{'system/module/detail'|app}/{$m.namespace}"><b>{$m.name}</b></a>
                                        </td>
                                        <td>{$m.desc|escape}</td>
                                        {if $type=='installed' || $type == 'upgradable'}
                                            <td>{$m.cver}{if $m.upgradable}
                                                    <b class="text-primary">&#10148;</b>
                                                    {$m.ver}{/if}
                                            </td>
                                        {else}
                                            <td>{$m.ver}</td>
                                        {/if}
                                        <td>{$m.author}</td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                {if $m.status == -1}
                                                    <a href="{'system/module/install'|app}/{$m.namespace}" data-ajax
                                                       data-confirm="你真要安装该模块吗?" class="btn btn-xs btn-primary"
                                                       title="安装"><i class="fa fa-hdd-o"></i></a>
                                                {elseif $m.status == 1}
                                                    <a href="{'system/module/stop'|app}/{$m.namespace}" data-ajax
                                                       data-confirm="你真的要停用模块『{$m.name}』吗?"
                                                       class="btn btn-xs btn-warning" title="停用"><i
                                                                class="fa fa-pause"></i></a>
                                                    <a href="{'system/module/uninstall'|app}/{$m.namespace}" data-ajax
                                                       data-confirm="你真的要卸载模块『{$m.name}』吗?"
                                                       class="btn btn-xs btn-danger" title="卸载"><i
                                                                class="fa fa-trash-o"></i></a>
                                                {elseif $m.status==2}
                                                    <a href="{'system/module/upgrade'|app}/{$m.namespace}" data-ajax
                                                       data-confirm="你确定要升级此模块吗?" class="btn btn-xs btn-primary"
                                                       title="升级"><i class="fa fa-arrow-up"></i></a>
                                                {else}
                                                    <a href="{'system/module/start'|app}/{$m.namespace}" data-ajax
                                                       data-confirm="你确定要启用模块『{$m.name}』吗?"
                                                       class="btn btn-xs btn-success" title="启用"><i
                                                                class="fa fa-play"></i></a>
                                                    <a href="{'system/module/uninstall'|app}/{$m.namespace}" data-ajax
                                                       data-confirm="你真的要卸载模块『{$m.name}』吗?"
                                                       class="btn btn-xs btn-danger" title="卸载"><i
                                                                class="fa fa-trash-o"></i></a>
                                                {/if}
                                            </div>
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>
    <script type="text/javascript">
        layui.use(['jquery', 'wulaui'], ($) => {
            let group = $('#core-module-groups');
            group.find('a').click(function () {
                let me = $(this), mp = me.closest('li');
                if (mp.hasClass('active')) {
                    return;
                }
                group.find('li').not(mp).removeClass('active');
                mp.addClass('active');
                $('#core-module-table').wulatable('filter', 'gp', me.attr('rel'));
                return false;
            });
            $('#module-page').removeClass('layui-hide')
        })
    </script>
</div>