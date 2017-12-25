<?php

/**
 * tools model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/08/28
 */
class ToolsModel extends BaseModel {
    protected $tableName = '__NONE__';
    /**
     * 获得机构信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $where
     * @param int $selectType
     * @param string $field
     * @param string $order
     *
     * @return mixed
     */
    public function getNodeInfo($where, 
        $selectType = BaseModel::SELECT_TYPE_FIELD, $field = '', $order = '') {
        return $this->getData('tnode_info', $where, $selectType, $field, $order);
    }
}