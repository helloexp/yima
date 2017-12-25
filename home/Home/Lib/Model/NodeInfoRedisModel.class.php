<?php

/**
 * 商户相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2016-04-13
 */
class NodeInfoRedisModel extends RedisDBBaseModel
{
    protected $tableName = 'tnode_info';
    protected $_pk = 'node_id';
    protected $_sk = null;

    public function getNodeInfo($where)
    {
        return $this->getData(
                $this->tableName,
                $where,
                BaseModel::SELECT_TYPE_ONE
        );
    }

    /**
     *
     * @param $where
     *
     * @return mixed
     */
    public function getNodeName($where)
    {
        return $this->getData(
                $this->tableName,
                $where,
                BaseModel::SELECT_TYPE_FIELD,
                'node_name'
        );
    }

    public function getChannelField($where, $field)
    {
        return $this->getData(
                $this->tableName,
                $where,
                BaseModel::SELECT_TYPE_FIELD,
                $field
        );
    }
}