<?php

namespace system\hooks\system;

use system\classes\ILogger;
use wulaphp\hook\Alter;

class Logger extends Alter implements ILogger {
    public function alter($value, ...$args) {
        $value['authlog'] = $this;

        return $value;
    }

    function getId(): string {
        return 'authlog';
    }

    function getName(): string {
        return __('Auth Logger');
    }

    function format(array $log): string {
        return @sprintf($log['message'], $log['value1']);
    }
}