<?php

/**
 * 积分 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2016/01/12
 */
class IntegalGetDetailModel extends BaseModel {

    protected $tableName = 'tintegal_get_detail';

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param int $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getIntegalGetDetail($where, 
        $selectType = BaseModel::SELECT_TYPE_ALL, $field = '') {
        return $this->getData($this->tableName, $where, $selectType, $field);
    }

    /**
     *
     * @param $where
     * @param $phone
     * @return bool
     */
    public function receiveIntegal($where, $phone) {
        return $this->table($this->tableName)->where($where)->save(
            array(
                'phone' => $phone, 
                'status' => 1));
    }
}