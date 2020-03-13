<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>安装</title>
    <link rel="stylesheet" href="{'backend/css/layui.css'|res}">
    <link rel="stylesheet" href="{'backend/css/install.css'|res}">
    <script type="text/javascript" src="{'backend/layui.js'|res}"></script>
    <script type="text/javascript" src="{'backend/vue.min.js'|res}"></script>
</head>
<body style="background: #2F4056;">
{literal}
    <div id="install" v-cloak>
        <div class="install_body ">
            <!-- 安装步骤 -->
            <div class="install_left">
                <p>wulacms <span class="layui-badge layui-bg-blue">v3</span></p>
                <ul class="install_left__steps">
                    <li v-for="(item,i) in step" :class="[current==item.name?'checked':'']">{{item.title}}</li>
                </ul>
            </div>

            <div class="install_right">
                <!-- 环境检测 -->
                <div class="layui-form install_right__verify" v-show="current=='home'">
                    <p class="title">环境检测</p>
                    <p class="info-title">
                        <span>检验项目</span>
                        <span>检验要求</span>
                        <span>实际状态</span>
                        <span>是否通过</span>
                    </p>
                    <p v-for="item in requirements" class="info">
                        <span>{{item[0]}}</span>
                        <span>{{item[1].required}}</span>
                        <span>{{item[1].checked}}</span>
                        <span>
                            <i class="layui-icon layui-icon-ok" v-if="(item[1].optional|item[1].pass)" style="color:#4caf50"></i>
                            <i class="layui-icon layui-icon-close" v-else style="color:#ff9800"></i>
                        </span>
                    </p>
                    <hr class="layui-bg-gray">
                    <p class="title">目录检测</p>
                    <p v-for="item in dirs" class="info">
                        <span>{{item[0]}}</span>
                        <span>{{item[1].required}}</span>
                        <span>{{item[1].checked}}</span>
                        <span>
                            <i class="layui-icon layui-icon-ok" v-if="(item[1].optional|item[1].pass)" style="color:#4caf50"></i>
                            <i class="layui-icon layui-icon-close" v-else style="color:#ff9800"></i>
                        </span>
                    </p>
                    <button :class="{'layui-btn-disabled':verifyNext()==0}" class="layui-btn layui-btn-primary install_right__next" @click="verifyNext()==1?go('next'):console.log('无法继续')">
                        继续
                    </button>
                </div>
                <!-- 安全码验证 -->
                <div class="layui-form code" v-show="current=='verify'">
                    <p class="title">安全码验证</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <span style="color:#999">安全码在 <em style="color:#FF5722">storage/tmp/install.txt</em>&nbsp;文件中</span>
                    <input type="text" placeholder="请输入安全码" class="layui-input" v-model="verify.code">
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="setup('verify')">
                        下一步
                    </button>
                    <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>

                <!-- 配置选择 -->
                <div v-show="current=='config'">
                    <p class="title">运行模式</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <p>
                        <input type="radio" name="config" value="pro" v-model="config.config">
                        <label for="">正式:线上部署</label>
                    </p>
                    <p>
                        <input type="radio" name="config" value="dev" v-model="config.config">
                        <label for="">测试:测试环境</label>
                    </p>
                    <p>
                        <input type="radio" name="config" value="test" v-model="config.config">
                        <label for="">开发:本地开发环境</label>
                    </p>
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="setup('config')">
                        下一步
                    </button>
                    <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>
                <!-- 数据库配置 -->
                <div class="layui-form database" v-show="current=='db'">
                    <p class="title">MySQL数据库配置</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <p>&nbsp;
                    <p>
                        <input type="text" placeholder="数据库名称" class="layui-input" v-model="db.dbname">
                        <input type="text" placeholder="用户名" class="layui-input" v-model="db.dbusername">
                        <input type="text" placeholder="密码" class="layui-input" v-model="db.dbpwd">
                        <input type="text" placeholder="Host(默认localhost)" class="layui-input" v-model="db.host">
                        <input type="text" placeholder="Port(默认3306)" class="layui-input" v-model="db.port">
                        <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                        <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="setup('db')">
                            下一步
                        </button>
                        <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>
                <!-- 用户创建 -->
                <div class="layui-form database" v-show="current=='user'">
                    <p class="title">管理员创建</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <input type="text" placeholder="管理员账号" v-model="user.name" class="layui-input">
                    <input type="text" placeholder="管理员密码" v-model="user.pwd" class="layui-input">
                    <input type="text" placeholder="确认管理员密码" v-model="user.confirm_pwd" class="layui-input">
                    <input type="text" placeholder="管理面板路径" v-model="user.url" class="layui-input">
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="user.confirm_pwd==user.pwd?setup('user'):tips='两次密码输入不一致'">
                        下一步
                    </button>
                    <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>
                <!-- 安装 -->
                <div class="layui-form progress" v-show="current=='install'">
                    <p class="title">安装</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <h1>{{install_progress==100?'finish':'installing...'}}</h1>
                    <div class=" layui-progress layui-progress-big" lay-filter="install-progress">
                        <div class="layui-progress-bar" lay-percent="0%">{{install_progress}}%</div>
                    </div>
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" @click="doInstall" v-show="install_progress==0">
                        安装
                    </button>
                    <button v-show="install_progress>0" :class="{'layui-btn-disabled':install_progress < 100}" class="layui-btn layui-btn-primary install_right__next" @click="install_progress<100?console.log('aa'):go('next')">
                        {{install_progress < 100 ? '安装' : '完成 '}}
                    </button>
                </div>
                <!-- 完成 -->
                <div class="layui-form progress" v-show="current=='finfish'">
                    <p class="title" style="text-align: center">完成</p>
                    <a class="layui-btn layui-btn-primary install_right__next">进入wulacms</a>
                </div>
            </div>
        </div>
    </div>
{/literal}
<script type="text/javascript">
    window.vueData = {
        step        : '{$step}',
        requirements: {$requirements|json_encode},
        dirs        : {$dirs|json_encode},
        data        : {$data|json_encode}
    };

    layui.config({
        base: "{'layui'|assets}"
    });

    layui.use(['layer', 'element', 'form', '&install'], function () {
        var form = layui.form;
        form.render();
    })
</script>
</body>
</html>