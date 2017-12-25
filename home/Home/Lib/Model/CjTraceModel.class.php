<?php

/**
 * 抽奖trace相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/11/17
 */
class CjTraceModel extends BaseModel {

    protected $tableName = 'tcj_trace';

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param int $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getCjTrace($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '') {
        return $this->getData('tcj_trace', $where, $selectType, $field);
    }

    /**
     * 获取所有的中奖记录列表
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $condition
     * @return mixed
     */
    public function getAwardList($condition) {
        $whereFormat = 't.mobile="%s" AND t.status=2 AND t.batch_id=%d and t.batch_type=%d';
        $mobile = isset($condition['mobile']) ? $condition['mobile'] : '';
        $batch_id = isset($condition['batch_id']) ? $condition['batch_id'] : '';
        $batch_type = isset($condition['batch_type']) ? $condition['batch_type'] : '';
        $where = sprintf($whereFormat, $mobile, $batch_id, $batch_type);
        
        $normalAwardList = M()->table('tcj_trace t')
            ->field(
            't.mobile,gi.goods_name,gi.node_id,gi.goods_type,gi.bonus_id,t.batch_id,bi.id as card_batch_id,
                bi.card_id,bi.batch_short_name,ti.link_url,ni.node_name,ti.bonus_end_time,ti.bonus_start_time,
                bud.bonus_num,bud.bonus_use_num,bud.use_time,wan.id as twx_assist_number_id,wan.status as wx_status,
                wan.assist_number')
            ->join('tbatch_info bi ON bi.id=t.b_id')
            ->join('tgoods_info gi ON bi.goods_id=gi.goods_id')
            ->join('tbonus_info ti ON ti.id=gi.bonus_id')
            ->join('tnode_info ni ON ni.node_id=t.node_id')
            ->join('tbonus_use_detail bud ON bud.request_id=t.request_id')
            ->join('twx_assist_number wan ON wan.request_id=t.request_id')
            ->where($where)
            ->select();
        return $normalAwardList;
    }

    /**
     * 获取所有的中奖记录列表
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $labelId
     * @return mixed
     */
    public function getAwardListByLabelId($labelId, $limit = 10) {
        $whereFormat = 't.label_id="%s" AND t.status=2';
        $where = sprintf($whereFormat, $labelId);
        $normalAwardList = M()->table('tcj_trace t')
            ->field(
            't.mobile,gi.goods_name,gi.node_id,gi.goods_type,gi.bonus_id,t.batch_id,bi.id as card_batch_id,
                bi.card_id,bi.batch_short_name,ti.link_url,ni.node_name,ti.bonus_end_time,ti.bonus_start_time,
                bud.bonus_num,bud.bonus_use_num,bud.use_time,wan.id as twx_assist_number_id,wan.status as wx_status,
                wan.assist_number')
            ->join('tbatch_info bi ON bi.id=t.b_id')
            ->join('tgoods_info gi ON bi.goods_id=gi.goods_id')
            ->join('tbonus_info ti ON ti.id=gi.bonus_id')
            ->join('tnode_info ni ON ni.node_id=t.node_id')
            ->join('tbonus_use_detail bud ON bud.request_id=t.request_id')
            ->join('twx_assist_number wan ON wan.request_id=t.request_id')
            ->where($where)
            ->order('t.id desc')
            ->limit($limit)
            ->select();
        return $normalAwardList;
    }
}