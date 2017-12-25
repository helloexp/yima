<?php

/**
 * 海报草稿相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class DraftModel extends BaseModel {

    protected $tableName = 'tdraft';

    /**
     * 获得海报草稿信息
     *
     * @author Jeff Liu<liuwy@iamgeco.com.cn>
     * @param $where
     * @return mixed
     */
    public function getDraftInfo($where) {
        $draftInfo = $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE);
        return $draftInfo;
    }

    /**
     *
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getDraftField($where, $field) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_FIELD, $field);
    }
    //删除 delete方法 (不知道为什么先上的有delete方法 fck)
}