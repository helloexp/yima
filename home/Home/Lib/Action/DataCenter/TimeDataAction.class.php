<?php

/**
 * 时段数据
 */
class TimeDataAction extends BaseAction {
    
    // 营销活动时段统计
    public function index() {
        $requestInfo = I('post.'); // 获取请求数据
        self::getBTraceStat($requestInfo,I('get.down'));
        $this->display();
    }
    // 卡券时段统计
    public function goodsData() {
        $requestInfo = I('post.'); // 获取请求数据
        self::getGoodsData($requestInfo,I('get.down'));
        $this->display();
    }

    /*******************以下是私有方法******************/

    private function getBTraceStat($requestInfo,$down){
        $map = array();
        $map['node_id'] = $this->nodeId;
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-90 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['trans_date'][] = array('EGT',$beginTime);
        $map['trans_date'][] = array('ELT',$endTime);
        // 查询字段
        $field = "SUM(pv) AS pv,SUM(uv) AS uv,concat(hours,':00-',hours,':59') AS durhour";
        $result = M('tbatch_trace_hour_stat')->field($field)
                ->where($map)->group('hours')->order('hours ASC')->select();
        if($down){
            $cols_arr = array(
                'durhour' => '小时', 
                'pv'      => 'pv', 
                'uv'      => 'uv', 
                );
            parent::csv_lead("营销活动峰值",$cols_arr,$result);
        }
        $this->assign('begin_time',$beginTime);
        $this->assign('end_time',$endTime);
        $this->assign('day_type',get_val($requestInfo,'day_type','1'));
        $this->assign('list',$result);
        $this->assign('pvuv',self::dealRes($result));
    }
    private function getGoodsData($requestInfo,$down){
        $map = array();
        $map['node_id']    = $this->nodeId;
        $map['status']     = '0';
        $map['trans_type'] = array('in','0,3');
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-90 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['trans_time'][] = array('EGT',$beginTime.'000000');
        $map['trans_time'][] = array('ELT',$endTime.'235959');
        // 查询字段
        $field = "concat(DATE_FORMAT(trans_time,'%H'),':00-',DATE_FORMAT(trans_time,'%H'),':59') AS durhour,count(*) as pv";
        $result = M('tpos_trace')->field($field)
                ->where($map)->group("DATE_FORMAT(trans_time,'%H')")->select();
        if($down){
            $cols_arr = array(
                'durhour' => '小时', 
                'pv'      => '验码量', 
                );
            parent::csv_lead("卡券核销峰值",$cols_arr,$result);
        }
        $this->assign('begin_time',$beginTime);
        $this->assign('end_time',$endTime);
        $this->assign('day_type',get_val($requestInfo,'day_type','1'));
        $this->assign('list',$result);
        $this->assign('pvuv',self::dealRes($result));
    }
    private function dealRes($result){
        $pv_arr = array();
        $uv_arr = array();
        $hr_arr = array();
        if(!empty($result)){
            foreach ($result as $v) {
                $hr_arr[] = "'" . $v['durhour'] . "'";
                $pv_arr[] = get_val($v,'pv','');
                $uv_arr[] = get_val($v,'uv','');
            }
        }
        return array(
            'pv' => '['.implode(',', $pv_arr).']',
            'uv' => '['.implode(',', $uv_arr).']',
            'hr' => '['.implode(',', $hr_arr).']'
            );
    }
}
