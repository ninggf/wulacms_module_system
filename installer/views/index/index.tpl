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
    <div class="install " v-cloak>
        <div class="install_body ">
            <!-- 安装步骤 -->
            <div class="install_left">
                <p>wulacms</p>
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
                    <button class="layui-btn layui-btn-disabled layui-btn-primary install_right__pre">上一步</button>
                    <button :class="{'layui-btn-disabled':verifyNext()==0}" class="layui-btn layui-btn-primary install_right__next" @click="verifyNext()==1?go('next'):console.log('无法继续')">
                        继续
                    </button>
                </div>
                <!-- 安全码验证 -->
                <div class="layui-form code" v-show="current=='verify'">
                    <p class="title">安全码验证</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <input type="text" placeholder="请输入安全码" class="layui-input" v-model="data.code">
                    <span style="color:#999">安全码位于...........</span>
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="setup('verify')">下一步</button>
                    <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>

                <!-- 配置选择 -->
                <div v-show="current=='config'">
                    <p class="title">配置选择</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <p>
                        <input type="radio" name="config" value="pro" v-model="data.config">
                        <label for="">正式1</label>
                    </p>
                    <p>
                        <input type="radio" name="config" value="dev" v-model="data.config">
                        <label for="">开发2</label>
                    </p>
                    <p>
                        <input type="radio" name="config" value="test" v-model="data.config">
                        <label for="">测试3</label>
                    </p>
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="setup('config')">
                        下一步
                    </button>
                    <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>
                <!-- 数据库配置 -->
                <div class="layui-form database" v-show="current=='db'">
                    <p class="title">数据库配置</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <p>DB:{{data.db}}</p>
                    <input type="text" placeholder="Database name" class="layui-input" v-model="data.dbname">
                    <input type="text" placeholder="Database username" class="layui-input" v-model="data.dbusername">
                    <input type="text" placeholder="Database password" class="layui-input" v-model="data.dbpwd">
                    <input type="text" placeholder="host" class="layui-input" v-model="data.host">
                    <input type="text" placeholder="port" class="layui-input" v-model="data.port">
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="setup('db')">
                        下一步
                    </button>
                    <i class="install_loading layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" v-show="status==1"></i>
                </div>
                <!-- 用户创建 -->
                <div class="layui-form database" v-show="current=='user'">
                    <p class="title">用户创建</p>
                    <p class="tips layui-bg-orange">{{tips}}</p>
                    <input type="text" placeholder="username" v-model="data.username" class="layui-input">
                    <input type="text" placeholder="userpwd" v-model="data.userpwd" class="layui-input">
                    <input type="text" placeholder="confirm pwd" v-model="data.confirm_pwd" class="layui-input">
                    <button class="layui-btn layui-btn-primary install_right__pre" @click="go('pre')">上一步</button>
                    <button class="layui-btn layui-btn-primary install_right__next" v-show="status!=1" @click="data.confirm_pwd==data.userpwd?setup('user'):tips='两次密码输入不一致'">
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

<script>
    window.vueData = {
        step        : '{$step}',
        requirements: {$requirements|json_encode},
        dirs        : {$dirs|json_encode}
    };
    layui.config({
        devMode: "<!-- @if env='dev' -->1<!-- @endif -->",
        base   : "{'layui'|assets}"
    });
    layui.use(['layer', 'element', 'form', '&install'], function () {
        var form = layui.form;
        form.render();
    })
</script>

</body>
</html>