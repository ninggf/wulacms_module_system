<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\installer\controllers;

use wula\cms\CmfModule;
use wulaphp\app\App;
use wulaphp\app\Module;
use wulaphp\auth\Passport;
use wulaphp\io\Response;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\controller\SessionSupport;

/**
 * Wulacms安装控制器。
 *
 * @author  Leo Ning <windywany@gmail.com>
 * @package system\installer\controllers
 * @since   3.0
 * @version 3.0
 */
class IndexController extends Controller {
    use SessionSupport;

    public function beforeRun($action, $refMethod) {
        if (WULACMF_INSTALLED) {
            //安装完成后不能再访问此控制器
            Response::respond(404);
        }

        return parent::beforeRun($action, $refMethod);
    }

    /**
     * 安装首页
     *
     * @return \wulaphp\mvc\view\View
     */
    public function index() {
        $checked[]  = ['安全模式', CmfModule::checkEnv('safe_mode', 0)];
        $checked[]  = ['文件上传', CmfModule::checkEnv('file_uploads', 1)];
        $checked[]  = ['输出缓冲区', CmfModule::checkEnv('output_buffering', 0, true)];
        $checked[]  = ['自动开启SESSION', CmfModule::checkEnv('session.auto_start', 0)];
        $checked[]  = [
            'SESSION支持',
            [
                'required' => '开',
                'checked'  => $this->sessionID ? '开' : '关',
                'pass'     => $this->sessionID ? true : false
            ]
        ];
        $checked [] = [
            'PHP版本',
            [
                'required' => '7.1.0+',
                'checked'  => phpversion(),
                'pass'     => version_compare('7.1.0', phpversion(), '<=')
            ]
        ];

        $pass = extension_loaded('pdo');
        if ($pass) {
            $drivers = \PDO::getAvailableDrivers();
            if (empty ($drivers)) {
                $pass = false;
            } else {
                $pass = in_array('mysql', $drivers);
            }
        }
        $checked [] = [
            'PDO (mysql)',
            [
                'required' => '有',
                'checked'  => $pass ? '有' : '无',
                'pass'     => $pass,
                'optional' => false
            ]
        ];

        $pass       = extension_loaded('gd');
        $checked [] = [
            'GD',
            [
                'required' => '有',
                'checked'  => $pass ? '有' : '无',
                'pass'     => $pass,
                'optional' => false
            ]
        ];

        $pass       = extension_loaded('json');
        $checked [] = [
            'JSON',
            [
                'required' => '有',
                'checked'  => $pass ? '有' : '无',
                'pass'     => $pass,
                'optional' => false
            ]
        ];

        $pass       = extension_loaded('mbstring');
        $checked [] = [
            'MB String',
            [
                'required' => '有',
                'checked'  => $pass ? '有' : '无',
                'pass'     => $pass,
                'optional' => false
            ]
        ];

        $pass       = extension_loaded('curl') && version_compare('7.30.0', curl_version()['version'], '<=');
        $checked [] = [
            'Curl',
            [
                'required' => '7.30.0+',
                'checked'  => $pass ? curl_version()['version'] : '无',
                'pass'     => $pass,
                'optional' => false
            ]
        ];

        $pass       = extension_loaded('redis');
        $checked [] = [
            'Redis',
            [
                'required' => '可选',
                'checked'  => $pass ? '有' : '无',
                'pass'     => $pass,
                'optional' => true
            ]
        ];

        $f     = TMP_PATH;
        $rst   = ['tmp目录', CmfModule::checkFile($f)];
        $dir[] = $rst;

        if ($rst[1]['pass'] && !is_file(TMP_PATH . 'install.txt')) {
            $code = md5(rand_str(8));//写入安全码
            @file_put_contents(TMP_PATH . 'install.txt', $code);
        }

        $f     = LOGS_PATH;
        $dir[] = ['logs目录', CmfModule::checkFile($f)];
        $f     = CONFIG_PATH;
        $dir[] = ['conf目录', CmfModule::checkFile($f)];

        $step   = sess_get('step', 'home');
        $config = sess_get('stepData', []);

        return view(['requirements' => $checked, 'dirs' => $dir, 'step' => $step, 'data' => $config]);
    }

    /**
     * 验证安全码
     * @return array
     */
    public function verify() {
        $code     = rqst('code');
        $verified = 0;
        if ($code && $code == @file_get_contents(TMP_PATH . 'install.txt')) {
            $verified             = 1;
            $_SESSION['verified'] = 1;
            $msg                  = '';
        } else {
            $msg = '安全码不正确';
        }

        return ['status' => $verified, 'msg' => $msg, 'step' => 'verify'];
    }

    /**
     * 环境与语言选项
     *
     * @return array
     */
    public function setup() {
        $step = rqst('step');

        $verified = sess_get('verified', 0);
        if (!$verified) {
            return ['status' => 0, 'step' => 'verify'];
        }
        $_SESSION['step'] = $step;
        $cfg              = rqst('cfg');

        if ($step == 'db') {
            $dbcfg = [
                'host'     => $cfg['host'] ? $cfg['host'] : 'localhost',
                'port'     => $cfg['port'] ? $cfg['port'] : 3306,
                'user'     => $cfg['dbusername'],
                'password' => $cfg['dbpwd'],
                'encoding' => 'UTF8MB4'
            ];

            try {
                $db = App::db($dbcfg);
                if ($db == null) {
                    throw new \Exception('无法连接数据库');
                }
            } catch (\Exception $e) {
                return ['status' => 0, 'msg' => $e->getMessage(), 'step' => 'db'];
            }
        } else if ($step == 'user') {
            $name = strtolower($cfg['name']);
            if (!$name) {
                return ['status' => 0, 'msg' => '管理员账号不能为空', 'step' => 'user'];
            } else if ($name == 'admin') {
                return ['status' => 0, 'msg' => '请不要使用admin做为管理员账号', 'step' => 'user'];
            }
            $pwd  = $cfg['pwd'];
            $pwd1 = $cfg['confirm_pwd'];

            if (!$pwd || $pwd != $pwd1) {
                return ['status' => 0, 'msg' => '两次输入的密码不一致', 'step' => 'user'];
            }
        }

        $config               = sess_get('stepData', []);
        $config[ $step ]      = $cfg;
        $_SESSION['stepData'] = $config;

        return ['status' => 1];
    }

    /**
     * 安装
     * @nobuffer
     * @return string
     */
    public function install() {
        header('Content-Type: text/octet-stream');
        set_time_limit(0);
        $verified = sess_get('verified', 0);
        if (!$verified) {
            return json_encode(['status' => 0, 'step' => 'verify']);
        }
        $config = sess_get('stepData', []);
        if (!$config) {
            return json_encode(['status' => 0, 'step' => 'home']);
        }
        // 创建数据库
        $rtn = ['status' => 1, 'tip' => '创建数据库', 'step' => 'db', 'percent' => 5, 'done' => 0];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        $rtn['status']  = $this->setupDb($msg);
        $rtn['done']    = 1;
        $rtn['percent'] = 10;
        if ($msg) {
            $rtn['msg'] = $msg;
        }
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        // 创建数据库结束
        // 安装模块
        $modules = ['system', 'backend'];
        if (is_file(CONFIG_PATH . 'install_config.php')) {
            $siteConfig = include CONFIG_PATH . 'install_config.php';
            if (isset($siteConfig['modules'])) {
                $modules = array_merge($modules, (array)$siteConfig['modules']);
            }
        }
        $pp = 10;
        $dp = 70 / count($modules);
        foreach ($modules as $i => $m) {
            $module = App::getModuleById($m);
            if ($module) {
                $this->installModule($m, $module, $pp, $dp);
            }
        }
        // 安装模块结束
        // 创建管理员
        $rtn = ['status' => 1, 'step' => 'user', 'tip' => '创建管理员', 'percent' => $pp + 5, 'done' => 0];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        $rtn['status']  = $this->createAdmin($msg);
        $rtn['done']    = 1;
        $rtn['percent'] = $pp + 10;
        if ($msg) {
            $rtn['msg'] = $msg;
        }
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        // 创建管理员结束
        // 保存配置
        $rtn = ['status' => 1, 'step' => 'cfg', 'tip' => '保存配置', 'percent' => $pp + 15, 'done' => 0];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        $rtn['status']  = $this->saveConf($msg);
        $rtn['done']    = 1;
        $rtn['percent'] = 98;
        if ($msg) {
            $rtn['msg'] = $msg;
        }
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();

        return json_encode([
            'status'  => 1,
            'step'    => 'doen',
            'tip'     => '安装完成',
            'percent' => 100,
            'url'     => ['/', App::url('backend')],
            'done'    => 1
        ], JSON_UNESCAPED_UNICODE);
    }

    private function setupDb(&$msg = null) {
        $stepData = sess_get('stepData', []);
        $cfg      = $stepData['db'];
        $dbcfg    = [
            'host'     => $cfg['host'] ? $cfg['host'] : 'localhost',
            'port'     => $cfg['port'] ? $cfg['port'] : 3306,
            'user'     => $cfg['dbusername'],
            'password' => $cfg['dbpwd'],
            'encoding' => 'UTF8MB4'
        ];
        $dbname   = $cfg['dbname'];

        try {
            $db      = App::db($dbcfg);
            $dialect = $db->getDialect();
            $dbs     = $dialect->listDatabases();
            $rst     = in_array($dbname, $dbs);
            if (!$rst) {
                $rst = $dialect->createDatabase($dbname, $dbcfg['encoding']);
            }
            if (!$rst) {
                $msg = '无法创建数据库';

                return 0;
            }
        } catch (\Exception $e) {
        }

        return 1;
    }

    private function createAdmin(&$msg = null) {
        $msg              = '';
        $stepData         = sess_get('stepData', []);
        $admin            = $stepData['user'];
        $username         = $admin['name'];
        $password         = $admin['pwd'];
        $user['id']       = 1;
        $user['username'] = $username;
        $user['nickname'] = '超级管理员';
        $user['hash']     = Passport::passwd($password);

        try {
            $db = $this->getDb();
            $db->start();
            if ($db->insert($user)->into('user')->exec() && $db->insert([
                    ['user_id' => 1, 'role_id' => 1],
                    ['user_id' => 1, 'role_id' => 2]
                ], true)->into('{user_role}')->exec()) {
                $db->commit();
            } else {
                throw_exception('cannot create admin');
            }
        } catch (\Exception $e) {
            $msg = '无法创建管理员:' . $e->getMessage();

            return 0;
        }

        return 1;
    }

    private function installModule(string $module, Module $m, &$percent, $dp) {
        $rtn = [
            'status'  => 1,
            'step'    => 'module-' . $module,
            'tip'     => '安装"' . $m->getName() . '"',
            'percent' => $percent,
            'done'    => 0
        ];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        try {
            $db      = $this->getDb();
            $dialect = $db->getDialect();
            if (!$m->install($db, 1)) {
                $sqls = $m->getDefinedTables($dialect);
                if ($sqls['tables']) {
                    foreach ($sqls['tables'] as $t) {
                        $db->exec('drop table if exists ' . $t);
                    }
                }
                if ($sqls['views']) {
                    foreach ($sqls['views'] as $t) {
                        $db->exec('drop view if exists ' . $t);
                    }
                }
                throw_exception('安装失败');
            }
        } catch (\Exception $e) {
            $rtn['status'] = 0;
            $rtn['msg']    = '安装模块"' . $m->getName() . '"失败';
        }
        $percent        += $dp;
        $rtn['done']    = 1;
        $rtn['percent'] = $percent;
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
    }

    private function saveConf(&$msg = null) {
        $msg       = '';
        $setupData = sess_get('stepData', []);
        $cfg       = $setupData['config'];
        $user      = $setupData['user'];
        $dbcfg     = $setupData['db'];
        $app_mode  = $cfg['config'];
        $dashboard = $user['url'] && $user['url'] != 'backend' ? $user['url'] : '';

        $cfg = CONFIG_PATH . 'install_config.php';
        if (is_file($cfg)) {
            $config         = @file_get_contents($cfg);
            $r["'{alias}'"] = $dashboard ? "['dashboard' => '$dashboard']" : '';
            $config         = str_replace(array_keys($r), array_values($r), $config);
        } else {
            if ($dashboard) {
                $alias = "'alias' => ['dashboard'=>'$dashboard'],";
            }
            $config = <<<CFG
<?php

return [
    'debug'     => env('debug', DEBUG_WARN),
    'resource'  => [
        'combinate' => env('resource.combinate', 0),
        'minify'    => env('resource.minify', 0)
    ],
    {$alias}
];
CFG;
        }

        if (!@file_put_contents(CONFIG_PATH . 'config.php', $config)) {
            $msg = '无法保存配置文件';

            return 0;
        }
        $dbconfig           = @file_get_contents(APPROOT . 'vendor' . '/wula/cms-support/tpl/dbconfig.php');
        $r['{db.host}']     = $dbcfg['host'] ? $dbcfg['host'] : 'localhost';
        $r['{db.port}']     = $dbcfg['port'] ? $dbcfg['port'] : 3306;
        $r['{db.name}']     = $dbcfg['dbname'];
        $r['{db.charset}']  = 'UTF8MB4';
        $r['{db.user}']     = $dbcfg['dbusername'];
        $r['{db.password}'] = $dbcfg['dbpwd'];
        $dbconfig           = str_replace(array_keys($r), array_values($r), $dbconfig);
        if (!@file_put_contents(CONFIG_PATH . 'dbconfig.php', $dbconfig)) {
            @unlink(CONFIG_PATH . 'config.php');
            $msg = '无法保存数据库配置文件';

            return 0;
        }
        if ($app_mode == 'dev') {
            $dcf[] = '[app]';
            $dcf[] = 'debug = DEBUG_DEBUG';
            $dcf[] = 'dashboard = ' . $dashboard;
            $dcf[] = '[db]';
            $dcf[] = 'db.host = ' . $r['{db.host}'];
            $dcf[] = 'db.port = ' . $r['{db.port}'];
            $dcf[] = 'db.name = ' . $r['{db.name}'];
            $dcf[] = 'db.user = ' . $r['{db.user}'];
            $dcf[] = 'db.password = ' . $r['{db.password}'];
            $dcf[] = 'db.charset = ' . $r['{db.charset}'];
            @file_put_contents(CONFIG_PATH . '.env', implode("\n", $dcf));
        }

        if (!@file_put_contents(CONFIG_PATH . 'install.lock', time())) {
            @unlink(CONFIG_PATH . 'config.php');
            @unlink(CONFIG_PATH . 'dbconfig.php');
            @unlink(CONFIG_PATH . '.env');
            $this->clearDb();
            $data['msg'] = '无法保存锁定文件';
        } else {
            @unlink(TMP_PATH . 'install.txt');
        }

        return 1;
    }

    /**
     * @return \wulaphp\db\DatabaseConnection
     * @throws \Exception
     */
    private function getDb() {
        $stepData = sess_get('stepData', []);
        $cfg      = $stepData['db'];
        $dbcfg    = [
            'host'     => $cfg['host'] ? $cfg['host'] : 'localhost',
            'port'     => $cfg['port'] ? $cfg['port'] : 3306,
            'user'     => $cfg['dbusername'],
            'password' => $cfg['dbpwd'],
            'encoding' => 'UTF8MB4',
            'dbname'   => $cfg['dbname']
        ];

        return App::db($dbcfg);
    }

    private function clearDb() {
        $modules = ['system', 'backend'];
        if (is_file(CONFIG_PATH . 'install_config.php')) {
            $siteConfig = include CONFIG_PATH . 'install_config.php';
            if (isset($siteConfig['modules'])) {
                $modules = array_merge($modules, (array)$siteConfig['modules']);
            }
        }
        try {
            $db      = $this->getDb();
            $dialect = $db->getDialect();
            foreach ($modules as $i => $m) {
                $module = App::getModuleById($m);
                if ($module) {
                    $sqls = $module->getDefinedTables($dialect);
                    if ($sqls['tables']) {
                        foreach ($sqls['tables'] as $t) {
                            $db->exec('drop table if exists ' . $t);
                        }
                    }
                    if ($sqls['views']) {
                        foreach ($sqls['views'] as $t) {
                            $db->exec('drop view if exists ' . $t);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }
}