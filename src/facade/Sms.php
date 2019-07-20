<?php


namespace yiqiniu\facade;


use think\Facade;

/**
 * Class AliyunSms
 * @package yiqiniu\facade
 * @method checkVerity($mobile, $veritycode) static 检查验证码是否正确
 * @method sendVerityCode($mobile, $code) static 发送验证码
 * @method sendSms($mobile, $veritycode, $codeName = null) static 发送短信
 */
class Sms extends Facade
{

    protected static function getFacadeClass()
    {
        return 'yiqiniu\\extend\\AliyunSms';
    }


}