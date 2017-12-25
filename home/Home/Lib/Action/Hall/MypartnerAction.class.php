<?php
//
class MypartnerAction extends BaseAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        // 介绍页判断
        // if(GROUP_NAME == 'Hall' && MODULE_NAME=='Mypartner' &&
        // ACTION_NAME=='index'){
        // $tsaleRelationModel = M('tsale_relation');
        // $supplierNum =
        // $tsaleRelationModel->where("relation_node_id='{$this->nodeId}' AND
        // check_status<>'1'")->count();//我是分销商未确认
        // $supplierCheckNum =
        // $tsaleRelationModel->where("relation_node_id='{$this->nodeId}' AND
        // check_status='1'")->count();//我是分销商已确认
        // $distributorNum =
        // $tsaleRelationModel->where("node_id='{$this->nodeId}' AND
        // check_status<>'1'")->count();//我是供应商未确认
        // $distributorCheckedNum =
        // $tsaleRelationModel->where("node_id='{$this->nodeId}' AND
        // check_status='1'")->count();//我是供应商已确认
        // $checkType = I('check_type',null);
        // $jumpUrl = U('Hall/Mypartner/index');
        // if($checkType === '1'){
        // if($supplierNum > 0) $jumpUrl =
        // U('Hall/Mypartner/supplierList',array('tab'=>'no_check'));
        // if($supplierCheckNum > 0) $jumpUrl =
        // U('Hall/Mypartner/supplierList');
        // if($distributorNum > 0) $jumpUrl = U('Hall/Mypartner/notIndex');
        // if($distributorCheckedNum > 0) $jumpUrl = U('Hall/Mypartner/index');
        // redirect($jumpUrl);
        // }else{
        // if($distributorNum > 0) $jumpUrl = U('Hall/Mypartner/notIndex');
        // if($distributorCheckedNum == 0 && $distributorNum > 0){
        // redirect($jumpUrl);
        // }
        // }
        
        // }
        /*
         * if($_POST['apply_type']!=2){ $check_user=$this->_hasEcshop();
         * if(!$check_user){ $this->assign('type',2);
         * $this->assign('check_c1',$this->_hasStandard());
         * $this->display('introduction'); die; } }
         */
        
        // $this->assign('check_user',$check_user==true?1:2);
    }
    // 主页，已确认的
    public function index() {
        import("ORG.Util.Page");
        $map = array(
            's.node_id' => $this->node_id);
        // 's.check_status'=>array(array('eq','1'),array('eq','4'),'or')
        
        $nodename = I('node_name');
        $seachtype = I('seachType');
        $control_type = I('control_type');
        $control_flag = I('control_flag');
        $begin_time = I("begin_time");
        $end_time = I("end_time");
        $time = date("YmdHis");
        if ($seachtype === '0') {
            $map['s.end_time'] = array(
                'egt', 
                $time);
            $map['s.status'] = '0';
        }
        if ($seachtype == 1) {
            $map['s.end_time'] = array(
                'lt', 
                $time);
            $map['s.status'] = '0';
        }
        if ($seachtype == 2) {
            $map['s.status'] = 1;
        }
        if ($control_type != '') {
            $map['control_type'] = $control_type;
        }
        if ($control_flag != '') {
            $map['control_flag'] = $control_flag;
        }
        
        if ($begin_time != '') {
            $begin_time .= "000000";
            $map['begin_time'] = array(
                'egt', 
                $begin_time);
        }
        if ($end_time != '') {
            $end_time .= "235959";
            $map['end_time'] = array(
                'elt', 
                $end_time);
        }
        
        // 按名称查询
        if ($nodename != '') {
            $map['n.node_name'] = array(
                'like', 
                "%{$nodename}%");
        }
        $count = M()->table("tsale_relation s")->where($map)
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->count();
        $p = new Page($count, 6);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tsale_relation s')
            ->where($map)
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $contrlClass = array(
            '0' => '否', 
            '1' => '是');
        $batchClass = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量');
        $statusClass = array(
            '0' => '正常', 
            '1' => '过期', 
            '2' => '取消');
        $page = $p->show();
        $this->assign('contrl', $contrlClass);
        $this->assign('batch', $batchClass);
        $this->assign('statusClass', $statusClass);
        $this->assign('page', $page);
        $this->assign('post', $_REQUEST);
        $this->assign('list', $list);
        $this->display();
    }
    
    // 未确认的
    public function notIndex() {
        import("ORG.Util.Page");
        $type = I("type");
        $whe = array(
            's.node_id' => $this->node_id, 
            's.check_status' => array(
                array(
                    'eq', 
                    '0'), 
                array(
                    'eq', 
                    '2'), 
                array(
                    'eq', 
                    '3'), 
                'or'));
        $node_name = I("node_name");
        $status = I("seachType");
        $begin_time = I("begin_time");
        $end_time = I("end_time");
        $start_time = I("start_time");
        $finish_time = I("finish_time");
        if ($node_name != '') {
            $whe['n.node_name'] = array(
                'like', 
                "%{$node_name}%");
        }
        $thetime = time() - (60 * 60 * 24 * 3);
        $thetime = date('YmdHis', $thetime);
        if ($status != '') {
            switch ($status) {
                case 0:
                    $whe['s.check_status'] = array(
                        'eq', 
                        '0');
                    break;
                case 2:
                    $whe['s.check_status'] = array(
                        'eq', 
                        '2');
                    break;
                case 3:
                    $whe['s.check_status'] = array(
                        'eq', 
                        '3');
                    break;
            }
        }
        if ($begin_time != '') {
            $begin_time .= "000000";
            $whe['s.begin_time'] = array(
                'egt', 
                $begin_time);
        }
        if ($end_time != '') {
            $end_time .= "235959";
            $whe['s.end_time'] = array(
                'lt', 
                $end_time);
        }
        if ($start_time != '' && $finish_time == '') {
            $start_time .= "000000";
            $whe['s.add_time'] = array(
                'egt', 
                $start_time);
        }
        if ($finish_time != '' && $start_time == '') {
            $finish_time .= "235959";
            $whe['s.add_time'] = array(
                'elt', 
                $finish_time);
        }
        if ($start_time != '' && $finish_time != '') {
            $start_time .= "000000";
            $finish_time .= "235959";
            $whe['s.add_time'] = array(
                array(
                    'egt', 
                    $start_time), 
                array(
                    'lt', 
                    $finish_time));
        }
        $count = M()->table('tsale_relation s')
            ->where($whe)
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->count();
        $p = new Page($count, 6);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $list = M()->table('tsale_relation s')
            ->where($whe)
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->limit($p->firstRow . "," . $p->listRows)
            ->order('s.add_time desc')
            ->select();
        $status_class = array(
            '0' => '未失效', 
            '2' => '已拒绝', 
            '3' => '已失效');
        $this->assign('statusClass', $status_class);
        $this->assign('page', $page);
        $this->assign('type', $type);
        $this->assign('post', $_REQUEST);
        $this->assign('list', $list);
        $this->display();
    }
    
    // 从我的分销商中分销
    public function firstDistri() {
        $relation_id = I("relationid");
        // $check_status=I("check_status");
        $relaList = M('tsale_relation')->where(
            "node_id=$this->node_id and relation_node_id='{$relation_id}'")->find();
        $saleList = M()->table("tsale_node_relation a")->where(
            "a.node_id='{$this->node_id}'")
            ->join('tnode_info b on a.relation_node_id=b.node_id')
            ->getField('b.node_id,b.node_name');
        $this->assign('saleList', $saleList);
        $this->assign('voucher_node_id', $relation_id);
        $this->assign('relaList', $relaList);
        $this->display();
    }
    
    // 分销，选择卡券
    public function chooseVou() {
        $model = M('tgoods_info');
        $map = array(
            'node_id' => $this->node_id, 
            'goods_type' => array(
                'in', 
                '1,2'), 
            'source' => array(
                'eq', 
                0), 
            'batch_no' => array(
                'exp', 
                'IS NOT NULL'), 
            'status' => 0);
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('add_time DESC')
            ->select();
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('post', $_REQUEST);
        $this->display(); // 输出模板
    }
    
    // 保存选择的代金券
    public function getGoodsInfo() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$goodsId}' AND status=0")->find();
        if (! $goodsInfo)
            $this->error('未找到该代金券信息');
        $goodsInfo['begin_time'] = dateformat($goodsInfo['begin_time'], 'Y-m-d');
        $goodsInfo['end_time'] = dateformat($goodsInfo['end_time'], 'Y-m-d');
        if ($goodsInfo['goods_image'] != '')
            $goodsInfo['goods_image'] = get_upload_url(
                $goodsInfo['goods_image']);
        $this->ajaxReturn($goodsInfo, '', 1);
    }
    
    // 编辑分销商
    public function edit() {
        $nodeid = $this->node_id;
        $relationid = I('relationid');
        // $check_status=I('check_status');
        $list = M()->table('tsale_relation s')
            ->where(
            array(
                's.relation_node_id' => $relationid, 
                's.node_id' => $nodeid))
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->find();
        $this->assign("list", $list);
        $this->display();
    }
    
    // 延期
    public function touptime() {
        $relation_node_id = I('relationid');
        // $check_status=I("check_status");
        $list = M('tsale_relation')->where(
            array(
                'relation_node_id' => $relation_node_id, 
                'node_id' => $this->node_id))->find();
        $this->assign('list', $list);
        $this->display();
    }
    // 合作关系详情
    public function details() {
        $nodeid = $this->node_id;
        $relationid = I('relationid');
        // $check_status=I('check_status');
        $list = M()->table('tsale_relation s')
            ->where(
            array(
                's.relation_node_id' => $relationid, 
                's.node_id' => $nodeid))
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->find();
        $this->assign("list", $list);
        // $this->assign('lasted',$check_status);
        $this->display();
    }
    
    // 删除
    public function deleteMp() {
        $relationid = I("relationid");
        $stat = M()->table('tsale_relation')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'relation_node_id' => $relationid, 
                'check_status' => 0))
            ->delete();
        if ($stat) {
            $this->success("删除成功！", 
                U('Mypartner/index', 
                    array(
                        'type' => '0')));
        } else {
            $this->error("删除失败！", 
                U('Mypartner/index', 
                    array(
                        'type' => '0')));
        }
    }
    // 保存修改(编辑)
    public function saveUpda() {
        // $check_status=I('check_status');
        $b_contact_name = I("b_contact_name");
        $b_contact_phone = I("b_contact_phone");
        $a_contact_name = I("a_contact_name");
        $a_contact_phone = I("a_contact_phone");
        $relation_node_id = I('relaid');
        $begin_time = I("begin_time");
        $end_time = I("end_time");
        $control_type = I("qsfs");
        $control_flag = I("gkbz");
        $reladata = M('tsale_relation')->where(
            array(
                'relation_node_id' => $relation_node_id, 
                'node_id' => $this->nodeId))->find();
        if ($control_flag == 1) {
            $bail = I('bail');
            // $max_amt=I('maxamt');
            $warning_amt = I('warning_amt');
            $party_a_phone = I('myphone');
            $party_b_phone = I('partphone');
            $data = array(
                'control_flag' => '1');
            if ($bail >= 500) {
                $data['bail'] = $bail;
            } else {
                $this->error("预付费金额不得低于500元！", 
                    U('Mypartner/edit/', 
                        array(
                            'relationid' => $relation_node_id)));
            }
            // if(!is_null($max_amt)){
            // if($max_amt>=500){
            // $data['max_amt']=$max_amt;
            // }else{
            // $this->error('峰值不能小于500元！',U('Mypartner/edit/',array('relationid'=>$relation_node_id)));
            // }
            // }
            // if($warning_amt!=='' && $bail===''){
            // if($warning_amt<=$reladata['max_amt']){
            // $data['warning_amt']=$warning_amt;
            // }else{
            // $this->error("预警金额必须小于峰值！",U('Mypartner/edit/',array('relationid'=>$relation_node_id)));
            // }
            // }
            if ($warning_amt !== '' && $bail !== '') {
                if ($warning_amt <= $bail) {
                    $data['warning_amt'] = $warning_amt;
                } else {
                    $this->error('预警金额不得大于预付费金额！', 
                        U('Mypartner/edit/', 
                            array(
                                'relationid' => $relation_node_id)));
                }
            }
            if ($party_a_phone != '') {
                if (! check_str($party_a_phone, 
                    array(
                        'strtype' => 'mobile'), $error)) {
                    $this->error("电话号码{$error}", 
                        U('Mypartner/edit/', 
                            array(
                                'relationid' => $relation_node_id)));
                }
                $data['party_a_phone'] = $party_a_phone;
            }
            if ($party_b_phone != '') {
                if (! check_str($party_b_phone, 
                    array(
                        'strtype' => 'mobile'), $error)) {
                    $this->error("电话号码{$error}", 
                        U('Mypartner/edit/', 
                            array(
                                'relationid' => $relation_node_id)));
                }
                $data['party_b_phone'] = $party_b_phone;
            }
            if ($b_contact_phone != '') {
                if (! check_str($b_contact_phone, 
                    array(
                        'strtype' => 'mobile'), $error)) {
                    $this->error("电话号码{$error}", 
                        U('Mypartner/edit/', 
                            array(
                                'relationid' => $relation_node_id)));
                }
                $data['party_b_linkman_phone'] = $b_contact_phone;
            }
            if ($a_contact_phone != '') {
                if (! check_str($a_contact_phone, 
                    array(
                        'strtype' => 'mobile'), $error)) {
                    $this->error("电话号码{$error}", 
                        U('Mypartner/edit/', 
                            array(
                                'relationid' => $relation_node_id)));
                }
                $data['party_a_linkman_phone'] = $a_contact_phone;
            }
        } else {
            $data['bail'] = '';
            $data['control_flag'] = 0;
            // $data['max_amt']='';
            $data['warning_amt'] = '';
            $data['party_a_phone'] = '';
            $data['party_b_phone'] = '';
        }
        $data['control_type'] = $control_type;
        $data['begin_time'] = $begin_time . "000000";
        $data['end_time'] = $end_time . "235959";
        $data['party_a_linkman'] = $a_contact_name;
        $data['party_b_linkman'] = $b_contact_name;
        M()->startTrans();
        $TransactionID = date("YmdHis") . mt_rand(10000, 99999);
        $requestServArr = array(
            'PrepaidControlAddReq' => array(
                'TransactionID' => $TransactionID, 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'BussNodeID' => $relation_node_id, 
                'ShopNodeID' => $this->node_id, 
                'OperateFlag' => 1, 
                'CooperateBeginTime' => $begin_time . "000000", 
                'CooperateEndTime' => $end_time . "235959", 
                'SettleType' => $control_type, 
                'ControlFlag' => $control_flag, 
                'PrepayMentAmt' => $bail, 
                'GuardAmt' => $warning_amt, 
                'GuardBussPhone' => $party_b_phone, 
                'GuardShopPhone' => $party_a_phone));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $return_serv_arr = $RemoteRequest->requestIssForImageco($requestServArr);
        $return_serv_sta = $return_serv_arr['PrepaidControlAddRes']['Status'];
        if (! $return_serv_arr || $return_serv_sta['StatusCode'] != '0000') {
            M()->rollback();
            log::write("编辑合作关系失败，原因：{$return_serv_sta['StatusText']}");
            $this->error('编辑合作关系失败！:' . $return_serv_sta['StatusText']);
        }
        $arr = M('tsale_relation')->where(
            array(
                'relation_node_id' => $relation_node_id, 
                'node_id' => $this->nodeId))->save($data);
        if ($arr) {
            M()->commit();
            $this->success('保存成功！', U('index'));
        } else {
            M()->rollback();
            $this->error('更改失败！', U('index'));
        }
    }
    
    // 保存延期
    public function saveUptime() {
        $relation_node_id = I('raletionid');
        // $check_status=I('check_status');
        $end_time = I("end_time");
        if ($end_time == '') {
            $this->error("合作期限的结束时间不能为空！", U('index'));
        }
        $end_time .= "235959";
        $data = array(
            'end_time' => $end_time);
        // 'check_status'=>'1'
        
        $once_end_time = M('tsale_relation')->where(
            array(
                'node_id' => $this->node_id, 
                'relation_node_id' => $relation_node_id))->getField('end_time');
        $cpyend_time = date("Ymd", strtotime($once_end_time));
        $staty = M('tsale_relation')->where(
            array(
                'node_id' => $this->node_id, 
                'relation_node_id' => $relation_node_id))->save($data);
        if ($staty) {
            echo "<script>alert('延期成功！');parent.art.dialog.list['clopar'].close();</script>";
            node_log("分销商延期成功！", 
                print_r($_POST, TRUE) . "'cpyend_time'=>$cpyend_time");
        } else {
            echo "<script>alert('延期失败！');parent.art.dialog.list['clopar'].close();</script>";
        }
    }
    
    // 清算管理
    public function settlement() {
        import("ORG.Util.Page");
        $node_id = $this->nodeId;
        $nodename = I('nodename');
        $begin_time = I('start_time');
        $end_time = I('end_time');
        $control_flag = I('contrl_list');
        $control_type = I('batch_class');
        $status_list = I('status_list');
        $map = array(
            's.node_id' => $node_id);
        // 's.check_status'=>array(array('eq','1'),array('eq','4'),'or')
        
        if ($begin_time != '') {
            $begin_time .= "000000";
            $map['s.begin_time'] = array(
                'egt', 
                $begin_time);
        }
        if ($end_time != '') {
            $end_time .= "235959";
            $map['s.end_time'] = array(
                'lt', 
                $end_time);
        }
        if ($control_flag == '0') {
            $map['s.control_flag'] = '0';
        }
        if ($control_flag == '1') {
            $map['s.control_flag'] = '1';
        }
        if ($control_type == '1') {
            $map['s.control_type'] = '1';
        }
        if ($control_type == '2') {
            $map['s.control_type'] = '2';
        }
        if ($status_list == '0') {
            $map['s.end_time'] = array(
                'egt', 
                date('YmdHis', time()));
            $map['s.status'] = '0';
        }
        if ($status_list == 1) {
            $map['s.end_time'] = array(
                'lt', 
                date('YmdHis', time()));
            $map['s.status'] = '0';
        }
        if ($status_list == 2) {
            $map['s.status'] = 1;
        }
        if ($nodename != '') {
            $map['n.node_name'] = array(
                'like', 
                "%{$nodename}%");
        }
        $count = M()->table("tsale_relation s")->where($map)->count();
        $p = new Page($count, 6);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $list = M()->table('tsale_relation s')
            ->where($map)
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('s.add_time desc')
            ->select();
        // $contrlClass=array('0'=>'否','1'=>'是');
        $batchClass = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量');
        $statusClass = array(
            '0' => '正常', 
            '1' => '过期', 
            '2' => '取消');
        // $this->assign('contrlClass',$contrlClass);
        $this->assign('batchClass', $batchClass);
        $this->assign('statusClass', $statusClass);
        $this->assign("page", $page);
        $this->assign('post', $_REQUEST);
        $this->assign("list", $list);
        $this->display();
    }
    // 去清算并保存数据
    public function tosettle() {
        $relationid = I('relationid');
        // $check_status=I('check_status');
        $nodeid = $this->node_id;
        $userid = $this->user_id;
        $type = I("type");
        $notSetAmt = I("notSetAmt");
        // $arr=M('tsale_relation')->where(array('relation_node_id'=>$relationid,'node_id'=>$nodeid))->find();
        if ($type != 1) {
            $rid = I('rid');
            $settleamt = I('settleamt');
            $notamt = I('notamt');
            $arrsale = M('tsale_relation')->where(
                array(
                    'relation_node_id' => $rid, 
                    'node_id' => $nodeid))->find();
            if ($settleamt > 0) {
                if ($settleamt > $notamt) {
                    $this->error('结算金额输入有误，必须小于等于未结算金额！');
                }
                M()->startTrans();
                $TransactionID = date("YmdHis") . mt_rand(10000, 99999);
                $repserv_set_arr = array(
                    'PrpCtrlSettleReq' => array(
                        'TransactionID' => $TransactionID, 
                        'SystemID' => C('ISS_SYSTEM_ID'), 
                        'BussNodeID' => $rid, 
                        'ShopNodeID' => $this->node_id, 
                        'SettleAmt' => $settleamt));
                $RemoteRequestServ = D('RemoteRequest', 'Service');
                $repserv_get_array = $RemoteRequestServ->requestIssForImageco(
                    $repserv_set_arr);
                $reparr_status = $repserv_get_array['PrpCtrlSettleRes']['Status'];
                if (! $repserv_get_array ||
                     $reparr_status['StatusCode'] != '0000') {
                    M()->rollback();
                    log::write("结算失败！原因:{$reparr_status['StatusText']}");
                    $this->error('结算失败！' . $reparr_status['StatusText']);
                }
                $data = array(
                    'node_id' => $this->node_id, 
                    'relation_node_id' => $rid, 
                    'settle_amt' => $settleamt, 
                    'settle_time' => date('YmdHis'), 
                    'oper_id' => $this->user_id);
                $stuas = M('tsale_relation_settle')->add($data);
                // $ayy=M('tsale_relation')->where(array('relation_node_id'=>$rid,'node_id'=>$nodeid))->setDec('not_settle_amt',$settleamt);
                if ($stuas) {
                    M()->commit();
                    $this->success('结算成功！');
                } else {
                    M()->rollback();
                    $this->error('结算失败');
                }
            } else {
                $this->error('你输入有误，清算金额必须大于0，且小于或等于未清算金额！');
            }
        }
        $this->assign("relationid", $relationid);
        $this->assign('notamt', $notSetAmt);
        $this->display();
    }
    
    // 去清算
    public function tosettlelog() {
        $relationid = I('relationid');
        $shopNodeId = $this->node_id;
        $bussNodeId = $relationid;
        $transactionId = date('YmdHis') . mt_rand(10000, 99999);
        $reqServArr = array(
            'PrpCtrlQueryReq' => array(
                'TransactionID' => $transactionId, 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'BussNodeID' => $bussNodeId, 
                'ShopNodeID' => $shopNodeId));
        $remoteRequest = D('RemoteRequest', 'Service');
        $resServ_array = $remoteRequest->requestIssForImageco($reqServArr);
        $resStatusArr = $resServ_array['PrpCtrlQueryRes']['Status'];
        if (! $resServ_array || $resStatusArr['StatusCode'] != '0000') {
            log::write('查看已用额度失败！原因：' . $resStatusArr['StatusText']);
            $this->error('操作失败！' . $resStatusArr['StatusText']);
        }
        $resRale_array = $resServ_array['PrpCtrlQueryRes']['PrpCtrlInfo'];
        if ($resRale_array['PrepayMentUseAmt'] <= 0) {
            $this->ajaxReturn($resRale_array['PrepayMentUseAmt'], '未结算金额为0', 0);
        } else {
            $this->ajaxReturn($resRale_array['PrepayMentUseAmt'], '可进行结算', 1);
        }
    }
    // 清算记录
    public function settleRecords() {
        import("ORG.Util.Page");
        $nodename = I('nodename');
        $star = I('start_time');
        $end = I('end_time');
        $relationid = I('relationid');
        // $check_status=I('check_status');
        $nodeid = $this->node_id;
        $relaList = M()->table("tnode_info a")->where(
            "a.node_id=$relationid and b.node_id=$this->node_id and b.relation_node_id=$relationid")
            ->field(
            'a.node_name,a.client_id,b.party_b_linkman,b.party_b_linkman_phone')
            ->join('tsale_relation b on a.node_id=b.relation_node_id')
            ->find();
        $map = array(
            'R.node_id' => $nodeid, 
            'S.relation_node_id' => $relationid);
        // 'R.check_status'=>$check_status
        
        if ($star != '' && $end != '') {
            $startime = $star . '000000';
            $endtime = $end . '235959';
            $map['S.settle_time'] = array(
                array(
                    'egt', 
                    $startime), 
                array(
                    'lt', 
                    $endtime));
        }
        $count = M()->table('tsale_relation_settle S')
            ->where($map)
            ->field('S.*,T.node_name,R.*')
            ->join('tnode_info T ON S.relation_node_id=T.node_id ')
            ->join('tsale_relation R ON S.relation_node_id=R.relation_node_id')
            ->count();
        $p = new Page($count, 4);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $arr = M()->table('tsale_relation_settle S')
            ->where($map)
            ->field('S.*,T.node_name,R.control_type')
            ->join('tnode_info T ON S.relation_node_id=T.node_id ')
            ->join('tsale_relation R ON S.relation_node_id=R.relation_node_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('S.settle_time desc')
            ->select();
        
        $summoney = M()->table('tsale_relation_settle S')
            ->where($map)
            ->field('S.*,T.node_name,R.control_type')
            ->join('tnode_info T ON S.relation_node_id=T.node_id ')
            ->join('tsale_relation R ON S.relation_node_id=R.relation_node_id')
            ->sum('S.settle_amt');
        $this->assign('relaList', $relaList);
        $this->assign('summoney', $summoney);
        $this->assign('relationid', $relationid);
        $this->assign('page', $page);
        $this->assign('list', $arr);
        $this->assign('post', $_REQUEST);
        $this->display();
    }
    // 清算数据统计
    public function mysale() {
        import("ORG.Util.Page");
        $node_name = I('node_name');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $batch_class = I('batch_class');
        $goods_name = I('goods_name');
        $sql = "SELECT b.trans_date,a.node_id,a.goods_name,n.node_name,a.id,c.control_type,
   	IFNULL(SUM(CASE WHEN c.control_type = '1'  THEN b.send_total_cnt ELSE b.verify_total_cnt END ),0) AS num,
   	IFNULL(ROUND(SUM(CASE WHEN c.control_type = '1'  THEN b.`send_settle_amt`-b.`send_settle_cancel_amt` ELSE b.`verify_settle_amt`-b.`verify_settle_cancel_amt` END ),2),0) AS money
   	FROM tgoods_info a,tgoods_stat b,tsale_relation c,tnode_info n
   	WHERE a.source = '4' AND a.id = b.g_id AND c.relation_node_id = a.node_id  
   	AND c.control_type IN ('1','2') AND a.node_id=n.node_id AND c.node_id=$this->node_id
   	GROUP BY b.trans_date,a.node_id,n.node_name";
        $map = array();
        
        if ($node_name != '') {
            $map['t.node_name'] = array(
                'like', 
                "%$node_name%");
        }
        if ($start_time != '' && $end_time == '') {
            $map['t.trans_date'] = array(
                'egt', 
                $start_time);
        }
        if ($end_time != '' && $start_time == '') {
            $map['t.trans_date'] = array(
                'elt', 
                $end_time);
        }
        if ($start_time != '' && $end_time != '') {
            $map['t.trans_date'] = array(
                array(
                    'egt', 
                    $start_time), 
                array(
                    'elt', 
                    $end_time));
        }
        if ($batch_class == 1) {
            $map['control_type'] = '1';
        }
        if ($batch_class == 2) {
            $map['control_type'] = '2';
        }
        if ($goods_name != '') {
            $map['t.goods_name'] = array(
                'like', 
                "%$goods_name%");
        }
        $count = M()->table("($sql) t")
            ->where($map)
            ->count();
        $p = new Page($count, 5);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $list = M()->table("($sql) t")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('t.trans_date desc')
            ->select();
        $sumamt = M()->table("($sql) t")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->sum('t.num');
        $summoney = M()->table("($sql) t")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->sum('t.money');
        $summoneyCount = M()->table("($sql) t")
            ->where($map)
            ->sum('t.money');
        $sumamtCount = M()->table("($sql) t")
            ->where($map)
            ->sum('t.num');
        $batchClass = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量');
        $this->assign('summoneyCount', $summoneyCount);
        $this->assign('sumamtCount', $sumamtCount);
        $this->assign('batchClass', $batchClass);
        // $this->assign('summoney',$summoney);
        // $this->assign('sumamt',$sumamt);
        $this->assign('post', $_REQUEST);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }
    // 验证输入的用户名
    public function ck_name() {
        // $cid=I("get.cid");
        $cid = $_GET['cid'];
        $row = M('tnode_info')->where(
            array(
                'client_id' => $cid))->getField('node_id, node_name');
        if ($row) {
            show_arr_opt($row);
        } else {
            echo null;
        }
        // $row=$row ? $row : array();
        // echo json_encode($row);
        // $this->display();
    }

    public function ck_type() {
        $node_id = I("node_id");
        // 判断添加商户的类型,是否标准版
        $stat = $this->_hasStandard($node_id);
        if ($stat == true) {
            $list = M()->table('tnode_info')
                ->where("node_id='{$node_id}'")
                ->find();
            // $this->ajaxReturn($list,'JSON');
            echo json_encode($list);
        } else {
            echo "1";
        }
    }

    public function ck_saleList() {
        $rela_node_id = I('rela_node_id');
        if ($rela_node_id != '') {
            $saleList = M('tsale_relation')->where(
                "node_id=$this->node_id and relation_node_id='{$rela_node_id}'")->find();
            if ($saleList) {
                $this->ajaxReturn($saleList, '查询到此用户可发货！', '1');
            } else {
                $this->ajaxReturn('0', '未查询到此用户！', '0');
            }
        } else {
            $this->error('操作有误！');
        }
    }
    // 添加合作关系
    public function addpartner() {
        // 甲方客户类型
        if ($this->isPost()) {
            $relation_type = I("partnerType");
            $vo_node_id = I("voucher_node_id");
            $node_id = $this->nodeId;
            $starttime = I("start_time");
            $endtime = I("end_time");
            $a_name = I("a_contact_name");
            $a_phone = I("a_contact_phone");
            $b_name = I("b_contact_name");
            $b_phone = I("b_contact_phone");
            $qsfs = I("qsfs");
            // $gkbz=I("gkbz"); // 默认管控，后期迭代
            $gkbz = 1;
            $meizu = I("meizu");
            $oper_id = $this->user_id;
            $node_name = M('tnode_info')->where("node_id='{$node_id}'")->getField(
                'node_name');
            $arr = array();
            if (! check_str($vo_node_id, 
                array(
                    'null' => false), $error)) {
                $this->error("所选商户{$error}", U('addpartner'));
            }
            if ($starttime == '' || $endtime == '') {
                if ($meizu == 4) {
                    $this->error("合作期限不能为空！", 
                        U('editAdd', 
                            array(
                                'relationid' => $vo_node_id)));
                }
                $this->error("合作期限不能为空！", U('addpartner'));
            }
            if ($gkbz == 1) {
                $bail = I("bail");
                // $maxamt=I("maxamt");
                $warningamt = I("warning_amt");
                $myphone = I("myphone");
                $custphone = I("custphone");
                if ($bail != '') {
                    // 保证金是否大于500
                    if ($bail < 500) {
                        if ($meizu == 4) {
                            $this->error("预付费额度不得低于500元！", 
                                U('editAdd', 
                                    array(
                                        'relationid' => $vo_node_id)));
                        }
                        $this->error('预付费额度不得低于500元！', U('addpartner'));
                    }
                    $arr['bail'] = $bail;
                    // 预警金额是否大于峰值
                    if ($warningamt == '') {
                        if ($meizu == 4) {
                            $this->error("预警金额不能为空！", 
                                U('editAdd', 
                                    array(
                                        'relationid' => $vo_node_id)));
                        }
                        $this->error('预警金额不能为空！', U('addpartner'));
                    }
                    if ($warningamt > $bail) {
                        if ($meizu == 4) {
                            $this->error("预警金额必须小于预付费金额！", 
                                U('editAdd', 
                                    array(
                                        'relationid' => $vo_node_id)));
                        }
                        $this->error('预警金额必须小于预付费金额！', U('addpartner'));
                    }
                    // $arr['max_amt']=$maxamt;
                    $arr['warning_amt'] = $warningamt;
                } else {
                    if ($meizu == 4) {
                        $this->error("预付费金额不能为空！", 
                            U('editAdd', 
                                array(
                                    'relationid' => $vo_node_id)));
                    }
                    $this->error('预付费金额不能为空！', U('addpartner'));
                }
                if ($myphone != '') {
                    if (! check_str($myphone, 
                        array(
                            'strtype' => 'mobile'), $error)) {
                        if ($meizu == 4) {
                            $this->error("您的预警通知手机号{$error}", 
                                U('editAdd', 
                                    array(
                                        'relationid' => $vo_node_id)));
                        }
                        $this->error("您的预警通知手机号{$error}", U('addpartner'));
                    }
                    $arr['party_a_phone'] = $myphone;
                } else {
                    $this->error("管控时，您的预警通知手机号不能为空！");
                }
                if ($custphone != '') {
                    if (! check_str($custphone, 
                        array(
                            'strtype' => 'mobile'), $error)) {
                        if ($meizu == 4) {
                            $this->error("采购方预警通知手机号{$error}", 
                                U('editAdd', 
                                    array(
                                        'relationid' => $vo_node_id)));
                        }
                        $this->error("采购方预警通知手机号{$error}", U('addpartner'));
                    }
                    $arr['party_b_phone'] = $custphone;
                } else {
                    $this->error("管控时，采购方预警通知号不能为空！");
                }
            }
            if (! $this->_hasStandard($vo_node_id)) {
                $this->error('您添加的采购商尚未开通旺财标准版，不能添加！', 
                    array(
                        '继续添加分销商' => U('addpartner'), 
                        '返回列表' => U('index')));
            }
            
            // 不能添加自己
            if ($node_id == $vo_node_id) {
                $this->error('您不能添加您自己！', 
                    array(
                        '继续添加分销商' => U('addpartner'), 
                        '返回列表' => U('index')));
            }
            if ($a_phone != '') {
                if (! check_str($a_phone, 
                    array(
                        'strtype' => 'mobile'), $error)) {
                    if ($meizu == 4) {
                        $this->error("您的联系人手机号{$error}！", 
                            U('Mypartner/editAdd/', 
                                array(
                                    'relationid' => $vo_node_id)));
                    }
                    $this->error("您的联系人手机号{$error}！", U('addpartner'));
                }
            }
            if ($b_phone != '') {
                if (! check_str($b_phone, 
                    array(
                        'strtype' => 'mobile'), $error)) {
                    if ($meizu == 4) {
                        $this->error("采购方手机号{$error}", 
                            U('editAdd', 
                                array(
                                    'relationid' => $vo_node_id)));
                    }
                    $this->error("采购方手机号{$error}", U('addpartner'));
                }
            }
            // 是否已经添加过此用户
            $extens = M('tsale_relation')->where(
                array(
                    'relation_node_id' => $vo_node_id, 
                    'node_id' => $node_id))->count();
            if ($extens > 0) {
                $this->error("此用户已经绑定过合作关系！请勿重复添加", 
                    array(
                        '去发货' => U('firstDistri', 
                            array(
                                'relationid' => $vo_node_id)), 
                        '返回列表' => U('index')));
            }
            M()->startTrans();
            $TransactionID = date("YmdHis") . mt_rand(10000, 99999);
            $requestServArr = array(
                'PrepaidControlAddReq' => array(
                    'TransactionID' => $TransactionID, 
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'BussNodeID' => $vo_node_id, 
                    'ShopNodeID' => $this->node_id, 
                    'OperateFlag' => 0, 
                    'CooperateBeginTime' => $starttime . "000000", 
                    'CooperateEndTime' => $endtime . "235959", 
                    'SettleType' => $qsfs, 
                    'ControlFlag' => $gkbz, 
                    'PrepayMentAmt' => $bail, 
                    'GuardAmt' => $warningamt, 
                    'GuardBussPhone' => $b_phone, 
                    'GuardShopPhone' => $a_phone));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $return_serv_arr = $RemoteRequest->requestIssForImageco(
                $requestServArr);
            $return_serv_sta = $return_serv_arr['PrepaidControlAddRes']['Status'];
            // dump($return_serv_sta);
            if (! $return_serv_arr || $return_serv_sta['StatusCode'] != '0000') {
                M()->rollback();
                log::write("创建合作关系失败，原因：{$return_serv_sta['StatusText']}");
                $this->error('创建合约失败:' . $return_serv_sta['StatusText']);
            }
            $arr['node_id'] = $node_id;
            $arr['relation_node_id'] = $vo_node_id;
            $arr['begin_time'] = $starttime . "000000";
            $arr['end_time'] = $endtime . "235959";
            $arr['control_flag'] = $gkbz;
            $arr['control_type'] = $qsfs;
            $arr['party_a_linkman'] = $a_name;
            $arr['party_a_linkman_phone'] = $a_phone;
            $arr['party_b_linkman'] = $b_name;
            $arr['party_b_linkman_phone'] = $b_phone;
            $arr['add_time'] = date('YmdHis');
            $arr['oper_id'] = $oper_id;
            $arr['not_settle_amt'] = 0;
            $arr['relation_type'] = $relation_type;
            /*
             * 旧版需对方确认
             * $testNodename=M('tsale_relation')->where(array('relation_node_id'=>$vo_node_id,'node_id'=>$node_id))->field('check_status')->order('add_time
             * desc')->select(); foreach($testNodename as $v){
             * if($v["check_status"]=='0'){
             * $this->error('您已经添加过此用户！请耐心等待分销商确认！如对方未在72小时内完成确认，此次添加失效！',array('继续添加分销商'=>U('addpartner'),'返回列表'=>U('index')));
             * } if($v['check_status']==3){
             * $deleSta=M('tsale_relation')->where(array('relation_node_id'=>$vo_node_id,'node_id'=>$node_id,'check_status'=>3))->delete();
             * } if($v['check_status']==1){
             * $this->error('此用户已经是您的分销商！请勿重复添加',array('继续添加分销商'=>U('addpartner'),'去分销代金券'=>U('firstDistri',
             * array('relationid'=>$vo_node_id)),'返回列表'=>U('index'))); } }
             */
            $stuas = M('tsale_relation')->add($arr);
            if ($stuas) {
                M()->commit();
                // $staty=$this->send_news($node_name,$vo_node_id);
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
                M()->rollback();
            }
        }
        $nodeList = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
        $saleList = M()->table("tsale_node_relation a")->where(
            "a.node_id='{$this->node_id}'")
            ->join('tnode_info b on a.relation_node_id=b.node_id')
            ->getField('b.node_id,b.node_name');
        if (empty($saleList)) {
            $this->error('您还没有采购方，无法进行合作关系绑定！');
        }
        $this->assign('saleList', $saleList);
        $this->assign('nodeList', $nodeList);
        $this->display();
    }
    
    // 发送消息（站内信）
    public function send_news($node_name, $node_id) {
        $data = array(
            'title' => "分销关系确认通知！！！", 
            'content' => "<pre>您好！ <br/>{$node_name}向您发来了分销关系确认消息，希望和您建立分销关系，请点击去确认。
				</b></pre>", 
            'add_time' => date('YmdHis'), 
            'msg_type' => '0');
        $add_ = M("tmessage_news")->add($data);
        $news_id = M('tmessage_news')->where(
            array(
                'title' => array(
                    'like', 
                    "%分销关系确认通知%")))
            ->order('id desc')
            ->getField('id');
        $arr = array(
            "message_id" => $news_id, 
            "node_id" => $node_id, 
            "send_status" => '0', 
            "status" => '0', 
            "add_time" => date('YmdHis'));
        $send_ = M("tmessage_recored")->add($arr);
        if ($send_) {
            return true;
        } else {
            return false;
        }
    }
    
    // 失效后重新添加
    public function editAdd() {
        $relationid = I("relationid");
        $list = M()->table('tsale_relation s')
            ->where(
            array(
                's.node_id' => $this->node_id, 
                's.relation_node_id' => $relationid, 
                's.check_status' => 3))
            ->field('s.*,n.node_name,n.client_id')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->find();
        $this->assign('list', $list);
        $this->display();
    }

    /*
     * 终止合作
     */
    public function stopCooperation() {
        if (IS_POST) {
            $relation_node_id = I('post.relaid');
            $stop_reason = I('post.stop_reason');
            if (! check_str($relation_node_id, 
                array(
                    'null' => false, 
                    'strtype' => 'string'), $error)) {
                $this->error("操作错误！");
            }
            if (! check_str($stop_reason, 
                array(
                    'strtype' => 'string', 
                    'maxlen_cn' => 50), $error)) {
                $this->error("终止合作原因$error");
            }
            M()->startTrans();
            $TransactionId = date('YmdHis') . mt_rand(10000, 99999);
            $reqServ_arr = array(
                'PrpCtrlFlagModifyReq' => array(
                    'TransactionID' => $TransactionId, 
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'BussNodeID' => $relation_node_id, 
                    'ShopNodeID' => $this->node_id, 
                    'EnablelFlag' => 0));
            $remoteRequest = D('RemoteRequest', 'Service');
            $resServ_arr = $remoteRequest->requestIssForImageco($reqServ_arr);
            $resServ_status = $resServ_arr['PrpCtrlFlagModifyRes']['Status'];
            if (! $resServ_arr || $resServ_status['StatusCode'] != '0000') {
                M()->rollback();
                log::write('终止合作失败！原因：' . $resServ_arr['StatusText']);
                $this->error('终止合作失败！' . $resServ_arr['StatusText']);
            }
            $stopCoop = M('tsale_relation')->where(
                array(
                    'node_id' => $this->node_id, 
                    'relation_node_id' => $relation_node_id))->save(
                array(
                    'status' => 1, 
                    'stop_reason' => $stop_reason));
            if ($stopCoop) {
                $stop_rela_status = M('tsale_node_relation')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'relation_node_id' => $relation_node_id))->save(
                    array(
                        'status' => 1));
                if ($stop_rela_status) {
                    M()->commit();
                    node_log('终止合作成功', 
                        print_r($_POST, TRUE) . "'change_time'=>date('YmdHis')");
                    $this->ajaxReturn('1', '终止合作成功！', '1');
                } else {
                    M()->rollback();
                    $this->ajaxReturn('0', '终止合作失败！', '0');
                }
            } else {
                M()->rollback();
                $this->ajaxReturn('0', '终止合作失败！', '0');
            }
        }
        $relationid = I('relationid');
        $list = M()->table('tsale_relation s')
            ->where(
            array(
                's.relation_node_id' => $relationid, 
                's.node_id' => $this->node_id))
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->find();
        $this->assign("list", $list);
        $this->display();
    }
    // 供应商信息查询
    public function supplierList() {
        $seachStatus = 0; // 更多筛选状态
        $nodeName = I('node_name', null, 'mysql_real_escape_string');
        if (isset($nodeName) && $nodeName != '') {
            $map['n.node_name'] = array(
                'like', 
                "%{$nodeName}%");
        }
        $startTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($startTime)) {
            $map['s.begin_time'] = array(
                'egt', 
                $startTime . '000000');
            $seachStatus = 1;
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['s.end_time '] = array(
                'elt', 
                $endTime . '235959');
            $seachStatus = 1;
        }
        $saddTime = I('sadd_time', null, 'mysql_real_escape_string');
        if (! empty($saddTime)) {
            $map['s.add_time'] = array(
                'egt', 
                $saddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['s.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $controlFlag = I('control_flag', null, 'mysql_real_escape_string');
        if (isset($controlFlag) && $controlFlag != '') {
            $map['s.control_flag'] = I('control_flag', null, 
                'mysql_real_escape_string');
        }
        $controlType = I('control_type', null, 'mysql_real_escape_string');
        if (! empty($controlType)) {
            $map['s.control_type'] = I('control_type', null, 
                'mysql_real_escape_string');
            $seachStatus = 1;
        }
        $checkStatus = I('check_status', null, 'mysql_real_escape_string');
        if ($checkStatus == '0') {
            $map['s.status'] = '0';
            $map['s.end_time'] = array(
                'egt', 
                date('YmdHis'));
        }
        if ($checkStatus == '1') {
            $map['s.status'] = '0';
            $map['s.end_time'] = array(
                'lt', 
                date('YmdHis'));
        }
        if ($checkStatus == '2') {
            $map['s.status'] = '1';
        }
        
        $map['s.relation_node_id'] = $this->nodeId;
        import("ORG.Util.Page");
        $count = M()->table("tsale_relation s")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tsale_relation s')
            ->field('s.*,n.node_name,n.client_id')
            ->join('tnode_info n ON s.node_id=n.node_id')
            ->where($map)
            ->order('s.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $controlType = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量'); // 协议清算方式
        $checkStatusType = array(
            '0' => '正常', 
            '1' => '过期', 
            '2' => '取消');
        $this->assign('list', $list);
        $this->assign('controlType', $controlType);
        $this->assign('checkStatusType', $checkStatusType);
        $this->assign('seachStatus', $seachStatus);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    // 供应商信息详情
    public function supplierDetail() {
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            's.id' => $id, 
            's.relation_node_id' => $this->nodeId);
        $info = M()->table('tsale_relation s')
            ->field(
            's.*,n.node_name,n.contact_name,n.contact_phone,o.contact_name as my_name,o.contact_phone as my_phone')
            ->join('tnode_info n ON s.node_id=n.node_id')
            ->
        // 供应商
        join('tnode_info o ON s.relation_node_id=o.node_id')
            ->
        // 分销商
        where($map)
            ->find();
        $controlType = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量'); // 协议清算方式
        $this->assign('controlType', $controlType);
        $this->assign('info', $info);
        $this->display();
    }
    
    // 审核确认操作
    public function checkConfirm() {
        $id = I('id', null, 'mysql_real_escape_string');
        $type = I('type', null, 'mysql_real_escape_string');
        $dataInfo = M('tsale_relation')->field('check_status')
            ->where(
            "id='{$id}' AND relation_node_id='{$this->nodeId}' AND check_status='0'")
            ->find();
        if (! $dataInfo)
            $this->error('未查询到有效数据');
        if ($type == '1') {
            $data = array(
                'check_status' => '1');
        } else {
            $data = array(
                'check_status' => '2');
        }
        $result = M('tsale_relation')->where(
            "id='{$id}' AND relation_node_id='{$this->nodeId}' AND check_status='0'")->save(
            $data);
        if ($result === false)
            $this->error('系统出错,更新失败');
        if ($type == '1') {
            $tipStr = '确认成功';
            $jump = array(
                '供应商信息' => U('Hall/Mypartner/supplierList'), 
                '分销的代金券' => U('Hall/Distri/giveme'));
        } else {
            $tipStr = '拒绝成功';
            $jump = array(
                '查看供应商信息' => U('Hall/Mypartner/supplierList', 
                    array(
                        'tab' => 'no_check')));
        }
        $this->success($tipStr, $jump);
    }
    
    // 供应商清算数据统计查询
    public function supplierClearCount() {
        $nodeName = I('node_name', null, 'mysql_real_escape_string');
        if (isset($nodeName) && $nodeName != '') {
            $map['t.node_name'] = array(
                'like', 
                "%{$nodeName}%");
        }
        $startTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($startTime)) {
            $map['t.trans_date'] = array(
                'egt', 
                $startTime);
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['t.trans_date '] = array(
                'elt', 
                $endTime);
        }
        $controlType = I('control_type', null, 'mysql_real_escape_string');
        if (! empty($controlType)) {
            $map['t.control_type'] = I('control_type', null, 
                'mysql_real_escape_string');
        }
        
        import("ORG.Util.Page");
        // sql语句
        $sql = "SELECT b.trans_date,c.node_id,n.node_name,c.id,c.control_type,c.relation_node_id,
			   	IFNULL(ROUND(SUM(CASE WHEN c.control_type = '1'  THEN b.`send_settle_amt`-b.`send_settle_cancel_amt` ELSE b.`send_settle_cancel_amt`-b.`verify_settle_cancel_amt` END ),2),0) AS money
			   	FROM tgoods_info a,tgoods_stat b,tsale_relation c,tnode_info n
			   	WHERE a.source = '4' AND a.id = b.g_id AND c.relation_node_id = a.node_id  
			   	AND c.control_type IN ('1','2') AND c.node_id=n.node_id
			   	GROUP BY b.trans_date,a.node_id,n.node_name";
        $map['t.relation_node_id'] = $this->nodeId;
        $count = M()->table("($sql) t")
            ->where($map)
            ->order('t.trans_date desc')
            ->count();
        $p = new Page($count, 5);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table("($sql) t")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        // 总交易量,清算金额
        $totalData = M()->table("($sql) t")
            ->field("SUM(t.num) as total_num,SUM(t.money) as total_money")
            ->where($map)
            ->find();
        
        $controlType = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量'); // 协议清算方式
        
        $this->assign('controlType', $controlType);
        $this->assign('totalData', $totalData);
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign('page', $page);
        $this->display();
    }
    
    // 清算记录详情
    public function clearDetail() {
        $id = I('relationid', null, 'mysql_real_escape_string');
        $map = array(
            's.id' => $id, 
            's.relation_node_id' => $this->nodeId);
        $startTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($startTime)) {
            $map['r.settle_time'] = array(
                'egt', 
                $startTime . '000000');
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['r.settle_time '] = array(
                'elt', 
                $endTime . '235959');
        }
        $controlType = I('control_type', null, 'mysql_real_escape_string');
        if (! empty($controlType)) {
            $map['s.control_type'] = I('control_type', null, 
                'mysql_real_escape_string');
        }
        import("ORG.Util.Page");
        $count = M()->table('tsale_relation_settle r')
            ->join(
            'tsale_relation s ON s.node_id=r.node_id AND s.relation_node_id=r.relation_node_id')
            ->join('tnode_info n ON r.node_id=n.node_id')
            ->
        // 供应商
        where($map)
            ->count();
        $p = new Page($count, 5);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tsale_relation_settle r')
            ->field('s.control_type,r.settle_time,r.settle_amt')
            ->join('tnode_info n ON r.node_id=n.node_id')
            ->
        // 供应商
        join(
            'tsale_relation s ON s.node_id=r.node_id AND s.relation_node_id=r.relation_node_id')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        // 总清算金额
        $totalData = M()->table('tsale_relation_settle r')
            ->field('IFNULL(SUM(r.settle_amt),0.00) as total_amt')
            ->join(
            'tsale_relation s ON s.node_id=r.node_id AND s.relation_node_id=r.relation_node_id')
            ->where($map)
            ->find();
        $controlType = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量'); // 协议清算方式
        $this->assign('controlType', $controlType);
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign('totalData', $totalData);
        $this->assign('page', $page);
        $this->display();
    }
    
    // 调支撑，查看已使用额度
    public function reqIssServ() {
        $type = I('type'); // 1-供应商nodeId 2-采购商nodeId
        $nodeId = I('node_id');
        if (empty($nodeId) || empty($type))
            $this->error('参数错误');
        if ($type == 1) {
            $shopNodeId = $nodeId;
            $bussNodeId = $this->nodeId;
        } else {
            $shopNodeId = $this->nodeId;
            $bussNodeId = $nodeId;
        }
        $transactionId = date('YmdHis') . mt_rand(10000, 99999);
        $reqServArr = array(
            'PrpCtrlQueryReq' => array(
                'TransactionID' => $transactionId, 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'BussNodeID' => $bussNodeId, 
                'ShopNodeID' => $shopNodeId));
        $remoteRequest = D('RemoteRequest', 'Service');
        $resServ_array = $remoteRequest->requestIssForImageco($reqServArr);
        $resStatusArr = $resServ_array['PrpCtrlQueryRes']['Status'];
        if (! $resServ_array || $resStatusArr['StatusCode'] != '0000') {
            log::write('查看已用额度失败！原因：' . $resStatusArr['StatusText']);
            $this->error('操作失败！' . $resStatusArr['StatusText']);
        }
        $resRale_array = $resServ_array['PrpCtrlQueryRes']['PrpCtrlInfo'];
        // $retRale_amt=round($resRale_array['PrepaymentUseAmt']-$resRale_array['PrepaymentRemainAmt'],2);
        $this->ajaxReturn($resRale_array['PrepayMentUseAmt'], '操作成功！', '1');
    }

    /*
     * 流水下载
     */
    public function download() {
        $node_name = I('node_name');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $batch_class = I('batch_class');
        $goods_name = I('goods_name');
        $sql = "SELECT b.trans_date,a.node_id,a.goods_name,n.node_name,a.id,c.control_type,
   	IFNULL(SUM(CASE WHEN c.control_type = '1'  THEN b.send_total_cnt ELSE b.verify_total_cnt END ),0) AS num,
   	IFNULL(ROUND(SUM(CASE WHEN c.control_type = '1'  THEN b.`send_settle_amt`-b.`send_settle_cancel_amt` ELSE b.`verify_settle_amt`-b.`verify_settle_cancel_amt` END ),2),0) AS money
   	FROM tgoods_info a,tgoods_stat b,tsale_relation c,tnode_info n
   	WHERE a.source = '4' AND a.id = b.g_id AND c.relation_node_id = a.node_id  
   	AND c.control_type IN ('1','2') AND a.node_id=n.node_id AND c.node_id=$this->node_id
   	GROUP BY b.trans_date,a.node_id,n.node_name";
        $map = array();
        if ($node_name != '') {
            $map['t.node_name'] = array(
                'like', 
                "%$node_name%");
        }
        if ($start_time != '' && $end_time == '') {
            $map['t.trans_date'] = array(
                'egt', 
                $start_time);
        }
        if ($end_time != '' && $start_time == '') {
            $map['t.trans_date'] = array(
                'elt', 
                $end_time);
        }
        if ($start_time != '' && $end_time != '') {
            $map['t.trans_date'] = array(
                array(
                    'egt', 
                    $start_time), 
                array(
                    'elt', 
                    $end_time));
        }
        if ($batch_class == 1) {
            $map['control_type'] = '1';
        }
        if ($batch_class == 2) {
            $map['control_type'] = '2';
        }
        if ($goods_name != '') {
            $map['t.goods_name'] = array(
                'like', 
                "%$goods_name%");
        }
        $list = M()->table("($sql) t")
            ->where($map)
            ->order('t.trans_date desc')
            ->select();
        $fileName = date("YmdHis") . str_shuffle('jssj') . "-结算数据统计.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "日期,采购商,卡券名称,结算方式,验证数量,应结算金额\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $vo) {
                $vo['trans_date'] = iconv('utf-8', 'gbk', 
                    date("Y-m-d", strtotime($vo['trans_date'])));
                $vo['node_name'] = iconv('utf-8', 'gbk', $vo['node_name']);
                $vo['goods_name'] = iconv('utf-8', 'gbk', $vo['goods_name']);
                $control_type = $vo['control_type'] == 1 ? iconv('utf-8', 'gbk', 
                    "按采购方使用量") : iconv('utf-8', 'gbk', "按供货方验证量");
                $vo['amt'] = iconv('utf-8', 'gbk', $vo['num']);
                $vo['money'] = sprintf("%.2f", $vo['money']);
                echo "{$vo['trans_date']}," . "\t" . "{$vo['node_name']}" . ",\t" .
                     "{$vo['goods_name']}" . ",\t" . "{$control_type}" . ",\t" .
                     "{$vo['num']}" . ",\t" . "{$vo['money']}\r\n";
            }
        }
    }
    
    // 第三方平台
    public function thirdPartyApply() {
        $Mode = M('tsale_third_party');
        $name = I('name', null, 'mysql_real_escape_string,trim');
        $pratyType = I('pratyType', '', 'mysql_real_escape_string,trim');
        $ticketType = I('ticketType', '', 'mysql_real_escape_string,trim');
        $checkStatus = I('checkStatus', '', 'mysql_real_escape_string,trim');
        
        $wh['node_id'] = $this->node_id;
        
        if (! empty($name)) {
            $wh['ticket_name'] = $name;
        }
        
        if ('' != $pratyType && $pratyType != '请选择' && $pratyType != '全部') {
            $wh['third_type'] = array(
                'like', 
                "%" . $pratyType . "%");
        }
        
        if ('' != $checkStatus && $checkStatus != '请选择' && $checkStatus != '全部') {
            $wh['check_status'] = array(
                'in', 
                $checkStatus);
        }
        
        $status = array(
            '0' => '正常', 
            '1' => '停用');
        $checkStatusRule = array(
            '0' => '未审核', 
            '1' => '审核通过', 
            '2' => '未通过', 
            '3' => '已失效', 
            '4' => '已过期');
        $pratyType = array(
            '0' => '天猫', 
            '1' => '京东', 
            '2' => '美团', 
            '3' => '大众点评', 
            '4' => '1号店', 
            '5' => '淘点点',
        	'6' => '融e购'	
        );
        $ticketType = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券');
        
        import("ORG.Util.Page");
        $count = $Mode->where($wh)->count();
        $p = new Page($count, 10);
        
        $applyList = $Mode->where($wh)
            ->order('add_time Desc')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        
        foreach ($applyList as $k => $v) {
            $v['third_type'] = explode(',', $v['third_type']);
            $applyList[$k]['third_type'] = '';
            foreach ($v['third_type'] as $vv) {
                $applyList[$k]['third_type'] .= $pratyType[$vv] . ' ';
            }
        }
        
        $this->assign('status', $status);
        $this->assign('checkStatusRule', $checkStatusRule);
        $this->assign('ticketType', $ticketType);
        $this->assign('list', $applyList);
        $this->assign('page', $page);
        $this->display();
    }

    public function thirdPartyAdd() {
        if ($this->isPost()) {
            $Mode = M('tsale_third_party');
            $goods_id = I('goods_id', null, 'mysql_real_escape_string,trim'); // 卡券ID
            $type = I('type', null, 'mysql_real_escape_string,trim'); // 第三方平台
            $ticket_type = I('ticket_type', null,'mysql_real_escape_string,trim'); // 卡券类型
            $name = I('name', null, 'mysql_real_escape_string,trim'); // 卡券名称
            $partyPay = I('pay2',null); // 平台售价
            $sale_start_time = I('sale_start_time', null,'mysql_real_escape_string,trim'); // 计划销售开始有效期
            $sale_end_time = I('sale_end_time', null,'mysql_real_escape_string,trim'); // 计划销售截至有效期
            $ticket_num = I('goods_amt', null, 'mysql_real_escape_string,trim'); // 卡券总数量
            $ticket_info = I('wap_info', null, 'mysql_real_escape_string,trim'); // 卡券详情
            $wh = array(
                'node_id' => $this->nodeId, 
                'goods_id' => $goods_id
            );
            $goodInfo = $Mode->where($wh)->find();
            if ($goodInfo) {
                $this->error('该卡券已经提交过');
            }
            //融e购处理
            $icbcCodeInfo = array();
            $icbcId = '';
            if($type == '6'){
            	$error = '';
            	$dataType = I('date_type');
            	$useTimeFrom = I('use_time_from');
            	$useTimeTo = I('use_time_to');
            	$verifyBeginDays = I('verify_begin_days');
            	$verifyEndDays = I('verify_end_days');
            	switch($dataType){
            		case '0':
            			if (! check_str($useTimeFrom,array('null' => false,'strtype' => 'datetime','format' => 'Ymd'), $error)) {
            				$this->error("卡券使用开始日期{$error}");
            			}
            			if (! check_str($useTimeTo,array('null' => false,'strtype' => 'datetime','format' => 'Ymd'), $error)) {
            				$this->error("卡券使用结束日期{$error}");
            			}
            			if($useTimeTo < $useTimeFrom) $this->error('卡券使用结束日期不能小于卡券使用开始日期');
            			$bTime = $useTimeFrom.'000000';
            			$eTime = $useTimeTo.'235959';
            			break;
            		case '1':
            			if (! check_str($verifyBeginDays,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
            				$this->error("卡券开始使用天数参数有误");
            			}
            			if (! check_str($verifyEndDays,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
            				$this->error("卡券结束使用天数参数有误");
            			}
            			$bTime = $verifyBeginDays;
            			$eTime = $verifyEndDays;
            			break;
            		default:
            			$this->error('请正确填写卡券使用时间');
            	}
            	$introduce = I('introduce');
            	if (! check_str($introduce,array('null' => false,'maxlen_cn' => '100'), $error)) {
            		$this->error("使用说明{$error}");
            	}
            	$icbcCodeInfo['content'] = $introduce;
            	$icbcCodeInfo['time_type'] = $dataType;
            	$icbcCodeInfo['b_time'] = $bTime;
            	$icbcCodeInfo['e_time'] = $eTime;
            	$icbcCodeInfo = json_encode($icbcCodeInfo);
            	$icbcId = I('icbc_id');
            	if (! check_str($icbcId,array('null' => false,'maxlen_cn' => '10'), $error)) {
            		$this->error("工商行ID{$error}");
            	}
            }
            if (! $goodInfo) {
                $partyArr = array(
                    'node_id' => $this->nodeId, 
                    'goods_id' => $goods_id, 
                    'third_type' => $type, 
                    'ticket_type' => $ticket_type, 
                    'ticket_name' => $name, 
                    'party_pay' => empty($partyPay) ? '0.00' : $partyPay, 
                    'sale_start_time' => $sale_start_time, 
                    'sale_end_time' => $sale_end_time, 
                    'ticket_num' => $ticket_num, 
                    'ticket_info' => $ticket_info, 
                    'add_time' => date('YmdHis'),
                	'tp_goods_id' => $icbcId,
                	'icbc_code_info' => $icbcCodeInfo,
                	'check_status' => $type == '6' ? '1' : '0'
                );
                
                	
                
                $partyInfo = $Mode->add($partyArr);
                if ($partyInfo) {
                    $this->ajaxReturn(1, '添加成功', 1);
                } else {
                    $this->ajaxReturn(0, '添加失败', 0);
                }
            }
        } else {
            $this->display();
        }
    }

    public function thirdPartyDetail() {
        $id = I('id', null, 'mysql_real_escape_string,trim');
        $info = M()->table("tsale_third_party s")->join(
            'tnode_info n ON s.node_id=n.node_id')
            ->where(array(
            's.id' => $id))
            ->field('s.*,n.node_name')
            ->select();
        $info = $info[0];
        $status = array(
            '0' => '正常', 
            '1' => '停用');
        $checkStatusRule = array(
            '0' => '未审核', 
            '1' => '审核通过', 
            '2' => '审核未通过', 
            '3' => '已失效', 
            '4' => '已过期');
        $pratyType = array(
            '0' => '天猫', 
            '1' => '京东', 
            '2' => '美团', 
            '3' => '大众点评', 
            '4' => '1号店', 
            '5' => '淘点点',
        	'6'	=> '融e购'
        );
        $ticketType = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券');
        
        // 根据ID转换为平台名称
        $third_type = explode(',', $info['third_type']);
        $info['third_type_name'] = '';
        foreach ($third_type as $vv) {
            $info['third_type_name'] .= $pratyType[$vv] . ' ';
        }
        $icbcCodeInfo = json_decode($info['icbc_code_info'],true);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('ticketType', $ticketType);
        $this->assign('checkStatusRule', $checkStatusRule);
        $this->assign('codeInfo',$icbcCodeInfo);
        $this->display();
    }

    public function thirdPartyAjaxCheckInfo() {
        $id = I('id', null, 'mysql_real_escape_string,trim');
        $check_info = M('tsale_third_party')->where(
            array(
                'id' => $id))->find();
        
        if ($check_info) {
            $this->ajaxReturn($check_info['check_info'], '查询成功', 1);
        } else {
            $this->ajaxReturn($check_info['check_info'], '查询失败', 0);
        }
    }

    public function introduction() {
        $type = I('type');
        $this->assign('type', $type);
        $this->display();
    }

    public function send_email() {
        $contact_phone = I('contact_phone', null);
        $contact_eml = I('contact_eml', null);
        $sendTime = date('Y-m-d H:i:s');
        if (! $contact_phone || ! $contact_eml)
            $this->error('手机号码或者联系邮箱不得为空', 
                array(
                    '返回分销助手' => U('Hall/Mypartner/index')));
        $nodeInfo = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
        
        // 判断是否今天发过申请邮件
        $count = M('tsend_email_trace')->where(
            array(
                'node_id' => $this->nodeId, 
                'send_time' => array(
                    'like', 
                    date('Ymd') . "%")))->count();
        if ($count > 0) {
            $this->error('您账户所属商户今天已经发送过申请邮件，无需再次发送', 
                array(
                    '返回分销助手' => U('Hall/Mypartner/index')));
        }
        
        $content = "旺号：{$nodeInfo['client_id']}<br>真实姓名：{$nodeInfo['contact_name']}<br/>手机号码：{$contact_phone}<br/>邮箱：{$contact_eml}<br/>公司名称：{$nodeInfo['node_name']}<br/><br/>申请时间：{$sendTime}<br/>";
        $ps = array(
            "subject" => "卡券分销助手业务权限审核", 
            "content" => $content, 
            "email" => "qianwen@imageco.com.cn");
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            $data = array(
                'node_id' => $nodeInfo['node_id'], 
                'contact_phone' => $contact_phone, 
                'contact_eml' => $contact_eml, 
                'send_time' => date('YmdHis'));
            $result = M('tsend_email_trace')->add($data);
            $this->success("您的申请已提交！<br/>旺小二会尽快与您联系，请耐心等待。<br/>", 
                array(
                    '返回分销助手' => U('Hall/Mypartner/index')));
        } else {
            $this->error("商品销售类业务权限申请失败，邮件发送失败", 
                array(
                    '返回分销助手' => U('Hall/Mypartner/index')));
        }
    }
}