<?php

namespace system\classes;
/**
 * 运行一个指定脚本的任务.
 *
 * @package system\classes
 */
class ScriptTask extends BaseTask {
    public function getName(): string {
        return __('Script');
    }

    public function getId(): string {
        return 'script';
    }

    public function execute(): bool {
        $script = $this->options['script'] ?? null;
        if ($script) {
            $file = APPROOT . $script;
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include $file;

                return true;
            }
        }

        return false;
    }
}