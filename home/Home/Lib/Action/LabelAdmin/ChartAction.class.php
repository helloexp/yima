<?php

/*
 * 报表图表工具
 */
class ChartAction extends BaseAction {
    
    // 点击数报表
    public function clickChart() {
        $batch_type = I('batch_type');
        $batch_id   = I('batch_id');
        $channel_id = I('channel_id');
        if (empty($batch_type) || empty($batch_id))
            $this->error("活动类型或活动编号不能为空！");
        
        $_get = I('request.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date',dateformat("0 days", 'Ymd'));
        // 获取活动名
        $batch_name = M('tmarketing_info')
            ->where("id='" . $batch_id . "' and batch_type='" . $batch_type . "'")
            ->getField('name');
        $model = M('Tdaystat');
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id);
        if ($channel_id != '') {
            $map['channel_id'] = $channel_id;
        }
        
        // 查询日期
        $map['day'] = array();
        if ($begin_date != '') {
            $map['day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['day'][] = array(
                'ELT', 
                $end_date);
        }
        $query_arr = $model->where($map)
            ->field(
            "batch_type,batch_id,day,
            sum(click_count) AS click_count,
            sum(uv_count) AS uv_count,
            sum(verify_count) AS verify_count,
            sum(send_count) send_count")
            ->group("day")
            ->select();
        
        foreach ($query_arr as &$vo) {
            if ($vo['batch_type'] == '26' || $vo['batch_type'] == '27') {
                $count = M('ttg_order_info')->where(
                    array(
                        'batch_no' => $vo['batch_id'], 
                        'add_time' => array(
                            'like', 
                            "%{$vo['day']}%"), 
                        'pay_status' => '2', 
                        'order_status' => '0'))->sum('buy_num');
                if ($count)
                    $vo['send_count'] = $count;
            }
        }
        // 计算出JS值
        $jsChartDataClick = array();
        $jsChartDataSend = array();
        $jsChartDataVerify = array();
        $jsChartDataUv = array();
        foreach ($query_arr as $v) {
            $jsChartDataClick[] = array(
                $v['day'], 
                $v['click_count'] * 1);
            $jsChartDataSend[] = array(
                $v['day'], 
                $v['send_count'] * 1);
            $jsChartDataVerify[] = array(
                $v['day'],
                $v['verify_count'] * 1);
            $jsChartDataUv[] = array(
                $v['day'],
                $v['uv_count'] * 1);
        }
        $this->assign('_get', $_get);
        $this->assign('jsChartDataClick', json_encode($jsChartDataClick));
        //$this->assign('jsChartDataSend', json_encode($jsChartDataSend));
        $this->assign('jsChartDataVerify', json_encode($jsChartDataVerify));
        //$this->assign('jsChartDataUv', json_encode($jsChartDataUv));
        $this->assign('batch_type', $batch_type);
        $this->assign('query_list', $query_arr);
        $this->assign('begin_date',$begin_date);
        $this->assign('end_date',$end_date);
        $this->assign('batch_name', $batch_name);
        
        $this->display();
    }
    
    // 活动渠道分析图表
    public function channelChart() {
        $channel_type_arr = C('CHANNEL_TYPE');
        $statusArr = array(
            '1' => '正常', 
            '2' => '停用');
        
        // 类型
        $batch_type = I('batch_type');
        // 活动号
        $batch_id = I('batch_id');
        
        // 获取活动名
        $batch_name = M('tmarketing_info')->where(
            "id='" . $batch_id . "' and batch_type='" . $batch_type . "'")->getField(
            'name');
        if (! $batch_type)
            $this->error('未查询到记录！');
        $where = " a.node_id in({$this->nodeIn()}) and ifnull(b.sns_type, -99) != '53'";//现在应该没有sns_type为53的，以后不等于53的应该能去掉
        $where .= " and a.batch_type = '" . $batch_type . "'";
        $where .= " and a.batch_id = '" . $batch_id . "'";
        $list = M()->table('tbatch_channel a')
            ->field(
            'a.id,a.batch_type,a.channel_id,a.id,a.channel_id,a.click_count,
            a.send_count,a.add_time,a.status,b.name,b.type,b.sns_type,b.status as channel_status')
            ->join('tchannel b ON a.channel_id=b.id')
            ->where($where)
            ->select();
        foreach ($list as &$vo) {
            if ($vo['batch_type'] == '26' || $vo['batch_type'] == '27') {
                $count = M()->table("ttg_order_info t")->field('sum(t.buy_num)')
                    ->join('tbatch_channel b ON b.id=t.batch_channel_id')
                    ->where(
                    array(
                        't.batch_no' => $batch_id, 
                        't.pay_status' => '2', 
                        't.order_status' => '0', 
                        'b.id' => $vo['id']))
                    ->sum('buy_num');
                if ($count)
                    $vo['order_num'] = $count;
                else
                    $vo['order_num'] = 0;
            }
        }
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('query_list', $list);
        $this->assign('statusArr', $statusArr);
        if ($batch_type == CommonConst::BATCH_TYPE_NEWPOSTER || $batch_type == CommonConst::BATCH_TYPE_TEMPLATE )
        { //新版海报特殊处理
            $this->display('channelChart_forNewPoster');
        } else {
            $this->display(); // 输出模板
        }

    }
    
    // 修改指定活动下的渠道状态
    public function editStatus() {
        $id = I('get.id', null);
        $status = I('get.status', null);
        if (is_null($id) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $where = array(
            'id' => $id, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $result = M('tbatch_channel')->where($where)->find();
        
        $node_id = $result['node_id'];
        
        if (! $result) {
            $this->error('未找到该渠道类型');
        }
        if ($status == '1') {
            $data = array(
                'status' => '1');
        } else {
            $data = array(
                'status' => '2');
        }
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        // 渠道类
        $c_type = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'id' => $result['channel_id']))->find();
        if (in_array($c_type['type'], array(
            2))) {
            
            // 更新渠道
            if ($status == '2') {
                $data['change_time'] = Date('YmdHis');
                $ch_data = array(
                    'batch_type' => '', 
                    'batch_id' => '');
            } else {
                $ch_data = array(
                    'batch_type' => $result['batch_type'], 
                    'batch_id' => $result['batch_id']);
                if ($c_type['batch_type'] != $result['batch_type'] ||
                     $c_type['batch_id'] != $result['batch_id']) {
                    
                    $b_data = array(
                        'node_id' => $node_id, 
                        'channel_id' => $c_type['id'], 
                        'batch_type' => $c_type['batch_type'], 
                        'batch_id' => $c_type['batch_id']);
                    $query = M('tbatch_channel')->where($b_data)->save(
                        array(
                            'status' => '2', 
                            'change_time' => date('YmdHis')));
                    if ($query === false) {
                        $tranDb->rollback();
                        $this->error('更新失败！');
                    }
                }
            }
            
            $query = M('tchannel')->where(
                array(
                    'node_id' => $node_id, 
                    'id' => $result['channel_id']))->save($ch_data);
            if ($query === false) {
                $tranDb->rollback();
                $this->error('更新失败！');
            }
        }
        
        // 更新渠道状态
        $query = M('tbatch_channel')->where($where)->save($data);
        if ($query === false) {
            $tranDb->rollback();
            $this->error('更新失败！');
        }
        
        $tranDb->commit();
        $this->success('更新成功', 
            array(
                '返回' => $_SERVER['HTTP_REFERER']));
    }
    
    // 活动渠道分析图表(针对渠道类型，以及不发码的进行分析）
    public function channelChartEasy() {
        $channel_type_arr = C('CHANNEL_TYPE');
        $statusArr = array(
            '1' => '正常', 
            '2' => '停用');
        // 类型
        $batch_type = I('batch_type');
        // 活动号
        $batch_id = I('batch_id');
        
        // 获取活动名
        $batch_name = M('tmarketing_info')->where(
            "id='" . $batch_id . "' and batch_type ='" . $batch_type . "'")->getField(
            'name');
        $model = M()->table('tbatch_channel a');
        $where = " a.node_id in({$this->nodeIn()}) and b.sns_type != '53'";
        $where .= " and a.batch_type = '" . $batch_type . "'";
        $where .= " and a.batch_id = '" . $batch_id . "'";
        $list = M()->Table('tbatch_channel a')
            ->field(
            'a.id,a.batch_type,a.channel_id,a.id,a.channel_id,a.click_count,a.send_count,a.add_time,a.status,b.name,b.type,b.sns_type')
            ->join('tchannel b ON a.channel_id=b.id')
            ->where($where)
            ->select();
        
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('query_list', $list);
        $this->assign('statusArr', $statusArr);
        
        $this->display(); // 输出模板
    }
    
    // 点击数报表(针对渠道类型，以及不发码的进行分析）
    public function clickChartEasy() {
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        $channel_id = I('channel_id');
        
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        
        // 获取活动名
        $batch_name = M('tmarketing_info')->where(
            "id='" . $batch_id . "' and batch_type='" . $batch_type . "'")->getField(
            'name');
        
        $model = M('Tdaystat');
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id);
        if ($channel_id != '') {
            $map['channel_id'] = $channel_id;
        }
        
        // 查询日期
        $map['day'] = array();
        if ($begin_date != '') {
            $map['day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['day'][] = array(
                'ELT', 
                $end_date);
        }
        $query_arr = $model->where($map)
            ->field(
            "batch_type,batch_id,day,sum(click_count) click_count,sum(send_count) send_count")
            ->group("day")
            ->select();
        
        // 计算出JS值
        $jsChartDataClick = array();
        $jsChartDataSend = array();
        foreach ($query_arr as $v) {
            $jsChartDataClick[] = array(
                $v['day'], 
                $v['click_count'] * 1);
            $jsChartDataSend[] = array(
                $v['day'], 
                $v['send_count'] * 1);
        }
        $this->assign('_get', $_get);
        $this->assign('jsChartDataClick', json_encode($jsChartDataClick));
        $this->assign('jsChartDataSend', json_encode($jsChartDataSend));
        $this->assign('batch_type', $batch_type);
        $this->assign('query_list', $query_arr);
        $this->assign('batch_name', $batch_name);
        
        $this->display();
    }
}