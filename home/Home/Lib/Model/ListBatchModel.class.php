<?php

/**
 *
 * @author lwb Time 20151010
 */
class ListBatchModel extends Model {
    protected $tableName = '__NONE__';
    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function getMarketingInfo($mId) {
        $mInfoModel = M('tmarketing_info');
        $mInfo = $mInfoModel->where(array(
            'id' => $mId))->find();
        if ($mInfo) {
            $configData = unserialize($mInfo['config_data']);
            $mInfo['banner_pic'] = $configData['banner_pic'];
            $mInfo['nav_1'] = $configData['nav_1'];
            $mInfo['nav_2'] = $configData['nav_2'];
            $mInfo['tab_nav'] = $configData['tab_nav'];
            $mInfo['list_type'] = $configData['list_type'];
            $mInfo['share_descript'] = $configData['share_descript'];
        }
        return $mInfo;
    }

    public function getListTypeById($mId) {
        $mInfoModel = M('tmarketing_info');
        $configData = $mInfoModel->where(array(
            'id' => $mId))->getField('config_data');
        $listType = '0';
        if ($configData) {
            $configData = unserialize($configData);
            $listType = $configData['list_type'];
        }
        return $listType;
    }

    public function getList($mId, $page) {
        if (! session('activity_map' . $mId)) {
            session('activity_map' . $mId, 
                array(
                    'side_nav' => '', 
                    'keyword' => json_encode(
                        array(
                            '', 
                            ''))));
        }
        $activityMap = session('activity_map' . $mId);
        if ($activityMap['side_nav']) {
            $map['nav_id'] = $activityMap['side_nav'];
        }
        $keywordArr = json_decode($activityMap['keyword'], true);
        
        $map['_string'] = '';
        $needPlusAnd = false;
        foreach ($keywordArr as $v) {
            $and = '';
            if ($v) {
                if ($needPlusAnd) {
                    $and = ' and ';
                }
                $map['_string'] .= $and . "find_in_set('" . $v . "',keyword)";
                $needPlusAnd = true;
            }
        }
        if ($map['_string'] == '') {
            unset($map['_string']);
        }
        $map['list_id'] = $mId;
        $map['status'] = '1';
        $model = M('tlist_batch_list');
        // 当前页
        $page = empty($page) ? 1 : (int) $page;
        // 每页条数
        $page_count = 10;
        // 初始行
        $firstRow = $page_count * ($page - 1);
        $list = $model->where($map)
            ->order('list_sort_id asc')
            ->limit($firstRow . ',' . $page_count)
            ->select();
        return $list;
    }
}