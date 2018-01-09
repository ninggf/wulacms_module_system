<div class="container-fluid p-t-xs wulaui">
    <form action="{'system/gridcfg/save'|app}" data-ajax id="table-column-form"
          class="form-horizontal" method="post" data-ajax-done="close:parent" data-loading>
        <input type="hidden" name="table" value="{$table}"/>
        <input type="hidden" name="reload" value="{$reload}"/>
        {foreach $columns as $cid => $col}
            <div class="form-group m-b-xs">
                <label class="col-xs-8 m-t-sm">{$col.name}</label>
                <div class="col-xs-4">
                    <div class="input-group input-group-sm">
                  <span class="input-group-addon">
                    <input type="checkbox" name="cols[{$cid}]" value="1" {if $col.show}checked="checked"{/if}/>
                  </span>
                        <input type="text" class="form-control" name="ord[{$cid}]" value="{$col.order|default:99}"/>
                    </div>
                </div>
            </div>
        {/foreach}
        <div class="form-group m-b-xs">
            <div class="col-xs-4 col-xs-offset-8 m-t-md text-right">
                <button class="btn btn-primary">保存</button>
            </div>
        </div>
    </form>
</div>
<script>
    layui.use(['jquery','wulaui'])
</script>