<?php

/**
 * 权重
 */
class RateDataAction extends BaseAction {
    
    // 营销活动综合排名
    public function index() {
        $requestInfo = I('request.'); // 获取请求数据
        self::getMarketTop($requestInfo,I('get.down'));
        $this->display();
    }
    // 渠道综合排名
    public function channelRateSort(){
        $requestInfo = I('request.'); // 获取请求数据
        self::getChannelTop($requestInfo,I('get.down'));
        $this->display();
    }
    // 卡券综合排名
    public function goodsRateSort() {
        $requestInfo = I('request.'); // 获取请求数据
        self::getCardTop($requestInfo,I('get.down'));
        $this->display();
    }

    /*-----------------------以下是私有方法--------------------*/

    // 获取营销活动排名
    private function getMarketTop($requestInfo,$down){
        // 获取活动类型
        $batchType = get_val($requestInfo,'batchType');
        $batchTypeArr = self::getBatchType();
        $batchTypeStr = $batchType ? $batchType : implode(array_keys($batchTypeArr), ',');
        // 获取查询年份
        $year = get_val($requestInfo,'year');
        $betYear = $year ? $year.'0101000000 AND '.$year.'1231235959' : '20110101000000 AND 20161231235959';
        // 获取排序准则
        $sort = get_val($requestInfo,'sort','1');
        $orderby = 'click_count';
        switch ($sort) {
            case '3':
                $orderby = 'cj_count';
                break;
            case '4':
                $orderby = 'verify_count';
                break;
            default:
                break;
        }
        // 查询条件
        $map = "where a.node_id='".$this->nodeId."' and a.click_count !=0 and a.batch_type in (".$batchTypeStr.") AND (a.start_time BETWEEN ".$betYear." OR a.end_time BETWEEN ".$betYear.')';
        // 子查询
        $sonTable = "(SELECT 
                        a.id 
                    FROM 
                        tmarketing_info a 
                        {$map} 
                    GROUP BY a.id) t";
        // 结果集数量
        $count = M()->query("SELECT COUNT(*) AS cnt FROM {$sonTable} LIMIT 1")[0]['cnt'];
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        // 查询域
        $field = "a.click_count,
            a.cj_count,
            a.send_count AS verify_count, 
            a.id,
            a.name,
            a.batch_type";
        $limit = $down ? "" : "LIMIT {$Page->firstRow},{$Page->listRows}";
        $sql = "SELECT 
                    {$field} 
                FROM 
                    tmarketing_info a 
                    {$map} 
                GROUP BY 
                    a.id
                ORDER BY 
                    {$orderby} DESC 
                {$limit}";
        $list = M()->query($sql);
        if(!empty($list)){
            foreach ($list as $k => $v) {
                $list[$k]['batch_type'] = C('BATCH_TYPE_NAME')[$v['batch_type']];
            }
        }
        if($down){
            $cols_arr = array(
                'name'         => '活动名称', 
                'batch_type'   => '活动类型', 
                'click_count'  => '访问量',
                'cj_count'     => '互动人次',
                'verify_count' => '转换人次'
                );
            parent::csv_lead("活动综合排名".$year,$cols_arr,$list);
        }
        
        $this->assign('batchTypeArr', $batchTypeArr);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list);
        $this->assign('year', $year);
        $this->assign('batchType', $batchType);
        $this->assign('sort', $sort);
    }

    private function getChannelTop($requestInfo,$down){
        // 获取渠道名称
        $channelName = get_val($requestInfo,'channelName');
        $channelMap =  $channelName? " AND a.name like '%".$channelName."%'" : "";
        // 获取查询年份
        $year = get_val($requestInfo,'year');
        $betYear = $year ? $year.'0101000000 AND '.$year.'1231235959' : '20110101000000 AND 20161231235959';
        // 获取排序准则
        $sort = get_val($requestInfo,'sort','1');
        $orderby = 'click_count';
        switch ($sort) {
            case '3':
                $orderby = 'cj_count';
                break;
            case '4':
                $orderby = 'verify_count';
                break;
            default:
                break;
        }
        // 查询条件
        $map   = "where a.node_id='".$this->nodeId."' and a.click_count !=0 ".$channelMap." AND a.add_time BETWEEN ".$betYear;
        // 子查询
        $sonTable = "(SELECT 
                        a.id 
                    FROM 
                        tchannel a 
                        {$map} 
                    GROUP BY a.id) t";
        // 结果集数量
        $count = M()->query("SELECT COUNT(*) AS cnt FROM {$sonTable} LIMIT 1")[0]['cnt'];
        
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        
        $field = "a.name,a.click_count,a.cj_count,a.send_count as verify_count";
        $limit = $down ? "" : "LIMIT {$Page->firstRow},{$Page->listRows}";
        $sql = "SELECT 
                    {$field} 
                FROM 
                    tchannel a 
                    {$map} 
                GROUP BY 
                    a.id
                ORDER BY 
                    {$orderby} DESC 
                {$limit}";
        $list = M()->query($sql);
        if($down){
            $cols_arr = array(
                'name'         => '渠道名称', 
                'click_count'  => '访问量',
                'cj_count'     => '互动人次',
                'verify_count' => '转换人次'
                );
            parent::csv_lead("渠道综合排名",$cols_arr,$list);
        }
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list);
        $this->assign('year', $year);
        $this->assign('channelName', $channelName);
        $this->assign('sort', $sort);
    }

    private function getCardTop($requestInfo,$down){
        // 获取查询年份
        $year = get_val($requestInfo,'year');
        $betYear = $year ? $year.'0101000000 AND '.$year.'1231235959' : '20110101000000 AND 20161231235959';
        // 卡券名称
        $cardName = get_val($requestInfo,'cardName');
        $cardMap  = $cardName ? " and a.goods_name like '%".$cardName."%'" : "";
        // 卡券类型
        $goodsTypeArr = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券');
        $cardType = implode(array_keys($goodsTypeArr), ',');
        // 查询条件
        $map   = "where a.node_id='".$this->nodeId."'".$cardMap." and a.goods_type IN ({$cardType}) and e.node_id='".$this->nodeId."' and a.add_time BETWEEN ".$betYear;
        // 获取排序准则
        $sort = get_val($requestInfo,'sort','1');
        $orderby = 's_num';
        switch ($sort) {
            case '2':
                $orderby = 'v_num';
                break;
            case '3':
                $orderby = 'p_num';
                break;
            default:
                break;
        }
        // 子查询
        $sonTable = "(SELECT 
                        a.id 
                    FROM 
                        tgoods_info a 
                    LEFT JOIN 
                        tpos_day_count e ON e.goods_id= a.goods_id 
                        {$map} 
                    GROUP BY a.goods_id) t";
        // 结果集数量
        $count = M()->query("SELECT COUNT(*) AS cnt FROM {$sonTable} LIMIT 1")[0]['cnt'];
        
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        
        $field = "a.goods_name,SUM(e.verify_num) AS v_num,SUM(e.send_num) AS s_num,concat(truncate(SUM(e.verify_num)/SUM(e.send_num),2)*100,'%') AS p_num";
        $limit = $down ? "" : "LIMIT {$Page->firstRow},{$Page->listRows}";
        $sql = "SELECT 
                    {$field} 
                FROM 
                    tgoods_info a 
                LEFT JOIN 
                    tpos_day_count e ON e.goods_id= a.goods_id 
                    {$map} 
                GROUP BY 
                    a.goods_id
                ORDER BY 
                    {$orderby} DESC 
                {$limit}";
        $list = M()->query($sql);
        if($down){
            $cols_arr = array(
                'goods_name' => '卡券名称', 
                's_num'      => '发码量',
                'v_num'      => '验码量', 
                'p_num'      => '到店率',
                );
            parent::csv_lead("卡券综合排名",$cols_arr,$list);
        }
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list);
        $this->assign('year', $year);
        $this->assign('cardName', $cardName);
        $this->assign('sort', $sort);
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
}
