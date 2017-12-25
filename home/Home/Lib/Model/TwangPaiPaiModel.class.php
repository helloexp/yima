<?php

/**
 *
 * @author zc Time 201508001
 */

/* 旺拍拍 */
class TwangPaiPaiModel extends BaseModel {

    private $tsignboard;
    // 申请
    private $tsignboard_trace;
    // 流水
    private $tsignboard_order;
    // 订单
    private $tsignboard2;

    private $tsignboard_trace2;

    const BATCH_TYPE_SHOPGPS = 17;

    private $typeArr = array(
        '1' => '青春版', 
        '2' => '青奢版');

    private $deliveryArr = array(
        '0' => '未配送', 
        '1' => '配送中', 
        '2' => '已配送');

    private $activateArr = array(
        '0' => '未激活', 
        '1' => '激活');

    private $statusArr = array(
        '0' => '正常', 
        '1' => '停用');

    private $typeWeightArr = array(
        '1' => 0.15, 
        '2' => 0.18);
    // 重量
    private $priceOne = array(
        '1' => 18, 
        '2' => 18);
    // 水牌种类价格
    private $payArr = array(
        '0' => '未支付', 
        '1' => '已支付');
    // 水牌种类价格
    private $wc_node_id = '00000000';

    public function _initialize() {
        import('ORG.Util.Page'); // 导入分页类
        $this->tsignboard = M("tsignboard");
        $this->tsignboard2 = M('tsignboard');
        $this->tsignboard_trace = M("tsignboard_trace");
        $this->tsignboard_trace2 = M('tsignboard_trace');
        $this->tsignboard_order = M('tsignboard_order');
        $this->tsignboard_order2 = M("tsignboard_order");
    }

    /**
     * 旺拍拍申请
     *
     * @param array $data
     * @return bool
     */
    public function addApply($data = array()) {
        $result = $this->tsignboard2->add($data);
        
        return $result;
    }

    /**
     * 旺拍拍申请
     *
     * @param array $data
     * @param array $where
     * @return string $result
     */
    public function saveApply($where = array(), $data = array()) {
        $result = $this->tsignboard2->where($where)->save($data);
        
        return $result;
    }

    /**
     * 申请订单重新编辑
     *
     * @param array $where
     * @return array $info
     */
    public function getApplyInfo($where = array()) {
        $info = $this->tsignboard->alias("s")->where($where)->find();
        
        return $info;
    }

    /**
     * 旺拍拍添加订单
     *
     * @param array $data
     * @return string $result
     */
    public function addOrder($data = array()) {
        $result = $this->tsignboard_order->add($data);
        
        return $result;
    }

    /**
     * 旺拍拍更新订单
     *
     * @param array $data
     * @param array $where
     * @return bool
     */
    public function saveOrder($where = array(), $data = array()) {
        $result = $this->tsignboard_order->where($where)->save($data);
        
        return $result;
    }

    /**
     * 旺拍拍生成单个流水
     *
     * @param array $data
     * @return bool
     */
    public function addApplyTrace($data = array()) {
        $result = $this->tsignboard_trace2->add($data);
        
        return $result;
    }

    /**
     * 水牌下载表单
     */
    public function down($where = array()) {
        $fileName = '旺水牌申请报表.csv';
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title_ = "申请单号, 水牌型号, 申请单状态, 申请数量, 支付状态, 支付金额\r\n";
        $title_ = iconv('utf-8', 'gbk', $title_);
        echo $title_;
        
        $list = $this->tsignboard->alias("s")->join(
            "tsignboard_order so on so.batch_id=s.batch_id")
            ->field(
            "s.batch_id,s.num,s.type,so.delivery_type,so.pay_type,so.total_price")
            ->where($where)
            ->order('s.add_time Desc')
            ->select();
        
        if ($list) {
            foreach ($list as $k => $value) {
                $list[$k]['batch_id2'] = 10000 + $value['batch_id'];
                $list[$k]['type2'] = $this->typeArr[$value['type']];
                // $list[$k]['check_status2'] =
                // $this->checkArr[$value['check_status']];
                $list[$k]['delivery_type2'] = $this->deliveryArr[$value['delivery_type']];
                $list[$k]['total_price2'] = $value['pay_type'] ? $value['total_price'] : '/';
                
                echo iconv('utf-8', 'gbk', $list[$k]['batch_id2']) . ", ";
                echo iconv('utf-8', 'gbk', $list[$k]['type2']) . ", ";
                // echo iconv('utf-8', 'gbk', $list[$k]['check_status2']) . ",
                // ";
                echo iconv('utf-8', 'gbk', $list[$k]['delivery_type2']) . ", ";
                echo iconv('utf-8', 'gbk', $list[$k]['num']) . ", ";
                echo iconv('utf-8', 'gbk', $this->payArr[$value['pay_type']]) .
                     ", ";
                echo iconv('utf-8', 'gbk', $list[$k]['total_price2']) . "\r\n";
            }
        }
        exit();
    }

    /**
     * 申请表分页信息
     *
     * @param array $where sql条件语句
     * @return array $info
     */
    public function applyPageInfo($where = array()) {
        $count = $this->tsignboard->alias("s")->join(
            "tsignboard_order so on so.batch_id=s.batch_id")
            ->where($where)
            ->count();
        
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $list = $this->tsignboard->alias("s")->join(
            "tsignboard_order so on so.batch_id=s.batch_id")
            ->field(
            "s.batch_id,s.num,s.type,so.delivery_type,so.pay_type,so.total_price")
            ->where($where)
            ->order('s.add_time Desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        foreach ($list as $k => $v) {
            $list[$k]['batch_id2'] = 10000 + $v['batch_id'];
            $list[$k]['type2'] = $this->typeArr[$v['type']];
            $list[$k]['delivery_type2'] = $this->deliveryArr[$v['delivery_type']];
            $list[$k]['pay_type2'] = $this->payArr[$v['pay_type']];
            $list[$k]['total_price2'] = $v['pay_type'] ? $v['total_price'] : '/';
        }
        
        return $info = array(
            'page' => $pageShow, 
            'list' => $list);
    }

    /**
     * 关注引导页提交
     *
     * @param $array $where
     * @param $array $arr
     * @return bool
     */
    public function advanced($where = array(), $arr = array()) {
        $result = $this->tsignboard2->where($where)->save($arr);
        
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 关注引导页展示
     *
     * @param $array $where
     * @return string $info
     */
    public function advancedShow($where = array()) {
        $info = $this->tsignboard->alias("s")->where($where)->find();
        
        return $info;
    }

    /**
     * 激活列表
     *
     * @param array $where
     * @return array
     */
    public function activateList($where = array()) {
        $count = $this->tsignboard->alias("s")->where($where)->count();
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出

        $list = M()->table("tsignboard_trace st")->join(
            'tsignboard s on st.batch_id=s.batch_id')
            ->join('tchannel c on st.channel_id=c.id')
            ->
        // ->join('tstore_info si on c.store_id = si.store_id')
        join('tbatch_channel bc on bc.channel_id=c.id')
            ->where($where)
            ->field(
            'c.name, c.address, s.batch_id, s.type, st.status,st.activate_type, st.make_no, bc.click_count')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('st.add_time Desc')
            ->select();
        
        foreach ($list as $k => $v) {
            $info[$k]['batch_id2'] = $v['batch_id'] + 10000;
            $info[$k]['make_no2'] = $v['make_no'] + 10000000000;
            $info[$k]['type2'] = $this->typeArr[$v['type']];
            $info[$k]['activate_type2'] = $this->activateArr[$v['activate_type']];
            $info[$k]['status'] = $this->statusArr[$v['status']];
        }
        
        return $info = array(
            'page' => $pageShow, 
            'list' => $list);
    }

    /**
     * 申请单详情
     *
     * @param array $where
     * @return array
     */
    public function orderDetails($where = array()) {
        $info = $this->tsignboard->alias("s")->where($where)->find();
        
        $info['batch_id2'] = 10000 + $info['batch_id'];
        $info['type2'] = $this->typeArr[$info['type']];
        $info['add_time'] = substr($info['add_time'], 0, 4) . '-' .
             substr($info['add_time'], 4, 2) . '-' .
             substr($info['add_time'], 6, 2);
        // $info['check_status2'] = $this->checkArr[$info['check_status']];
        $info['delivery_type2'] = $this->deliveryArr[$info['delivery_type']];
        
        return $info;
    }

    /**
     * 整体确认收货
     *
     * @param array $where
     * @return array
     */
    public function orderConfirm1($where = array()) {
        $info = $this->tsignboard_order2->alias("so")->join(
            'tsignboard s on s.batch_id=so.batch_id')
            ->where($where)
            ->find();
        
        $info['batch_id2'] = 10000 + $info['batch_id'];
        $info['type2'] = $this->typeArr[$info['type']];
        $info['add_time'] = substr($info['add_time'], 0, 4) . '-' .
             substr($info['add_time'], 4, 2) . '-' .
             substr($info['add_time'], 6, 2);
        $info['delivery_type2'] = $this->deliveryArr[$info['delivery_type']];
        
        return $info;
    }

    /**
     * 单个确认收货2
     *
     * @param array $where
     * @return array
     */
    public function orderConfirm2($where = array()) {
        $info = $this->tsignboard_trace->alias("st")->where($where)->select();
        
        return $info;
    }

    /**
     * 确认收货
     *
     * @param array $where
     * @return array
     */
    public function order_confirm_submit($where = array()) {
        $info = $this->tsignboard_order->where($where)->save(
            array(
                'delivery_type' => 2));
        if ($info) {
            return $info;
        }
    }

    /**
     * 激活
     *
     * @param array $where
     * @return array
     */
    public function active($where = array()) {
        $queryList = M()->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('a.*,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->select();
        
        return $queryList;
    }

    /**
     *
     * @param string $node_id 激活添加渠道
     * @return bool
     */
    public function addChannel($node_id) {
        $model = M('tchannel');
        $data = array_map('trim', I('post.'));
        
        $id = I('id', 0);
        
        $data_arr['store_id'] = $data['store_id'];
        $data_arr['name'] = $data['name'];
        $data_arr['type'] = 2;
        $data_arr['sns_type'] = 27;
        $data_arr['address'] = $data['addre'];
        $data_arr['add_time'] = date('YmdHis');
        $data_arr['node_id'] = $node_id;
        $data_arr['status'] = '1';
        $data_arr['province_code'] = $data['province'];
        $data_arr['city_code'] = $data['city'];
        $data_arr['town_code'] = $data['town'];
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        
        $execute = $model->where(
            array(
                'node_id' => '00000000', 
                'label_id' => $id))->save($data_arr);
        
        if (! $execute) {
            $tranDb->rollback();
            log_write('渠道添加失败' . M()->_sql());
        }
        
        $dataTrace = array(
            'name' => $data['name'], 
            'activate_type' => '1', 
            'activate_time' => date("YmdHis"), 
            'address' => $data['addre']);
        
        $update = M('tsignboard_trace')->where(
            array(
                'make_no' => $id))->save($dataTrace);
        
        if (! $update) {
            $tranDb->rollback();
            $this->error('激活失败');
            log_write('激活失败' . M()->_sql());
        }
        
        $tranDb->commit();
        return true;
    }

    /**
     *
     * @param array $where
     * @return array $info
     */
    public function activateLists($where = array()) {
        $count = $this->tsignboard->alias("s")->join(
            'tsignboard_trace st on s.batch_id=st.batch_id')
            ->join('tsignboard_order so on s.batch_id=so.batch_id')
            ->where($where)
            ->count();
        
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $list = $this->tsignboard->alias("s")->join(
            'tsignboard_trace st on s.batch_id=st.batch_id')
            ->join('tsignboard_order so on s.batch_id=so.batch_id')
            ->where($where)
            ->order('s.add_time Desc')
            ->field(
            's.batch_id,st.make_no,st.activate_type,st.name,s.type,st.address')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        foreach ($list as $k => $v) {
            $list[$k]['batch_id2'] = $v['batch_id'] + 10000;
            $list[$k]['type2'] = $this->typeArr[$v['type']];
            $list[$k]['activate_type2'] = $this->activateArr[$v['activate_type']];
            $list[$k]['status'] = $this->statusArr[$v['status']];
        }
        
        return $info = array(
            'page' => $pageShow, 
            'list' => $list);
    }

    /**
     * 添加营销活动
     *
     * @return array
     */
    public function addbatch() {
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_SHOPGPS);
        $batch_data = $batch_model->where($onemap)->find();
        if (! $batch_data) {
            // 营销活动不存在则新增
            $batch_arr = array(
                'batch_type' => self::BATCH_TYPE_SHOPGPS, 
                'name' => '门店导航默认营销活动', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $query = $batch_model->add($batch_arr);
            if (! $query) {
                $this->error('添加门店导航默认营销活动失败');
            }
            return array(
                'batch_id' => $query, 
                'batch_name' => '门店导航默认营销活动', 
                'click_count' => 0);
        } else {
            return array(
                'batch_id' => $batch_data['id'], 
                'batch_name' => $batch_data['name'], 
                'click_count' => $batch_data['click_count'], 
                'wap_title' => $batch_data['wap_title']);
        }
    }

    /**
     * 停用此水牌
     *
     * @param array $where
     * @return bool
     */
    public function stopStatus($where) {
        $tmp = $this->tsignboard_trace2->where($where)->getField('status');
        $tmp = $tmp ? 0 : 1;
        $status = $this->tsignboard_trace2->where($where)->save(
            array(
                'status' => $tmp));
        
        if ($status) {
            return true;
        }
    }

    /**
     * 商品及运费价格
     *
     * @param string $batch_id
     * @param string $node_id
     * @param string $num 个数
     * @return array price
     */
    public function carriagePrice($batch_id, $node_id, $num = 0) {
        $orderInfo = $this->order($batch_id, $node_id, $num);
        
        if (! $orderInfo) {
            return false;
        }
        
        $num = $num ? $num : $orderInfo['num'];
        
        $priceOne = $this->priceOne[$orderInfo['type']];
        $priceCount = $priceOne * $num;
        
        $weight = $this->typeWeightArr[$orderInfo['type']];
        $weightCount = $weight * $num;
        
        $Service = D('Shipping', 'Service');
        if (strlen($orderInfo['city_code']) > 5) {
            $city_code = substr($orderInfo['city_code'], 0, 5);
        }
        
        $region = $Service->index($this->wc_node_id, $city_code, 
            (int) ceil($weightCount));
        
        // 运费计算异常
        if ('10001' == $region['errorCode']) {
            $region = 0;
        }
        if ('20001' == $region['errorCode']) {
            $region = 0;
        }
        
        $region['price'] ? $region = $region['price'] : $region;
        $total = (int) $priceCount + (int) $region;
        
        $priceResult = array(
            'region_price' => (int) $region, 
            'count_price' => $priceCount, 
            'total_price' => $total);
        
        // 更新运费，水牌价格，支付价格
        $data = array(
            'total_price' => $priceResult['total_price'], 
            'region_price' => $priceResult['region_price'], 
            'count_price' => $priceResult['count_price']);
        
        $price = $this->tsignboard_order->where(
            array(
                'batch_id' => $batch_id, 
                'node_id' => $node_id))->find();
        
        // 总金额/运费/水牌金额 有改动时更新
        if ($data['total_price'] != $price['total_price'] ||
             $data['region_price'] != $price['region_price'] ||
             $data['count_price'] != $price['count_price']) {
            $result = $this->tsignboard_order->where(
                array(
                    'batch_id' => $batch_id, 
                    'node_id' => $node_id))->save($data);
            
            if (! $result) {
                return false;
            }
        }
        
        return $priceResult;
    }

    /**
     * 订单校验（是否已支付）
     *
     * @param $batch_id
     * @param $node_id
     * @return bool
     */
    public function checkOrder($batch_id, $node_id) {
        $orderPay = $this->tsignboard_order2->alias("so")->join(
            'tsignboard s on s.batch_id = so.batch_id')
            ->where(
            array(
                's.batch_id' => $batch_id, 
                's.node_id' => $node_id, 
                'so.pay_type' => '1'))
            ->select();
        
        return $orderPay ? false : true;
    }

    /**
     * 订单查询
     *
     * @param string $batch_id
     * @param string $node_id
     * @param string $num
     * @return array $info
     */
    public function order($batch_id, $node_id, $num) {
        // 存在数量变动，更新申请单数量
        if ($num) {
            $result = $this->tsignboard->alias("s")->where(
                array(
                    'batch_id' => $batch_id, 
                    'node_id' => $node_id))->save(
                array(
                    'num' => $num));
        }
        $info = $this->tsignboard_order2->alias("so")->join(
            'tsignboard s on s.batch_id = so.batch_id')
            ->where(
            array(
                's.batch_id' => $batch_id, 
                's.node_id' => $node_id))
            ->find();
        
        return $info;
    }

    /**
     * 提交订单
     *
     * @param string $batch_id
     * @param string $node_id
     * @param array $priceArr
     * @return bool $resp_array array( 'TransactionId' => '凭证发送单号', array(
     *         'Status' => array( 'StatusCode' => '成功为0000，非0000失败,0793为余额不足',
     *         'StatusText' => '处理应答解释 详见：返回码说明', ), 'SID' => 'sid', );
     */
    public function orderSumbit($batch_id, $node_id, $client_id, $priceArr) {
        $data = array(
            'total_price' => $priceArr['total_price'], 
            'region_price' => $priceArr['region_price'], 
            'count_price' => $priceArr['count_price']);
        
        $price = $this->tsignboard_order->where(
            array(
                'batch_id' => $batch_id, 
                'node_id' => $node_id))->find();
        M()->startTrans();
        // 总金额/运费/水牌金额 有改动时更新
        if ($data['total_price'] != $price['total_price'] ||
             $data['region_price'] != $price['region_price'] ||
             $data['count_price'] != $price['count_price']) {
            $result = $this->tsignboard_order->where(
                array(
                    'batch_id' => $batch_id, 
                    'node_id' => $node_id))->save($data);
            if (! $result) {
                return false;
            }
        }
        
        $RemoteRequest = D('RemoteRequest', 'Service');
        
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   
        // 请求参数
        $req_array = array(
            'TransactionId' => $TransactionID, 
            'ClientId' => $client_id, 
            'NodeId' => $node_id, 
            'ActivityId' => '', 
            'FeeType' => '4', 
            'RuleType' => '82', 
            'TraceType' => '6002', 
            'Price' => $priceArr['total_price'], 
            'Remark' => '旺拍拍接口备注');
        
        $resp_array = $RemoteRequest->requestYzServ(
            array(
                'DeductEVReq' => $req_array));
        
        if (! $resp_array || ($resp_array['Status']['StatusCode'] != '0000')) {
            M()->rollback();
        } elseif ('0793' == $resp_array['Status']['StatusCode']) {
            M()->rollback();
        }
        M()->commit();
        
        return $resp_array;
    }

    /**
     *
     * @param string $batch_id
     * @param string $node_id
     * @return bool $resultBool
     */
    public function orderSumbitsave($batch_id) {
        $resultBool = $this->tsignboard_order->where(
            array(
                'batch_id' => $batch_id))->save(
            array(
                'pay_type' => '1', 
                'pay_time' => date('YmdHis')));
        
        return $resultBool;
    }
}