<?php

const API_SUCCESS = 200;
// 处理失败
const API_ERROR = 400;
// 登录超时
const API_TIMEOUT = 1001;
// 服务器处理异常
const API_EXCEPTION = 402;
// 数据验证失败
const API_VAILD_EXCEPTION = 401;
//const API_VAILD_EXCEPTION = 400;
// 其他错误
const API_OTHER_ERROR = 0;


// 1小时 = 3600秒
CONST HOUR_SECOND = 3600;

// 1天 = 86400秒
CONST DAY_SECOND = HOUR_SECOND * 24;


// 用户登陆超时时间 秒  1天 = 86400秒
const AUTH_TIME = DAY_SECOND * 10;
// 用户验证缓存时间 秒
const REFRESH_TIME = 60;
// 手机验证码绑在时间
const CACHE_MOBILECODE = 60;

// API 状态说明
CONST API_STATUS_TEXT = [
    API_OTHER_ERROR => '其它错误',
    API_SUCCESS => '请求成功',
    API_VAILD_EXCEPTION => '表单提交失败',
    API_EXCEPTION => '服务器处理异常',
    API_TIMEOUT => '登记超时',
    API_ERROR => '处理失败'
];