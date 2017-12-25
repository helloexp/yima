<?php

class IndexAction extends BaseAction {

    public $_authAccessMap = "*";
    // 权限映射表
    private $mode;

    const BATCH_TYPE_SHOPGPS = 17;

    public function _initialize() {
        parent::_initialize();
        $this->mode = D('TwangPaiPai');
    }
    
    // 判断版本权限
    public function _checkUser() {
        $version_arr = array(
            'v4', 
            'v9');
        $checkUesr = in_array($this->wc_version, $version_arr) ? true : false;
        if (ACTION_NAME == 'index') {
            $checkUesr = 2; // 首页过滤
        }
        return $checkUesr;
    }
    
    // 申请首页
    public function index() {
        $checkUesr = $this->_checkUser();
        $url = $checkUesr ? U('WangPaiPai/Index/apply') : U(
            'Home/Wservice/buyNorWc');
        $this->assign('url', $url);
        $this->display();
    }
    
    // 水牌申请
    public function apply() {
        $batch_id = I('get.batch_id', 0);
        
        if ($this->_post()) {
            $type = $_POST['type'];
            $num = $_POST['num'];
            $use = $_POST['use'];
            $name = $_POST['name'];
            $tel = $_POST['tel'];
            $addre = $_POST['addre'];
            $des = $_POST['des'];
            $province_code = $_POST['province_code'];
            $city_code = $_POST['city_code'];
            $town_code = $_POST['town_code'];
            
            if ($num < 10) {
                $this->error("申请数量最少为10个");
            }
            if (! $name) {
                $this->error("收货人不能为空");
            }
            if (! $tel) {
                $this->error("收货人电话不能为空");
            }
            if (! $addre) {
                $this->error("详细信息不能为空");
            }
            if (! $province_code) {
                $this->error("请选择省市区");
            }
            
            $applyData = array(
                'type' => $type, 
                'num' => $num, 
                'describe' => $use, 
                'delivery_name' => $name, 
                'delivery_phone' => $tel, 
                'delivery_add' => $addre, 
                'city_code' => $province_code . $city_code . $town_code, 
                'node_id' => $this->node_id, 
                'comment' => $des, 
                'add_time' => date('YmdHis'));
            
            M()->startTrans();
            
            if ($batch_id) {
                // 重新编辑
                $where = array(
                    'batch_id' => $batch_id);
                $apply = $this->mode->saveApply($where, $applyData);
                
                if (! $apply) {
                    M()->rollback();
                    $this->error('水牌申请更新失败');
                }
                
                $orderData = array(
                    'batch_id' => $batch_id, 
                    'add_time' => date('YmdHis'));
                
                $order = $this->mode->saveOrder($where, $orderData);
                
                if (! $order) {
                    M()->rollback();
                    $this->error('水牌订单更新失败');
                }
            } else {
                // 添加
                $apply = $this->mode->addApply($applyData);
                if (! $apply) {
                    M()->rollback();
                    $this->error('水牌申请添加失败');
                }
                $batch_id = $apply;
                
                $orderData = array(
                    'batch_id' => $batch_id, 
                    'add_time' => date('YmdHis'), 
                    'count_price' => $num * 18);
                $order = $this->mode->addOrder($orderData);
                if (! $order) {
                    M()->rollback();
                    $this->error('水牌订单添加失败');
                }
            }
            
            M()->commit();
            
            $this->redirect(
                U('WangPaiPai/Index/orderPay', 
                    array(
                        'batch_id' => $batch_id)));
        }
        
        if ($batch_id) {
            $info = $this->mode->getApplyInfo(
                array(
                    's.batch_id' => $batch_id));
            
            $this->assign('info', $info);
        }
        $this->display();
    }
    
    // 付款展示
    public function orderPay() {
        $batch_id = I('batch_id', '');
        $num = I('num', '');
        
        $checkOrder = $this->mode->checkOrder($batch_id, $this->node_id);
        if (! $checkOrder) {
            $this->error("此订单已支付");
        }
        $orderInfo = $this->mode->order($batch_id, $this->nodeId);
        $priceInfo = $this->mode->carriagePrice($batch_id, $this->nodeId, $num);
        
        $this->assign('priceInfo', $priceInfo);
        $this->assign('orderInfo', $orderInfo);
        
        $this->assign('AccountPrice', $this->getAccountInfo());
        $this->display();
    }
    
    // 运费ajax展示
    public function carriagAjax() {
        $batch_id = I('batch_id', '');
        $num = I('num', '');
        
        if ($num < 10) {
            $this->error("申请水牌个数不能小于10个");
        }
        
        $checkOrder = $this->mode->checkOrder($batch_id, $this->nodeId);
        if (! $checkOrder) {
            $this->error("此订单已支付");
        }
        $priceInfo = $this->mode->carriagePrice($batch_id, $this->nodeId, $num);
        
        $this->success($priceInfo);
    }
    
    // 付款提交
    public function orderSumbit() {
        $batch_id = I('batch_id', '');
        $num = I('num', '');
        
        if ($num < 10) {
            $this->error("申请水牌个数不能小于10个");
        }
        
        // 已修改后请求的ajax状态为最后订单最终价格
        $priceInfo = $this->mode->carriagePrice($batch_id, $this->nodeId, $num);
        $order = $this->mode->order($batch_id, $this->nodeId, $num);
        
        if ($order) {
            $resp_array = $this->mode->orderSumbit($batch_id, $this->nodeId, 
                $this->clientId, $priceInfo);
        } else {
            $this->error("订单查询失败");
        }
        
        if (! $resp_array || ($resp_array['Status']['StatusCode'] != '0000')) {
            $resp_desc = "支撑应答：" . $resp_array['Status']['StatusText'];
            
            log_write(print_r($resp_array, true));
            log_write("旺水牌订单付款失败[tsignboard]失败");
            $this->error("订单付款失败:" . $resp_desc, 
                $resp_array['Status']['StatusText']);
        } elseif ('0793' == $resp_array['Status']['StatusCode']) {
            $this->error("余额不足，请充值后再付款");
        }
        
        $resultBool = $this->mode->orderSumbitsave($batch_id);
        
        if ($resultBool) {
            $this->success("支付成功");
        } else {
            $this->error("支付失败");
        }
    }
    
    // 申请列表
    public function applylist() {
        $batchId = I('bId', '');
        $payType = I('pType', '');
        $deliveryType = I('dType', '');
        $export = I('export', 0);
        
        if ('' != $batchId && $batchId > 10000) {
            $batchId -= 10000;
            $where['s.batch_id'] = $batchId;
        }
        
        if ('' != $payType) {
            $where['so.pay_type'] = $payType;
        }
        
        if ('' != $deliveryType) {
            $where['so.delivery_type'] = $deliveryType;
        }
        $where['node_id'] = $this->node_id;
        
        if ($export) {
            $this->mode->down($where);
        }
        
        $info = $this->mode->applyPageInfo($where);
        
        $this->assign('list', $info['list']);
        $this->assign('page', $info['page']);
        $this->display();
    }

    /*
     * public function ywgk() { //dump($this->node_id); $this->display(); }
     */
    
    // 引导页
    public function advancedSetting() {
        $arr = array(
            'guide_flag' => I('flag', 0), 
            'flag_content' => I('title', 0));
        if (IS_POST) {
            $info = $this->mode->advanced(
                array(
                    'node_id' => $this->node_id), $arr);
            
            if ($info) {
                $this->success("更新成功");
            } else {
                $this->error("请修改后再更新");
            }
        } else {
            $info = $this->mode->advancedShow(
                array(
                    'node_id' => $this->node_id), $arr);
            
            $this->assign('info', $info);
            $this->display();
        }
    }
    
    // 激活列表
    public function lists() {
        $name = I('name', '');
        $type = I('type', '');
        // $statusType = I('statusType', '');
        $activeType = I('activeType', '');
        
        if ('' != $name) {
            $where['st.name'] = array(
                'like', 
                $name);
        }
        if ('' != $type) {
            $where['s.type'] = $type;
        }
        /*
         * if ('' != $statusType) { $where['st.status'] = $statusType; }
         */
        if ('' != $activeType) {
            $where['st.activate_type'] = $activeType;
        }
        $where['s.node_id'] = $this->node_id;
        $where['st.delivery_type'] = array(
            'in', 
            '1,2');
        $where['so.delivery_type'] = 2;
        
        $info = $this->mode->activateLists($where);
        
        $this->assign('page', $info['page']);
        $this->assign('list', $info['list']);
        $this->display();
    }
    
    // 申请详情
    public function order_details() {
        $id = I('id', '');
        $info = $this->mode->orderDetails(
            array(
                'batch_id' => $id));
        
        $this->assign('info', $info);
        $this->display();
    }
    
    // 确认收货
    public function order_confirm() {
        $id = I('id', '');
        $info = $this->mode->orderConfirm1(
            array(
                's.batch_id' => $id, 
                's.node_id' => $this->node_id));
        $list = $this->mode->orderConfirm2(
            array(
                'st.batch_id' => $info['batch_id'], 
                'st.delivery_type' => 1));
        
        foreach ($list as $k => $v) {
            $list[$k]['k'] = $k + 1;
        }
        
        $this->assign('info', $info);
        $this->assign('list', $list);
        $this->display();
    }

    public function order_confirm_submit() {
        $id = I('id', '');
        
        if ($id) {
            $result = $this->mode->order_confirm_submit(
                array(
                    'batch_id' => $id));
            if ($result) {
                $this->success("确认收货");
            } else {
                $this->error("确认收货失败");
            }
        } else {
            $this->error("确认收货失败");
        }
    }

    /**
     *
     * @return StoresModel
     */
    private function getStoresModel() {
        if (empty($this->storesModel)) {
            $this->storesModel = D('Stores');
        }
        return $this->storesModel;
    }
    
    // 水牌激活
    public function active() {
        $id = I('id', '');
        
        $nodeIn = $this->nodeIn();
        $storesModel = $this->getStoresModel();
        
        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, "查询成功", 0);
            exit();
        }
        
        $queryList = $storesModel->getAllStore($nodeIn);
        
        $this->assign('id', $id);
        $this->assign('allStores', $queryList);
        
        $this->display();
    }
    
    // 添加营销活动
    private function addbatch() {
        $this->mode->addbatch();
    }
    
    // 激活更新渠道
    public function addChannel() {
        $return = $this->mode->addChannel($this->nodeId);
        
        if ($return) {
            $this->success("激活成功");
        } else {
            $this->error("激活失败");
        }
    }

    public function ok() {
        $this->display();
    }
    
    // 停用
    public function stop() {
        $id = I('id', '');
        
        $status = $this->mode->stopStatus(
            array(
                'make_no' => $id));
        
        if ($status) {
            $this->success("成功");
        } else {
            $this->error("失败");
        }
    }
    
    // 活动详情查询
    public function view() {
        $id = I('id', '');
        
        $this->display();
    }
}
