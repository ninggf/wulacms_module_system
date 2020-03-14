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
        $rtn = ['status' => 1, 'tip' => '创建数据库', 'step' => 'db', 'percent' => 5, 'done' => 0];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        //TODO: 创建数据库
        sleep(2);
        $rtn['done']    = 1;
        $rtn['percent'] = 10;
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();

        sleep(1);
        $rtn = ['status' => 1, 'step' => 'user', 'tip' => '创建管理员', 'percent' => 15, 'done' => 0];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        // TODO: 创建管理员
        sleep(2);
        $rtn['done']    = 1;
        $rtn['percent'] = 18;
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();

        sleep(1);
        $this->installModule('system', 20);

        sleep(1);

        return json_encode([
            'status'  => 1,
            'step'    => 'doen',
            'tip'     => '安装完成',
            'percent' => 100,
            'done'    => 1
        ], JSON_UNESCAPED_UNICODE);
    }

    private function installModule($module, $percent) {
        $m   = App::getModuleById($module);
        $rtn = [
            'status'  => 1,
            'step'    => 'module-' . $module,
            'tip'     => $m->getName(),
            'percent' => $percent,
            'done'    => 0
        ];
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
        // TODO: 安装模块
        sleep(2);
        $rtn['done']    = 1;
        $rtn['percent'] = $percent + 5;
        echo json_encode($rtn, JSON_UNESCAPED_UNICODE);
        flush();
    }
}