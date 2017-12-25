<?php

class MhStaticAction extends BaseAction
{
    public $_authAccessMap = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->meiHuiModel = D('FbMhWfx', 'Model');
    }

    public function beforeCheckAuth()
    {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = '*';
        } elseif (!$this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        }
    }

    public function index()
    {
        $name = I('name');
        $agency_info = self::getAllAgency($this->node_id, $name);
        $pre_month = date('Ymd', strtotime('-30 day'));
        $cur_month = date('Ymd');
        $start_time = I('start_time', $pre_month);
        $end_time = I('end_time', $cur_month);
        $map['d.node_id'] = $this->node_id;
        if (!empty($name)) {
            $map['s.name'] = array('like', $name.'%');
        }

        if (!empty($start_time) && !empty($end_time)) {
            $map['d.order_date'][0] = array(
                'EGT',
                $start_time, );
            $map['d.order_date'][1] = array(
                'ELT',
                $end_time, );
        }
        if (!empty($start_time) && empty($end_time)) {
            $map['d.order_date'] = array(
                'EGT',
                $start_time, );
        }
        if (empty($start_time) && !empty($end_time)) {
            $map['d.order_date'] = array(
                'ELT',
                $end_time, );
        }
        $order_info = M()->table('twfx_saler_daystat d')->field(
            's.id AS saler_id,s.name,0 as my_order_amt,0.00 as my_amount_amt, 0 as custom_order_amt,0.00 as custom_amount_amt,sum(d.order_count) AS order_amt,sum(d.amount) AS amount_amt,sum(bonus_amount) AS bonus_amt,s.phone_no')
            ->where($map)
            ->join('twfx_saler s ON s.id = d.saler_id')
            ->group('d.saler_id')
            ->order('bonus_amt desc')
            ->select();

        if (!empty($order_info)) {
            foreach ($order_info as $k => &$v) {
                $my_level = $this->meiHuiModel->getMyLevel($v['saler_id']);
                if (isset($agency_info[$v['saler_id']])) {
                    unset($agency_info[$v['saler_id']]);
                } elseif ((int) $my_level == 1) {
                    unset($agency_info[$v['saler_id']]);
                    unset($order_info[$k]);
                }
            }
        }
        if ($order_info) {
            $new_order_info = array_merge($order_info, $agency_info);
        } else {
            $new_order_info = $agency_info;
        }
        // 引入分页类
        import('ORG.Util.Page');
        $mapcount = count($new_order_info);
        $CPage = new Page($mapcount, 10);
        $page = $CPage->show();
        $list = array_slice($new_order_info, $CPage->firstRow, $CPage->listRows);
        $trace_map = array();
        if (!empty($start_time) && !empty($end_time)) {
            $trace_map['pay_time'][0] = array(
                'EGT',
                $start_time.'000000', );
            $trace_map['pay_time'][1] = array(
                'ELT',
                $end_time.'235959', );
        }
        if (!empty($start_time) && empty($end_time)) {
            $trace_map['pay_time'] = array(
                'EGT',
                $start_time.'000000', );
        }
        if (empty($start_time) && !empty($end_time)) {
            $trace_map['pay_time'] = array(
                'ELT',
                $end_time.'235959', );
        }
        $trace_map['node_id'] = $this->node_id;
        $trace_map['order_type'] = 2;
        foreach ($list as $k => &$v) {
            unset($trace_map['saler_id']);
            $v['my_order_amt'] = M('ttg_order_info')->where(array_merge($trace_map, array('order_phone' => $v['phone_no'], '_string' => 'IFNULL(saler_id, 0) = 0')))->count();
            $v['my_amount_amt'] = round(M('ttg_order_info')->where(array_merge($trace_map, array('order_phone' => $v['phone_no'], '_string' => 'IFNULL(saler_id, 0) = 0')))->getField(' sum(order_amt) as my_amount_amt'), 2);
            $trace_map['saler_id'] = $v['saler_id'];
            $v['custom_order_amt'] = M('ttg_order_info')->where($trace_map)->count();
            $v['custom_amount_amt'] = round(M('ttg_order_info')->where($trace_map)->getField(' sum(order_amt) as my_amount_amt'), 2);
        }

        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('name', $name);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display('meihui/Static_index');
    }

    public function loadStatic()
    {
        $name = I('name');
        $agency_info = self::getAllAgency($this->node_id, $name);
        $pre_month = date('Ymd', strtotime('-30 day'));
        $cur_month = date('Ymd');
        $start_time = I('start_time', $pre_month);
        $end_time = I('end_time', $cur_month);
        $map['d.node_id'] = $this->node_id;
        if (!empty($name)) {
            $map['s.name'] = array('like', $name.'%');
        }

        if (!empty($start_time) && !empty($end_time)) {
            $map['d.order_date'][0] = array(
                'EGT',
                $start_time, );
            $map['d.order_date'][1] = array(
                'ELT',
                $end_time, );
        }
        if (!empty($start_time) && empty($end_time)) {
            $map['d.order_date'] = array(
                'EGT',
                $start_time, );
        }
        if (empty($start_time) && !empty($end_time)) {
            $map['d.order_date'] = array(
                'ELT',
                $end_time, );
        }
        $order_info = M()->table('twfx_saler_daystat d')->field(
            's.id AS saler_id,s.name,0 as my_order_amt,0 as my_amount_amt, 0 as custom_order_amt,0 as custom_amount_amt,sum(d.order_count) AS order_amt,sum(d.amount) AS amount_amt,sum(bonus_amount) AS bonus_amt,s.phone_no')
            ->where($map)
            ->join('twfx_saler s ON s.id = d.saler_id')
            ->group('s.name,d.saler_id')
            ->order('bonus_amt desc')
            ->select();
        $trace_map = array();
        if (!empty($start_time) && !empty($end_time)) {
            $trace_map['pay_time'][0] = array(
                'EGT',
                $start_time.'000000', );
            $trace_map['pay_time'][1] = array(
                'ELT',
                $end_time.'235959', );
        }
        if (!empty($start_time) && empty($end_time)) {
            $trace_map['pay_time'] = array(
                'EGT',
                $start_time.'000000', );
        }
        if (empty($start_time) && !empty($end_time)) {
            $trace_map['pay_time'] = array(
                'ELT',
                $end_time.'235959', );
        }
        $trace_map['node_id'] = $this->node_id;
        $trace_map['order_type'] = 2;
        if (!empty($order_info)) {
            foreach ($order_info as $k => &$v) {
                $my_level = $this->meiHuiModel->getMyLevel($v['saler_id']);
                if (isset($agency_info[$v['saler_id']])) {
                    unset($agency_info[$v['saler_id']]);
                } elseif ((int) $my_level == 1) {
                    unset($agency_info[$v['saler_id']]);
                    unset($order_info[$k]);
                }
            }
        }

        if ($order_info) {
            $new_order_info = array_merge($order_info, $agency_info);
        } else {
            $new_order_info = $agency_info;
        }
        foreach ($new_order_info as $k => &$v) {
            unset($trace_map['saler_id']);
            $v['my_order_amt'] = M('ttg_order_info')->where(array_merge($trace_map, array('order_phone' => $v['phone_no'], '_string' => 'IFNULL(saler_id, 0) = 0')))->count();

            $v['my_amount_amt'] = round(M('ttg_order_info')->where(array_merge($trace_map, array('order_phone' => $v['phone_no'], '_string' => 'IFNULL(saler_id, 0) = 0')))->getField(' sum(order_amt) as my_amount_amt'), 2);
            $trace_map['saler_id'] = $v['saler_id'];
            $v['custom_order_amt'] = M('ttg_order_info')->where($trace_map)->count();
            $v['custom_amount_amt'] = round(M('ttg_order_info')->where($trace_map)->getField(' sum(order_amt) as my_amount_amt'), 2);
            unset($v['phone_no']);
        }
        $cols_arr = array(
            'saler_id' => '会员id',
            'name' => '会员名称',
            'my_order_amt' => '本人订单数',
            'my_amount_amt' => '本人订单金额',
            'custom_order_amt' => '客户订单数',
            'custom_amount_amt' => '客户订单金额',
            'order_amt' => '分销订单数',
            'amount_amt' => '分销订单总金额',
            'bonus_amt' => '提成总金额',
        );
        self::csv_lead('会员业绩统计表', $cols_arr, $new_order_info);
    }

    public function recruitEffect()
    {
        $agencyName = I('agencyName');
        $startTime = I('post.startTime', date('Ymd', strtotime('-30 days')));
        $endTime = I('post.endTime', date('Ymd'));
        $map['d.node_id'] = $this->nodeId;
        if (!empty($agencyName)) {
            $map['s.name'] = array(
                'like',
                '%'.$agencyName.'%', );
        }

        if (!empty($startTime) && !empty($endTime)) {
            $map['d.add_time'][0] = array(
                'EGT',
                $startTime, );
            $map['d.add_time'][1] = array(
                'ELT',
                $endTime, );
        }
        if (!empty($startTime) && empty($endTime)) {
            $map['d.add_time'] = array(
                'EGT',
                $startTime, );
        }
        if (empty($startTime) && !empty($endTime)) {
            $map['d.add_time'] = array(
                'ELT',
                $endTime, );
        }

        $result = M()->table('twfx_channel_daystat d')->field(
            'SUM(d.click_count) AS click_count,SUM(d.apply_count) AS apply_count,SUM(d.trans_count) AS trans_count,s.name AS agencyName')
            ->join('twfx_saler s ON s.id=d.saler_id')
            ->where($map)
            ->group('d.saler_id')
            ->order('click_count desc')
            ->select();
        $allTransInfo = M()->table('twfx_channel_daystat d')->field(
            'SUM(d.trans_count) AS all_trans_count')
            ->join('twfx_saler s ON s.id=d.saler_id')
            ->where($map)
            ->select();
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                if (empty($v['agencyName'])) {
                    $result[$k]['agencyName'] = '其他';
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
        $this->display('meihui/Static_recruitEffect');
    }

    private function getAllAgency($node_id, $name)
    {
        $agency_info = M('twfx_saler')->where(
            array(
                'node_id' => $node_id,
                'status' => 3,
                'level' => array('gt', 1),
                'name' => array('like', $name.'%'), ))->select();
        $new_agency_info = array();
        if (!empty($agency_info)) {
            foreach ($agency_info as $k => $v) {
                $new_agency_info[$v['id']] = array(
                    'saler_id' => $v['id'],
                    'name' => $v['name'],
                    'my_order_amt' => 0,
                    'my_amount_amt' => '0.00',
                    'custom_order_amt' => 0,
                    'custom_amount_amt' => '0.00',
                    'order_amt' => 0,
                    'amount_amt' => '0.00',
                    'bonus_amt' => '0.00',
                    'phone_no' => $v['phone_no'],
                );
            }
        }

        return $new_agency_info;
    }

    private function getAllSaler($node_id)
    {
        $saler_info = M('twfx_saler')->where(
            array(
                'node_id' => $node_id,
                'status' => 3,
                'role' => 1, ))->select();
        $new_saler_info = array();
        if (!empty($saler_info)) {
            foreach ($saler_info as $k => $v) {
                $new_saler_info[$v['id']] = array(
                    'saler_id' => $v['id'],
                    'name' => $v['name'],
                    'phone_no' => $v['phone_no'],
                    'order_amt' => 0,
                    'amount_amt' => '0.00',
                    'bonus_amt' => '0.00', );
            }
        }

        return $new_saler_info;
    }

    /**
     * 导出csv格式文件.
     *
     * @param $file_name string csv文件名
     * @param $title array() csv头部标题
     * @param $body array() csv内容部分
     */
    public function csv_lead($file_name, $string_head, $string_body)
    {
        header('Content-type: text/csv; charset=utf-8');
        header('Content-type:   application/octet-stream');
        header("Accept-Ranges:   bytes\r\n\r\n");
        header('Content-type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename='.$file_name.'.csv');
        // 头部显示文件
        $head_string = '';
        $string = '';
        if ($string_head) {
            if (is_array($string_head)) {
                foreach ($string_head as $key => $value) {
                    $head_string .= $value.',';
                }
                $head_string = substr($head_string, 0, -1);
                $head_string .= "\n";
            }
            $head_string = mb_convert_encoding($head_string, 'GBK', 'UTF-8');
            echo $head_string;
        }
        // 需要导出的数据部分
        if (is_array($string_body)) {
            foreach ($string_body as $key => $value) {
                foreach ($value as $k => $v) {
                    $string .= $v.',';
                }
                $string = substr($string, 0, -1);
                $string .= "\n";
            }
            $string = mb_convert_encoding($string, 'GBK', 'UTF-8');
            echo $string;
        }
        exit();
    }
    public function reward()
    {
        $type = I('type');
        $pre_month = date('Ymd', strtotime('-30 day'));
        $cur_month = date('Ymd');
        $start_time = I('start_time', $pre_month);
        $end_time = I('end_time', $cur_month);
        $name = I('name');
        if (empty($type)) {
            $type = 0;
        }
        if ($type == 0) {
            $trace_map = array();
            if (!empty($name)) {
                $trace_map['name'] = array('like', $name.'%');
            }

            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['audit_time'][0] = array(
                    'EGT',
                    $start_time.'000000', );
                $trace_map['audit_time'][1] = array(
                    'ELT',
                    $end_time.'235959', );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['audit_time'] = array(
                    'EGT',
                    $start_time.'000000', );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['audit_time'] = array(
                    'ELT',
                    $end_time.'235959', );
            }

            $list = M('twfx_saler')->where(array_merge($trace_map, array('status' => 3, 'node_id' => $this->node_id)))->field('id,name')->select();
            unset($trace_map['name']);
            foreach ($list as $k => &$v) {
                $v['static_time'] = $start_time.'-'.$end_time;
                $v['my_count'] = M('twfx_saler')->where(array_merge($trace_map, array('referee_id' => $v['id'], 'parent_id' => $v['id'])))->count();
                $v['my_amount'] = round(M('tfb_mh_wfx_trace')->where(array('trace_month' => array(array('EGT', substr($start_time, 0, 6)), array('ELT', substr($end_time, 0, 6))), 'saler_id' => $v['id'], 'trace_type' => 'A'))->getField('sum(amount) as amount'), 2);
                if ((int) $v['my_count'] == 0 && (int) $v['my_amount'] == 0) {
                    unset($list[$k]);
                }
            }
        } elseif ($type == 1) {
            $trace_map = array();
            $end_time = substr($end_time, 0, 6);
            $start_time = substr($start_time, 0, 6);
            if (!empty($name)) {
                $trace_map['b.name'] = array('like', $name.'%');
            }
            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'][0] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
                $trace_map['a.trace_month'][1] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            $list = M('tfb_mh_wfx_team_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($trace_map)
                ->group('a.saler_id,a.trace_month')
                ->field('a.trace_month,a.trace_config,b.name,a.amount')
                ->select();

            foreach ($list as &$v) {
                $trace_config = json_decode($v['trace_config'], true);

                //$v['team_amount'] = 0;
                if (isset($trace_config['team_all_reward'])) {
                    $v['team_amount'] = $trace_config['team_all_reward'];
                }
            }
        } elseif ($type == 2) {
            $trace_map = array();
            $end_time = substr($end_time, 0, 6);
            $start_time = substr($start_time, 0, 6);
            if (!empty($name)) {
                $trace_map['b.name'] = array('like', $name.'%');
            }
            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'][0] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
                $trace_map['a.trace_month'][1] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            $list = M('tfb_mh_wfx_ranking_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($trace_map)
                ->group('a.saler_id,a.trace_month')
                ->field('a.trace_month,a.ranking,b.name,a.amount')
                ->select();
        }elseif ($type == 3) {
            $trace_map = array();
            $end_time = substr($end_time, 0, 6);
            $start_time = substr($start_time, 0, 6);
            if (!empty($name)) {
                $trace_map['b.name'] = array('like', $name.'%');
            }
            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'][0] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
                $trace_map['a.trace_month'][1] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            $list = M('tfb_mh_wfx_settlement_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($trace_map)
                ->group('a.saler_id,a.trace_month')
                ->field('a.trace_month,b.name,sum(a.amount) as my_amount')
                ->select();
        }
        if ($list) {
            import('ORG.Util.Page');
            $mapcount = count($list);
            $CPage = new Page($mapcount, 10);
            $page = $CPage->show();
            $list = array_slice($list, $CPage->firstRow, $CPage->listRows);
        }

        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->assign('name', $name);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('page', $page);
        $this->display('meihui/Static_reward');
    }
    public function loadStaticRward()
    {
        $type = I('type');
        $pre_month = date('Ymd', strtotime('-30 day'));
        $cur_month = date('Ymd');
        $start_time = I('start_time', $pre_month);
        $end_time = I('end_time', $cur_month);
        $name = I('name');
        if (empty($type)) {
            $type = 0;
        }
        if ($type == 0) {
            $trace_map = array();
            if (!empty($name)) {
                $trace_map['name'] = array('like', $name.'%');
            }

            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['audit_time'][0] = array(
                    'EGT',
                    $start_time.'000000', );
                $trace_map['audit_time'][1] = array(
                    'ELT',
                    $end_time.'235959', );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['audit_time'] = array(
                    'EGT',
                    $start_time.'000000', );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['audit_time'] = array(
                    'ELT',
                    $end_time.'235959', );
            }

            $list = M('twfx_saler')->where(array_merge($trace_map, array('status' => 3, 'node_id' => $this->node_id)))->field('id,name')->select();
            unset($trace_map['name']);
            $data_list = array();
            foreach ($list as &$v) {
                $data_list[] = array(
                    'static_time' => $start_time.'-'.$end_time,
                    'name' => $v['name'],
                    'my_count' => M('twfx_saler')->where(array_merge($trace_map, array('referee_id' => $v['id'])))->count(),
                    'my_amount' => round(M('tfb_mh_wfx_trace')->where(array('trace_month' => array(array('EGT', substr($start_time, 0, 6)), array('ELT', substr($end_time, 0, 6))), 'saler_id' => $v['id'], 'trace_type' => 'A'))->getField('sum(amount) as amount'), 2),
                );
            }
            $cols_arr = array(
                'static_time' => '时间',
                'name' => '会员名称',
                'my_count' => '招募人数',
                'my_amount' => '招募奖金总金额',
            );
            self::csv_lead('招募奖励统计表', $cols_arr, $data_list);
        } elseif ($type == 1) {
            $trace_map = array();
            if (!empty($name)) {
                $trace_map['b.name'] = array('like', $name.'%');
            }
            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'][0] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
                $trace_map['a.trace_month'][1] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            $list = M('tfb_mh_wfx_ranking_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($trace_map)
                ->group('a.saler_id,a.trace_month')
                ->field('a.trace_month,a.ranking,b.name,a.amount')
                ->select();
            $data_list = array();
            foreach ($list as &$v) {
                $trace_config = json_decode($v['trace_config']);
                $data_list[] = array(
                    'trace_month' => $v['trace_month'],
                    'name' => $v['name'],
                    'team_amount' => $trace_config['team_all_reward'],
                    'amount' => $v['amount'],
                );
            }
            $cols_arr = array(
                'trace_month' => '时间',
                'name' => '会员名称',
                'team_amount' => '团队总奖金',
                'amount' => '个人所得奖金',
            );
            self::csv_lead('团队奖励统计表', $cols_arr, $data_list);
        } elseif ($type == 2) {
            $trace_map = array();
            if (!empty($name)) {
                $trace_map['b.name'] = array('like', $name.'%');
            }
            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'][0] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
                $trace_map['a.trace_month'][1] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            $list = M('tfb_mh_wfx_ranking_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($trace_map)
                ->group('a.saler_id,a.trace_month')
                ->field('a.trace_month,a.ranking,b.name,a.amount')
                ->select();
            $data_list = array();
            foreach ($list as $key => $v) {
                $data_list[$key + 1] = array(
                    'trace_month' => $v['trace_month'],
                    'name' => $v['name'],
                    'ranking' => $v['ranking'],
                    'amount' => $v['amount'],
                );
            }
            $cols_arr = array(
                'trace_month' => '时间',
                'name' => '会员名称',
                'ranking' => '奖项',
                'amount' => '奖金',
            );
            self::csv_lead('排名奖励统计表', $cols_arr, $data_list);
        }elseif ($type == 3) {
            $trace_map = array();
            $end_time = substr($end_time, 0, 6);
            $start_time = substr($start_time, 0, 6);
            if (!empty($name)) {
                $trace_map['b.name'] = array('like', $name.'%');
            }
            if (!empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'][0] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
                $trace_map['a.trace_month'][1] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            if (!empty($start_time) && empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'EGT',
                    substr($start_time, 0, 6), );
            }
            if (empty($start_time) && !empty($end_time)) {
                $trace_map['a.trace_month'] = array(
                    'ELT',
                    substr($end_time, 0, 6), );
            }
            $list = M('tfb_mh_wfx_settlement_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($trace_map)
                ->group('a.saler_id,a.trace_month')
                ->field('a.trace_month,b.name,sum(a.amount) as my_amount')
                ->select();
            $data_list = array();
            foreach ($list as $key => $v) {
                $data_list[$key + 1] = array(
                    'trace_month' => $v['trace_month'],
                    'name' => $v['name'],
                    'ranking' => $v['ranking'],
                    'amount' => $v['my_amount'],
                );
            }
            $cols_arr = array(
                'trace_month' => '时间',
                'name' => '会员名称',
                'amount' => '差价提成',
            );
            self::csv_lead('差价提成统计表', $cols_arr, $data_list);
        }

    }
}
