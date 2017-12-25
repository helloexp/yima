<?php

/* 终端管理申请 */
class PosAction extends BaseAction {

    public $uploadPath;

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Store/'; // 设置附件上传目录
    }
    // epos添加
    public function eposAdd() {
        // 默认错误跳转对应的模板文件
        C('TMPL_ACTION_ERROR', './Home/Tpl/Public/Public_msgArtdialog.html');
        // 默认成功跳转对应的模板文件
        C('TMPL_ACTION_SUCCESS', './Home/Tpl/Public/Public_msgArtdialog.html');
        
        $sId = I('store_id');
        $type = I('type');
        $storeInfo = M('tstore_info')->where(
            array(
                'id' => $sId, 
                'node_id' => array(
                    'exp', 
                    "in ({$this->nodeIn()})")))->find();
        if (! $storeInfo) {
            $this->error("门店不存在");
        }
        $node_id = $storeInfo['node_id'];
        // 判断是否已有正常的ePos
        $countEpos = M('tpos_info')->where(
            array(
                'store_id' => $storeInfo['store_id'], 
                'node_id' => $node_id, 
                'pos_type' => '2', 
                'pos_status' => '0'))->count();
        if ($countEpos) {
            $this->error("该门店已开通过Epos。");
        }
        // 校验一下是否允许开免费EPOS
        if ($type == 'EposSpring2015') {
            if (! $this->checkEposNodeType($type)) {
                $this->error("您不允许开设此类型终端");
            }
        }
        if ($this->isPost()) {
            // 接收表单传值
            $req_arr = array();
            $pos_name = $storeInfo['store_short_name'] . $storeInfo['pos_count'];
            $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
            $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
            $req_arr['ISSPID'] = $node_id;
            $req_arr['StoreID'] = $storeInfo['store_id'];
            $req_arr['PosGroupID'] = '';
            $req_arr['PosFlag'] = 0;
            $req_arr['PosType'] = 3;
            $req_arr['PosName'] = $pos_name;
            $req_arr['PosShortName'] = $pos_name;
            if ($type == 'EposAi') // 爱拍终端
{
                // 免费的哟
                $req_arr['PosPayFlag'] = 1;
            } elseif ($type == 'EposSpring2015') // 打炮总动员
{
                // 免费的哟
                $req_arr['PosPayFlag'] = 2;
            }
            $req_arr['UserID'] = $this->userId;
            
            // $req_arr['ProduceFlag'] = 0;
            // $req_arr['PosType'] = 3;
            // $req_arr['PosPayFlag'] = 1;
            // $req_arr['PosGroupID'] = '';
            
            /*
             * $req_arr['StoreName'] = $storeInfo['store_name');
             * $req_arr['CustomNo'] = $storeInfo['custom_no');
             * $req_arr['ProvinceCode'] = $storeInfo['province_code');
             * $req_arr['CityCode'] = $storeInfo['city_code');
             * $req_arr['TownCode'] = $storeInfo['town_code');
             */
            // $req_arr['PrincipalName'] = $storeInfo['principal_name'];
            // $req_arr['PrincipalTel'] = $storeInfo['principal_tel'];
            // $req_arr['PrincipalEmail'] = I('principal_email');
            // $req_arr['Address'] = $storeInfo['address'];
            // $req_arr['LAT'] = $storeInfo['lat'];
            // $req_arr['LNG'] = $storeInfo['lan'];
            $req_result = D('RemoteRequest', 'Service')->requestIssServ(
                array(
                    'PosCreateReq' => $req_arr));
            $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
            if ($respStatus['StatusCode'] != '0000') {
                $this->error($respStatus['StatusText']);
            }
            $respData = $req_result['PosCreateRes'];
            
            // $store_id = $respData['StoreID'];
            $pos_id = $respData['PosID'];
            
            if (! $pos_id) {
                $this->error('创建支撑终端失败');
            }
            
            M()->startTrans();
            
            // 创建终端
            $data = array(
                'pos_id' => $pos_id, 
                'node_id' => $node_id, 
                'pos_name' => $pos_name, 
                'pos_short_name' => $pos_name, 
                'pos_serialno' => $pos_id, 
                'store_id' => $storeInfo['store_id'], 
                'store_name' => $storeInfo['store_name'], 
                'login_flag' => 0, 
                'pos_type' => '2', 
                'is_activated' => 0, 
                'pos_status' => 0, 
                'add_time' => date('YmdHis'));
            if ($type == 'EposAi') // 爱拍终端
{
                // 免费
                $data['pay_type'] = 1;
            } elseif ($type == 'EposSpring2015') // 春节打炮
{
                // 免费2
                $data['pay_type'] = 2;
            } else {
                $data['pay_type'] = 0;
            }
            $result = M('tpos_info')->add($data);
            if (! $result) {
                log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
                $this->error('创建终端失败,原因：' . M()->getDbError());
            }
            // 更新pos_range
            if ($storeInfo['pos_range'] == '0') {
                if ($type == 'EposAi') { // 爱拍终端
                    $data = array(
                        'pos_range' => '1');
                } else {
                    $data = array(
                        'pos_range' => '2');
                }
                $result = M('tstore_info')->where(
                    "store_id={$storeInfo['store_id']}")->save($data);
                if (! $result) {
                    log_write(print_r($data, true) . M()->getDbError(), 
                        'DB ERROR');
                    $this->error('创建终端失败,原因：' . M()->getDbError());
                }
            }
            
            M()->commit();
            node_log("【门店管理】门店验证终端创建"); // 记录日志
            
            $url = U('Store/index');
            
            $this->success("门店验证终端已创建成功。请到邮箱中查收用户名和密码", 
                array(
                    'href' => $url));
            exit();
        }
        $this->assign('sId', $sId);
        $this->assign('info', $storeInfo);
        $this->assign('type', $type);
        $this->display();
    }
    // 停用终端
    public function posStop() {
        $this->error("暂不开通");
        $posId = I('id');
        $result = M('tpos_info')->where(array(
            'id' => $posId))->save(array(
            'pos_status' => 2));
        if ($result === false) {
            $this->error("终端停用失败");
        }
        node_log("【门店管理】终端停用"); // 记录日志
        $this->success("终端停用成功");
    }
    
    // 终端申请
    public function posApply() {
        if (I('type') == 'ER6800') {
            $this->assign('iss_url', 
                get_iss_page_url(C('ISS_PAGE_PROCESS_ADDTERMINALS'), 
                    '&gongdan=true'));
        }
        // 判断是否C2是否审核
        $nodeInfo = M('tnode_info')->field('check_status')
            ->where("node_id='" . $this->nodeId . "'")
            ->find();
        $this->assign('check_status', $nodeInfo['check_status']);
        $this->assign('type_c', $this->node_type_name);
        $this->checkEposNodeType() ? $show = '1' : $show = '0';
        $this->assign('show', $show);
        $this->assign('showSpring2015', 
            $this->checkEposNodeType('EposSpring2015'));
        $this->assign('type', I('type'));
        $this->assign('store_id', I('store_id'));
        $this->display();
    }
    
    // 申请全业务eops终端商户类型校验
    private function checkEposNodeType($eposType = 'epos') {
        // 如果是
        if ($eposType == 'EposSpring2015') {
            if ($this->wc_version == 'v0' || $this->wc_version == 'v0.5') {
                return true;
            }
            return false;
        }
        // 无法开通全业务epos的行业类型
        $tradeType = array(
            '21', 
            '26', 
            '41', 
            '90');
        $nodeTrade = M('tnode_info')->where("node_id='{$this->nodeId}'")->getField(
            'trade_type');
        if ($this->node_type_name == 'staff' || ($this->node_type_name == 'c2' && ! in_array(
            $nodeTrade, $tradeType))) {
            return true;
        }
        return false;
    }
}