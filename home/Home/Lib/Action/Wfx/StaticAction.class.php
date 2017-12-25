<?php

class StaticAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }

    public function beforeCheckAuth() {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = "*";
        } elseif (! $this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        }
    }

    public function index() {
        $level = I('level', '1');
        $agency_info = self::getAllAgency($this->node_id, $level);
        $pre_month = date('Ymd', strtotime("-30 day"));
        $cur_month = date('Ymd');
        $start_time = I('start_time', $pre_month);
        $end_time = I('end_time', $cur_month);
        $map['d.node_id'] = $this->node_id;
        $map['s.role'] = 2;
        $map['s.level'] = $level;
        empty($start_time) or $map['d.order_date'][0] = array(
            'EGT', 
            $start_time);
        empty($end_time) or $map['d.order_date'][1] = array(
            'ELT', 
            $end_time);
        $order_info = M()->table("twfx_saler_daystat d")->field(
            's.id AS saler_id,s.name,sum(d.order_count) AS order_amt,sum(d.amount) AS amount_amt,sum(bonus_amount) AS bonus_amt')
            ->where($map)
            ->join('twfx_saler s ON s.id = d.saler_id')
            ->group('s.name')
            ->order('bonus_amt desc')
            ->select();
        if (! empty($order_info)) {
            foreach ($order_info as $k => $v) {
                if ($agency_info[$v['saler_id']]) {
                    unset($agency_info[$v['saler_id']]);
                }
            }
        }
        $new_order_info = array_merge($order_info, $agency_info);
        // 引入分页类
        import('ORG.Util.Page');
        $mapcount = count($new_order_info);
        $CPage = new Page($mapcount, 10);
        $page = $CPage->show();
        $list = array_slice($new_order_info, $CPage->firstRow, $CPage->listRows);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('level', $level);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function loadStatic() {
        $level = I('level', '1');
        $agency_info = self::getAllAgency($this->node_id, $level);
        $start_time = I('start_time', '');
        $end_time = I('end_time', '');
        $level = I('level', '1');
        $map['d.node_id'] = $this->node_id;
        empty($start_time) or $map['d.order_date'][0] = array(
            'EGT', 
            $start_time);
        empty($end_time) or $map['d.order_date'][1] = array(
            'ELT', 
            $end_time);
        $map['s.level'] = $level;
        $map['s.role'] = 2;
        $order_info =M()->table("twfx_saler_daystat d")->field(
            's.id AS saler_id,s.name,sum(d.order_count) AS order_amt,sum(d.amount) AS amount_amt,sum(bonus_amount) AS bonus_amt')
            ->where($map)
            ->join('twfx_saler s ON s.id = d.saler_id')
            ->group('s.name')
            ->order('bonus_amt desc')
            ->select();
        if (! empty($order_info)) {
            foreach ($order_info as $k => $v) {
                if ($agency_info[$v['saler_id']]) {
                    unset($agency_info[$v['saler_id']]);
                }
            }
        }
        $new_order_info = array_merge($order_info, $agency_info);
        if (! empty($new_order_info)) {
            foreach ($new_order_info as $kk => $vv) {
                unset($new_order_info[$kk]['saler_id']);
            }
        }
        $cols_arr = array(
            'name' => '经销商名称', 
            'order_amt' => '分销订单数', 
            'amount_amt' => '分销订单总金额', 
            'bonus_amt' => '提成总金额');
        self::csv_lead("经销商业绩统计表", $cols_arr, $new_order_info);
    }

    public function saler() {
        $saler_info = self::getAllSaler($this->node_id);
        $pre_month = date('Ymd', strtotime("-30 day"));
        $cur_month = date('Ymd');
        $start_time = I('start_time', $pre_month);
        $end_time = I('end_time', $cur_month);
        $map['d.node_id'] = $this->node_id;
        $map['s.role'] = 1;
        empty($start_time) or $map['d.order_date'][0] = array(
            'EGT', 
            $start_time);
        empty($end_time) or $map['d.order_date'][1] = array(
            'ELT', 
            $end_time);
        $order_info = M()->table("twfx_saler_daystat d")->field(
            's.id AS saler_id,s.name,sum(d.order_count) AS order_amt,sum(d.amount) AS amount_amt,sum(bonus_amount) AS bonus_amt')
            ->where($map)
            ->join('twfx_saler s ON s.id = d.saler_id')
            ->group('s.name')
            ->order('bonus_amt desc')
            ->select();
        if (! empty($order_info)) {
            foreach ($order_info as $k => $v) {
                if ($saler_info[$v['saler_id']]) {
                    unset($saler_info[$v['saler_id']]);
                }
            }
        }
        $new_order_info = array_merge($order_info, $saler_info);
        // 引入分页类
        import('ORG.Util.Page');
        $mapcount = count($new_order_info);
        $CPage = new Page($mapcount, 10);
        $page = $CPage->show();
        $list = array_slice($new_order_info, $CPage->firstRow, $CPage->listRows);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('level', $level);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function loadStatic_saler() {
        $saler_info = self::getAllSaler($this->node_id);
        $start_time = I('start_time', '');
        $end_time = I('end_time', '');
        $map['d.node_id'] = $this->node_id;
        empty($start_time) or $map['d.order_date'][0] = array(
            'EGT', 
            $start_time);
        empty($end_time) or $map['d.order_date'][1] = array(
            'ELT', 
            $end_time);
        $map['s.role'] = 1;
        $order_info = M()->table("twfx_saler_daystat d")->field(
            's.id AS saler_id,s.name,sum(d.order_count) AS order_amt,sum(d.amount) AS amount_amt,sum(bonus_amount) AS bonus_amt')
            ->where($map)
            ->join('twfx_saler s ON s.id = d.saler_id')
            ->group('s.name')
            ->order('bonus_amt desc')
            ->select();
        if (! empty($order_info)) {
            foreach ($order_info as $k => $v) {
                if ($saler_info[$v['saler_id']]) {
                    unset($saler_info[$v['saler_id']]);
                }
            }
        }
        $new_order_info = array_merge($order_info, $saler_info);
        if (! empty($new_order_info)) {
            foreach ($new_order_info as $kk => $vv) {
                unset($new_order_info[$kk]['saler_id']);
            }
        }
        $cols_arr = array(
            'name' => '销售员名称', 
            'order_amt' => '分销订单数', 
            'amount_amt' => '分销订单总金额', 
            'bonus_amt' => '提成总金额');
        self::csv_lead("销售员业绩统计表", $cols_arr, $new_order_info);
    }

    public function recruitEffect() {
        $agencyName = I('agencyName');
        $startTime = I('post.startTime', date('Ymd', strtotime('-7 days')));
        $endTime = I('post.endTime', date('Ymd'));
        $map['d.node_id'] = $this->nodeId;
        if (! empty($agencyName)) {
            $map['s.name'] = array(
                'like', 
                '%' . $agencyName . '%');
        }
        $map['d.add_time'] = array(
            array(
                'EGT', 
                $startTime), 
            array(
                'ELT', 
                $endTime));
        $result = M()->table("twfx_channel_daystat d")->field(
            'SUM(d.click_count) AS click_count,SUM(d.apply_count) AS apply_count,SUM(d.trans_count) AS trans_count,s.name AS agencyName')
            ->join('twfx_saler s ON s.id=d.saler_id')
            ->where($map)
            ->group('d.saler_id')
            ->order('click_count desc')
            ->select();
        $allTransInfo = M()->table("twfx_channel_daystat d")->field(
            'SUM(d.trans_count) AS all_trans_count')
            ->join('twfx_saler s ON s.id=d.saler_id')
            ->where($map)
            ->select();
        if (! empty($result)) {
            foreach ($result as $k => $v) {
                if (empty($v['agencyName'])) {
                    $result[$k]['agencyName'] = "其他";
                }
                $result[$k]['transPercent'] = number_format(
                    100 * $v['trans_count'] / $allTransInfo[0]['all_trans_count'], 
                    2);
            }
        }
        // 引入分页类
        import('ORG.Util.Page');
        $mapcount = count($result);
        $CPage = new Page($mapcount, 10);
        $page = $CPage->show();
        $list = array_slice($result, $CPage->firstRow, $CPage->listRows);
        
        $this->assign('list', $list);
        $this->assign('agencyName', $agencyName);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('page', $page);
        $this->display();
    }

    private function getAllAgency($node_id, $level) {
        $agency_info = M('twfx_saler')->where(
            array(
                'node_id' => $node_id, 
                'status' => 3, 
                'role' => 2, 
                'level' => $level))->select();
        $new_agency_info = array();
        if (! empty($agency_info)) {
            foreach ($agency_info as $k => $v) {
                $new_agency_info[$v['id']] = array(
                    'saler_id' => $v['id'], 
                    'name' => $v['name'], 
                    'order_amt' => 0, 
                    'amount_amt' => '0.00', 
                    'bonus_amt' => '0.00');
            }
        }
        return $new_agency_info;
    }

    private function getAllSaler($node_id) {
        $saler_info = M('twfx_saler')->where(
            array(
                'node_id' => $node_id, 
                'status' => 3, 
                'role' => 1))->select();
        $new_saler_info = array();
        if (! empty($saler_info)) {
            foreach ($saler_info as $k => $v) {
                $new_saler_info[$v['id']] = array(
                    'saler_id' => $v['id'], 
                    'name' => $v['name'], 
                    'order_amt' => 0, 
                    'amount_amt' => '0.00', 
                    'bonus_amt' => '0.00');
            }
        }
        return $new_saler_info;
    }

    /**
     * 导出csv格式文件
     *
     * @param $file_name string csv文件名
     * @param $title array() csv头部标题
     * @param $body array() csv内容部分
     */
    public function csv_lead($file_name, $string_head, $string_body) {
        header("Content-type: text/csv; charset=utf-8");
        header("Content-type:   application/octet-stream");
        header("Accept-Ranges:   bytes\r\n\r\n");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $file_name . ".csv");
        // 头部显示文件
        $head_string = "";
        $string = "";
        if ($string_head) {
            if (is_array($string_head)) {
                foreach ($string_head as $key => $value) {
                    $head_string .= $value . ",";
                }
                $head_string = substr($head_string, 0, - 1);
                $head_string .= "\n";
            }
            $head_string = mb_convert_encoding($head_string, 'GBK', 'UTF-8');
            echo $head_string;
        }
        // 需要导出的数据部分
        if (is_array($string_body)) {
            foreach ($string_body as $key => $value) {
                foreach ($value as $k => $v) {
                    $string .= $v . ",";
                }
                $string = substr($string, 0, - 1);
                $string .= "\n";
            }
            $string = mb_convert_encoding($string, 'GBK', 'UTF-8');
            echo $string;
        }
        exit();
    }
}

