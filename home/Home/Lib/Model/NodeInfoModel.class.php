<?php

/**
 * 商户相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class NodeInfoModel extends BaseModel {

    protected $tableName = 'tnode_info';

    public function getNodeInfo($where) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE);
    }

    /**
     *
     * @param $where
     * @return mixed
     */
    public function getNodeName($where) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_FIELD, 'node_name');
    }

    public function getChannelField($where, $field) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_FIELD, $field);
    }
}