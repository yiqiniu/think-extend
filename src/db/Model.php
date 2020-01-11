<?php


namespace yiqiniu\db;


/**
 * Class Model
 * @package yiqiniu\db
 * @mixin Query
 */
class Model extends \think\Model
{

    protected $jsonAssoc = true;

    public function return_page($page_list, $list = null)
    {
        $list = is_null($list) ? $page_list->items() : $list;
        if ($this->page_simple) {
            $r_arr = ['hasmore' => $page_list->hasMore, 'list' => $list];
        } else {
            $r_arr = ['total' => $page_list->total, 'current_page' => $page_list->currentPage,
                'last_page' => $page_list->lastPage, 'list_rows' => $page_list->listRows,
                'render' => $page_list->render(), 'list' => $list
            ];
        }
        return $r_arr;
    }

    /**
     * 添加修改
     * @param $data
     * @return int|string
     * @throws Exception
     */
    public function addEdit($data)
    {
        try {
            $id = empty($data[$this->pk]) ? '' : $data[$this->pk];
            if (empty($id))
                $this->where([$this->pk => $id])->update($data);
            else
                $id = $this->insert($data);
            return $id;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 删除数据
     * @param $id
     * @throws Exception
     */
    public function del($id)
    {
        try {
            $this->where([$this->pk => $id])->delete();
        } catch (Exception $e) {
            throw $e;
        }
    }
}