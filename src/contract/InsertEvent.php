<?php


namespace yiqiniu\extend\contract;

/**
 * 插入事件
 * Interface InsertEvent
 * @package app\contract
 */
interface InsertEvent
{
    /**
     * 插入前事件，有错误可以直接抛出
     * @param array $data 要插入的数据
     * @return void
     */
    public function insert_before(array &$data): void;

    /**
     * 插入后事件
     * @param array $data 要插入的数据
     * @param int $result
     */
    public function insert_after(array $data, int $result): void;

}