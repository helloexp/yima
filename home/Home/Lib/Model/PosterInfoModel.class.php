<?php

/**
 * 新版海报相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class PosterInfoModel extends BaseModel {

    protected $tableName = 'tposter_info';

    public function getPosterInfo($where) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE);
    }

    public function getPosterInfoField($where, $field) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_FIELD, $field);
    }
}