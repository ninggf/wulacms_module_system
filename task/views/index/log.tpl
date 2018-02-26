<div class="bg-light">
    <div class="wrapper-lg p-b-xs p-t-xs">
        <div class="row">
            <div class="col-sm-6">
                <h6 class="m-b-xs">任务</h6>
                <p class="text-xxl m-b-none"><i class="fa fa-tasks text-warning"></i> {$task.name}</p>
                <p class="text-sm">{$task.id}</p>
            </div>
            <div class="col-sm-2">
                <h6 class="m-b-xs">进度</h6>
                <p class="text-xxl m-b-none" id="progress">{$task.progress}%</p>
                {if $task.run_time}
                    <p class="text-sm">{$task.run_time|date_format:'Y-m-d H:i:s'}</p>
                {/if}
            </div>
            <div class="col-sm-2">
                <h6 class="m-b-xs">重试</h6>
                <p class="text-xxl" id="retry">{$task.retry}/{$task.retryCnt}</p>
            </div>
            <div class="col-sm-2">
                <h6 class="m-b-xs">状态</h6>
                <p class="text-xxl m-b-none" id="status" data-status="{$task.status}">{$task.status}</p>
                <p class="text-sm"
                   id="ftime">{if $task.finish_time}{$task.finish_time|date_format:'Y-m-d H:i:s'}{/if}</p>
            </div>
        </div>
    </div>
    <ul class="nav nav-tabs p-t-n-xs">
        <li class="m-l-lg active"><a href="#module-doc" data-toggle="tab">日志</a></li>
    </ul>
</div>
<div>
    <div class="tab-content">
        <div class="tab-pane active hidden" id="module-doc">
            <div class="p-md" id="logs">
                {foreach $logs as $log}
                    <p data-time="{$log.create_time}">{$log.create_time|date_format:'Y-m-d H:i:s'} {$log.content}</p>
                {/foreach}
            </div>
        </div>
    </div>
    <script>
		layui.use(['jquery', 'bootstrap'], function ($) {
			var taskId          = '{$task.id}',
				ltime = 0, logs = $('#logs'),
				mh              = $('body').height() - $('body div:first-child').height(),
				timer           = 0,
				status          = $('#status').data('status'),
				url             = '{'system/task/status'|app}';
			$('#module-doc').height(mh).css('overflow', 'auto').removeClass('hidden').animate({
				scrollTop: 10000000
			});

			var gp = function () {
				if (status === 'P' || status === 'D' || status === 'R') {
					var lp = logs.find('p:last-child');
					if (lp.length > 0) {
						ltime = lp.data('time');
					} else {
						ltime = 0;
					}
					$.get(url, {
						id  : taskId,
						time: ltime
					}, function (data) {
						if (data) {
							if (data.progress) {
								$('#progress').text(data.progress.progress + '%');
								status = data.progress.status;
								$('#status').data('status', status).text(status);
								$('#retry').text(data.progress.retrys);
								if (data.progress.finish_time) {
									$('#ftime').text(data.progress.finish_time);
								}
							}
							if (data.logs) {
								logs.append($(data.logs));
								$('#module-doc').animate({
									scrollTop: 10000000
								});
							}
						}
					}, 'json');
				} else if (timer) {
					clearInterval(timer);
				}
			};
			timer  = setInterval(gp, 5000);
		});
    </script>
</div>
