<?php


namespace yiqiniu\facade;

use think\Facade;
/**
 * @see yiqiniu\library\Arrays
 * @method mixed removeEmpty( $arr, $trim = '1') static     从数组中删除空白的元素（包括只有空白字符的元素）
 * @method mixed removeKey( $array, $keys) static     去掉指定的项
 * @method mixed toTree( $arr, $key_node_id, $key_parent_id = 'parent_id', $key_childrens = 'children', $treeIndex =  '', $refs =  '') static     将一个平面的二维数组按照指定的字段转换为树状结构
 * @method mixed toHashmap( $arr, $key_field, $value_field) static
 * @method mixed toString( $array, $comma = ',', $find =  '') static     将数组用分隔符连接并输出
 * @method mixed getCols( $arr, $col) static     从一个二维数组中返回指定键的所有值
 * @method mixed implode( $arr, $glue = ',', $key = 'id', $field = 'id') static     数组转换为字符串
 * @method mixed groupBy( $arr, $key_field) static     根据字段分组
 * @method mixed treeToArray( $tree, $key_childrens = 'childrens') static     将树转换为数组
 * @method mixed sortByMultiCols( $rowset, $args) static     @desc    将一个二维数组按照多个列进行排序，类似 SQL 语句中的 ORDER BY<br/>
 * @method mixed sortByCol( $array, $keyname, $dir = '4') static     根据指定的键对数组进行排序
 * @method mixed getChildren( $array, $parent_id) static     获得无限分类的所有孩子
 * @method mixed getSiblings( $array, $self) static     获得无限分类的所有同辈兄弟姐妹
 * @method mixed getDescendants( $tree, $key_node_id = 'id', $key_childrens = 'children', $self =  '') static     获取后代的id 返回id的数组
 * @method mixed toSQL( $array, $key) static     将数组转换成SQL语句
 * @method mixed find( $array, $ref, $value = 'id') static     从二维数组中查找结果
 * @method mixed replace( $array, $arr) static     替换数组中的某个值
 * @method mixed fill( $array, $string, $pos = 'left') static     将数组中的每个元素的头或尾填充字符串
 * @method mixed array_extend( $arr, $name = 'attr') static     把数组中某个值展开,合成为一个数组,主要用于扩展的


 */
class Arrays extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'yiqiniu\library\Arrays';
    }
}