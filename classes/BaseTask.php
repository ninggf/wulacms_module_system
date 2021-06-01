<?php

namespace system\classes;

use system\classes\model\TaskModel;
use wulaphp\db\DatabaseConnection;
use wulaphp\mvc\view\View;

/**
 * 后台任务. 由task worker执行
 *
 * @package system\classes
 */
abstract class BaseTask {
    private static $tasks = null;
    protected      $id;
    /**
     * 数据库连接.
     * @var \wulaphp\db\DatabaseConnection
     */
    protected $db;
    /**
     * 任务选项
     * @var array
     */
    protected $options;
    /**
     * 错误信息，任务执行出错时请设置.
     *
     * @var string
     */
    protected $errorMsg;
    /**
     * @var string
     */
    private $name = '';  //任务名称
    /**
     * @var int
     */
    private $retry = 0;
    /**
     * @var int
     */
    private $interval = 0;
    /**
     * @var string
     */
    private $crontab = '';
    /**
     * @var int
     */
    private $runat = 0;
    /**
     * @var string
     */
    private $remark;

    /**
     * BaseTask constructor.
     *
     * @param string $name   任务实例名
     * @param string $remark 说明.
     */
    public function __construct(string $name = '', string $remark = '') {
        $this->name   = $name;
        $this->remark = $remark;
    }

    /**
     * 运行前配置.
     *
     * @param int                                 $id
     * @param \wulaphp\db\DatabaseConnection|null $db
     * @param array                               $options
     *
     * @return bool
     */
    public function setup(int $id, DatabaseConnection $db = null, array $options = []): bool {
        $this->id      = $id;
        $this->db      = $db;
        $this->options = $options;

        return true;
    }

    /**
     * 更新状态。
     *
     * @param int        $progress 进度.
     * @param null|array $data     中间状态数据
     */
    public final function update(int $progress, ?array $data = null) {
        if ($this->id) {
            $sql = 'UPDATE {task_queue} SET progress = %d';
            if ($data) {
                $data = json_encode(array_merge($this->options, $data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $sql  .= ', options = %s WHERE id = %s';
                $this->db->cud($sql, $progress, $data, $this->id);
            } else {
                $this->db->cud($sql . ' WHERE id = %s', $progress, $this->id);
            }
        }
    }

    /**
     * 任务运行出错.
     *
     * @param string $msg
     */
    public function error(string $msg) {
        if ($this->id) {
            $sql = 'UPDATE {task_queue} SET msg = %s, status = %s WHERE id = %s';
            $this->db->cud($sql, $msg, 'E', $this->id);
        }
    }

    /**
     * 记录日志.
     *
     * @param string $text 日志信息
     */
    public final function log(string $text) {
        if ($this->id) {
            $sql = 'INSERT INTO `{task_log}`(task_queue_id,create_time,content) VALUES(%s,%d,%s)';
            $this->db->cud($sql, $this->id, time(), $text);
        }
    }

    /**
     * 配置表单.
     *
     * @return \wulaphp\mvc\view\View|null 表单实例.
     */
    public function getEditView(): ?View {
        return null;
    }

    /**
     * 执行出错信息.
     *
     * @return string|null
     */
    public function errorMsg(): ?string {
        return $this->errorMsg;
    }

    /**
     * 获取配置视图.
     *
     * @return string
     */
    public function getConfigView(): string {
        return '';
    }

    /**
     * 任务实例名称.
     *
     * @param string $name
     *
     * @return $this
     */
    public final function name(string $name): BaseTask {
        $this->name = $name;

        return $this;
    }

    /**
     * 出错重试次数.
     *
     * @param int $retry
     *
     * @return $this
     */
    public final function retry(int $retry): BaseTask {
        $this->retry = $retry;

        return $this;
    }

    /**
     * 重试间隔.
     *
     * @param int $interval
     *
     * @return $this
     */
    public final function interval(int $interval): BaseTask {
        $this->interval = $interval;

        return $this;
    }

    /**
     * cron tab表达式.
     *
     * @param string $crontab
     *
     * @return $this
     */
    public final function crontab(string $crontab): BaseTask {
        $this->crontab = $crontab;

        return $this;
    }

    /**
     * 选项（配置）
     *
     * @param array $options
     *
     * @return $this
     */
    public final function options(array $options): BaseTask {
        $this->options = $options;

        return $this;
    }

    public final function runat(int $time): BaseTask {
        $this->runat = $time;

        return $this;
    }

    public final function remark(string $remark): BaseTask {
        $this->remark = $remark;

        return $this;
    }

    /**
     * 将任务加入任务列表.
     *
     * @param int      $uid
     * @param int|null $runat
     *
     * @return int
     */
    public final function run(int $uid = 0, ?int $runat = null): int {
        $task['create_time'] = time();
        $task['user_id']     = $uid;
        $task['name']        = $this->name ?: $this->getName();
        $task['task']        = $this->getId();
        $task['retry']       = $this->retry >= 0 ? $this->retry : 0;
        $task['interval']    = $this->interval >= 0 ? $this->interval : 0;
        $task['crontab']     = $this->crontab;
        if ($this->crontab) {
            if (!is_array(\CrontabHelper::format_crontab($this->crontab))) {
                return 0;
            }
            $task['next_runtime'] = \CrontabHelper::next_runtime($this->crontab);
        } else if ($runat) {
            $task['next_runtime'] = $runat;
        } else if ($this->runat) {
            $task['next_runtime'] = $this->runat;
        } else {
            $task['next_runtime'] = $task['create_time'];//立即运行.
        }
        $task['remark']  = $this->remark;
        $task['options'] = json_encode($this->options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $taskModel       = new TaskModel();

        return $taskModel->insert($task);
    }

    /**
     * 注册任务到系统.
     *
     * @param \system\classes\BaseTask $task
     */
    public final static function register(BaseTask $task) {
        self::$tasks[ $task->getId() ] = $task;
    }

    /**
     * 获取注册到系统的任务.
     *
     * @return \system\classes\BaseTask[]
     */
    public final static function getTasks(): array {
        if (self::$tasks === null) {
            self::$tasks = [];
            try {
                fire('taskRegister');
            } catch (\Exception $e) {
            }
        }

        return self::$tasks;
    }

    /**
     * 创建$task对应的类实例.
     *
     * @param string $task
     *
     * @return \system\classes\BaseTask|null
     */
    public static function createTask(string $task): ?BaseTask {
        $tasks = self::getTasks();

        return $tasks[ $task ] ?? null;
    }

    /**
     * 任务ID.
     * @return string
     */
    public abstract function getId(): string;

    /**
     * 任务名称.
     *
     * @return string
     */
    public abstract function getName(): string;

    /**
     * 运行.
     *
     * @return bool 成功返回true，失败返回false.
     */
    public abstract function execute(): bool;
}