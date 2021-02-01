<?php

namespace yiqiniu\extend\db\contract;


/**
 * 查询事件
 * Interface SelectEvent
 * @package app\contract
 */
interface SelectEvent
{
    /**
     * 查询后事件
     * @param array $data
     */
    public function select_after(array &$data): void;

}