## 系统内核

提供账户管理，模块管理，任务管理，日志记录等核心功能.

## 配置

### 缓存配置

配置文件`conf/cache_config.php`

支持`redis`与`memcached`，具体参考配置文件里的注释.

### 集群配置

配置文件`conf/cluster_config.php`

基于`redis`集群配置,具体参考配置文件里的注释.

### redis配置

配置文件`conf/redis_config.php`

```php
return ['host'=>'localhost','port'=>6379,'db'=>0,'auth'=>'','timeout'=>5];
```

> 说明:
>
> * `host` 主机
> * `port` 端口
> * `db` 数据库
> * `auth` 密码
> * `timeout` 连接超时

## 勾子

1. 1.`system\logs`: 注册系统日志类型
    ```php
        bind('system\logs',array $types){
            $types['mylog'] = '我的日志';
            
            return $types;
        }
    ```
> 注册类型后就可以使用`\system\classes\Syslog`类记录日志并通过后台查看:
> ```php
>    Syslog::info('这是我的日志内容',1,'mylog');
> ```

2. 2.`system\registerTask`： 注册任务
    ```php
        bind('system\registerTask',array $tasks){
           'your\task\TaskClass' => '你的任务'
            return $tasks;
        }
    ``` 
> 关于任务请参见 `\system\classes\Task`类。

## 任务

通过`artisan service`来运行任务队列。

### 添加任务服务

任务队列会默认添加到`service`配置中,如果有需要可以在`service_config.php`进行修改:

```php
'taskq' => [
    'worker' => 5,
    'sleep'  => 10
]
```

根据需要调整`worker`和`sleep`的值

### 任务保留参数

1. `crontab`: 类linux的crontab配置
2. `repeatInterval`: 重复间隔，单位秒
