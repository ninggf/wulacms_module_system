<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system;

use system\classes\AdminPassport;
use system\classes\cmd\ResetPasswdCommand;
use system\classes\model\RoleModel;
use system\classes\model\UserMetaTable;
use system\classes\model\UserTable;
use wulaphp\app\App;
use wulaphp\auth\Passport;
use wulaphp\cache\RtCache;
use wulaphp\cmf\CmfModule;
use wulaphp\conf\Configuration;
use wulaphp\db\DatabaseConnection;
use wulaphp\io\Response;
use wulaphp\mvc\view\View;
use wulaphp\router\Router;

/**
 * 系统内核模块.
 *
 * @group kernel
 * @subEnabled
 */
class SystemModule extends CmfModule {

    public function getName(): string {
        return __('System');
    }

    public function getDescription(): string {
        return 'wualcms系统内核模块，提供用户、模块、日志、等基础功能。';
    }

    public function getHomePageURL(): string {
        return 'https://www.wulacms.com/modules/system';
    }

    public function getAuthor(): string {
        return 'Leo Ning';
    }

    public function getVersionList(): array {
        $v['1.0.0'] = '初始版本';
        $v['1.1.0'] = '添加消息功能';
        $v['1.2.0'] = '添加后台任务功能';
        $v['1.2.1'] = '添加running字段';
        $v['1.3.0'] = '添加message_meta表';

        return $v;
    }

    // 第一次安装时创建账户信息.
    public function upgradeTo1_0_0(DatabaseConnection $db): bool {
        return !empty($db->trans(function (DatabaseConnection $db) {
            $role  = new RoleModel($db);
            $ctime = time();
            if (!$role->create([
                'id'          => 1,
                'name'        => 'admin',
                'role'        => 'Administrator',
                'create_time' => $ctime,
                'update_time' => $ctime,
            ])) {
                return false;
            }
            $user = new UserTable($db);
            if (!$user->create([
                'id'            => 1,
                'name'          => 'admin',
                'create_time'   => $ctime,
                'update_time'   => $ctime,
                'passwd'        => Passport::passwd('admin'),
                'is_super_user' => 1,
                'status'        => 1
            ])) {
                return false;
            }

            $userMeta = new UserMetaTable();

            if (!$userMeta->setMetas(1, [
                'nickname' => 'Administrator'
            ])) {
                return false;
            }

            return $user->setRoles(1, [1]);
        }));
    }

    /**
     * 处理安装.
     *
     * @param Router $router
     * @param string $url
     *
     * @bind router\beforeDispatch
     * @return \wulaphp\mvc\view\View
     */
    public static function beforeDispatch(Router $router, string $url): ?View {
        if (defined('WULACMF_INSTALLED') && !WULACMF_INSTALLED) {
            if (PHP_RUNTIME_NAME == 'cli-server' && is_file(WWWROOT . $url)) {
                return null;//运行在开发服务器
            }
            Response::respond(503, "Please run 'php artisan install' first!");
        }

        return null;
    }

    /**
     * @param Passport|null $passport
     *
     * @filter passport\newAdminPassport
     *
     * @return Passport
     */
    public static function createAdminPassport(?Passport $passport) {
        if (!$passport instanceof AdminPassport) {
            $passport = new AdminPassport();
        }

        return $passport;
    }

    /**
     * 注册重置用户密码命令.
     *
     * @param array $cmds
     *
     * @filter artisan\init_admin_commands
     * @return array
     */
    public static function addResetPasswdCmd(array $cmds): array {
        if (defined('WULACMF_INSTALLED') && WULACMF_INSTALLED) {
            $cmds['reset:passwd'] = new ResetPasswdCommand('admin');
        }

        return $cmds;
    }

    /**
     * 加载配置时触发.(只在APP_TENANT_ID定义后有效)
     *
     * @filter on_load_config
     *
     * @param \wulaphp\conf\Configuration $config
     *
     * @return \wulaphp\conf\Configuration
     */
    public static function onLoadConfig(Configuration $config): Configuration {
        if (defined('APP_TENANT_ID')) {
            //从缓存加载
            $name    = $config->name();
            $setting = RtCache::get('cfg.' . $name);
            if (!is_array($setting)) {
                //从数据库加载
                try {
                    $setting = App::table('setting')->findAll([
                        'group'     => $name,
                        'tenant_id' => APP_TENANT_ID
                    ], 'name,value')->toArray('value', 'name');
                    RtCache::add('cfg.' . $name, $setting);
                } catch (\Exception $e) {
                }
            }
            if ($setting) {
                $config->setConfigs($setting);
            }
        }
        if ($config->name() == 'service') {
            $services = $config->geta('services', []);
            # 任务启动者
            $cronDef             = (array)($services['crontab'] ?? []);
            $services['crontab'] = array_merge([
                'type'   => 'script',
                'script' => 'modules/system/bin/cron.php',
                'status' => 'enabled',
                'sleep'  => 1
            ], $cronDef);
            # 任务执行者
            $execDef                  = (array)($services['cronExecutor'] ?? []);
            $services['cronExecutor'] = array_merge([
                'type'   => 'script',
                'script' => 'modules/system/bin/executor.php',
                'status' => 'enabled',
                'worker' => 1,
                'sleep'  => 1
            ], $execDef);
            $config->set('services', $services);
        }

        return $config;
    }
}
