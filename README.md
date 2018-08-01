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
