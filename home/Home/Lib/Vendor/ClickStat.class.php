<?php
// 更新点击数
class ClickStat {
    
    // 获取标签详情
    public function updateStat($id) {
        $now = Date('Ymd');
        $model = M('tbatch_channel');
        $map = array(
                'id' => $id);
        $result = $model->where($map)->find();
        if (! $result) {
            return false;
        }
        // 更新日统计
        $batch_type = $result['batch_type'];
        $batch_id = $result['batch_id'];
        $channel_id = $result['channel_id'];
        $node_id = $result['node_id'];
        $ip = get_client_ip();
        // 记录流水
        $t_map = array(
                'batch_type' => $batch_type,
                'batch_id' => $batch_id,
                'channel_id' => $channel_id,
                'add_time' => date('YmdHis'),
                'node_id' => $node_id,
                'ip' => $ip);
        $trace_query = M('tbatch_trace')->add($t_map);

        return true;//全部走异步（数据库存储过程实现）

            // 开启事物活动
        $tranDb = new Model();
        $tranDb->startTrans();
        
        // 活动更新
        
        $model_ = M('tmarketing_info');
        $map_ = array(
            'id' => $result['batch_id'], 
            'batch_type' => $result['batch_type']);
        $query_arr = $model_->where($map_)->setInc('click_count', 1);
        
        // 标签更新
        $query = $model->where($map)->setInc('click_count', 1);
        

        $dmodel = M('tdaystat');
        $smap = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id, 
            'day' => $now, 
            'node_id' => $result['node_id']);
        $row = $dmodel->where($smap)->find();
        if ($row) {
            $squery = $dmodel->where($smap)->setInc('click_count', 1);
        } else {
            $smap['click_count'] = '1';
            $squery = $dmodel->add($smap);
        }
        // 更新tchannel表click_count数据
        $clickCount = $dmodel->where("channel_id={$channel_id}")->sum(
            'click_count'); // 获取该渠道的所有访问量
        $data = array(
            'click_count' => $clickCount);
        $result = M('tchannel')->where("id={$channel_id}")->save($data);
        

        
        if ($query !== false && $query_arr !== false && $squery !== false &&
             $result !== false && $trace_query) {
            $tranDb->commit();
            // 更新uv
            $uv_map = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'ip' => $ip);
            $uv_query = M('tbatch_trace')->field('id')
                ->where($uv_map)
                ->count();
            if ($uv_query == 1) {
                $uv_wh = array(
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id, 
                    'channel_id' => $channel_id, 
                    'day' => $now, 
                    'node_id' => $node_id);
                $uvquery = $dmodel->where($uv_wh)->setInc('uv_count', 1);
            }
        } else {
            $tranDb->rollback();
        }
    }
}
