<?php

namespace system\hooks;

use system\classes\BaseTask;
use system\classes\ScriptTask;
use wulaphp\hook\Handler;

class TaskRegister extends Handler {
    public function handle(...$args) {
        BaseTask::register(new ScriptTask());
    }
}