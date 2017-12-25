<?php

class MapAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '3';
    // 图片路径
    public $img_path;

    public function index() {
        $batch_id = I('batch_id');
        $market_name = M('tmarketing_info')->where(
            array(
                'id' => I('batch_id'), 
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->node_id))->getField('name');
        
        $model = M('tquestion');
        $map = array(
            'label_id' => I('batch_id'), 
            'type' => '4');
        
        $list = $model->where($map)
            ->order('sort desc')
            ->select();
        
        $model_c = M('tbatch_channel');
        $map_c = array(
            't1.batch_id' => I('batch_id'), 
            't1.batch_type' => $this->BATCH_TYPE, 
            't1.node_id' => $this->node_id);
        $channel_arr = $model_c->alias('t1')
            ->join('inner join tchannel t2 on t1.channel_id = t2.id')
            ->join('inner join tstore_info t3 on t2.store_id = t3.store_id')
            ->where($map_c)
            ->field(
            't1.channel_id,t1.batch_id,t3.store_id,t3.store_name,t3.lbs_x,t3.lbs_y')
            ->order('t1.id desc')
            ->select();
        $i = '0';
        $lbs = '';
        foreach ($channel_arr as $key => $val) {
            if (! $lbs && $val['lbs_x'] && $val['lbs_y']) {
                $lbs = array(
                    'lbs_x' => number_format($val['lbs_x'], 6, '.', ''), 
                    'lbs_y' => number_format($val['lbs_y'], 6, '.', ''));
            }
            if (! $store_arr[$val['store_id']]) {
                $store_arr[$val['store_id']] = array(
                    'key' => $i ++, 
                    'batch_id' => $val['batch_id'], 
                    'store_name' => $val['store_name'], 
                    'lbs_x' => number_format($val['lbs_x'], 6, '.', ''), 
                    'lbs_y' => number_format($val['lbs_y'], 6, '.', ''));
                $store_arr[$val['store_id']]['channel_id'][] = $val['channel_id'];
            } else {
                $store_arr[$val['store_id']]['channel_id'][] = $val['channel_id'];
            }
            
            $map_trace = array(
                't1.label_id' => $batch_id, 
                't1.channel_id' => $val['channel_id']);
            $q_arr = M()->table("tbm_trace t1")->join(
                'tquestion_stat t2 on t1.id = t2.bm_seq_id')
                ->where($map_trace)
                ->field('question_id,answer_list')
                ->select();
            foreach ($q_arr as $k => $v) {
                $v_arr = explode('|#|', $v['answer_list']);
                if (count($v_arr) != '3') {
                    continue;
                } else {
                    $store_arr[$val['store_id']]['question_id'][$v['question_id']][] = array(
                        'lbs_x' => substr($v_arr['1'], 0, 10), 
                        'lbs_y' => substr($v_arr['2'], 0, 10));
                }
            }
        }
        $this->assign('lbs', $lbs);
        $this->assign('store_arr', $store_arr);
        $this->assign('batch_id', $batch_id);
        $this->assign('list', $list);
        $this->assign('channel_arr', $channel_arr);
        $this->assign('market_name', $market_name);
        $this->display();
    }

    public function _check_storage($start_trans = false) {
        $batch_id = I('batch_id', 0, 'intval,abs');
        if ($batch_id == 0)
            $this->error('参数错误！');
            
            // 校验营销活动
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $batch_id, 
            'batch_type' => $this->BATCH_TYPE);
        $marketingInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketingInfo) {
            echo M()->_sql();
            $this->error('参数错误！查询营销活动失败！');
        }
        
        if ($start_trans) {
            M()->startTrans();
            $store_id = I('store_id', '', 'trim');
            if ($store_id === '') {
                $this->error('参数错误！');
            }
            $map = array(
                'store_id' => $store_id);
            M('tfb_hgds_storage')->where($map)
                ->lock(true)
                ->select();
        }
        
        // 获取该营销活动发布渠道的门店信息
        $map = array(
            'a.batch_id' => $batch_id, 
            'a.channel_id' => array(
                'exp', 
                '=b.id'), 
            'b.store_id' => array(
                'exp', 
                '=c.store_id'));
        // 'c.store_id' => array('exp', '=d.store_id')
        
        $store_list = M()->table('tbatch_channel a, tchannel b, tstore_info c')
            ->join('tfb_hgds_storage d on d.store_id = c.store_id')
            ->where($map)
            ->getField('c.store_id, c.store_name, d.storage_conf');
        if (! $store_list) {
            M()->rollback();
            $this->error('营销活动还未发布至门店渠道，请发布之后再设置门店参与量');
        }
        
        foreach ($store_list as &$store) {
            // $k = $store['store_id'];
            if ($store['storage_conf'] != '')
                $store['storage_conf'] = json_decode($store['storage_conf'], 
                    true);
            else
                $store['storage_conf'] = array(
                    'day' => new stdClass(), 
                    'timeRange' => new stdClass());
        }
        return $store_list;
    }
    
    // 哈根达斯库存设置
    public function set_storage() {
        $batch_id = I('batch_id', 0, 'intval,abs');
        $store_list = $this->_check_storage();
        
        $this->assign('batch_id', $batch_id);
        $this->assign('store_list', $store_list);
        $this->display();
    }

    public function get_storage() {
        $store_id = I('store_id', '', 'trim');
        if ($store_id === '') {
            $this->error('参数错误！');
        }
        $store_list = $this->_check_storage();
        if (! isset($store_list[$store_id])) {
            $this->error('门店信息获取失败');
        }
        $this->success($store_list[$store_id]);
    }

    public function save_storage() {
        if (! $this->isPost()) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
        $store_id = I('store_id', '', 'trim');
        if ($store_id === '') {
            $this->error('参数错误！[01]');
        }
        
        $week = I('week', 0, 'intval');
        if ($week === 0) {
            $this->error('参数错误！[02]');
        }
        if ($week < 1 && $week > 7) {
            $this->error('参数错误！[03]');
        }
        if ($week == date('N')) {
            $this->error('不能编辑当天的库存数！');
        }
        
        $conf = I('conf', '', 'trim');
        $num_arr = explode(',', $conf);
        if (sizeof($num_arr) != 13) {
            $this->error('参数错误！');
        }
        $num_arr = array_map('trim', $num_arr);
        $num_arr = array_map('trim', $num_arr);
        if ($num_arr[0] == '' || ! preg_match('/^(0|[1-9][0-9]*)$/', 
            $num_arr[0])) {
            $this->error('请录入正确的总份数！');
        }
        
        $day_num = $num_arr[0];
        $range_arr = array_slice($num_arr, 1);
        foreach ($range_arr as $i => $a) {
            if ($a != '' && ! preg_match('/^(0|[1-9][0-9]*)$/', $a)) {
                $t = str_pad($i, 2, '0', STR_PAD_LEFT);
                $str = "$t:00~" . ($t + 1) . ":59";
                $this->error("时间段{$str}数量有误！");
            }
        }
        
        if (array_sum($range_arr) > $day_num) {
            $this->error('时间段数量总和不能超过当天总数！');
        }
        
        $store_list = $this->_check_storage(true);
        if (! isset($store_list[$store_id])) {
            $this->error('门店信息获取失败');
        }
        
        $msg = '';
        do {
            $info = $store_list[$store_id]['storage_conf'];
            $update_arr = array_combine(range(1, 12), $range_arr);
            $new = false;
            if (is_object($info['day'])) {
                $new = true;
                $info['day'] = array();
                $info['timeRange'] = array();
            }
            
            $info['day'][$week] = $day_num;
            $info['timeRange'][$week] = $update_arr;
            if ($new) {
                $flag = M('tfb_hgds_storage')->add(
                    array(
                        'store_id' => $store_id, 
                        'storage_conf' => json_encode($info)));
            } else {
                $flag = M('tfb_hgds_storage')->where("store_id = '{$store_id}'")->save(
                    array(
                        'storage_conf' => json_encode($info)));
            }
            if ($flag === false) {
                $msg = '保存失败，请重试！';
                break;
            }
        }
        while (0);
        
        if ($msg != '') {
            M()->rollback();
            $this->error($msg);
        }
        
        M()->commit();
        $this->success('保存成功！');
    }
}