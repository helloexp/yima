<?php

/**
 * DF数据中心
 */
class DfStoreDataAction extends BaseAction {

    public $_authAccessMap = '*';

    public $sql_user_store = '';

    public function _initialize() {
        parent::_initialize();
        $new_role_id = M('tuser_info')->where("user_id = '{$this->user_id}'")->getField(
            'new_role_id');
        
        if ($new_role_id != '2') {
            $map = array(
                'param_name' => 'df_store_id', 
                'user_id' => $this->user_id);
            $store_id = M('tuser_param')->where($map)->getField('param_value');
            if ($store_id != '')
                $this->sql_user_store = " and a.store_id = '$store_id'";
        }
    }
    
    // 门店发码验证统计
    Public function storeNumber() {
        import('ORG.Util.Page');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $province_code = I('province_code');
        $city_code = I('city_code');
        $town_code = I('town_code');
        $wh = '';
        if ($start_time != '') {
            $start_time = date('Y-m-d', strtotime($start_time));
            $wh .= " and c.trans_date > $start_time";
        }
        if ($end_time != '') {
            $end_time = date('Y-m-d', strtotime($end_time));
            $wh .= " and c.trans_date < $end_time";
        }
        if ($province_code != '')
            $wh .= " and a.province_code = $province_code";
        if ($city_code != '')
            $wh .= " and a.city_code = $city_code";
        if ($town_code != '')
            $wh .= " and a.town_code = $town_code";
        
        $sql = "SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id
				WHERE a.node_id in (" . $this->nodeIn() . ")
				{$this->sql_user_store}
				GROUP BY a.store_id
				ORDER BY  fcount DESC limit 10";
        $result = M()->query($sql);
        
        // $storeCount=M('tstore_info s')->WHERE("s.node_id in
        // (".$this->nodeIn().") $wh")->COUNT();
        $storeCountsql = "SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh
				{$this->sql_user_store}
				GROUP BY a.store_id
				ORDER BY  fcount DESC";
        $storeCount = M()->query($storeCountsql);
        $storeCount = count($storeCount);
        $Page = new Page($storeCount, 8);
        $pageShow = $Page->show();
        
        $listsql = "SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh
				{$this->sql_user_store}
				GROUP BY a.store_id
				ORDER BY  fcount DESC limit " .
             $Page->firstRow . ',' . $Page->listRows;
        $listresult = M()->query($listsql);
        
        $storeSendMaNumber = array();
        $storeSendMaName = array();
        foreach ($result as $v) {
            $storeSendMaNumber[] = (int) $v['fcount'];
            $storeSendMaName[] = $v['store_name'];
        }
        
        $this->assign('_chartData', 
            json_encode(
                array(
                    'storeSendMaNumber' => $storeSendMaNumber, 
                    'storeSendMaName' => $storeSendMaName)));
        $this->assign('pageShow', $pageShow);
        $this->assign('listresult', $listresult);
        $this->display('DfStoreData/storeNumber');
    }
    
    // 门店活跃度
    Public function static_active() {
        $where = array(
            '_string' => "a.node_id in (" . $this->nodeIn() .
                 ") {$this->sql_user_store}");
        
        import('ORG.Util.Page');
        
        $sql = "(SELECT type,store_id,SUM(click_count) as ccount,COUNT(0) AS fcount,sum(z) bcount FROM tchannel c
    			LEFT JOIN (SELECT z,channel_id FROM (SELECT channel_id,COUNT(0) AS z FROM tbatch_channel b where b.node_id in (" .
             $this->nodeIn() . ") GROUP BY b.channel_id ) r) as f ON c.id=f.channel_id
   				WHERE c.type=2 and c.node_id in (" . $this->nodeIn() .
             ") GROUP BY c.store_id)";
        
        $count = M()->table('tstore_info a')
            ->field(
            'a.store_name,a.province_code,a.city_code,a.town_code,IFNULL(fcount,0),IFNULL(bcount,0),IFNULL(ccount,0)')
            ->join("LEFT JOIN $sql e ON a.store_id=e.store_id ")
            ->where($where)
            ->count();
        $Page = new Page($count, 12);
        $pageShow = $Page->show();
        
        $list = M()->table('tstore_info a')
            ->field(
            'a.store_name,a.province_code,a.province,a.city,a.town,a.city_code,a.town_code,IFNULL(fcount,0) fcount,IFNULL(bcount,0) bcount, IFNULL(ccount,0) ccount')
            ->join("LEFT JOIN $sql e ON a.store_id=e.store_id ")
            ->where($where)
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // echo M()->_sql();
        $this->assign('page', $pageShow);
        $this->assign('list', $list);
        $this->display('DfStoreData/static_active');
    }

    Public function storeNumber2() {
        import('ORG.Util.Page');
        $start_time2 = I('start_time2');
        $end_time2 = I('end_time2');
        $province_code2 = I('province_code2');
        $city_code2 = I('city_code2');
        $town_code2 = I('town_code2');
        $wh2 = '';
        if ($start_time2 != '') {
            $start_time2 = date('Y-m-d', strtotime($start_time2));
            $wh2 .= " and c.trans_date > '$start_time2'";
        }
        if ($end_time2 != '') {
            $end_time2 = date('Y-m-d', strtotime($end_time2));
            $wh2 .= " and c.trans_date < '$end_time2'";
        }
        if ($province_code2 != '')
            $wh2 .= " and a.province_code = $province_code2";
        if ($city_code2 != '')
            $wh2 .= " and a.city_code = $city_code2";
        if ($town_code2 != '')
            $wh2 .= " and a.town_code = $town_code2";
        
        $verifySql = "SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a 
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in (" . $this->nodeIn() . ")
				{$this->sql_user_store}
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC limit 10";
        $verifyResult = M()->query($verifySql);
        
        $storeCountsql = "SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a 
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh2
				{$this->sql_user_store}
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC";
        
        $storeCount2 = M()->query($storeCountsql);
        $storeCount2 = count($storeCount2);
        C('VAR_PAGE', 'pr');
        $P = new Page($storeCount2, 8);
        $pShow = $P->show();
        
        $listverifySql = "SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a 
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh2
				{$this->sql_user_store}
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC limit " .
             $P->firstRow . ',' . $P->listRows;
        $listverifyResult = M()->query($listverifySql);
        
        $storeVerifyMaNumber = array();
        $storeVerifyMaName = array();
        foreach ($verifyResult as $v) {
            $storeVerifyMaNumber[] = (int) $v['scount'];
            $storeVerifyMaName[] = $v['store_name'];
        }
        
        $this->assign('_chartData', 
            json_encode(
                array(
                    'storeVerifyMaNumber' => $storeVerifyMaNumber, 
                    'storeVerifyMaName' => $storeVerifyMaName)));
        $this->assign('pShow', $pShow);
        $this->assign('listverifyResult', $listverifyResult);
        $this->display('DfStoreData/storeNumber2');
    }
}
