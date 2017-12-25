<?php

/**
 *
 * @author lwb Time 20150720
 */
class MarketingInfoModel extends Model {

    protected $tableName = 'tmarketing_info';

    /**
     * 检查对应的活动是不是该机构下的
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @return boolean
     */
    public function checkActivityNodeCorrect($nodeId, $mId) {
        $id = $this->where(
            array(
                'id' => $mId, 
                'node_id' => $nodeId))->getField('id');
        return $id ? true : false;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $condition
     * @param int $limit
     *
     * @return mixed
     */
    public function getList($condition, $limit = 10) {
        return $this->where($condition)
            ->limit($limit)
            ->select();
    }
}