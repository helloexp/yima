<?php

/* 抽奖编辑 */
class TCjBatchModel {

    public $batch_id;

    public $batch_type;

    public $node_id;

    public $this_day;
    
    // 初始化
    public function editCjRule($data) {
        $this->this_day = date('Ymd');
        $this->node_id = $data['node_id'];
        $this->batch_type = $data['batch_type'];
        $this->batch_id = $data['batch_id'];
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type, 
            'status' => '1');
        $jp_set_type = M('tcj_rule')->where($map)->getField('jp_set_type');
        if ($jp_set_type == '1') {
            $into_arr = array(
                'day_goods_count_edit' => $data['day_goods_count_edit'], 
                'goods_count_edit' => $data['goods_count_edit'], 
                'chance_edit' => $data['chance_edit'] ? $data['chance_edit'] : 0);
            $resp = $this->editOneCj($into_arr);
        } elseif ($jp_set_type == '2') {
            $into_arr = array(
                'day_goods_count_edit_1' => $data['day_goods_count_edit_1'], 
                'goods_count_edit_1' => $data['goods_count_edit_1'], 
                'day_goods_count_edit_2' => $data['day_goods_count_edit_2'], 
                'goods_count_edit_2' => $data['goods_count_edit_2'], 
                'day_goods_count_edit_3' => $data['day_goods_count_edit_3'], 
                'goods_count_edit_3' => $data['goods_count_edit_3'], 
                'total_chance_edit' => $data['total_chance_edit']);
            $resp = $this->editMoreCj($into_arr);
        }
        if ($resp === true)
            return true;
        else
            return $resp;
    }

    public function editCjRuleWorldCup($data) {
        $this->this_day = date('Ymd');
        $this->node_id = $data['node_id'];
        $this->batch_type = $data['batch_type'];
        $this->batch_id = $data['batch_id'];
        
        $batch_data = $data['batch_data'];
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type, 
            'status' => '1');
        $jp_set_type = M('tcj_rule')->where($map)->getField('jp_set_type');
        if ($jp_set_type == '1') {
            $into_arr = array(
                'day_goods_count_edit' => $batch_data['list'][0]['total_count'], 
                'goods_count_edit' => $batch_data['list'][0]['total_count'], 
                'chance_edit' => $batch_data['chance']);
            $resp = $this->editOneCj($into_arr);
        } elseif ($jp_set_type == '2') {
            M()->startTrans();
            $resp = '';
            foreach ($batch_data['list'] as $vv) {
                $map = array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $this->batch_id, 
                    'id' => $vv['cj_batch_id']);
                
                $cj_batch = M('tcj_batch')->where($map)->find();
                if (! $cj_batch) {
                    $resp = "参数错误：error cj_batch_id";
                    break;
                }
                
                $cj_rule_id = $cj_batch['id'];
                
                // 每日
                $map = array(
                    'rule_id' => $cj_rule_id);
                $count = M('taward_daytimes')->where($map)->sum('award_times');
                if ($count > $vv['total_count']) {
                    M()->rollback();
                    $resp = $cj_batch['award_level'] . '等奖日中奖数大于每日奖品限量！';
                    break;
                }
                
                // 总数
                $map['trans_date'] = $this->this_day;
                $day_count = M('taward_daytimes')->where($map)->getField(
                    'award_times');
                if ($day_count > $vv['total_count']) {
                    M()->rollback();
                    return $cj_batch['award_level'] . '等奖已中奖数已大于奖品总数！';
                }
                
                // 更新
                $save_arr = array(
                    'award_rate' => $vv['total_count'], 
                    'total_count' => $vv['total_count'], 
                    'day_count' => $vv['total_count']);
                
                $query = M('tcj_batch')->where(
                    array(
                        'id' => $vv['cj_batch_id']))->save($save_arr);
                if ($query === false) {
                    M()->rollback();
                    $resp = '奖品设置更新失败！';
                    break;
                }
                // 更新总中奖概率
                if ($data['chance_edit']) {
                    $save_rule_arr = array(
                        'id' => $cj_batch['cj_rule_id']);
                    $query = M('tcj_rule')->where($save_rule_arr)->save(
                        array(
                            'total_chance' => $data['chance_edit']));
                    if ($query === false) {
                        M()->rollback();
                        $resp = '奖品概率设置失败！';
                        break;
                    }
                }
            }
            
            if ($resp == '') {
                M()->commit();
                $resp = true;
            }
        }
        
        if ($resp === true)
            return true;
        else
            return $resp;
    }
    
    // 校验单奖品jp_set_type =1
    private function editOneCj($array = array()) {
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type, 
            'status' => '1');
        $cj_rule_id = M('tcj_rule')->where($map)->getField('id');
        $rule_id = M('tcj_batch')->where(
            array(
                'cj_rule_id' => $cj_rule_id))->getField('id');
        
        $map = array(
            'rule_id' => $rule_id);
        $count = M('taward_daytimes')->where($map)->sum('award_times');
        if ($count > $array['goods_count_edit'])
            return '已中奖数已大于奖品总数！';
        
        $map['trans_date'] = $this->this_day;
        $day_count = M('taward_daytimes')->where($map)->getField('award_times');
        if ($day_count > $array['day_goods_count_edit'])
            return '当日中奖数大于每日奖品限量！';
            
            // 更新
        $save_arr = array(
            'total_count' => $array['goods_count_edit'], 
            'day_count' => $array['day_goods_count_edit'], 
            'award_rate' => $array['chance_edit']);
        $query = M('tcj_batch')->where(
            array(
                'id' => $rule_id))->save($save_arr);
        if ($query === false)
            return '奖品设置更新失败！';
        
        return true;
    }
    
    // 校验多奖品jp_set_type =2
    private function editMoreCj($array = array()) {
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type, 
            'status' => '1');
        $cj_rule_id = M('tcj_rule')->where($map)->getField('id');
        $query_arr = M('tcj_batch')->field(
            'id,award_rate,award_level,total_count,day_count')
            ->where(array(
            'cj_rule_id' => $cj_rule_id))
            ->select();
        if ($query_arr) {
            // 开启事物
            $tranDb = new Model();
            $tranDb->startTrans();
            foreach ($query_arr as $v) {
                // 每日
                $map = array(
                    'rule_id' => $v['id']);
                $count = M('taward_daytimes')->where($map)->sum('award_times');
                if ($count > $array['day_goods_count_edit_' . $v['award_level']]) {
                    $tranDb->rollback();
                    return $v['award_level'] . '等奖日中奖数大于每日奖品限量！';
                }
                
                // 总数
                $map['trans_date'] = $this->this_day;
                $day_count = M('taward_daytimes')->where($map)->getField(
                    'award_times');
                if ($day_count > $array['goods_count_edit_' . $v['award_level']]) {
                    $tranDb->rollback();
                    return $v['award_level'] . '等奖已中奖数已大于奖品总数！';
                }
                
                // 更新
                $save_arr = array(
                    'total_count' => $array['goods_count_edit_' .
                         $v['award_level']], 
                        'day_count' => $array['day_goods_count_edit_' .
                         $v['award_level']], 
                        'award_rate' => $array['goods_count_edit_' .
                         $v['award_level']]);
                $query = M('tcj_batch')->where(
                    array(
                        'id' => $v['id']))->save($save_arr);
                if ($query === false) {
                    $tranDb->rollback();
                    return '奖品设置更新失败！';
                }
                // 更新总中奖概率
                $save_rule_arr = array(
                    'id' => $cj_rule_id);
                $query = M('tcj_rule')->where($save_rule_arr)->save(
                    array(
                        'total_chance' => $array['total_chance_edit']));
                if ($query === false) {
                    $tranDb->rollback();
                    return '奖品概率设置失败！';
                }
            }
            $tranDb->commit();
            return true;
        }
        return false;
    }
}