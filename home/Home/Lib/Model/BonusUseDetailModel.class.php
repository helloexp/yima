<?php

/**
 * 抽奖trace相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/12/08
 */
class BonusUseDetailModel extends BaseModel {
    protected $tableName = 'tbonus_use_detail';
    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param int $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getBonusUseDetail($where, 
        $selectType = BaseModel::SELECT_TYPE_ALL, $field = '') {
        return $this->getData('tbonus_use_detail', $where, $selectType, $field);
    }

    public function updateBonusPhone($where, $phone) {
        return $this->table('tbonus_use_detail')
            ->where($where)
            ->save(array(
            'phone' => $phone));
    }
}