<?php

namespace yiqiniu\extend\contract;


/**
 * 删除事件
 * Interface DeleteEvent
 * @package app\contract
 */
interface DeleteEvent
{
    /**
     * 删除前事件，有错误可以直接抛出
     * @param array $where
     * @return void
     */
    public function delete_before(array &$where): void;

    /**
     * 删除后事件
     * @param array $where
     * @param int $result
     */
    public function delete_after(array $where, int $result): void;

}