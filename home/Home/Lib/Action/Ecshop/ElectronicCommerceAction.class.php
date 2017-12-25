<?php

/**
 * 促销管理
 */
class ElectronicCommerceAction extends BaseAction {

    public $BATCH_TYPE = '41';

    public $img_path = "";

    public $tmp_path = "";

    public $goodsInfoModel = '';

    public $_authAccessMap = '*';
    // 权限限制
    public function _initialize() {
        parent::_initialize();
        //调入API接口
        $this-> setApi();
        //实例化对象
        $this->goodsInfoModel = D('GoodsInfo');

        // 判断用户权限
        $hasEcshop = $this->_hasEcshop();
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    /**
     * 电商促销管理介绍页
     */
    public function index() {
        // 是否有红包数据
        $bonusCount = M('tbonus_info')->where(
            array(
                'node_id' => $this->node_id))->count();
        if ($bonusCount > 0) {
            $bonus_jsChartDataClick = array(); // 红包使用量
            $bonus_jsChartDataClick1 = array(); // 定额红包使用量
            $bonus_jsChartDataClick2 = array(); // 随机红包使用量
                                                // 访问量
            $_get = I('get.');
            $_get['begin_date'] = $begin_date = I('begin_date', 
                dateformat("-7 days", 'Ymd'));
            $_get['end_date'] = $end_date = I('end_date', 
                dateformat("0 days", 'Ymd'));
            $statInfo = M()->query(
                "select SUBSTR(use_time,1,8) trans_date,COUNT(*) bonus_count,SUM(order_amt_per) bonus_amt from tbonus_use_detail where node_id='" .
                     $this->node_id . "' and (substr(use_time,1,8) between '" .
                     $begin_date . "' and '" . $end_date .
                     "') and (bonus_num-bonus_use_num)=0 group by SUBSTR(use_time,1,8)");
            foreach ($statInfo as $v) {
                $bonus_jsChartDataClick[$v['trans_date']] = array(
                    $v['trans_date'], 
                    $v['bonus_count'] * 1);
            }
            // 定额红包
            $statInfo1 = M()->query(
                "select SUBSTR(t.use_time,1,8) trans_date,COUNT(t.id) bonus_count,SUM(t.order_amt_per) bonus_amt from tbonus_use_detail t left join tmarketing_info m on m.id=t.m_id where t.node_id='" .
                     $this->node_id . "' and (substr(t.use_time,1,8) between '" .
                     $begin_date . "' and '" . $end_date .
                     "') and (t.bonus_num-t.bonus_use_num)=0 and m.batch_type=47 group by SUBSTR(t.use_time,1,8)");
            foreach ($statInfo1 as $v1) {
                $bonus_jsChartDataClick1[$v1['trans_date']] = array(
                    $v1['trans_date'], 
                    $v1['bonus_count'] * 1);
            }
            // 随机红包
            $statInfo2 = M()->query(
                "select SUBSTR(t.use_time,1,8) trans_date,COUNT(t.id) bonus_count,SUM(t.order_amt_per) bonus_amt from tbonus_use_detail t left join tmarketing_info m on m.id=t.m_id where t.node_id='" .
                     $this->node_id . "' and (substr(t.use_time,1,8) between '" .
                     $begin_date . "' and '" . $end_date .
                     "') and (t.bonus_num-t.bonus_use_num)=0 and m.batch_type=41 group by SUBSTR(t.use_time,1,8)");
            foreach ($statInfo2 as $v2) {
                $bonus_jsChartDataClick2[$v2['trans_date']] = array(
                    $v2['trans_date'], 
                    $v2['bonus_count'] * 1);
            }
            
            // 补齐数据
            foreach ($bonus_jsChartDataClick as $kk => $vv) {
                if (! $bonus_jsChartDataClick1[$kk])
                    $bonus_jsChartDataClick1[$kk] = array(
                        $vv[0], 
                        0);
                if (! $bonus_jsChartDataClick2[$kk])
                    $bonus_jsChartDataClick2[$kk] = array(
                        $vv[0], 
                        0);
            }
            ksort($bonus_jsChartDataClick1);
            ksort($bonus_jsChartDataClick2);
            $nodeDate = array_keys($bonus_jsChartDataClick);
            $this->assign('nodeDate', json_encode($nodeDate));
            $this->assign('_get', $_get);
            $this->assign('bonus_jsChartDataClick', 
                json_encode(array_values($bonus_jsChartDataClick)));
            $this->assign('bonus_jsChartDataClick1', 
                json_encode(array_values($bonus_jsChartDataClick1)));
            $this->assign('bonus_jsChartDataClick2', 
                json_encode(array_values($bonus_jsChartDataClick2)));
            $this->display();
        } else {
            $this->display('noDataIndex');
        }
    }

    /**
     * 满减规则介绍页
     */
    public function ruleindex() {
        import('ORG.Util.Page'); // 导入分页类

        $mapcount = M()->table("tfull_reduce_rule m")->where(
            array(
                'node_id' => $this->node_id, 
                'status' => array(
                    'in', 
                    '1,2')))->count(); // 查询满足要求的总记录数
                                       // 取得总规则
        $Page = new Page($mapcount, 6); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $fullReduceList = M('tfull_reduce_rule')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => array(
                    'in', 
                    '1,2')))
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($fullReduceList) {
            foreach ($fullReduceList as &$val) {
                $val['rule_data'] = json_decode($val['rule_data'], true);
            }
        }
        $this->assign('page', $show); // 赋值分页输出
        $this->assign("fullReduceList", $fullReduceList);
        $this->display();
    }
    
    // 获取页面图片数据
    /**
     * 获取chart数据 供页面ajax提交 $begin 开始时间 $end 结束时间 $type 类型 1红包使用量 2 带动销售额 3 引流
     * 4面额分析
     */
    public function getChartInfo() {
        $days = I('days', 7);
        $type = I('type', 3);
        
        $begin = date('Ymd', strtotime('-' . $days . ' days'));
        $end = date('Ymd');
        $result = $this->_getChartData($begin, $end, $type);
        $return = array(
            'info' => $result['info'], 
            'info1' => $result['info1'], 
            'info2' => $result['info2'], 
            'node_date' => $result['node_date'], 
            'begin' => $begin, 
            'end' => $end);
        $this->ajaxReturn($return, 'json');
    }
    
    // 5红包种类分析
    public function getChartInfo2() {
        $days = I('days', 7);
        
        $begin = date('Ymd', strtotime('-' . $days . ' days'));
        $end = date('Ymd');
        $result = $this->_getChartData($begin, $end, '5');
        $return = array(
            'bonus_count41' => array_values($result['bonus_count41']),  // 随机红包使用量
            'bonus_amt41' => array_values($result['bonus_amt41']),  // 随机红包带动销售额
            'click_count41' => array_values($result['click_count41']),  // 随机红包引流
            'bonus_count47' => array_values($result['bonus_count47']),  // 定额红包使用量
            'bonus_amt47' => array_values($result['bonus_amt47']),  // 定额红包带动销售额
            'click_count47' => array_values($result['click_count47']),  // 定额红包使用量
            'node_date' => $result['node_date'], 
            'begin' => $begin, 
            'end' => $end);
        $this->ajaxReturn($return, 'json');
    }
    
    // 查询按钮 传入开始和结束时间
    public function getChartInfo3() {
        $type = I('type', 3);
        $begin = I('begin');
        $end = I('end');
        if ($type != 5) {
            $result = $this->_getChartData($begin, $end, $type);
            $return = array(
                'info' => $result['info'], 
                'info1' => $result['info1'], 
                'info2' => $result['info2'], 
                'node_date' => $result['node_date'], 
                'begin' => $begin, 
                'end' => $end);
        } else {
            $result = $this->_getChartData($begin, $end, '5');
            $return = array(
                'bonus_count41' => array_values($result['bonus_count41']),  // 随机红包使用量
                'bonus_amt41' => array_values($result['bonus_amt41']),  // 随机红包带动销售额
                'click_count41' => array_values($result['click_count41']),  // 随机红包引流
                'bonus_count47' => array_values($result['bonus_count47']),  // 定额红包使用量
                'bonus_amt47' => array_values($result['bonus_amt47']),  // 定额红包带动销售额
                'click_count47' => array_values($result['click_count47']),  // 定额红包使用量
                'node_date' => $result['node_date'], 
                'begin' => $begin, 
                'end' => $end);
        }
        $this->ajaxReturn($return, 'json');
    }

    /**
     * 获取chart数据 $begin 开始时间 $end 结束时间 $type 类型 1红包使用量 2 带动销售额 3 引流 4面额分析
     * 5红包种类分析
     */
    protected function _getChartData($begin, $end, $type = 0) {
        $chartData = array();
        $chartData1 = array(); // 定额红包
        $chartData2 = array(); // 随机红包
                               // 红包使用量+带动销售额
        if (in_array($type, array(
            '1', 
            '2'))) {
            $statInfo = M()->query(
                "select SUBSTR(use_time,1,8) trans_date,COUNT(*) bonus_count,SUM(order_amt_per) bonus_amt from tbonus_use_detail where node_id='" .
                     $this->node_id . "' and (substr(use_time,1,8) between '" .
                     $begin . "' and '" . $end .
                     "') and (bonus_num-bonus_use_num)=0 group by SUBSTR(use_time,1,8)");
            foreach ($statInfo as $v) {
                if ($type == 1)
                    $chartData[$v['trans_date']] = array(
                        $v['trans_date'], 
                        $v['bonus_count'] * 1);
                elseif ($type == 2)
                    $chartData[$v['trans_date']] = array(
                        $v['trans_date'], 
                        $v['bonus_amt'] * 1);
            }
            // 定额红包
            $statInfo1 = M()->query(
                "select SUBSTR(t.use_time,1,8) trans_date,COUNT(t.id) bonus_count,SUM(t.order_amt_per) bonus_amt from tbonus_use_detail t left join tmarketing_info m on m.id=t.m_id where t.node_id='" .
                     $this->node_id . "' and (substr(t.use_time,1,8) between '" .
                     $begin . "' and '" . $end .
                     "') and (t.bonus_num-t.bonus_use_num)=0 and m.batch_type=47 group by SUBSTR(t.use_time,1,8)");
            foreach ($statInfo1 as $v1) {
                if ($type == 1)
                    $chartData1[$v1['trans_date']] = array(
                        $v1['trans_date'], 
                        $v1['bonus_count'] * 1);
                elseif ($type == 2)
                    $chartData1[$v1['trans_date']] = array(
                        $v1['trans_date'], 
                        $v1['bonus_amt'] * 1);
            }
            // 随机红包
            $statInfo2 = M()->query(
                "select SUBSTR(t.use_time,1,8) trans_date,COUNT(t.id) bonus_count,SUM(t.order_amt_per) bonus_amt from tbonus_use_detail t left join tmarketing_info m on m.id=t.m_id where t.node_id='" .
                     $this->node_id . "' and (substr(t.use_time,1,8) between '" .
                     $begin . "' and '" . $end .
                     "') and (t.bonus_num-t.bonus_use_num)=0 and m.batch_type=41 group by SUBSTR(t.use_time,1,8)");
            foreach ($statInfo2 as $v2) {
                if ($type == 1)
                    $chartData2[$v2['trans_date']] = array(
                        $v2['trans_date'], 
                        $v2['bonus_count'] * 1);
                elseif ($type == 2)
                    $chartData2[$v2['trans_date']] = array(
                        $v2['trans_date'], 
                        $v2['bonus_amt'] * 1);
            }
            
            // 补齐数据
            foreach ($chartData as $kk => $vv) {
                if (! $chartData1[$kk])
                    $chartData1[$kk] = array(
                        $vv[0], 
                        0);
                if (! $chartData2[$kk])
                    $chartData2[$kk] = array(
                        $vv[0], 
                        0);
            }
            ksort($chartData1);
            ksort($chartData2);
        } elseif ($type == 3) {
            // 引流
            /*
             * $channel_id =
             * M('tchannel')->where(array('node_id'=>$this->node_id,'type'=>5,'sns_type'=>array('in','58,59')))->field('id')->select();
             * $channel_arr = implode(',',array_valtokey($channel_id,'','id'));
             * $map = array( 'node_id'=>$this->node_id,
             * 'channel_id'=>array('in',$channel_arr), ); //查询日期 $map['day'] =
             * array(); $map['day'][] = array('EGT',$begin); $map['day'][] =
             * array('ELT',$end); $pv_arr =
             * M('Tdaystat')->where($map)->field("day,sum(click_count)
             * click_count")->group("day")->select(); //计算出JS值 foreach($pv_arr
             * as $v){ $chartData[$v['day']] =
             * array($v['day'],$v['click_count']*1); }
             */
            
            $map = array(
                't.node_id' => $this->node_id, 
                'c.sns_type' => array(
                    'in', 
                    '58,59'));
            
            // 查询日期
            $map['t.day'] = array();
            $map['t.day'][] = array(
                'EGT', 
                $begin);
            $map['t.day'][] = array(
                'ELT', 
                $end);
            $pv_arr = M()->table('tdaystat t')
                ->join('tchannel c on c.id=t.channel_id')
                ->where($map)
                ->field("t.day,c.sns_type,sum(t.click_count) click_count")
                ->group("t.day,c.sns_type")
                ->select();
            // 计算出JS值
            foreach ($pv_arr as $v) {
                $chartData[$v['day']] ? $chartData[$v['day']][1] += $v['click_count'] : $chartData[$v['day']] = array(
                    $v['day'], 
                    $v['click_count'] * 1);
                if ($v['sns_type'] == '58')
                    $chartData1[$v['day']] ? $chartData1[$v['day']][1] += $v['click_count'] : $chartData1[$v['day']] = array(
                        $v['day'], 
                        $v['click_count'] * 1);
                elseif ($v['sns_type'] == '59')
                    $chartData2[$v['day']] ? $chartData2[$v['day']][1] += $v['click_count'] : $chartData2[$v['day']] = array(
                        $v['day'], 
                        $v['click_count'] * 1);
            }
            ksort($chartData);
            foreach ($chartData as $kk => $vv) {
                if (! $chartData1[$kk])
                    $chartData1[$kk] = array(
                        $vv[0], 
                        0);
                if (! $chartData2[$kk])
                    $chartData2[$kk] = array(
                        $vv[0], 
                        0);
            }
            ksort($chartData1);
            ksort($chartData2);
        } elseif ($type == 4) {
            // 4面额分析
            $map = array(
                't.node_id' => $this->node_id);
            $map['SUBSTR(t.use_time,1,8)'] = array();
            if ($begin != '') {
                $map['SUBSTR(t.use_time,1,8)'][] = array(
                    'EGT', 
                    $begin);
            }
            if ($end != '') {
                $map['SUBSTR(t.use_time,1,8)'][] = array(
                    'ELT', 
                    $end);
            }
            $map['_string'] = ' (t.bonus_num-t.bonus_use_num) = 0 ';
            $info = M()->table('tbonus_use_detail t')
                ->join('tbonus_info b on b.id=t.bonus_id')
                ->field(
                'CONCAT(t.bonus_id,b.bonus_page_name) bonus_page_name,SUM(IFNULL(t.order_amt_per,0)) bonus_amt')
                ->where($map)
                ->group('t.bonus_id')
                ->limit('15')
                ->order('SUM(IFNULL(t.order_amt_per,0)) desc')
                ->select();
            foreach ($info as $v) {
                $chartData[$v['bonus_page_name']] = array(
                    $v['bonus_page_name'], 
                    $v['bonus_amt'] * 1);
            }
        } elseif ($type == 5) {
            $node_date = array();
            $chartDatacount41 = array();
            $chartDataamt41 = array();
            $chartDataclick41 = array();
            $chartDatacount47 = array();
            $chartDataamt47 = array();
            $chartDataclick47 = array();
            
            // 随机红包引流量
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => 5, 
                    'sns_type' => '59'))->getField('id');
            $map = array(
                'node_id' => $this->node_id, 
                'channel_id' => $channel_id);
            
            // 查询日期
            $map['day'] = array();
            $map['day'][] = array(
                'EGT', 
                $begin);
            $map['day'][] = array(
                'ELT', 
                $end);
            $pv_arr = M('Tdaystat')->where($map)
                ->field("day,sum(click_count) click_count")
                ->group("day")
                ->select();
            // 计算出JS值
            foreach ($pv_arr as $v) {
                $chartDataclick41[$v['day']] = array(
                    $v['day'], 
                    $v['click_count'] * 1);
                if (! in_array(
                    array(
                        'trans_date' => $v['day']), $node_date))
                    $node_date[] = array(
                        'trans_date' => $v['day']);
            }
            // 随机使用量+销售额
            $statInfo = M()->query(
                "SELECT SUBSTR(t.use_time, 1, 8) trans_date,COUNT(t.id) bonus_count,SUM(t.order_amt_per) bonus_amt from tbonus_use_detail t left JOIN tmarketing_info m ON m.id=t.m_id where t.node_id = '" .
                     $this->node_id . "' AND (SUBSTR(t.use_time, 1, 8) BETWEEN '" .
                     $begin . "' AND '" . $end .
                     "') AND (t.bonus_num - t.bonus_use_num) = 0 AND m.batch_type=41 GROUP BY SUBSTR(t.use_time, 1, 8)");
            foreach ($statInfo as $v) {
                $chartDatacount41[$v['trans_date']] = array(
                    $v['trans_date'], 
                    $v['bonus_count'] * 1);
                $chartDataamt41[$v['trans_date']] = array(
                    $v['trans_date'], 
                    $v['bonus_amt'] * 1);
                if (! in_array(
                    array(
                        'trans_date' => $v['trans_date']), $node_date))
                    $node_date[] = array(
                        'trans_date' => $v['trans_date']);
            }
            
            // 定额红包引流量
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => 5, 
                    'sns_type' => '58'))->getField('id');
            $map = array(
                'node_id' => $this->node_id, 
                'channel_id' => $channel_id);
            
            // 查询日期
            $map['day'] = array();
            $map['day'][] = array(
                'EGT', 
                $begin);
            $map['day'][] = array(
                'ELT', 
                $end);
            $pv_arr = M('Tdaystat')->where($map)
                ->field("day,sum(click_count) click_count")
                ->group("day")
                ->select();
            // 计算出JS值
            foreach ($pv_arr as $v) {
                $chartDataclick47[$v['day']] = array(
                    $v['day'], 
                    $v['click_count'] * 1);
                if (! in_array(
                    array(
                        'trans_date' => $v['day']), $node_date))
                    $node_date[] = array(
                        'trans_date' => $v['day']);
            }
            // 随机使用量+销售额
            $statInfo = M()->query(
                "SELECT SUBSTR(t.use_time, 1, 8) trans_date,COUNT(t.id) bonus_count,SUM(t.order_amt_per) bonus_amt from tbonus_use_detail t left JOIN tmarketing_info m ON m.id=t.m_id where t.node_id = '" .
                     $this->node_id . "' AND (SUBSTR(t.use_time, 1, 8) BETWEEN '" .
                     $begin . "' AND '" . $end .
                     "') AND (t.bonus_num - t.bonus_use_num) = 0 AND m.batch_type=47 GROUP BY SUBSTR(t.use_time, 1, 8)");
            foreach ($statInfo as $v) {
                $chartDatacount47[$v['trans_date']] = array(
                    $v['trans_date'], 
                    $v['bonus_count'] * 1);
                $chartDataamt47[$v['trans_date']] = array(
                    $v['trans_date'], 
                    $v['bonus_amt'] * 1);
                if (! in_array(
                    array(
                        'trans_date' => $v['trans_date']), $node_date))
                    $node_date[] = array(
                        'trans_date' => $v['trans_date']);
            }
            $node_date_t = array_valtokey($node_date, '', 'trans_date');
            sort($node_date_t);
            
            // 补齐数据
            foreach ($node_date as $v) {
                if (! $chartDatacount41[$v['trans_date']])
                    $chartDatacount41[$v['trans_date']] = array(
                        $v['trans_date'], 
                        0);
                if (! $chartDataamt41[$v['trans_date']])
                    $chartDataamt41[$v['trans_date']] = array(
                        $v['trans_date'], 
                        0.00);
                if (! $chartDataclick41[$v['trans_date']])
                    $chartDataclick41[$v['trans_date']] = array(
                        $v['trans_date'], 
                        0);
                if (! $chartDatacount47[$v['trans_date']])
                    $chartDatacount47[$v['trans_date']] = array(
                        $v['trans_date'], 
                        0);
                if (! $chartDataamt47[$v['trans_date']])
                    $chartDataamt47[$v['trans_date']] = array(
                        $v['trans_date'], 
                        0.00);
                if (! $chartDataclick47[$v['trans_date']])
                    $chartDataclick47[$v['trans_date']] = array(
                        $v['trans_date'], 
                        0);
            }
            ksort($chartDatacount41);
            ksort($chartDataamt41);
            ksort($chartDataclick41);
            ksort($chartDatacount47);
            ksort($chartDataamt47);
            ksort($chartDataclick47);
            return array(
                'bonus_count41' => $chartDatacount41, 
                'bonus_amt41' => $chartDataamt41, 
                'click_count41' => $chartDataclick41, 
                'bonus_count47' => $chartDatacount47, 
                'bonus_amt47' => $chartDataamt47, 
                'click_count47' => $chartDataclick47, 
                'node_date' => $node_date_t);
        }
        return array(
            'info' => array_values($chartData), 
            'info1' => array_values($chartData1), 
            'info2' => array_values($chartData2), 
            'node_date' => array_keys($chartData));
    }
    
    // 统计数据导出
    public function export() {
        $type = I('type', 1);
        $begin = I('begin');
        $end = I('end');
        if (! $begin || ! $end)
            $this->error('时间错误');
        if ($type == 1) {
            $sql = "select SUBSTR(use_time,1,8) trans_date,COUNT(*) bonus_count from tbonus_use_detail where node_id='" .
                 $this->node_id . "' and (substr(use_time,1,8) between '" .
                 $begin . "' and '" . $end .
                 "') and (bonus_num-bonus_use_num)=0 group by SUBSTR(use_time,1,8)";
            $cols_arr = array(
                'trans_date' => '日期', 
                'bonus_count' => '红包使用量');
        } elseif ($type == 2) {
            $sql = "select SUBSTR(use_time,1,8) trans_date,SUM(order_amt_per) bonus_amt from tbonus_use_detail where node_id='" .
                 $this->node_id . "' and (substr(use_time,1,8) between '" .
                 $begin . "' and '" . $end .
                 "') and (bonus_num-bonus_use_num)=0 group by SUBSTR(use_time,1,8)";
            $cols_arr = array(
                'trans_date' => '日期', 
                'bonus_amt' => '带动销售额');
        } elseif ($type == 3) {
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => 5, 
                    'sns_type' => array(
                        'in', 
                        '58,59')))
                ->field('id')
                ->select();
            $channel_arr = implode(',', array_valtokey($channel_id, '', 'id'));
            $sql = "select day,sum(click_count) click_count from tdaystat where node_id='" .
                 $this->node_id . "' and (day between '" . $begin . "' and '" .
                 $end . "') and channel_id in (" . $channel_arr .
                 ") group by day";
            $cols_arr = array(
                'day' => '日期', 
                'click_count' => '引流');
        } elseif ($type == 4) {
            $sql = "select CONCAT(t.bonus_id,b.bonus_page_name) bonus_page_name,SUM(IFNULL(t.order_amt_per,0)) bonus_amt from tbonus_use_detail t left join tbonus_info b on b.id=t.bonus_id where t.node_id='" .
                 $this->node_id . "' and (SUBSTR(t.use_time,1,8) between '" .
                 $begin . "' and '" . $end .
                 "') and (t.bonus_num-t.bonus_use_num) = 0 group by t.bonus_id order by SUM(IFNULL(t.order_amt_per,0)) desc";
            $cols_arr = array(
                'bonus_page_name' => '红包名称', 
                'bonus_amt' => '红包面额分析');
        } elseif ($type == 5) {
            $this->error('该统计暂不支持下载');
        }
        
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }

    /**
     * 将2个日期间天数平均分成若干日期节点
     *
     * @param $startDate 开始日期
     * @param $endDate 结束日期
     * @param int $nodeCount 日期节点个数
     * @param string 返回数据的时间格式
     * @return array
     */
    public function formatDateNode($startDate, $endDate, $nodeCount = 5, 
        $format = 'Y-m-d') {
        $begin = strtotime($startDate);
        $end = strtotime($endDate);
        $days = floor(($end - $begin) / (24 * 3600));
        $node = $nodeCount - 1; // 日期节点数
        $dateArr = array(
            date($format, $begin));
        if ($days <= $node) {
            // 一天一个节点
            for ($i = 0; $i < $days; $i ++) {
                $begin += 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        } else {
            $nodeDays = floor($days / $node); // 每个节点之间的天数
            $remainder = $days % $node; // 余数
            for ($i = 0; $i < $node; $i ++) {
                if ($i == $node - 1) {
                    $nodeDays += $remainder;
                }
                $begin += $nodeDays * 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        }
        return $dateArr;
    }

    /**
     * Description of SkuService 满减规则创建
     *
     * @param
     *
     * @return
     *
     * @author john_zeng
     */
    function creteRule() {
        // 获取O2O所有分组 除吴刚砍树
        $o2oClass = C('O2O_CHILD_TYPE.shop');
        $limitArray = array(
            '55', 
            '29'); // 吴刚砍树
        $allType = array();
        foreach ($o2oClass as $val) {
            if (! in_array($val['id'], $limitArray))
                $allType[] = $val;
        }
        $goodsInfo = array();
        $nowTime = date('YmdHis');
        $field = 'm.id as m_id, m.name, b.id as b_id, g.goods_name, ecshop_classify';
        foreach ($allType as $vals) {
            $maps = array(
                'b.end_time' => array(
                    'gt', 
                    $nowTime), 
                'm.node_id' => $this->node_id);
            $key = $vals['id'];
            $maps['m.batch_type'] = $vals['id'];
            $key .= isset($vals['is_new']) ? '_' . $vals['is_new'] : '';
            isset($vals['is_new']) ? $maps['m.is_new'] = $vals['is_new'] : '';
            $goodsInfo[$key] = M()->table("tmarketing_info m")->join(
                'tbatch_info b ON m.id = b.m_id')
                ->join('tgoods_info g ON b.goods_id = g.goods_id')
                ->join('tecshop_goods_ex ex ON m.id = ex.m_id')
                ->where($maps)
                ->field($field)
                ->order('m.id')
                ->select();
            unset($maps);
        }
        // 获取小店分组
        $allGroup = M()->table("tecshop_goods_ex ex")->join(
            'tecshop_classify c ON ex.ecshop_classify = c.id')
            ->where(array(
            'ex.node_id' => $this->node_id))
            ->field('c.id, c.class_name')
            ->order('c.sort')
            ->group('ex.ecshop_classify')
            ->select();
        
        $this->assign('allType', $allType);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('allGroup', $allGroup);
        $this->display();
    }

    /**
     * 满减规则状态修改
     */
    public function ruleChangeStatus() {
        $id = I('id', '', 'trim');
        $status = I('status', '', 'trim');
        if ($status == "" || $id == "") {
            $this->error('参数错误！');
        }
        
        $where = array(
            'id' => $id, 
            'node_id' => $this->node_id);
        
        $data = array(
            'status' => $status);
        $res = M("tfull_reduce_rule")->where($where)->save($data);
        if ($res !== false) {
            $this->success("满减使用规则状态更改成功！");
        } else {
            $this->error("满减使用规则状态更改失败！");
        }
    }

    /**
     * 删除满减规则
     */
    public function ruleDelete() {
        $id = I('id', '', 'trim');
        if (! $id) {
            $this->error('参数错误！');
        }
        $where = array(
            'id' => $id, 
            'node_id' => $this->node_id);
        
        $data = array(
            'status' => '3');
        $res = M("tfull_reduce_rule")->where($where)->save($data);
        if ($res !== false) {
            
            $this->success("满减使用规则状态删除成功！");
        } else {
            $this->error("满减使用规则状态删除失败！");
        }
    }

    /**
     * 电子卷列表页
     */
    public function cardList() {
        $data = [
            'node_id'=> $this-> node_id,
        ];
        $return = Api::get( 'ecshop/sales/index', $data );
        Api::apiReturn( $return );
    }

    /**
     * 电子卷列表页
     */
    public function getCardInfo() {
        $id = I('id', '', 'trim');
        $data = [
            'id' => $id,
            'node_id'=> $this-> node_id,
        ];
        $return = Api::get( 'ecshop/sales/get', $data );
        Api::apiReturn( $return );
    }

    /**
     * 保存电子卷
     */
    public function saveCard() {
        //提交信息验证
        $data = self::checkCardPost();
        //模拟数据
        $data['node_id'] = $this->node_id;
        $return = Api::post( 'ecshop/sales/save', $data );
//        var_dump($return);die;
        Api::apiReturn( $return );
    }

    /**
     * 满减规则展示页
     */
    public function fullList() {
        $data = [
            'node_id'=> $this-> node_id,
        ];
        $return = Api::get( 'ecshop/sales/full', $data );
        Api::apiReturn( $return );
    }

    //检查优惠卷提交信息
    public function checkCardPost(){
        $rules = [
            //电子卡卷基本内容
            'id' => $this->goodsInfoModel->_verifyInfo(false, '卡卷ID'),
            'bonus_name' => $this->goodsInfoModel->_verifyInfo(true, '优惠卷名称', ['maxlen_cn'=>24]),
            'bonus_num' => $this->goodsInfoModel->_verifyInfo(true, '发放总量', ['strtype'=>'number']),
            'bonus_amt' => $this->goodsInfoModel->_verifyInfo(true, '优惠卷面额', ['strtype'=>'number']),
            'use_amt' => $this->goodsInfoModel->_verifyInfo(true, '抵扣金额', ['strtype'=>'number']),
            'goods_type' => $this->goodsInfoModel->_verifyInfo(true, '优惠卷使用范围', ['inarr'=>['0', '1'], 'strtype'=>'int']),
            'begin_date' => $this->GoodsInfoModel->_verifyInfo(true, '优惠卷有效开始时间', ['format'=>'Ymd', 'strtype'=>'datetime']),
            'end_date' => $this->GoodsInfoModel->_verifyInfo(true, '优惠卷有效结束时间', ['format'=>'Ymd', 'strtype'=>'datetime']),
        ];
        //检验数据
        $reqData = $this->_verifyReqData($rules);
        //订单可使用金额限制
        if ($reqData['goods_type'] == 1){
            $rules['goods_list'] = $this->goodsInfoModel->_verifyInfo(false, '优惠卷可以使用商品', ['strtype'=>'string']);
        }

        $reqData = array_merge($reqData, $this->_verifyReqData($rules));

        return $reqData;
    }
}