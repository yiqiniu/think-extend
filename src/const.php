<?php
// 处理成功
const API_SUCCESS = 200;
// 数据验证失败
const API_VAILD_EXCEPTION = 400;
const API_ERROR = 400;
// 登录超时
const API_TIMEOUT = 401;
// 服务器处理异常 / 客户端自定义处理
const API_EXCEPTION = 402;

// 访问拒绝
const API_ACCESS_DENIED = 403;
// 其他错误
const API_OTHER_ERROR = 0;

// 1分钟 = 3600秒

const MINUTES_SECOND = 60;
// 3分钟
const MINUTES_SECOND_3 = MINUTES_SECOND * 3;
// 5分钟
const MINUTES_SECOND_5 = MINUTES_SECOND * 5;
// 1小时 = 3600秒
const HOUR_SECOND = 3600;
// 1天 = 86400秒
const DAY_SECOND = HOUR_SECOND * 24;



// 用户登陆超时时间 秒  1天 = 86400秒
const TOKEN_AUTH_TIME = DAY_SECOND * 3;
// refresh_token 默认为10天
const REFRESH_TOKEN_TIMEOUT = DAY_SECOND * 10;

// 用户验证缓存时间 秒
const REFRESH_TIME = 60;
// 手机验证码绑在时间
const CACHE_MOBILECODE = 60;


// API 状态说明
const API_STATUS_TEXT = [
    API_OTHER_ERROR => '其它错误',
    API_SUCCESS => '请求成功',
    API_VAILD_EXCEPTION => '数据验证失败',
    API_EXCEPTION => '服务器处理异常',
    API_TIMEOUT => '登记超时',
];