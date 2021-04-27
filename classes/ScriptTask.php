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
                $this->log('开始执行"' . $script . '"');
                /** @noinspection PhpIncludeInspection */
                include $file;
                $this->log('脚本执行完成!');

                return true;
            } else {
                $this->error('脚本文件"' . $script . '"不存在!');
            }
        } else {
            $this->error('未指定脚本文件!');
        }

        return false;
    }
}