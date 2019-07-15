<?php
/**
 * Created by PhpStorm.
 * User: 13271
 * Date: 2016/2/17
 * Time: 14:37
 */

namespace yiqiniu\traits\model;

use yiqiniu\library\Str;
use think\Exception;

trait Treelist
{
    private static $treeList = [];

    /**
     * 添加修改
     * @param $request
     * @throws Exception
     */
    public function add_edit($request)
    {
        $data = $request->post();
        try {
            $this->startTrans();
            $up_ids = [];
            if ($data["id"] > 0) {
                $odt = $this->get_info($data["id"]);
                if (empty($odt))
                    exception(400, '该分类不存在或已被管理员删除！请返回刷新后重新操作！');
                if ($odt["id"] == $data["pid"])
                    exception(400, '所属分类不能指定自己！');
                if ($odt["pid"] != $data["pid"]) {
                    $npdt = null;
                    $opdt = null;
                    if ($data["pid"] > 0) {
                        $npdt = $this->get_info($data["pid"]);
                        if (empty($npdt))
                            exception(400, '所属分类不存在或已被管理员删除！请返回刷新后重新操作！');
                        if (strpos("," . $npdt["path"] . ",", "," . $odt["id"] . ",") !== false)
                            exception(400, '您不能指定该分类的下级分类作为所属分类！');
                        $data["depth"] = $npdt["depth"] + 1;
                        $data["path"] = $npdt["path"] ? $npdt["path"] . "," . $npdt["id"] : $npdt["id"];
                    } else {
                        $data["depth"] = null;
                        $data["path"] = null;
                    }
                    if ($odt["pid"] > 0) {
                        $opdt = $this->get_info($odt["pid"]);
                        if (empty($opdt))
                            exception(400, "原所属分类不存在或已被管理员删除！请返回刷新后重新操作！");
                    }
                    if ($odt["child"] > 0) {
                        $query = "CONCAT(',',path,',') like '," . ($odt["path"] ? $odt["path"] . "," . $odt["id"] : $odt["id"]) . ",%";
                        $up_ids = $this->where($query)->column('id');
                        $this->where($query)->inc('depth', $data["depth"] - $odt["depth"])->update([
                            "path" => $this->raw("CONCAT('" . (!empty($data["path"]) ? $data["path"] . "," : "") . "',substring(path," . ($odt["path"] ? ((string)strlen($odt["path"]) + 2) : "1") . "))")]);
                    }
                    if (!isset($data["orderid"]))
                        $data["orderid"] = $this->where(["pid" => $data["pid"]])->max("orderid") + 1;
                    if (!empty($npdt)) {
                        $up_ids[] = $npdt["id"];
                        $this->where(["id" => $npdt["id"]])->setInc("child");
                    }
                    if (!empty($opdt)) {
                        $up_ids[] = $opdt["id"];
                        $this->where(["id" => $opdt["id"]])->setDec("child");
                    }
                }
                $this->where(['id' => $data['id']])->update($data);
                $up_ids[] = $data['id'];
            } else {
                $pdt = $this->get_info($data['pid']);
                if (!empty($pdt)) {
                    $this->where(["id" => $pdt["id"]])->setInc("child");
                    $up_ids[] = $pdt['id'];
                    $data["depth"] = $pdt["depth"] + 1;
                    $data["path"] = $pdt["path"] ? $pdt["path"] . "," . $pdt["id"] : $pdt["id"];
                }
                if (!isset($data["orderid"]))
                    $data["orderid"] = $this->where(["pid" => $data["pid"]])->max("orderid") + 1;

                $id = $this->insert($data, false, true);
                $up_ids[] = $id;
            }
            $this->redis_hset('list', $this->where('id', $up_ids)->column('*'));
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取所有列表
     * @return mixed
     */
    public function get_list()
    {
        if (isset(self::$treeList[$this->name]))
            return self::$treeList[$this->name];
        $ca = $this->redisAll();
        array_multisort(array_column($ca, 'depth'), SORT_ASC, array_column($ca, 'orderid'), SORT_DESC, $ca);
        self::$treeList[$this->name] = $ca;
        return $ca;
    }

    /**
     * 删除信息
     * @param $id
     */
    public function del($id)
    {

        $id = $id ?: $this->request->post('id');
        $info = $this->redisInfo($id);
        if ($info['pid'] > 0) {
            $pinfo = $this->redisInfo($id);
            $pinfo['child'] -= 1;
            $this->redisSave([$pinfo['id'] => $pinfo]);
        }
        $this->redisDel($id);
    }

    /**
     * 通过层级获取列表集合
     * @param $depth
     * @return array
     */
    public function get_list_bydepth($depth)
    {
        $list = $this->get_list();
        $data = [];
        foreach ($list as $row) {
            if ($row['depth'] == $depth)
                $data[$row['id']] = $row;
        }
        return $data;
    }

    /**
     * 获取所有小于该层集的列表集合
     * @param $depth
     * @return array
     */
    public function get_list_ltdepth($depth)
    {
        $list = $this->get_list();
        $data = [];
        foreach ($list as $row) {
            if ($row['depth'] < $depth)
                $data[$row['id']] = $row;
        }
        return $data;
    }

    /**
     * 获取子根集合
     * @param $pid
     * @return array
     */
    public function get_list_child($pid)
    {
        $list = $this->get_list();
        $data = [];
        foreach ($list as $row) {
            if ($row['pid'] == $pid)
                $data[$row['id']] = $row;
        }
        return $data;
    }

    /**
     * 获取所有子集合的ID集合
     * @param $pid
     * @return array
     * @throws Exception
     */
    public function get_list_childids($pid)
    {
        $info = $this->redisInfo($pid);

        $path = $info['path'] ? $info['path'] . ',' . $pid : $pid;
        $list = $this->get_list();
        $data[] = (int)$pid;
        foreach ($list as $row) {
            if (strpos(',' . $row['path'] . ',', ',' . $path . ',') !== false)
                $data[] = $row['id'];
        }
        return $data;
    }

    /**
     * 获取列表树信息
     * @param null $list
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int $root
     * @return array
     */
    public function get_tree($list = null, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0)
    {
        if (is_null($list))
            $list = $this->get_list();
        // 创建Tree
        $tree = [];
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = $list;
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    public function getDown($selectid = 0, $add = true, $addfield = [], $list = null)
    {
        $list = $this->getTree($list);
        $treelist = $this->getDownChild($list, $selectid, $add, $addfield);
        return $treelist;
    }

    private function getDownChild($list, $selectid = 0, $add = true, $addfield)
    {
        $treelist = "";
        $j = 1;
        $count = count($list);
        foreach ($list as $k => $v) {
            $text = $v["title"];
            $vac = $v["child"] > 0 && !$add ? 0 : $v["id"];
            $iconstr = "";

            $fieldstr = '';
            $addfield = is_array($addfield) ? $addfield : explode(',', $addfield);
            foreach ($addfield as $name) {
                $fieldstr .= ' data-' . $name . '="' . $v[$name] . '"';
            }
            for ($i = 0; $i < $v['depth']; $i++) {
                $iconstr .= ($j == $count && $v['depth'] == ($i + 1) ? "┗&nbsp;" : "┣&nbsp;");
            }
            if (!empty($v['path']))
                $text = $iconstr . $text;
            $treelist .= Str::format('<option value="{0}" {1} {2}>{3}</option>', $vac, ($v["id"] == $selectid ? 'selected="selected"' : ''), $fieldstr, $text);
            if (!empty($v['children']))
                $treelist .= $this->getDownChild($v['children'], $selectid, $add, $addfield);
            $j++;
        }
        return $treelist;
    }


}