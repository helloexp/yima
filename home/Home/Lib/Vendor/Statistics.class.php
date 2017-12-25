<?php

/**
 * 统一统计类
 *
 * @author : Jeff Liu Date: 2015/7/1 Time: 13:46
 */
class Statistics {

    private static $tableMapping = array(
        'participation' => 'tparticipation_trace', 
        'statistics' => 'tstatistics_summary');

    private static $whereMapping = array(
        'participation' => array(
            'batch_type', 
            'batch_id', 
            'channel_id'));

    private static $defautlValue = array(
        'label_id' => '',  // 标签ID
        'node_id' => '',  // 机构id
        'user_id' => '',  // 用户id
        'batch_type' => '',  // 1-游戏 2 阅读 3报名 4会员招募 5爱拍 6团购 7宁夏移动 8列表 9优惠券发放
                            // 10有奖答题
        'batch_id' => '',  // 活动号id
        'channel_id' => '',  // 渠道id
        'mobile' => '',  // 手机号
        'ip' => '',  // ip地址
        'add_time' => '',  // 添加时间
        'join_mode' => '',  // 参与方式
        'wx_name' => '',  // 微信名称
        'full_id' => '',  // 访问路径
        'batch_trace_id' => '');
    // tbatch_trace表中的id字段
    
    /**
     * 记录新的log
     *
     * @author Jeff Liu
     * @param string $table 表名
     * @param array $insertData 需要更新的数据
     * @return mixed
     */
    public static function initLog($table, $insertData) {
        if (isset($insertData['id'])) {
            unset($insertData['id']);
        }
        $insertData = self::buildData($insertData);
        $insertData['ip'] = get_client_ip();
        $insertData['add_time'] = date('YmdHis');
        return M($table)->add($insertData);
    }

    /**
     * 防止因为传递一些其他字段 导致sql出错的情况
     *
     * @param $data
     * @return array
     */
    public static function buildData($data) {
        foreach ($data as $key => $value) {
            if (! isset(self::$defautlValue[$key])) {
                unset($data[$key]);
            }
        }
        return array_merge(self::$defautlValue, $data);
    }

    /**
     *
     * @param string $table
     * @param DrawLottery $drawLottery
     *
     * @return mixed
     */
    public static function logViaDrawLottery($table, $drawLottery) {
        $insertData = get_class_vars($drawLottery);
        return self::initLog($table, $insertData);
    }

    /**
     * 初始化参与相关数据(在活动发布的时候进行初始化)
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $insertData
     * @return mixed
     */
    public static function initParticipationLogViaPublish($insertData) {
        if (isset(self::$tableMapping['participation'])) {
            $table = self::$tableMapping['participation'];
        } else {
            $table = 'tcj_participation';
        }
        return self::initLog($table, $insertData);
    }

    /**
     * 参与日志流水
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $insertData
     * @return mixed
     */
    public static function addParticipationLog($insertData) {
        if (isset(self::$tableMapping['participation'])) {
            $table = self::$tableMapping['participation'];
        } else {
            $table = 'tcj_participation';
        }
        return self::initLog($table, $insertData);
    }

    public static function buildWhere($originData) {
        $finalWhere = array();
        foreach (self::$whereMapping['participation'] as $v) {
            $finalWhere[$v] = isset($originData[$v]) ? $originData[$v] : '';
        }
        return $finalWhere;
    }

    public static function updateParticipationLog($updateData, $where = array()) {
        if (empty($where)) {
            $where = self::buildWhere($updateData);
        }
        
        if (isset(self::$tableMapping['participation'])) {
            $table = self::$tableMapping['participation'];
        } else {
            $table = 'tcj_participation';
        }
        
        return self::updateLog($table, $updateData, $where);
    }

    public static function initLogViaPublish($table, $insertData) {
        return self::logViaDrawLottery($table, $insertData);
    }

    /**
     * 更新log
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $table 表名
     * @param array $updateData 需要插入的数据
     * @param mixed $where 更新条件
     * @return bool
     */
    public static function updateLog($table, $updateData, $where) {
        return M($table)->where($where)->save($updateData);
    }

    /**
     * 记录参与日志
     *
     * @author Jeff Liu
     * @param DrawLottery $drawLottery
     */
    public static function LogParticipation($drawLottery) {
        if (isset(self::$tableMapping['participation'])) {
            $table = self::$tableMapping['participation'];
        } else {
            $table = 'tcj_participation';
        }
        self::logViaDrawLottery($table, $drawLottery);
    }

    /**
     * 批量更新
     *
     * @param array $data
     */
    public static function batchLog($data) {
        foreach ($data as $current_data) {
            $table = $current_data['table'];
            $where = $current_data['where'];
            $updateData = $current_data['updateData'];
            M($table)->where($where)->save($updateData);
        }
    }

    /**
     * 根据给定的where子句获得所需数据
     *
     * @author Jeff Liu
     * @param array $where
     *
     * @return mixed
     */
    public static function getStatisticsViaWhere($where) {
        $table = 'tstatistics_summary';
        if (isset(self::$tableMapping['statistics'])) {
            $table = self::$tableMapping['statistics'];
        }
        return M($table)->where($where)->select();
    }
}