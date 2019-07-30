<?php


namespace yiqiniu\extend;


use Aliyun\Send;
use think\Exception;

class AliyunSms
{
    // 间隔时间 60秒
    CONST TIMEOUT = 60;

    //验证码前缀
    CONST PREFIX = 'veritycode_';

    /**
     * 检测手机验证码
     * @param string $mobile 手机号码
     * @param string $veritycode 验证码
     * @return bool
     */
    public function checkVerity($mobile, $veritycode)
    {

        $verityinfo = cache(self::PREFIX . $mobile);
        if (empty($verityinfo)) {
            return false;
        }
        if ($verityinfo['code'] == $veritycode) {
            cache(self::PREFIX . $mobile, null);
            return true;
        }
        return false;
    }

    /**
     * 生成手机验证码
     * @param $mobile
     * @param $code
     * @return bool
     * @throws Exception
     */
    public function sendVerityCode($mobile, $code)
    {


        $verityinfo = cache(self::PREFIX . $mobile);
        // 判断手机号码是否发送超时
        if (!empty($verityinfo) && (time() - $verityinfo['time']) < self::TIMEOUT) {
            api_exception(API_VAILD_EXCEPTION, '获取频繁,请稍候再试');
        }
        $veritycode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_BOTH);
        if ($this->sendSms($mobile, $veritycode, $code)) {
            return true;
        } else {
            return false;
        }
    }

    //

    /**
     * 发送验证码
     * @param $mobile   string  接收的手机号码
     * @param $veritycode
     * @param null $codeName
     * @return bool
     * @throws Exception
     */
    public function sendSms($mobile, $veritycode, $codeName = null)
    {
        try {
            if (empty($codeName)) {
                api_exception(400, '未指定发送模板');
            }

            $config = config('sms.');
            if (!isset($config['template'])) {
                api_exception(400, '未配置发送模板');
            }
            $codeName = isset($config['template'][$codeName]) ? $config['template'][$codeName] : null;
            if (empty($codeName)) {
                api_exception(400, '指定的发送模板未找到');
            }

            $send = new Send($config);
            $sendret = $send->sendSms($mobile, ['code' => $veritycode], $config['signname'], $codeName);
            if ($sendret) {
                $verityinfo['time'] = time();
                $verityinfo['code'] = $veritycode;
                cache(self::PREFIX . $mobile, $verityinfo, self::TIMEOUT);
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw  $e;
        }
    }

}