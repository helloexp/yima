<?php

class BserverAction extends BaseAction {

    /**
     * 商家服务
     */
    public $_authAccessMap = '*';

    private $isOpeningStr;

    private $noOpenStr;

    private $openType;

    public function _initialize() {
        parent::_initialize();
        $this->duoMiSendMail();
        $this->assign('isOpeningStr', $this->isOpeningStr);
        $this->assign('noOpenStr', $this->noOpenStr);
        $this->assign('duoMiUserInfo', $this->duoMiUserInfo);
        // $this->assign('Bservermenu',true);
        $this->assign('tophide', 1);
    }

    /**
     *
     * @return JobApplyModel
     */
    private function getJobApplyModel() {
        return D('JobApply');
    }

    /**
     * 获取当前商户 以邮件形式开通支付方式的信息
     */
    public function duoMiSendMail() {
        // 多米收单所有页面的 头部信息的商户信息
        $nodeInfo = M('tnode_info')->field(
            'contact_name, contact_phone, contact_tel')
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        $userInfo = array(
            'userName' => $nodeInfo['contact_name'], 
            'userTel' => $nodeInfo['contact_phone']);
        if (empty($userInfo['userTel'])) {
            $userInfo['userTel'] = $nodeInfo['contact_tel'];
        }
        
        $this->duoMiUserInfo = $userInfo;
        
        // 除付满送的其他4种类型，用来控制页面弹窗的
        $typeArr = array(
            'a' => '.tghx', 
            'b' => '.shfw', 
            'c' => '.yykq', 
            'd' => '.yhkzf');
        
        $jobApplyModel = $this->getJobApplyModel();
        $beenOpenType = $jobApplyModel->getApplyInfo($this->node_id);
        $noOpenType = array_diff_key($typeArr, $beenOpenType);
        $noOpenStr = implode(',', $noOpenType);
        $isOpeningStr = implode(',', array_diff_key($typeArr, $noOpenType));
        $this->isOpeningStr = $isOpeningStr;
        $this->noOpenStr = $noOpenStr;
        $this->openType = $beenOpenType;
    }

    public function index() {
        import('ORG.Util.Page');
        $fromType = array(
            '0' => '支付宝', 
            '1' => '微信', 
            '2' => '翼支付', 
            '5' => 'QQ支付');
        $from_type = I('from_type');
        $this->assign('from_type', $from_type);
        $client = I('client');
        $name = I('name');
        $this->assign('fromType', $fromType);
        $map = array(
            'tsub.node_id' => $this->node_id);
        if (! empty($client))
            $map['ti.client_id'] = array(
                'like', 
                '%' . intval($client) . '%');
        if (! empty($name))
            $map['ti.node_name'] = array(
                'like', 
                '%' . $name . '%');
        if ($from_type != '')
            $map['tp.pay_type'] = $from_type;
        else {
            $map['tp.pay_type'] = array(
                'in', 
                '0,1,2,5');
        }
        $map['tp.status'] = 1;
        $countsql = M()->field(
            'ti.client_id,ti.node_name,GROUP_CONCAT(tp.pay_type) paytypes,tp.open_time,
                        sum(tds.verify_amt) verify_amt,sum(tds.cancel_amt) cancel_amt,
                        sum(tds.fee_amt) fee_amt
                ')
            ->table(
            array(
                'tzfb_offline_shop_relation' => 'tsub', 
                'tzfb_offline_pay_info' => 'tp', 
                'tnode_info' => 'ti'))
            ->join('tzfb_day_stat tds on tds.node_id = ti.node_id')
            ->where($map)
            ->where('ti.node_id=tsub.sub_node_id and tp.node_id=ti.node_id')
            ->group('ti.node_id')
            ->select(false);
        $mapcount = M()->selectSlave()->query(
            'select count(*)  c from (' . $countsql . ')t');
        $Page = new Page($mapcount[0]['c'], 10);
        $list = M()->field(
            'ti.client_id,ti.node_name,GROUP_CONCAT(tp.pay_type) paytypes,tp.open_time,
                        sum(tds.verify_amt) verify_amt,sum(tds.cancel_amt) cancel_amt,
                        sum(tds.fee_amt) fee_amt
                ')
            ->table(
            array(
                'tzfb_offline_shop_relation' => 'tsub', 
                'tzfb_offline_pay_info' => 'tp', 
                'tnode_info' => 'ti'))
            ->join('tzfb_day_stat tds on tds.node_id = ti.node_id')
            ->where($map)
            ->where('ti.node_id=tsub.sub_node_id and tp.node_id=ti.node_id')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->group('ti.node_id')
            ->selectSlave()
            ->select();
        $this->assign('list', $list);
        $show = $Page->show();
        $this->assign('page', $show);
        $this->display();
    }

    public function pos_trace() {
        $arr_ = array(
            '0' => '成功', 
            '1' => '失败', 
            '2' => '成功', 
            '6' => '待确认');
        $arr2_ = array(
            '0' => '已撤销', 
            '1' => '撤销失败', 
            '2' => '已撤销', 
            '6' => '已撤销');
        $arr3_ = array(
            '0' => '成功', 
            '1' => '失败', 
            '2' => '已撤销');
        $type_ = array(
            'T' => '支付', 
            'R' => '退款');
        $fromType = array(
            '100' => '支付宝', 
            '101' => '微信', 
            '102' => '翼支付', 
            '105' => 'QQ支付');
        $client = $_REQUEST['client'];
        $this->assign('client', $client);
        $name = $_REQUEST['name'];
        $this->assign('name', $name);
        $storename = $_REQUEST['storename'];
        $this->assign('storename', $storename);
        $posnumber = I('posnumber');
        $this->assign('posnumber', $posnumber);
        $from_type = I('from_type');
        $this->assign('from_type', $from_type);
        $trade_no = $_REQUEST['trade_no'];
        $this->assign('trade_no', $trade_no);
        $trade_type = $_REQUEST['trade_type'];
        $this->assign('trade_type', $trade_type);
        $zfb_out_pos_seq = $_REQUEST['zfb_out_pos_seq'];
        $this->assign('zfb_out_pos_seq', $zfb_out_pos_seq);
        
        $map = array(
            'T.zfb_trade_no' => array(
                array(
                    'neq', 
                    ''), 
                array(
                    'exp', 
                    'is not null')), 
            // '_string'=>"T.trans_type='T' OR T.trans_type='R'",
            'T.trans_type' => array(
                'in', 
                array(
                    'C', 
                    '1', 
                    'T', 
                    'R')), 
            'tsub.node_id' => $this->node_id);
        if (! empty($client)) {
            $map['ti.client_id'] = array(
                'like', 
                '%' . $client . '%');
        }
        if (! empty($name)) {
            $map['ti.node_name'] = array(
                'like', 
                '%' . $name . '%');
        }
        if (! empty($storename)) {
            $map['ti1.store_name'] = array(
                'like', 
                '%' . $storename . '%');
        }
        if ($posnumber != '') {
            $map['P.pos_id'] = $posnumber;
        }
        if (! empty($trade_no)) {
            $map['zfb_out_trade_no'] = array(
                'like', 
                '%' . $trade_no . '%');
        }
        if (! empty($zfb_out_pos_seq)) {
            $map['zfb_trade_no'] = array(
                'like', 
                '%' . $zfb_out_pos_seq . '%');
        }
        if (I('badd_time')) {
            $badd_time = date('YmdHis', strtotime(I('badd_time')));
        }
        if (I('eadd_time')) {
            $eadd_time = date('YmdHis', strtotime(I('eadd_time')));
        }
        if ($badd_time != '' || $eadd_time != '') {
            $map['T.trans_time'] = array(
                'between', 
                array(
                    $badd_time, 
                    $eadd_time));
        } else {
            $eadd_time = $badd_time = date('Ymd');
            $eadd_time .= '235959';
            $map['T.trans_time'] = array(
                'between', 
                array(
                    $badd_time, 
                    $eadd_time));
        }
        $this->assign('badd_time', date('Y-m-d H:i', strtotime($badd_time)));
        $this->assign('eadd_time', date('Y-m-d H:i', strtotime($eadd_time)));
        if ($trade_type != '') {
            $map['T.trans_type'] = $trade_type;
        }
        if ($from_type != '') {
            $map['T.code_type'] = $from_type;
        }
        $model = M()->table(
            array(
                'tzfb_offline_shop_relation' => 'tsub', 
                'tnode_info' => 'ti', 
                'tzfb_offline_pay_trace' => 'T'))
            ->join("tpos_info P ON T.pos_id=P.pos_id")
            ->join("tstore_info ti1 ON P.store_id=ti1.store_id")
            ->where('T.node_id=ti.node_id and  ti.node_id=tsub.sub_node_id')
            ->where($map)
            ->selectSlave();
        $dataurl = I('request.');
        $listmodel = clone $model;
        $listmodel->field(
            'T.*,ti.client_id,ti.node_name,p.store_name pstorename,ti1.store_name');
        if ($_REQUEST['down'] == 1) {
            $mapcount = $model->count();
            $DOWNLOAD_MAX_COUNT = C('DOWNLOAD_MAX_COUNT') ? C(
                'DOWNLOAD_MAX_COUNT') : 10;
            if ($mapcount > $DOWNLOAD_MAX_COUNT) {
                $this->error('数据量太大,请选择时间区间分批次下载');
            }
            ini_set("max_execution_time", "120");
            $list = $listmodel->select();
            csv_h('条码支付/服务商平台交易明细数据');
            downloadCsvData(
                array(
                    array(
                        "服务商平台交易明细查询", 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "查询起始时间:", 
                        $badd_time ? $badd_time . "\t" : '无', 
                        "查询结束时间:", 
                        $eadd_time ? $eadd_time . "\t" : '无', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "交易方式:", 
                        ($fromType[$from_type] ? $fromType[$from_type] : '不限'), 
                        "\r\n\r\n")));
            downloadCsvData(null, 
                array(
                    '交易流水', 
                    '商户旺号', 
                    '商户名称', 
                    '门店简称', 
                    '交易时间', 
                    '交易金额', 
                    '交易账户', 
                    '支付方式', 
                    '交易类型', 
                    '交易状态', 
                    "\r\n"));
            foreach ($list as $k => $vo) {
                $list_bak['zfb_trade_no'] = $vo['zfb_trade_no'] ? $vo['zfb_trade_no'] .
                     "\t" : '未知';
                $list_bak['zfb_trade_no'] = iconv("UTF-8", "gbk", 
                    $list_bak['zfb_trade_no']);
                $list_bak['client_id'] = $vo['client_id'] ? $vo['client_id'] .
                     "\t" : '未知';
                $list_bak['client_id'] = iconv("UTF-8", "gbk", 
                    $list_bak['client_id']);
                $list_bak['node_name'] = ($vo['node_name'] != '') ? $vo['node_name'] : '未知';
                $list_bak['node_name'] = iconv("UTF-8", "gbk", 
                    $list_bak['node_name']);
                
                $list_bak['store_name'] = ($vo['store_name'] != '') ? $vo['store_name'] : '未知';
                $list_bak['store_name'] = iconv("UTF-8", "gbk", 
                    $list_bak['store_name']);
                $list_bak['trans_date'] = date('Y-m-d H:i', 
                    strtotime($vo['trans_time'])) . "\t";
                $list_bak['trans_date'] = iconv("UTF-8", "gbk", 
                    $list_bak['trans_date']);
                $list_bak['exchange_amt'] = ($vo['exchange_amt'] > 0) ? $vo['exchange_amt'] : "0.00\t";
                $list_bak['exchange_amt'] = iconv("UTF-8", "gbk", 
                    $list_bak['exchange_amt']);
                
                $list_bak['zfb_buyer_logon_id'] = $vo['zfb_buyer_logon_id'] ? $vo['zfb_buyer_logon_id'] : '未知';
                $list_bak['zfb_buyer_logon_id'] = iconv("UTF-8", "gbk", 
                    $list_bak['zfb_buyer_logon_id']);
                $list_bak['code_type'] = $fromType[$vo['code_type']];
                $list_bak['code_type'] = iconv("UTF-8", "gbk", 
                    $list_bak['code_type']);
                
                $list_bak['trans_type'] = $type_[$vo['trans_type']];
                $list_bak['trans_type'] = iconv("UTF-8", "gbk", 
                    $list_bak['trans_type']);
                
                $list_bak['status'] = ($vo['is_canceled'] == 1) ? $arr2_[$vo['status']] : $arr_[$vo['status']];
                $list_bak['status'] = iconv("UTF-8", "gbk", $list_bak['status']);
                $csv_row = trim(implode(",", $list_bak), ',');
                echo $csv_row . "\r\n";
            }
        } else {
            $mapcount = $model->count();
            import('ORG.Util.Page');
            $Page = new Page($mapcount, 10);
            $list = $listmodel->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('T.trans_time desc')
                ->select();
            $show = $Page->show();
            $this->assign('arr_', $arr_);
            $this->assign('arr2_', $arr2_);
            $this->assign('arr3_', $arr3_);
            
            $this->assign('fromType', $fromType);
            // $this->assign('from_type_arr',$from_type_arr);
            $this->assign('type_', $type_);
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->assign('parmes', 
                $_REQUEST + array(
                    'down' => 1));
            $this->display();
        }
    }

    public function day_stat() {
        $fromType = array(
            '0' => '支付宝', 
            '1' => '微信', 
            '2' => '翼支付', 
            '4' => '通联支付', 
            '5' => 'QQ支付');
        $sttype = $_REQUEST['sttype'] ? $_REQUEST['sttype'] : 1;
        $model = M();
        $map = array(
            'T.node_id' => $this->node_id);
        $ttype = 1;
        if ($_REQUEST['ttype'] != '') {
            $ttype = $_REQUEST['ttype'];
        }
        $tvalue = 3;
        if ($_REQUEST['tvalue'] != '') {
            $tvalue = $_REQUEST['tvalue'];
        }
        $name = I('name');
        $this->assign('name', $name);
        $client = I('client');
        $this->assign('client', $client);
        $map = array(
            'tsub.node_id' => $this->node_id);
        $from_type = I('from_type', null);
        if ($from_type != '') {
            $map['T.from_type'] = $from_type;
        }
        if ($name != '') {
            $map['ti.node_name'] = array(
                'like', 
                '%' . $name . '%');
        }
        if ($client != '') {
            $map['ti.client_id'] = array(
                'like', 
                '%' . $client . '%');
        }
        
        if ($ttype == 1) {
            switch ($tvalue) {
                case 1:
                    $badd_time = date('Ymd');
                    $map['T.trans_date'] = array(
                        'egt', 
                        $badd_time);
                    break;
                case 3:
                    $eadd_time = date('Ymd');
                    $badd_time = date('Ymd', strtotime("-7 day"));
                    $map['T.trans_date'] = array(
                        array(
                            'elt', 
                            $eadd_time), 
                        array(
                            'gt', 
                            $badd_time));
                    break;
                case 4:
                    $eadd_time = date('Ymd');
                    $badd_time = date('Ymd', strtotime("-30 day"));
                    $map['T.trans_date'] = array(
                        array(
                            'elt', 
                            $eadd_time), 
                        array(
                            'gt', 
                            $badd_time));
                    break;
                case 2:
                default:
                    $badd_time = date('Ymd', strtotime("-1 day"));
                    $eadd_time = date('Ymd');
                    $map['T.trans_date'] = array(
                        array(
                            'egt', 
                            $badd_time), 
                        array(
                            'lt', 
                            $eadd_time));
                    break;
            }
            $this->assign('badd_time', date('Y-m-d', strtotime($badd_time)));
            $this->assign('eadd_time', date('Y-m-d', strtotime($eadd_time)));
        } else {
            $badd_time = I('badd_time', null);
            $this->assign('badd_time', $badd_time);
            $eadd_time = I('eadd_time');
            $this->assign('eadd_time', $eadd_time);
            if ($badd_time != '' || $eadd_time != '') {
                if ($badd_time == '') {
                    $badd_time = '00000000';
                } else {
                    $badd_time = str_replace('-', '', $badd_time);
                }
                if ($eadd_time == '') {
                    $eadd_time = '99999999';
                } else {
                    $eadd_time = str_replace('-', '', $eadd_time);
                }
                $map['T.trans_date'] = array(
                    'between', 
                    array(
                        $badd_time, 
                        $eadd_time));
            }
        }
        import('ORG.Util.Page');
        $dataurl = I('request.');
        $model = M()->table(
            array(
                'tzfb_offline_shop_relation' => 'tsub', 
                'tnode_info' => 'ti', 
                'tzfb_day_stat' => 'T'))
            ->where('T.node_id=ti.node_id and  ti.node_id=tsub.sub_node_id')
            ->where($map)
            ->selectSlave();
        $otherfield = array();
        if ($sttype == 1) {
            $model->join(
                "left join (select count(P.id)c,P.node_id from tpos_info P,tzfb_offline_shop_relation tsub
                         where P.node_id=tsub.sub_node_id and tsub.node_id='$this->node_id' group by P.node_id)tbak on 
                         tbak.node_id=T.node_id");
            $otherfield = array(
                'tbak.c');
        }
        $model->field(
            array_merge(
                array(
                    'sum(cancel_fee_amt)' => 'cancel_fee_amt', 
                    'sum(verify_fee_amt)' => 'verify_fee_amt', 
                    'sum(verify_cnt)' => 'verify_cnt', 
                    'sum(verify_amt)' => 'verify_amt', 
                    'sum(fee_amt)' => 'fee_amt', 
                    'sum(cancel_cnt)' => 'cancel_cnt', 
                    'sum(cancel_amt)' => 'cancel_amt', 
                    'ti.node_name', 
                    'T.trans_date', 
                    'T.from_type', 
                    'ti.client_id'), $otherfield))->where($map);
        if ($sttype == 2) {
            $model->group('T.trans_date,T.from_type');
        } elseif ($sttype == 3) {
            $model->group('T.from_type');
        } else {
            $model->group('ti.node_id,T.from_type');
        }
        $modelbak = clone $model;
        $mapcount = M()->query(
            'select count(*) c from (' . $model->select(false) . ')t');
        $mapcount = $mapcount[0][c];
        if (! empty($_REQUEST['down']) && $_REQUEST['down'] == 1) {
            $DOWNLOAD_MAX_COUNT = C('DOWNLOAD_MAX_COUNT') ? C(
                'DOWNLOAD_MAX_COUNT') : 10;
            if ($mapcount > $DOWNLOAD_MAX_COUNT) {
                $this->error('数据量太大,请选择时间区间分批次下载');
            }
            ini_set("max_execution_time", "120");
            $list = $modelbak->select();
            $count = 0;
            $count_ = 0;
            $fee_amt = 0;
            $cancel_cnt = 0;
            $cancel_amt = 0;
            $cancel_fee_amt = 0;
            $verify_fee_amt = 0;
            foreach ($list as $k => $vo) {
                $count += $vo['verify_cnt'];
                $count_ += $vo['verify_amt'];
                $fee_amt += $vo['fee_amt'];
                $cancel_cnt += $vo['cancel_cnt'];
                $cancel_amt += $vo['cancel_amt'];
                $cancel_fee_amt += $vo['cancel_fee_amt'];
                $verify_fee_amt += $vo['verify_fee_amt'];
                if ($sttype == 2) {
                    $list_bak[$k]['trans_date'] = date('Y-m-d', 
                        strtotime($vo['trans_date']));
                        $list_bak[$k]['fromType'] = $fromType[$vo['from_type']];
                } else if ($sttype == 3) {
                    $list_bak[$k]['fromType'] = $fromType[$vo['from_type']];
                } else {
                    $list_bak[$k]['client_id'] = $vo['client_id'];
                    $list_bak[$k]['node_name'] = $vo['node_name'];
                    $list_bak[$k]['fromType'] = $fromType[$vo['from_type']];
                    $list_bak[$k]['c'] = $vo['c'];
                }
                $list_bak[$k]['verify_cnt'] = $vo['verify_cnt'];
                $list_bak[$k]['verify_amt'] = $vo['verify_amt'] > 0 ? $vo['verify_amt'] : "0.00\t";
                $list_bak[$k]['cancel_cnt'] = $vo['cancel_cnt'];
                $list_bak[$k]['cancel_amt'] = $vo['cancel_amt'] > 0 ? $vo['cancel_amt'] : "0.00\t";
                /*
                 * $list_bak[$k]['v1'] = round($vo['verify_fee_amt'], 2) > 0 ?
                 * round( $vo['verify_fee_amt'], 2 ) : "0.00\t";
                 * $list_bak[$k]['v2'] = round($vo['cancel_fee_amt'], 2) > 0 ?
                 * round( $vo['cancel_fee_amt'], 2 ) : "0.00\t";
                 */
                $list_bak[$k]['v3'] = ($vo['verify_amt'] - $vo['cancel_amt'] -
                     round($vo['fee_amt'], 2)) > 0 ? (round(
                        $vo['verify_amt'] - $vo['cancel_amt'] -
                         round($vo['fee_amt'], 2), 2)) : "0.00\t";
            }
            if ($sttype == 2) {
                csv_h('条码支付/服务商平台交易按商户统计数据');
            } elseif ($sttype == 3) {
                csv_h('条码支付/服务商平台交易按交易方式分组统计');
            } else {
                csv_h('条码支付/服务商平台交易按日期统计数据');
            }
            downloadCsvData(
                array(
                    array(
                        '交易统计查询', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "查询起始时间:", 
                        $badd_time ? $badd_time : '无', 
                        "查询结束时间:", 
                        $eadd_time ? $eadd_time : '无', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "交易方式:", 
                        ($fromType[$from_type] ? $fromType[$from_type] : '不限'), 
                        "\r\n\r\n")));
            
            if ($sttype == 2) {
                downloadCsvData($list_bak, 
                    array(
                        '日期', 
                        '交易方式', 
                        '支付成功笔数', 
                        '支付金额', 
                        '退款/撤销笔数', 
                        '退款/撤销金额', 
                        '结算金额'));
            } elseif ($sttype == 3) {
                downloadCsvData($list_bak, 
                    array(
                        "交易方式", 
                        '支付成功笔数', 
                        '支付金额', 
                        '退款/撤销笔数', 
                        '退款/撤销金额', 
                        '结算金额'));
            } else {
                downloadCsvData($list_bak, 
                    array(
                        "商户旺号", 
                        '商户名称', 
                        '交易方式', 
                        '接入终端数', 
                        '支付成功笔数', 
                        '支付金额', 
                        '退款/撤销笔数', 
                        '退款/撤销金额', 
                        '结算金额'));
            }
            downloadCsvData(
                array(
                    array(
                        "\r\n\r\n支付笔数:", 
                        $count . '笔', 
                        '共' . ($count_ > 0 ? $count_ : "0.00\t") . '元', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "退款笔数:", 
                        $cancel_cnt . '笔', 
                        '共' . ($cancel_amt > 0 ? $cancel_amt : "0.00\t") . '元', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "交易手续费:", 
                        (round($verify_fee_amt, 2) > 0 ? round($verify_fee_amt, 
                            2) : "0.00\t") . '元', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "退手续费:", 
                        (round($cancel_fee_amt, 2) > 0 ? round($cancel_fee_amt, 
                            2) : "0.00\t") . '元', 
                        "\r\n")));
            downloadCsvData(
                array(
                    array(
                        "结算金额:", 
                        (($count_ - $cancel_amt - round($fee_amt, 2)) > 0 ? (round(
                            $count_ - $cancel_amt - round($fee_amt, 2), 2)) : "0.00\t") .
                             '元', 
                            "\r\n")));
        } else {
            $Page = new Page($mapcount, 10);
            $list = $modelbak->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('T.trans_date desc')
                ->select();
            $show = $Page->show();
            $this->assign('parmes', 
                $_REQUEST + array(
                    'down' => 1));
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->assign('fromType', $fromType);
            $this->assign('from_type', $from_type);
            $this->assign('ttype', $ttype);
            $this->assign('tvalue', $tvalue);
            if ($sttype == 3)
                $this->display('day_stat3');
            elseif ($sttype == 2)
                $this->display('day_stat2');
            else
                $this->display('day_stat1');
        }
    }
}
?>