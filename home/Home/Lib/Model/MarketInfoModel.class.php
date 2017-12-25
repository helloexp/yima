<?php

/**
 * 抽奖相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class MarketInfoModel extends BaseModel {

    protected $tableName = 'tmarketing_info';

    /**
     * 获得符合$where条件的记录条数
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @return mixed
     */
    public function count($where, $field = '') {
        if ($field) {
            return M($this->tableName)->where($where)->count($field);
        } else {
            return M($this->tableName)->where($where)->count();
        }
    }

    /**
     *
     * @param $where
     * @param string $orderBy
     * @param string $limit
     * @param string $field
     * @param int $selectType
     *
     * @return mixed
     */
    public function get($where, $orderBy = '', $limit = '', $field = '', 
        $selectType = BaseModel::SELECT_TYPE_ALL) {
        // $list =
        // M($this->tableName)->where($where)->order($orderBy)->limit($limit)->select();
        return $this->getData($this->tableName, $where, $selectType, $field, 
            $orderBy, $limit);
    }

    public function getMarketingInfo($where) {
        return $this->get($where, '', '', '', basemodel::SELECT_TYPE_ONE);
    }

    /**
     *
     * @param $where
     * @return mixed
     */
    public function getSingleInfo($where) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE);
    }

    /**
     * 报错数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveData($where, $data) {
        return M($this->tableName)->where($where)->save($data);
    }

    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取送礼标识
     * @param array $marketInfo marketInfo
     * @return int $sendGift 是否送礼
     */
    public function getSendGiftTage($marketInfo) {
        $sendGift = 0;
        if (null != $marketInfo['config_data']) {
            $sendGiftInfo = unserialize($marketInfo['config_data']);
            if (isset($sendGiftInfo['send_gift'])) {
                $sendGift = $sendGiftInfo['send_gift'];
            }
        }
        return $sendGift;
    }

    /**
     * 获取单条符合条件的记录的某些字段
     *
     * @param array $where
     * @param mixed $field
     * @return mixed
     */
    public function getSingleField($where, $field) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE, $field);
    }
}