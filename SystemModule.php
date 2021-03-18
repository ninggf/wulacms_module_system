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
use wulaphp\auth\Passport;
use wulaphp\cmf\CmfModule;
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
        return '内核';
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

        return $v;
    }

    // 第一次安装时创建账户信息.
    public function upgradeTo1_0_0(DatabaseConnection $db): bool {
        return !empty($db->trans(function (DatabaseConnection $db) {
            $role = new RoleModel($db);
            if (!$role->create(['id' => 1, 'name' => 'Admin'])) {
                return false;
            }
            $user  = new UserTable(true, $db);
            $ctime = time();
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
        if ($passport instanceof Passport) {
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
}
