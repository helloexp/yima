<?php

/**
 * 厦门银行中奖名单
 * @author lwb Time 20160225
 */
class XmyhZjmdModel extends Model {
    protected $tableName = 'tfb_xmyh_cjmd';
    
    /**
     * 是否在中奖名单上
     * @param string $phone 电话号码
     * @return boolean|mixed|boolean|NULL|string|unknown
     */
    public function isOnList($phone, $mId) {
        if (empty($phone)) {
            return false;
        }
        $re = $this->where(array('phone' => $phone, 'm_id' => $mId))->find();
        return $re;
    }
    
    /**
     * 根据电话查出这次活动中的奖项id
     * @param 电话号码 $phone
     * @param 活动id $batchId
     * @return boolean|string|unknown
     */
    public function getPrizeCateIdByPhone($phone, $batchId) {
        $prizeCate = $this->where(array('phone' => $phone, 'm_id' => $batchId))->getField('prize_cate');
        if (!$prizeCate) {
            return false;
        }
        $cateList = M('tcj_cate')
        ->where(array('batch_id' => $batchId, 'status' => 1))
        ->order('sort asc,id asc')
        ->select();
        $cateId = '';
        foreach ($cateList as $k => &$v) {
            if ($k == ($prizeCate - 1)) {
                $cateId = $v['id'];
                break;
            }
        }unset($v);
        return $cateId;
    }
    
    public function setJoined($phone, $mId) {
        return $this->where(array('phone' => $phone, 'm_id' => $mId))->save(array('joined' => 1));
    }
}