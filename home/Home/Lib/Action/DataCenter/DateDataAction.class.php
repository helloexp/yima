<?php

/**
 * 营销数据中心
 */
class DateDataAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        self::setCommonAssign();
    }
    
    // 营销活动趋势
    public function index() {
        $requestInfo = I('post.'); // 获取请求数据
        self::getMarketPvUv($requestInfo);
        $this->display();
    }
    // 营销粉丝趋势
    public function memberData() {
        $requestInfo = I('post.'); // 获取请求数据
        self::getMemberPvUv($requestInfo);
        $this->display();
    }

    /*-----------------------以下为私有方法-----------------------*/

    // 设置公共的头部数据
    private function setCommonAssign(){
        $this->assign('aclick_counts',  self::getClickCnt());
        $this->assign('amember_counts', self::getMembers());
        $this->assign('asend_num',      self::getCardPv(true));
        $this->assign('averify_num',    self::getCardPv(false));
        $this->assign('abatchNum',      self::getNormalBatchCnt());
        $this->assign('ahd_count',      self::getBatchChart());
    }
    // 营销访问人次
    private function getClickCnt(){
        $map = array();
        $map['node_id']    = $this->nodeId;
        $map['batch_type'] =  array('in',implode(array_keys(self::getBatchType()), ','));
        return M('tmarketing_info')->where($map)->sum('click_count');
    }
    // 营销互动人次
    private function getBatchChart(){
        return M('tmarketing_info')->where(['node_id' => $this->nodeId])->sum('cj_count');
    }
    // 获取粉丝总量
    private function getMembers(){
        return M('tmember_info')->where(['node_id' => $this->nodeId])->count();
    }
    // 获取卡券发放数/验证数
    private function getCardPv($flag){
        $field = 'SUM(send_num) as send_num,sum(verify_num) as verify_num';
        $result = M('tpos_day_count')->field($field)->where(['node_id'=>$this->nodeId])->select();
        if(!empty($result)){
            return $flag ? $result[0]['send_num'] : $result[0]['verify_num'];
        }
        return 0;
    }
    // 已发布，并正在进行的活动，并无付费判断
    public function getNormalBatchCnt(){
        $curTime = date('YmdHis');
        $map = array(
            'm.node_id'    => $this->nodeId,
            'b.node_id'    => $this->nodeId,
            'm.status'     => '1',
            'b.status'     => '1',
            'm.start_time' => array('ELT',$curTime),
            'm.end_time'   => array('EGT',$curTime)
            );
        $batchNum = M()->table("tmarketing_info m")->join('tbatch_channel b ON m.id=b.batch_id')->where($map)->count('DISTINCT m.id');
        return $batchNum;
    }
    // 获取活动类型
    private function getBatchType(){
        $map = array();
        $map['status'] = '1';
        $info = M('tmarketing_active')->field('batch_type,batch_name')->where($map)->select();
        $batchType = array();
        foreach ($info as $v) {
            $batchType[$v['batch_type']] = $v['batch_name'];
            // 只有翼码市场部的可以看到注册有礼
            if($v['batch_type'] == '32' && $this->node_id != '00014056'){
                unset($batchType[$v['batch_type']]);
            }
        }
        return $batchType;
    }

    // 获取PV/UV
    private function getMarketPvUv($requestInfo){
        $map = array();
        $map['node_id'] = $this->nodeId;
        // 按日、周、月查询
        $dayType = get_val($requestInfo,'day_type');
        $dayGroup = $dayType == '2' ? "%Y-%m" : "%Y-%m-%d";
        // 活动类型
        $batchType = get_val($requestInfo,'batch_type');
        $batchTypeArr = self::getBatchType();
        if($batchType){
            $map['batch_type'] =  $batchType;
        }else{
            $map['batch_type'] =  array('in',implode(array_keys($batchTypeArr), ','));
        }
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-30 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['day'][] = array('EGT',$beginTime);
        $map['day'][] = array('ELT',$endTime);

        $field = "SUM(click_count) AS  pv,SUM(uv_count) AS uv,DATE_FORMAT(day,'{$dayGroup}') AS durhour";
        $group = "DATE_FORMAT(day,'{$dayGroup}')";
        $list  = M('tdaystat')->field($field)->where($map)->group($group)->order('id ASC')->select();

        $this->assign('list', $list);
        $this->assign('pvuv',self::dealRes($list));
        $this->assign('batch_type_arr', $batchTypeArr);
        $this->assign('day_type', $dayType);
        $this->assign('batch_type', $batchType);
        $this->assign('begin_time', $beginTime);
        $this->assign('end_time', $endTime);

    }

    // 获取粉丝数据
    private function getMemberPvUv($requestInfo){
        // 按日、周、月查询
        $dayType = get_val($requestInfo,'day_type');
        $dayGroup = $dayType == '2' ? "%Y-%m" : "%Y-%m-%d";
        // 时间区间
        $beginTime = get_val($requestInfo,'begin_time',date('Ymd', strtotime("-30 days")));
        $endTime   = get_val($requestInfo,'end_time',date('Ymd'));
        $wh   = " where node_id = '" . $this->nodeId . "'";
        $whd  = " where day >= '" . $beginTime . "' and day <= '" . $endTime . "'";
        $wh1  = " and day >= '" . $beginTime . "' and day <= '" . $endTime . "'";
        $wh2  = " and trans_date >= '" . $beginTime . "' and trans_date <= '".$endTime . "'";
        $wh3  = " and trans_date >= '" . $beginTime . "' and trans_date <= '".$endTime . "'";

        $sql = "SELECT 
                    d1 AS durhour,
                    ifnull(cj_count,0)      AS pv,
                    ifnull(add_count,0)     AS uv,
                    ifnull(send_count,0)    AS sv 
                FROM
                    (SELECT 
                        DATE_FORMAT(DAY,'{$dayGroup}') AS d1  
                    FROM tday {$whd}  
                    GROUP BY DATE_FORMAT(DAY,'{$dayGroup}')) d 
                LEFT JOIN 
                    (SELECT 
                        SUM(cj_count) AS cj_count,
                        DATE_FORMAT(DAY,'{$dayGroup}') AS t1 
                    FROM tdaystat {$wh} {$wh1} 
                    GROUP BY DATE_FORMAT(DAY,'{$dayGroup}')) a ON  d.d1 = a.t1  
                LEFT JOIN 
                    (SELECT 
                        SUM(add_num) AS add_count,
                        DATE_FORMAT(trans_date,'{$dayGroup}') AS t2 
                    FROM tmember_stat {$wh} {$wh2} 
                    GROUP BY DATE_FORMAT(trans_date,'{$dayGroup}')) b ON  d.d1 = b.t2 
                LEFT JOIN 
                    (SELECT 
                        SUM(send_num) AS send_count,
                        DATE_FORMAT(trans_date,'{$dayGroup}') AS t3 
                    FROM tpos_day_count {$wh} {$wh3} 
                    GROUP BY DATE_FORMAT(trans_date,'{$dayGroup}')) c ON d.d1 =c.t3
                WHERE (cj_count !='' OR add_count !='' OR send_count !='')";
        $list = M()->query($sql);
        
        $this->assign('list',   $list);
        $this->assign('pvuv',self::dealRes($list));
        $this->assign('day_type',   $dayType);
        $this->assign('begin_time', $beginTime);
        $this->assign('end_time',   $endTime);
    }

    private function dealRes($result){
        $pv_arr = array();
        $uv_arr = array();
        $sv_arr = array();
        $hr_arr = array();
        if(!empty($result)){
            foreach ($result as $v) {
                $hr_arr[] = "'" . $v['durhour'] . "'";
                $pv_arr[] = get_val($v,'pv','');
                $uv_arr[] = get_val($v,'uv','');
                $sv_arr[] = get_val($v,'sv','');
            }
        }
        return array(
            'pv' => '['.implode(',', $pv_arr).']',
            'uv' => '['.implode(',', $uv_arr).']',
            'sv' => '['.implode(',', $sv_arr).']',
            'hr' => '['.implode(',', $hr_arr).']',
            );
    }
}
