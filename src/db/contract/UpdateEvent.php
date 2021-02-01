<?php


namespace yiqiniu\extend\db\contract;


/**
 * 更新事件
 * Interface UpdateEvent
 * @package app\contract
 */
interface UpdateEvent
{
    /**
     * 修改前事件，有错误可以直接抛出
     * @param $where
     * @param $data
     * @return void
     */
    public function update_before(array &$where, array &$data): void;

    /**
     * 修改后事件
     * @param array $where
     * @param array $data
     * @param int $result
     */
    public function update_after(array $where, array $data, int $result): void;

}