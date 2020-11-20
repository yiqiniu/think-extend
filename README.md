# yiqiniu-extend for ThinkPHP6

## 安装

> composer require yiqiniu/think-extend

## 配置

> 配置文件位于 `config/yqnapi.php`

### 公共配置

```
 [
    //不需要认证的操作
    'no_auth' => [
        'module' => [

        ],
        // 不需要认证的操作
        'action' => [
            'users/login'
        ],
        // 不需要认证的控制器
        'controller' => [],

    ],
    // APP 类型
    'app' => [
        0 => 'android',
        1 => 'ios',
        2 => 'weixin',
        3 => 'pc',
        9 => '其它'
    ],

    'auth' => [
        'token_key' => '',
        //过期时间:0不过期,时间单位秒
        'expire' => 0,
    ],
]

```
使用jwt 认证
composer require firebase/php-jwt
```
