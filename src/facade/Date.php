<?php


namespace yiqiniu\facade;

use think\Facade;

/**
 * @see yiqiniu\library\Date
 * @method mixed parse( $date) static     日期分析
 * @method mixed set( $date) static     日期参数设置
 * @method mixed format($type = 0) static 日期格式化
 * @method mixed valid( $date) static     验证日期数据是否有效
 * @method mixed isLeapYear( $year) static     是否为闰年
 * @method mixed between( $sdate, $edate) static     计算日期差
 * @method mixed Diffc( $time, $precision) static     人性化的计算日期差
 * @method mixed firstDayOfWeek() static     计算周的第一天 返回Date对象
 * @method mixed getDayOfWeek( $n) static     返回周的某一天 返回Date对象
 * @method mixed firstDayOfMonth() static     计算月份的第一天 返回Date对象
 * @method mixed firstDayOfYear() static     计算年份的第一天 返回Date对象
 * @method mixed lastDayOfWeek() static     计算周的最后一天 返回Date对象
 * @method mixed lastDayOfMonth() static     计算月份的最后一天 返回Date对象
 * @method mixed lastDayOfYear() static     计算年份的最后一天 返回Date对象
 * @method mixed maxDayOfMonth() static     计算月份的最大天数
 * @method mixed Diff( $date, $elaps = 'd') static     计算日期差
 * @method mixed Add( $number, $interval = 'd') static     取得指定间隔日期
 * @method mixed numberToCh( $number) static     日期数字转中文
 * @method mixed yearToCh( $yearStr, $flag) static     年份数字转中文
 * @method mixed magicInfo( $type) static      判断日期 所属 干支 生肖 星座
 * @method mixed dateAdd( $date, $day, $format = 'Y-m-d') static     日期加减操作
 */
class Date extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'yiqiniu\library\Date';
    }
}