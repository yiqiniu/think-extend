<?php


namespace yiqiniu\extend\db\traits;


/**
 * Model 扩展函数
 * Trait ModelTraits
 * @package yiqiniu\extend\traits
 */
trait ModelExtendTraits
{

    /**
     * 执行存储过程
     * @param       $sql string       要执行sql
     * @param mixed ...$argv 参数
     * @return mixed            返回执行的结果
     * @throws Exception
     */
    public function exec_procedure($sql, ...$argv)
    {
        try {
            $sql = vsprintf($sql, $argv);
            // 取返回值变量
            $pos = strpos($sql, '@');
            $param = '';
            if ($pos !== false) {
                $param = substr($sql, $pos, strpos($sql, ')') - strlen($sql));
            }
            $bret = $this->db()->execute($sql);
            if ($bret !== false && !empty($param)) {
                $bret = $this->db()->query('select ' . $param)[0];
            }
            return $bret;
        } catch (Exception $e) {
            throw $e;
        }
    }
}