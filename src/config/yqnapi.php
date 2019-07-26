<?php

$api_config = [

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
    ],
];

/**
 *  开启刷新token功能
 */
\think\facade\Route::post('api/refresh_token', function () {
    api_refresh_token();
});


return $api_config;