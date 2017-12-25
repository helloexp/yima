<?php
// 首页
class AccountCenterAction extends IndexAction {

    public function index() {
        
        // 获取商户服务信息
        $nodeSerList = array();
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户服务信息报文参数
        $req_array = array(
            'QueryShopServReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $this->nodeId, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $this->contractId));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $nodeSerInfo = $RemoteRequest->requestYzServ($req_array);
        // dump($nodeSerInfo);
        if (! $nodeSerInfo || ($nodeSerInfo['Status']['StatusCode'] != '0000' &&
             $nodeSerInfo['Status']['StatusCode'] != '0001')) {
            $nodeSerInfo = array();
        }
        if (! empty($nodeSerInfo['ServList']['Serv'])) {
            if (isset($nodeSerInfo['ServList']['Serv'][0])) {
                $nodeSerList = array_merge($nodeSerList, 
                    $nodeSerInfo['ServList']['Serv']);
            } else {
                $nodeSerList[] = $nodeSerInfo['ServList']['Serv'];
            }
            // 获取服务描述
            foreach ($nodeSerList as $k => $v) {
                $nodeSerList[$k]['memo'] = M('tcharge_info')->where(
                    "charge_id={$v['ServCode']}")->getField('charge_memo');
            }
        }
        // dump($nodeSerList);
        
        // //获取商户终端服务信息
        // $nodeTerList = array();
        // $TransactionID = date("YmdHis").mt_rand(100000,999999); //请求单号
        // //商户终端服务信息报文参数
        // $req_array = array(
        // 'QueryPosServReq'=>array(
        // 'SystemID'=>C('YZ_SYSTEM_ID'),
        // 'NodeID'=>$this->nodeId,
        // 'TransactionID'=>$TransactionID,
        // 'ContractID'=>$this->contractID
        // )
        // );
        // $RemoteRequest = D('RemoteRequest','Service');
        // $nodeTerInfo = $RemoteRequest->requestYzServ($req_array);
        // //dump($nodeTerInfo);exit;
        // if(!$nodeTerInfo||($nodeTerInfo['Status']['StatusCode'] != '0000'
        // && $nodeTerInfo['Status']['StatusCode'] != '0001'))
        // {
        // $nodeSerList = array();
        // }
        // if(!empty($nodeTerInfo['PosList']['Pos'])){
        // if(isset($nodeTerInfo['PosList']['Pos'][0])){
        // $nodeTerList =
        // array_merge($nodeTerList,$nodeTerInfo['PosList']['Pos']);
        // }else{
        // $nodeTerList[] = $nodeTerInfo['PosList']['Pos'];
        // }
        // }
        // dump($nodeTerList);exit;
        
        $this->assign('nodeSerList', $nodeSerList);
        // $this->assign('nodeTerList',$nodeTerList);
        $this->display();
    }
    
    /*
     * //首页 public function index(){ if($this->charge_id){//已购买服务界面
     * //获取礼包单项中的套餐信息 $packagesInfo = M()->Table('tcharge_relation r')
     * ->join('tcharge_info i ON r.relation_id=i.charge_id')
     * ->where("r.charge_id='{$this->charge_id}' AND i.charge_type=1") ->find();
     * if(!$packagesInfo){ $this->error('未找到套餐信息'); }
     * $this->assign('packagesInfo',$packagesInfo); $this->display('buyIndex');
     * }else{//未购买服务界面 $this->display(); } $chargeInfoModel = D('TchargeInfo');
     * //购买页面,获取所有旺财终端服务 if($this->terminalCount > 0){ $TerminalServices =
     * $chargeInfoModel->getChargeInfoByType(2,2); } //获取所有旺财营销服务
     * $MarketingServices = $chargeInfoModel->getChargeInfoByType(2,1);
     * $this->assign('TerminalServices',$TerminalServices);
     * $this->assign('MarketingService',$MarketingServices);
     * $this->assign('terminalCount',$this->terminalCount); $this->display(); }
     * //礼包列表页 public function chargeInfoList(){
     * if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } //获取礼包信息 $chargeInfoModel
     * = M('tcharge_info'); $where = array(//查询所有礼包 'charge_type' => '0' );
     * $chargeInfo = $chargeInfoModel->where($where)->select(); //处理礼包内数据
     * if($chargeInfo){ foreach ($chargeInfo as $k=>$v){ $arr = explode('|',
     * $v['charge_memo']); // foreach ($arr as $k_1=>$v_1){ // $arr[$k_1] =
     * explode('~', $v_1); // } $chargeInfo[$k]['charge_memo'] = $arr; } }
     * $this->assign('chargeInfo',$chargeInfo); $this->display(); } //填写订单信息
     * public function goBuy(){ if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } $chargeId =
     * $this->_post('charge_id','trim'); if(empty($chargeId)){
     * $this->error('请选择要购买的礼包'); } //检查是否是有效礼包 $chargeInfoModel =
     * M('tcharge_info'); $where = array( 'charge_id' => $chargeId,
     * 'charge_type' => 0 ); $chargeInfo =
     * $chargeInfoModel->where($where)->find(); if(!$chargeInfo){
     * $this->error('未找到礼包信息'); } $this->assign('chargeInfo',$chargeInfo);
     * $this->display(); } //确认订单 public function confirmOrder(){
     * if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } $data = array_map('trim',
     * $_POST); //数据验证 if(empty($data['charge_id'])){ $this->error('参数错误'); }
     * //检查是否是有效礼包 $chargeInfoModel = M('tcharge_info'); $where = array(
     * 'charge_id' => $data['charge_id'], 'charge_type' => 0 ); $chargeInfo =
     * $chargeInfoModel->where($where)->find(); if(!$chargeInfo){
     * $this->error('未找到礼包信息'); } $error = '';
     * if(!check_str($data['contact_name'],array('null'=>false),$error)){
     * $this->error("请填写收货人姓名"); }
     * if(!check_str($data['contact_addr'],array('null'=>false),$error)){
     * $this->error("请填写详细地址"); }
     * if(!check_str($data['contact_mobile'],array('null'=>false,'strtype'=>'mobile'),$error)){
     * $this->error("手机号码{$error}"); } if(!isset($data['agreement']) ||
     * $data['agreement']!='1' ){ $this->error("您还没同意我们的《旺财礼包购买协议》"); }
     * $data['order_id'] = get_sn(); $data['user_id'] = $this->userId;
     * $data['node_id'] = $this->nodeId; $data['add_time'] = date('YmdHis');
     * $data['charge_name'] = $chargeInfo['charge_name']; $data['busi_amt'] =
     * $chargeInfo['charge_amt']; session('orderInfo',$data);
     * $this->assign('chargeInfo',$chargeInfo); $this->assign('data',$data);
     * $this->display(); } //支付订单 public function payOrder(){
     * if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } $data =
     * session('orderInfo'); if(empty($data)) $this->error('缺少必要参数'); //添加订单
     * $result = M('torder_list')->data($data)->add(); if(!$result)
     * $this->error('订单添加失败'); //更新商户表推荐人信息 if(isset($data['reference_name']) &&
     * $data['reference_name']!=''){
     * M('tnode_info')->where("node_id='{$data['node_id']}'")->save(array('reference_name'
     * => $data['reference_name'],)); } session('orderInfo',null); //调用支付宝接口
     * import('Home.Vendor.AlipayModel'); $alipayModel = new AlipayModel();
     * $alipayModel->AlipayTo($data['order_id'],$data['charge_name'],$data['busi_amt']);
     * }
     */
}