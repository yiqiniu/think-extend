<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://zjzit.cn>
// +----------------------------------------------------------------------

namespace yiqiniu\exception;



use think\Exception;

/**
 * Database相关异常处理类
 */
class ApiException extends Exception
{

    /**
     * ApiException constructor.
     * @access public
     * @param string $message
     * @param int $code
     * @param array $data
     */
    public function __construct($message, $code = 400, $data = [])
    {
        parent::__construct($message,$code);
        $this->setData('data', $data);
    }


}
