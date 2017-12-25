<?php

/**
 * 活动服务类
 */
class ActivityService {

    private $dao;

    public function __construct() {
        $this->dao = D('TbatchInfo');
    }

    /**
     * 写凭证信息信息
     *
     * @param unknown_type $batchInfo
     *
     * @return boolean
     */
    public function writeActivity($batchInfo) {
        $rs = $this->dao->add($batchInfo);

        return $rs;
    }

    /**
     * 搜索活动列表
     *
     * @param unknown_type $search 条件
     *
     * @return boolean
     */
    public function searchActivity($search, $field = "*", $page = 1, $perPage) {
        $start = ($page - 1) * $perPage;

        return $this->dao->where($search)->field($field)->limit("{$start},{$perPage}")->order("STATUS asc, ADD_TIME desc")->select();
    }

    /**
     * 活动总数目
     *
     * @param unknown_type $search 条件
     *
     * @return boolean
     */
    public function countActivity($search) {
        $rs = $this->dao->where($search)->count();

        return $rs;
    }

    /**
     * 更新活动信息
     *
     * @param unknown_type $batchInfo 更新字段
     * @param unknown_type $where     条件
     *
     * @return boolean
     */
    public function updateActivity($batchInfo, $where) {
        $rs = $this->dao->where($where)->save($batchInfo);

        return $rs;
    }

    /**
     * 获取活动信息
     *
     * @param unknown_type $search 条件
     *
     * @return array
     */
    public function getActivityInfo($search) {
        return $this->dao->where($search)->find();
    }
}

?>