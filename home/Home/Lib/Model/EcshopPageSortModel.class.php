<?php
class EcshopPageSortModel extends Model {

    protected $tableName = 'tecshop_page_sort';

    public function getCount($nodeId) {
        $where = array(
            'node_id' => $nodeId
        );
        $count = $this->where($where)->count();
        return $count;
    }
    
    public function getSelect($nodeId, $limit, $order) {
        $where = array(
            'node_id' => $nodeId
        );
        return $this->where($where)->limit($limit)->order($order)->select();
    }
}