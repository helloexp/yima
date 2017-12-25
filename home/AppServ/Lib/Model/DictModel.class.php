<?php

/* 字典信息转义 */

class DictModel {
	protected $tableName = '__NONE__';
    private $batch_class = array(
            '0' => '优惠券',
            '1' => '代金券',
            '2' => '实物券',
            '3' => '储值卡',
    );

    private $batch_status = array(
            '0' => '正常',
            '1' => '停用',
            '2' => '过期',
            '3' => '停发',
    );

    /**
     * 获取活动类型
     */
    public function getBatchClass($batch_key = '') {
        return $this->getDictValue($this->batch_class, $batch_key);
    }

    /**
     * 获取活动状态
     */
    public function getBatchStatus($status = '') {
        return $this->getDictValue($this->batch_status, $status);
    }

    // 根据字典键获取值
    public function getDictValue($dict_array, $key) {
        if ($key == '') {
            return $dict_array;
        }

        return isset($dict_array[$key]) ? $dict_array[$key] : '';
    }
}

?>