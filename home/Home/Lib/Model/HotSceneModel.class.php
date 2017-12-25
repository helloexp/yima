<?php

/**
 * 热门营销场景 by zc time 2015/08/11
 */
class HotSceneModel extends BaseModel {
    protected $tableName = '__NONE__';
    /**
     * 热门场景营销
     *
     * @param string $nodeId
     * @return array
     *
     */
    public function hotScene($nodeId) {
        // 点击推荐
        $clickNum = M('tbuss_visit_stat')->where(
            array(
                'visit_num' => array(
                    'egt', 
                    '5'), 
                'node_id' => $nodeId))
            ->order('visit_num Desc')
            ->limit(5)
            ->field('auth_power_id')
            ->select() or $clickNum = array();
        if (!isset($clickNum['num'])) {
            $clickNum['num'] = '';
        }
        foreach ($clickNum as $v) {
            $clickNum['num'] .= get_val($v,'auth_power_id') . ',';
        }
        
        if ($clickNum) {
            $clickList = M('tbusiness_recommend')->where(
                array(
                    'auth_power_id' => array(
                        'in', 
                        $clickNum['num']), 
                    'recommend_type' => '9'))
                ->order('sort_num Desc,add_time Desc')
                ->limit(5)
                ->select();
            if(empty($clickList))
                $clickList = array();
        }
        
        // 过滤经常使用
        if ($clickNum['num']) {
            $whFore = array(
                'recommend_type' => 1, 
                'status' => 0, 
                'begin_time' => array(
                    'lt', 
                    date('YmdHis') . '000000'), 
                'end_time' => array(
                    'gt', 
                    date('YmdHis') . '235959'), 
                'auth_power_id' => array(
                    'not in', 
                    $clickNum['num']));
        } else {
            $whFore = array(
                'recommend_type' => 1, 
                'status' => 0, 
                'begin_time' => array(
                    'lt', 
                    date('YmdHis') . '000000'), 
                'end_time' => array(
                    'gt', 
                    date('YmdHis') . '235959'));
        }
        
        // 强制推荐
        $forceList = M('tbusiness_recommend')->where($whFore)
            ->order('sort_num Desc,add_time Desc')
            ->limit(2)
            ->select();
        
        foreach ($forceList as $k => $v) {
            $clickNum['num'] .= $v['auth_power_id'] . ',';
        }
        
        /* 数据中心提供的行业排名数据 */
        $date = date('Ymd');
        $info = $this->hotFtpUrl($date);
        //初始化数据
        $list = $businessArr = $businessArrTmp = array();
        
        if($info){
            $str = preg_replace(
                '/(' . date('Ymd', strtotime("-1 day")) . ')|(\s)/iUs', '', $info);
            $result = explode(',', substr($str, 1));
            
            for ($i = 0; $i < count($result); $i ++) {
                $list[] = array($result[$i],$result[$i + 1],$result[$i + 2]);
                $i += 2;
            }
        
        }
        // 行业推荐
        $businessNNum = get_node_info($nodeId, 'trade_type');
        
        foreach ($list as $k => $v) {
            if ($v['0'] == $businessNNum) {
                $businessArr[] = array(
                    $v[1]); // 行业统计ID
            }
        }
        
        // 去除重复数据
        $tmpArr = $this->arrayColumn($businessArr, 0);
        $repetitionNum = explode(',', $clickNum['num']);
        
        foreach ($repetitionNum as $k => $v) {
            foreach ($tmpArr as $kk => $vv) {
                if ($v == $vv) {
                    unset($tmpArr[$kk]);
                }
            }
        }
        
        $businessArr = array_merge($tmpArr);
        $whBusiness = implode(',', $businessArr);
        
        if ($businessNNum) {
            $businessList = M('tbusiness_recommend')->where(
                array(
                    'auth_power_id' => array(
                        'in', 
                        $whBusiness), 
                    'status' => 0))
                ->order('sort_num Desc,add_time Desc')
                ->limit(2)
                ->select();
        }
        if($businessList){
            // 行业id组装
            foreach ($businessList as $k => $v) {
                $businessArrTmp[] = $v['auth_power_id'];
            }
        }
        $repetitionAll = array_merge($repetitionNum, $businessArrTmp); // 规则重复id(包含经常+强制+行业)
        $whRepetition = implode(',', $repetitionAll);
        
        if ($clickList) {
            $clickNNum = count($clickList);
        } else {
            $clickNNum = 0;
        }
        if ($forceList) {
            $forceNum = count($forceList);
        } else {
            $forceNum = 0;
        }
        if ($businessList) {
            $businessNum = count($businessList);
        } else {
            $businessNum = 0;
        }
        
        // 统计除常规推荐数量
        
        $numCount = $clickNNum + $forceNum + $businessNum;
        
        if ($clickNum['num']) {
            $wh = array(
                'recommend_type' => 3, 
                'status' => 0, 
                'auth_power_id' => array(
                    'not in', 
                    $whRepetition));
        } else {
            $wh = array(
                'recommend_type' => 3, 
                'status' => 0);
        }
        $hotList = M('tbusiness_recommend')->where($wh)
            ->limit(10 - $numCount)
            ->order('sort_num Desc,add_time Desc')
            ->select();
        
        $sceneList = array();
        
        /*
         * if($clickList) $sceneList = array_merge($clickList, $sceneList);
         */
        
        // 强制
        if ($forceList)
            $sceneList = array_merge($sceneList, $forceList);
            // 行业
        if ($businessList)
            $sceneList = array_merge($sceneList, $businessList);
            // 常规
        if ($hotList)
            $sceneList = array_merge($sceneList, $hotList);
        
        $info = array(
            'hotList' => $sceneList, 
            'clickList' => $clickList);
        return $info;
    }

    /**
     * 行业统计ftp抓取
     *
     * @return array
     *
     */
    public function hotFtpUrl() {
        $today = date('Ymd');
        $curl = curl_init();
        /* 测试 */
        $target_ftp_file = 'ftp://222.44.51.34/dw_pop_scene_' . $today . '.txt'; // 完整路径
        $pwd = "mdlftp:mdlftp!1";
        /* 生产 */
        /*
         * $target_ftp_file =
         * 'ftp://172.16.0.200/dw_pop_scene_'.$today.'.txt';//完整路径 $pwd =
         * "wc_yz_user:wc_yz_user1";
         */
        
        curl_setopt($curl, CURLOPT_URL, $target_ftp_file);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_FTP_USE_EPSV, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1); // 1秒
        curl_setopt($curl, CURLOPT_USERPWD, $pwd); // FTP用户名：密码
                                                   // Sets up the output file
        $info = curl_exec($curl);
        
        return $info;
    }

    /**
     * PHP 5.5新增函数array_column
     */
    public function arrayColumn($input, $columnKey, $indexKey = NULL) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
        $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
        $result = array();
        
        foreach ((array) $input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && ! empty($tmp)) ? current($tmp) : NULL;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
            }
            if (! $indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    }
}