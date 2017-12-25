<?php

class MyAddressAction extends Action {

    public $seqNumber;

    public function _initialize() {
        $seq = I('get.seq');
        $this->seqNumber = $seq;
        $this->assign('seq', $seq);
    }

    /**
     * 确认收货地址
     */
    public function addressList() {
        $phoneAddressModel = D('TphoneAddress');
        $allAddress = $phoneAddressModel->getAllPhoneAddress();
        $type = I('get.type', 'withDraw', 'string');
        switch ($type) {
            case 'bookOrder':
                $jumpUrl = U('Label/MyOrder/bookOrderDelivery', 
                    array(
                        'order' => $this->seqNumber));
                break;
            case 'wfxBook':
                $jumpUrl = U('Label/WfxShop/showBookOrderList');
                break;
            default:
                $jumpUrl = U('Label/MyAddress/withDrowAddr', 
                    array(
                        'seq' => $this->seqNumber));
                break;
        }
        $this->assign('type', $type);
        $this->assign('count', count($allAddress));
        $this->assign('address', $allAddress);
        $this->assign('manageAddrUrl', 
            U('Label/MyAddress/viewAddressList', 
                array(
                    'seq' => $this->seqNumber)));
        $this->assign('addrUrl', $jumpUrl);
        $this->display();
    }

    /**
     * 收货地址列表
     */
    public function viewAddressList() {
        $phoneAddressModel = D('TphoneAddress');
        $allAddress = $phoneAddressModel->getAllPhoneAddress();
        $node_id = I('get.node_id');
        $this->assign('node_id', $node_id);
        $this->assign('address', $allAddress);
        $addressCount = count($allAddress);
        $this->assign('addressCount', $addressCount);
        $leaveCount = 20 - $addressCount;
        if ($this->seqNumber == '') {
            $this->assign('historyUrl', 
                U('Label/MyOrder/my', 
                    array(
                        'node_id' => $_SESSION['node_id'])));
        } else {
            $this->assign('historyUrl', 
                U('Label/MyAddress/addressList', 
                    array(
                        'seq' => $this->seqNumber)));
        }
        $this->assign('node_id', session('cc_node_id'));
        $this->assign('leaveAddressCount', $leaveCount);
        $this->display();
    }

    /**
     * 添加地址页
     */
    public function addNewAddr() {
        $addrId = I('get.addr');
        if ($addrId) {
            $phoneAddressModel = D('TphoneAddress');
            $lastAddress = $phoneAddressModel->getDefinedPhoneAddress($addrId);
            $this->assign('address', $lastAddress);
        }
        $this->assign('delUrl', 
            U('Label/MyAddress/viewAddressList', 
                array(
                    'seq' => $this->seqNumber)));
        $this->display();
    }

    /**
     * 保存新地址
     */
    public function saveNewAddr() {
        $path = $_POST['privince'] . $_POST['city'] . $_POST['town'];
        if ($path == '') {
            $result['error'] = '3001';
            $result['msg'] = '请选择省市区！';
            $this->ajaxReturn($result);
            exit();
        }
        $addr = I('post.address');
        if ($addr == '') {
            $result['error'] = '4001';
            $result['msg'] = '请填写详细地址！';
            $this->ajaxReturn($result);
            exit();
        }
        $receiver = I('post.receiver');
        if ($receiver == '') {
            $result['error'] = '5001';
            $result['msg'] = '请填写收货人姓名！';
            $this->ajaxReturn($result);
            exit();
        }
        $phone = I('post.connect');
        if (! check_str($phone, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $result['error'] = '6001';
            $result['msg'] = '手机号码：' . $error;
            $this->ajaxReturn($result);
            exit();
        }
        
        $seq = I('post.seq');
        
        $saveData = array();
        $saveData['user_phone'] = $_SESSION['groupPhone'];
        $saveData['user_name'] = $receiver;
        $saveData['phone_no'] = $phone;
        $saveData['address'] = $addr;
        $saveData['add_time'] = date('YmdHis');
        $saveData['path'] = $path;
        
        $phoneAddressModel = M('tphone_address');
        $addressCount = $phoneAddressModel->where(
            array(
                'user_phone' => $_SESSION['groupPhone']))->count();
        if ($addressCount > 19) {
            $result['error'] = '2001';
            $result['msg'] = '您已经存在20条地址数据，请删除后，再创建！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $result = array();
        
        if ($_POST['addr'] != '') {
            $phoneAddressModel->where(
                array(
                    'id' => $_POST['addr'], 
                    'user_phone' => $_SESSION['groupPhone']))->save($saveData);
            $result['error'] = '0';
            $result['msg'] = '修改成功！';
            if ($seq) {
                $result['url'] = U('Label/MyAddress/withDrowAddr', 
                    array(
                        'seq' => $seq, 
                        'addr' => $_POST['addr']));
            } else {
                $result['url'] = U('Label/MyAddress/viewAddressList');
            }
            $this->ajaxReturn($result);
            exit();
        } else {
            $addId = $phoneAddressModel->add($saveData);
            if ($addId) {
                $result['error'] = '0';
                $result['msg'] = '添加成功！';
                if ($seq) {
                    $result['url'] = U('Label/MyAddress/withDrowAddr', 
                        array(
                            'seq' => $seq, 
                            'addr' => $addId));
                } else {
                    $result['url'] = U('Label/MyAddress/viewAddressList');
                }
                $this->ajaxReturn($result);
                exit();
            } else {
                $result['error'] = '1001';
                $result['msg'] = '数据库访问过大，请稍后再试！';
                $this->ajaxReturn($result);
                exit();
            }
        }
    }

    /**
     * 最近使用的地址
     */
    public function addr() {
        $phoneAddressModel = D('TphoneAddress');
        $lastAddress = $phoneAddressModel->getLastPhoneAddress();
        $this->assign('address', $lastAddress);
        $this->display();
    }

    /**
     * 确认提领地址页
     */
    public function withDrowAddr() {
        $coudOnlineVerify = M()->table("tbarcode_trace tt")->field(
            'ti.online_verify_flag')
            ->join('tgoods_info ti ON ti.goods_id = tt.goods_id')
            ->where(array(
            'tt.req_seq' => $this->seqNumber))
            ->find();
        $this->assign('coudOnlineVerify', 
            $coudOnlineVerify['online_verify_flag']);
        
        $addrId = I('get.addr');
        $phoneAddressModel = D('TphoneAddress');
        if ($addrId) {
            $lastAddress = $phoneAddressModel->getDefinedPhoneAddress($addrId);
        } else {
            $lastAddress = $phoneAddressModel->getLastPhoneAddress();
        }
        if (empty($lastAddress) && $coudOnlineVerify['online_verify_flag'] == '1') {
            redirect(
                U('Label/MyAddress/addNewAddr', 
                    array(
                        'seq' => $this->seqNumber)));
        }
        $this->assign('address', $lastAddress);
        $this->display();
    }

    /**
     * 删除地址
     */
    public function delAddress() {
        $addressId = I('post.address');
        $result = array();
        $phoneAddressModel = M('tphone_address');
        $phoneAddressModel->where(
            array(
                'user_phone' => $_SESSION['groupPhone'], 
                'id' => $addressId))->delete();
        $result['error'] = '0';
        $result['msg'] = '删除成功！';
        $this->ajaxReturn($result);
    }

    /**
     * 更新提领券物流信息
     */
    public function getExpressInfo() {
        $seq = I('post.seq');
        $expressInfo = M('tonline_get_order')->where(
            array(
                'req_seq' => $seq))
            ->field('node_id, delivery_number, delivery_company')
            ->order('`id` DESC')
            ->find();
        if (! empty($expressInfo)) {
            $orderNum = $seq;
            if ($_SESSION['node_id'] != '') {
                $nodeId = $_SESSION['node_id'];
            } else {
                $nodeId = $expressInfo['node_id'];
            }
            $expressInfoId = M('torder_express_info')->where(
                array(
                    'node_id' => $nodeId, 
                    'order_id' => $seq))
                ->field('check_time, status')
                ->find();
            if (! empty($expressInfoId)) {
                $time = time() - strtotime($expressInfoId['check_time']);
                if ($expressInfoId['status'] == '1' || $time < 3600) {
                    $result = array(
                        'error' => '0');
                    $this->ajaxReturn($result);
                    exit();
                } else {
                    $isExit = 1;
                }
            } else {
                $isExit = 0;
            }
            if ($expressInfo['delivery_number'] != '') {
                $expNum = str_replace(' ', '', $expressInfo['delivery_number']);
                $expressInfoModel = M('texpress_info');
                $expCom = $expressInfoModel->where(
                    array(
                        'express_name' => array(
                            'like', 
                            '%' . $expressInfo['delivery_company'] . '%')))->getfield(
                    'query_str');
                $type = '1';
                $expressService = D('Express', 'Service');
                $expressService->index($orderNum, $nodeId, $isExit, $expCom, 
                    $expNum, $type);
            }
        }
        $result = array(
            'error' => '0');
        $this->ajaxReturn($result);
    }
}
