<?php


namespace yiqiniu\facade;

use think\Facade;
/**
 * @see yiqiniu\library\Token
 * @method mixed getToken( $data, $is_exp = '1', $time = '86400') static     @param $data 加密的数据
 * @method mixed verifyToken( $jwt, $app) static     验证签名
 * @method mixed verificationOther( $jwt, $data) static     @param $jwt


 */
class Token extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'yiqiniu\library\Token';
    }
}