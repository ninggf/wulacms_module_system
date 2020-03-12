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
     * @param string $step
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
            //TODO: 验证数据库连接
        }

        $config               = sess_get('stepData', []);
        $config[ $step ]      = $cfg;
        $_SESSION['stepData'] = $config;

        return ['status' => 1];
    }

    /**
     * 安装
     * @return array
     */
    public function install() {
        $verified = sess_get('verified', 0);
        if (!$verified) {
            return ['status' => 0, 'step' => 'verify'];
        }
        $config = sess_get('stepData', []);
        if (!$config) {
            return ['status' => 0, 'step' => 'home'];
        }

        return ['status' => 1];
    }
}