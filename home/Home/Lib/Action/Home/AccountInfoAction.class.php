<?php

// 首页
class AccountInfoAction extends BaseAction {

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        // 特殊权限判断
        // 如果是管理员不校验
        if ($userInfo['user_name'] == 'admin') {
            $this->_authAccessMap = '*';
        }
        // 如果是首页 消息页，不则校验
        if (in_array(ACTION_NAME, 
            array(
                'index', 
                'message', 
                'message_new', 
                'pmessage_new', 
                'batch_msg', 
                'node_msg', 
                'message_view'))) {
            $this->_authAccessMap = '*';
        }
        /*
         * //判断如果没有账户权限就跳到修改密码页
         * elseif(!$this->_checkUserAuth('Home/AccountInfo/index')){
         * redirect(U('Home/EditPwd/index')); }
         */
        $this->_authAccessMap = '*';
        parent::_initialize();
    }

    /**
     * APP账户
     */
    public function wap_account()
    {
        $nodeInfo = $this->nodeInfo;
        //app_default_pos终端号  可根据终端号在tpos_info表中拿门店号
        //账户也是终端号 个人头像默认

        if($this->position){
            $flowInfo = $this->getAccountInfo();
            //AccountPrice是余额  WbPrice是旺币
            $this->assign('flowInfo',$flowInfo);
            $this->assign('nodeInfo',$nodeInfo);

            //免费版升级付费版需要调营账接口
        }

        $this->assign('position',$this->position);
        $this->assign('nodeInfo',$nodeInfo);
    }

    public function index() {
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('nodeid', $this->nodeId);
        $this->assign('head_photo', $nodeInfo['head_photo']);
        $this->assign('node_pay_time', $nodeInfo['node_pay_time']);
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();

        // 多乐互动
        $model = M('tmarketing_info');
        // 所有多乐互动数
        $batchNum = $model->where(
            array(
                'node_id' => $this->nodeId))->count();
        $batchingNum = $model->where(
            array(
                'node_id' => $this->nodeId, 
                'status' => '1'))->count();
        
        // 卡券
        $numGoodsSumInfo = M('tbatch_info')->field(
            'COUNT(DISTINCT batch_no) as goods_count')
            ->where("node_id='{$this->nodeId}'")
            ->find();
        $numGoodsSum = $numGoodsSumInfo['goods_count'];
        $sendSum = M('tpos_day_count')->where("node_id='{$this->nodeId}'")->sum(
            'send_num');
        $sendSum = is_null($sendSum) ? 0 : $sendSum;
        
        // 渠道
        $channelSum = M('tchannel')->where("node_id='{$this->nodeId}'")->count();
        $clickCount = M('tchannel')->where("node_id='{$this->nodeId}'")->sum(
            'click_count');
        $clickCount = is_null($clickCount) ? 0 : $clickCount;
        
        // 粉丝
        $memberSum = M('tmember_info')->where("node_id='{$this->nodeId}'")->count();
        
        // 创建接口对象
        $RemoteRequest = D('RemoteRequest', 'Service');
        // 获取商户认证信息
        if ($nodeInfo['check_status'] != '2') {
            $nodeCheckInfo = array();
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 商户认证信息报文参数
            $req_array = array(
                'CertifQueryReq' => array(
                    'TransactionID' => $TransactionID, 
                    'SystemID' => C('YZ_SYSTEM_ID'), 
                    'ClientID' => $nodeInfo['client_id']));
            
            $nodeCheckInfo = $RemoteRequest->requestYzServ($req_array);
            if (! empty($nodeCheckInfo['CertifDate'])) {
                $data = array(
                    'check_status' => '2', 
                    'node_check_time' => $nodeCheckInfo['CertifDate']);
                if ($this->wc_version == 'v0')
                    $data['wc_version'] = 'v0.5';
                $result = M('tnode_info')->where("node_id='{$this->nodeId}'")->save(
                    $data);
                if ($result) {
                    unset($nodeInfo['check_status']);
                    $nodeInfo['check_status'] = '2';
                }
            }
        }
        // 获取合同信息
        if($this->hasPayModule('m1') || $this->hasPayModule('m2') || $this->nodeInfo['sale_flag'])
        {
            $this->assign('showDocDownload',1);
        }
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
        $nodeSerInfo = $RemoteRequest->requestYzServ($req_array);
        // dump($nodeSerInfo);exit;
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
        
        $this->assign('nodeSerList', $nodeSerList);
        // dump($nodeSerList);die;
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $storecount = M('tstore_info')->where($map)->count();
        
        $nodeWbList = $this->getWbInfo();
        $this->assign('nodeWbList', $nodeWbList);
        $this->assign('storecount', $storecount);
        $this->assign('batchNum', $batchNum);
        $this->assign('batchingNum', $batchingNum);
        $this->assign('numGoodsSum', $numGoodsSum);
        $this->assign('sendSum', $sendSum);
        $this->assign('channelSum', $channelSum);
        $this->assign('clickCount', $clickCount);
        $this->assign('memberSum', $memberSum);
        $this->assign('node', $nodeInfo);
        $this->assign('node_id', $this->nodeId);
        $this->assign('check_status', $nodeInfo['check_status']);
        
        $this->assign('nodename', $nodeInfo['node_name']);
        $this->assign('node_license_value', $nodeInfo['node_license_img']);
        $this->assign('node_license_img', 
            get_upload_url('business/' . $nodeInfo['node_license_img']));
        $this->assign('node_short_name', $nodeInfo['node_short_name']);
        $this->assign('contact_name', $nodeInfo['contact_name']);
        $this->assign('contact_phone', $nodeInfo['contact_phone']);
        $this->assign('node_service_hotline', $nodeInfo['node_service_hotline']);
        $this->assign('clientid', 
            str_pad($nodeInfo['client_id'], 6, '0', STR_PAD_LEFT));
        $this->assign('nodetype', $nodeInfo['node_type']);
        $this->assign('FlowInfo', $this->getAccountInfo());
        
        $this->assign('type_c', $this->node_type_name);
        $this->assign('user_info', $this->userInfo);
        $this->display();
    }
    
    // 收款帐号信息
    public function shoukuan() {
        // 获取商户收款帐号信息
        $nodeInfo = M('tnode_info')->field(
            'client_id,node_name,check_status,node_short_name,node_license_img,node_type,contact_name,contact_phone,node_service_hotline,check_status,check_memo,receive_phone,person_url')
            ->where("node_id='" . $this->nodeId . "'")
            ->find();
        $this->assign('node_id', $this->nodeId);
        $accountInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->nodeId, 
                'account_type' => '1'))->find();
        $accountBankArr = array(
            '0' => '银联', 
            '1' => '支付宝', 
            '2' => '银行卡');
        $this->assign('receive_phone', $nodeInfo['receive_phone']);
        $this->assign('accountInfo', $accountInfo);
        $this->assign('accountBankArr', $accountBankArr);
        $this->display();
    }
    
    // 旺币
    public function peak() {
        // 旺币信息
        $nodeWbList = $this->getWbInfo();
        $accountInfo = $this->getAccountInfo();
        $this->assign('nodeWbList', $nodeWbList);
        $this->assign('accountInfo', $accountInfo);
        $this->display();
    }
    
    // 下载电子合约
    public function print_xy() {
        $node_name = M('tnode_info')->field('node_name,node_pay_time')
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        if ($node_name['node_pay_time'] == '' ||
             $node_name['node_pay_time'] < '20140805000000') {
            exit();
        }
        $time_xy = getdate(strtotime($node_name['node_pay_time']));
        $this->assign('node_name', $node_name['node_name']);
        $this->assign('year', $time_xy['year']); // 年
        $this->assign('mon', $time_xy['mon']); // 月
        $this->assign('mday', $time_xy['mday']); // 日
        $this->display();
    }

    /**
     * [downloadDoc 现在电子合约]
     */
    public function downloadDoc() {
        $doc_path =  'Home/Upload/pdf/'.$this->nodeId.'/';
        if(!is_dir($doc_path))
        {
            mkdir($doc_path);
        }
        $moduleArr = array(
            'm1'        => iconv('UTF-8','GBK','多乐互动'),
            'm2'        => iconv('UTF-8','GBK','多宝电商'),
            'sale_flag' => iconv('UTF-8','GBK','多米收单'),
            );
        
        if($this->hasPayModule('m1'))
        {
            $contents['m1'] = "Home/Upload/oto/Wservice_docm1.html";
        }
        if($this->hasPayModule('m2')){
            $contents['m2'] = "Home/Upload/oto/Wservice_docm2.html";
        }
        if($this->nodeInfo['sale_flag']){
            $contents['sale_flag'] = "Home/Upload/oto/Wservice_doc_alipay.html";
        }
        $img    = "Home/Upload/oto/yz.png";
        Vendor('tcpdf.tcpdf');
        // pdf文件路径
        $pdf_path = array();
        foreach ($contents as $module => $con) {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 
            'UTF-8', true);
            $pdf->AddPage();
            $html   = file_get_contents($con);
            $html   = str_replace("[甲方]", $this->nodeInfo['node_name'], $html);
            $html   = str_replace("[年]", date('Y'), $html);
            $html   = str_replace("[月]", date('m'), $html);
            $html   = str_replace("[日]", date('d'), $html);
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Image($img, 20, 80, 50, 50, 'PNG', '', '', true, 150, '', false, 
                false, 1, false, false, false);
            $txt = $pdf->Output('','S');
            $pdf_path[$module] =  $module;
            $file = fopen($doc_path.$pdf_path[$module].'.pdf', 'x');
            fwrite($file, $txt);
            fclose($file);
        }
        $zip = new ZipArchive();
        $zipfilename = 'edoc.zip';
        $zipfile_path = $doc_path . $zipfilename;
        if ($zip->open($zipfile_path, ZipArchive::OVERWRITE) === TRUE) {
            foreach ($pdf_path as $k => $v) {
                $zip->addFile($doc_path.$v.'.pdf', $moduleArr[$v].'.pdf');
            }
            $zip->close();
            redirect($zipfile_path);
        }
    }

    /**
     * 企业资质
     */
    /*
     * public function qualification(){ $info = D('tnode_info')->where("node_id
     * = '{$this->nodeId}'")->find(); $check_status = $info['check_status'];
     * if($this->isPost()){ if($check_status == '2'){
     * $this->error('企业资质已经审核，不允许编辑！'); } $node_short_name =
     * strip_tags(trim($_POST['node_short_name'])); $resp_img =
     * strip_tags(trim($_POST['resp_img'])); $node_service_hotline =
     * strip_tags(trim($_POST['node_service_hotline'])); if($node_short_name==''
     * || ( $info['node_license_img'] == '' && $resp_img=='')){
     * $this->error('参数错误'); } $data = array(
     * 'node_short_name'=>$node_short_name, 'node_license_img'=> $resp_img == ''
     * ? $info['node_license_img'] : $resp_img, 'node_service_hotline'=>
     * $node_service_hotline, 'check_status' => '0' ); $flag =
     * M('tnode_info')->where("node_id = '{$this->nodeId}'")->save($data);
     * if($flag === false) $this->error('保存失败！请重试！',
     * U('Home/AccountInfo/qualification')); if($resp_img != '' &&
     * $info['node_license_img'] != '')
     * @unlink(C('UPLOAD').$info['node_license_img']);
     * node_log("编辑企业资质：".$node_short_name);
     * $this->success('信息提交审核成功！请等待工作人员审核！',
     * U('Home/AccountInfo/qualification')); exit; }
     * if($info['node_license_img'] != '') $info['node_license_img'] =
     * C('UPLOAD'). 'business/' . $info['node_license_img'];
     * $this->assign('info', $info); $this->display(); }
     */
    
    /**
     * 企业资质上传
     */
    function qualification_upload() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024;
        $upload->savePath = C('UPLOAD') . 'business/'; // 设置附件
        
        $upload->allowExts = array(
            'gif', 
            'jpg', 
            'jpeg', 
            'png');
        
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }

    /**
     * 商户操作日志
     */
    function nodeLog() {
        $logModel = M('tweb_log_info');
        $wh_arr['_string'] = ' 1=1 ';
        
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $wh_arr['node_id '] = $nodeId;
        }
        $wh_arr['node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        
        // 关键字
        $keyword = I('keyword');
        if ($keyword != '' && $keyword != '日志内容')
            $wh_arr['_string'] .= " and log_info like '%" . $keyword . "%'";
            
            // 操作时间
        $add_begin_time = I('add_begin_time');
        if ($add_begin_time != '') {
            $wh_arr['_string'] .= " and add_time >= '{$add_begin_time}000000'";
        }
        $add_end_time = I('add_end_time');
        if ($add_end_time != '') {
            $wh_arr['_string'] .= " and add_time <= '{$add_end_time}235959'";
        }
        import('ORG.Util.Page'); // 导入分页类
        $count = $logModel->where($wh_arr)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $list = $logModel->where($wh_arr)
            ->order('log_id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('node_id', $this->nodeId);
        $this->assign('list', $list);
        $this->assign('page', $pageShow);
        $this->assign('keyword', $keyword);
        $this->assign('empty', '<tr><td colspan="3">无数据</td></span>');
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
    }

    /**
     * 账户基本信息
     */
    function node_service_hotline() {
        // dump(I('GET.'));die;
        $node_id = I('get.node_id');
        $type_arr = array(
            "1" => "企业简称", 
            "2" => "联系人", 
            "3" => "联系手机", 
            "4" => "企业服务热线", 
            "5" => "营业执照扫描件", 
            "6" => "接受通知手机号");
        $type = I('get.type');
        switch ($type) {
            case '1':
                $node_short_name = I('get.user_info');
                break;
            case '2':
                $contact_name = I('get.user_info');
                break;
            case '3':
                $contact_phone = I('get.user_info');
                break;
            case '4':
                $node_service_hotline = I('get.user_info');
                break;
            case '6':
                $receive_phone = I('get.user_info');
                break;
        }
        if ($node_service_hotline == 'undefined') {
            $node_service_hotline = '';
        }
        $edit = I('get.edit');
        if (! $edit) {
            if ($node_id == '') {
                $this->error('参数错误');
            }
            $this->assign("type", $type);
            $this->assign("type_arr", $type_arr);
            $this->assign("node_short_name", $node_short_name);
            $this->assign("contact_name", $contact_name);
            $this->assign("contact_phone", $contact_phone);
            $this->assign("receive_phone", $receive_phone);
            $this->assign("node_service_hotline", $node_service_hotline);
            $this->display();
        } else {
            $type = I('post.type');
            // 同步营帐和支撑
            $editNodeYzReq = array(
                'TransactionID' => $this->contractId, 
                'NodeId' => $this->nodeId, 
                'Name' => $this->userInfo['user_name'], 
                'ClientId' => $this->clientId);
            $editNodeZcReq = array(
                'NodeId' => $this->nodeId);
            
            switch ($type) {
                // case '1':
                // $node_short_name = I('post.node_short_name');
                // if(!check_str($node_short_name,array('null'=>false,'maxlen_cn'=>'20'),$error)){
                // $this->error($type_arr[$type].$error);
                // }
                // $data = array(
                // "node_short_name"=>$node_short_name
                // );
                // $node_center_log = $node_short_name;
                // break;
                case '2':
                    $contact_name = I('post.contact_name');
                    if (! check_str($contact_name, 
                        array(
                            'null' => false, 
                            'maxlen_cn' => '10'), $error)) {
                        $this->error($type_arr[$type] . $error);
                    }
                    $data = array(
                        "contact_name" => $contact_name);
                    $editNodeYzReq['UserName'] = $contact_name;
                    $editNodeZcReq['ContactName'] = $contact_name;
                    $node_center_log = $contact_name;
                    break;
                case '3':
                    $contact_phone = I('post.contact_phone');
                    if (! check_str($contact_phone, 
                        array(
                            'null' => false, 
                            'maxlen_cn' => '15'), $error)) {
                        $this->error($type_arr[$type] . $error);
                    }
                    $data = array(
                        "contact_phone" => $contact_phone);
                    $editNodeYzReq['UserTel'] = $contact_phone;
                    $editNodeZcReq['ContactPhone'] = $contact_phone;
                    $node_center_log = $contact_phone;
                    break;
                case '4':
                    $node_service_hotline = I('post.node_service_hotline');
                    if (! check_str($node_service_hotline, 
                        array(
                            'null' => false, 
                            'maxlen_cn' => '18'), $error)) {
                        $this->error($type_arr[$type] . $error);
                    }
                    $data = array(
                        "node_service_hotline" => $node_service_hotline);
                    $editNodeYzReq['ServiceTel'] = $node_service_hotline;
                    $editNodeZcReq['ServiceLine'] = $node_service_hotline;
                    $node_center_log = $node_service_hotline;
                    break;
                // case '5':
                // $resp_img = I('post.resp_img');
                // if(!check_str($resp_img,array('null'=>false,'maxlen_cn'=>'100'),$error)){
                // $this->error($type_arr[$type].$error);
                // }
                // $data = array(
                // "node_license_img"=>$resp_img,
                // "check_status"=>'3'
                // );
                // $node_center_log = $resp_img;
                // break;
                case '6':
                    $receive_phone = I('post.receive_phone');
                    if (! check_str($receive_phone, 
                        array(
                            'null' => false, 
                            'maxlen_cn' => '15'), $error)) {
                        $this->error($type_arr[$type] . $error);
                    }
                    $data = array(
                        "receive_phone" => $receive_phone);
                    // $editNodeInfoReq['receive_phone'] = $receive_phone;
                    $node_center_log = $receive_phone;
                    break;
            }
            // 同步支撑
            
            if (count($editNodeYzReq) > 2) {
                $RemoteRequest = D('RemoteRequest', 'Service');
                // 通知营帐
                $yz_array = array(
                    'EditAUZInfoReq' => $editNodeYzReq);
                $resp_array = $RemoteRequest->requestYzServ($yz_array);
                $ret_msg = $resp_array['Status'];
                if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                     $ret_msg['StatusCode'] != '0001')) {
                    log_write("Yz商户信息修改失败，原因：{$ret_msg['StatusText']}");
                    $this->error('Yz修改失败:' . $ret_msg['StatusText']);
                }
                // 通知支撑
                $zc_array = array(
                    'EditNodeInfoReq' => $editNodeZcReq);
                $resp_array = $RemoteRequest->requestIssForImageco($zc_array);
                $ret_msg = $resp_array['EditNodeInfoRes']['Status'];
                if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                     $ret_msg['StatusCode'] != '0001')) {
                    log_write("Zc商户信息修改失败，原因：{$ret_msg['StatusText']}");
                    $this->error('Zc修改失败:' . $ret_msg['StatusText']);
                }
            }
            $result = M('tnode_info')->where('node_id = ' . $this->nodeId)->save(
                $data);
            if ($result === false) {
                $this->error($type_arr[$type] . '填写失败');
                exit();
            }
            // 更新用户表
            $result = M('tuser_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'new_role_id' => 2))->save(
                array(
                    'true_name' => $contact_name));
            node_log("编辑" . $type_arr[$type] . "为：" . $node_center_log);
            $this->success($type_arr[$type] . '填写成功');
            exit();
        }
    }

    /*
     * 消息中心
     */
    // 系统消息
    public function message() {
        import('@.ORG.Util.Page');
        $wh_arr = " node_id='{$this->nodeId}' ";
        $status = $_REQUEST['status']; // 全部空,已读1,未读0;
        $Model = new Model();
        $tmessage_newsModel = M('tmessage_news');
        
        // 取出消息条数按已读未读分组
        $results = M('tnode_info')->where("node_id = $this->nodeId")
            ->field('add_time')
            ->find();
        $nodeIdAddTime = date("YmdHis", strtotime($results['add_time'])); // node_id创建时间
        $whe['tmessage_news.add_time'] = array(
            'gt', 
            $nodeIdAddTime);
        $whe['tmessage_news.status'] = 0; // 状态正常
        
        $time = $nodeIdAddTime;
        
        $unReadMsg = $Model->query(
            "SELECT total_cnt,new_message_cnt FROM tmessage_stat WHERE node_id = '$this->nodeId' and message_type=1");
        
        $sumCount = $unReadMsg[0]['total_cnt']; // 消息总数量
        $unreadCount = $unReadMsg[0]['new_message_cnt']; // 消息未读数量
        
        switch ($status) {
            case null: // null全部
                $yp = new Page($sumCount, 10);
                $yp->setConfig('theme', 
                    '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
                $yp->setConfig('prev', '«');
                $yp->setConfig('next', '»');
                // 以tmessage_news为左表外连tmessage_news的id=tmessage_recored的message_id
                // 查询条件为tmessage_news的status=0并且node_id并且tmessage_recored的add_time要大于$time查出八个字段的所有数据
                // 查询tmessage_news表条件为tmessage_news的status=0并且id不在tmessage_recored表的message_id中
                // 并且tmessage_news的add_time要大于$time 并且 send_to_who要等于1
                // 查出八个字段的所有数据
                // 将两次查询的结果合并为一个结果
                $sql = "SELECT * FROM (
SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_recored.add_time AS add_time,
tmessage_recored.seq_id,tmessage_recored.status,tmessage_news.msg_type
FROM `tmessage_news` LEFT JOIN tmessage_recored ON tmessage_news.id = tmessage_recored.message_id
 WHERE ( tmessage_news.status = 0 ) AND ( $wh_arr ) AND(tmessage_recored.add_time > '$time')
UNION
SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_news.add_time AS add_time,
NULL,'0',tmessage_news.msg_type
FROM `tmessage_news`  WHERE ( tmessage_news.status = 0 ) AND id NOT IN (  SELECT message_id FROM `tmessage_recored` WHERE node_id='$this->nodeId' ) AND(tmessage_news.add_time > '$time') AND send_to_who = '1'
) a ORDER BY a.add_time DESC limit $yp->firstRow,$yp->listRows";
                
                $message_news = $Model->query($sql);
                break;
            
            case 0: // 0未读
                $yp = new Page($unreadCount, 10);
                $yp->setConfig('theme', 
                    '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
                $yp->setConfig('prev', '«');
                $yp->setConfig('next', '»');
                
                $sql = "SELECT * FROM (
SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_recored.add_time AS add_time,
tmessage_recored.seq_id,tmessage_recored.status,tmessage_news.msg_type
FROM `tmessage_news` LEFT JOIN tmessage_recored ON tmessage_news.id = tmessage_recored.message_id
 WHERE ( tmessage_news.status = 0 ) AND ( $wh_arr ) AND(tmessage_recored.add_time > '$time') AND(tmessage_recored.status = 0)
UNION
SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_news.add_time AS add_time,
NULL,'0',tmessage_news.msg_type
FROM `tmessage_news`  WHERE ( tmessage_news.status = 0 ) AND id NOT IN (  SELECT message_id FROM `tmessage_recored` WHERE node_id='$this->nodeId' ) AND(tmessage_news.add_time > '$time') AND send_to_who = '1'
) a ORDER BY a.add_time DESC limit $yp->firstRow,$yp->listRows";
                $message_news = $Model->query($sql);
                break;
            
            case 1: // 1已读
                $wh_arr .= ' and tmessage_recored.status = ' . $status;
                
                $yp = new Page($sumCount - $unreadCount, 10);
                $yp->setConfig('theme', 
                    '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
                $yp->setConfig('prev', '«');
                $yp->setConfig('next', '»');
                
                $alreadyRead = $tmessage_newsModel->join(
                    "tmessage_recored on tmessage_news.id = tmessage_recored.message_id")
                    ->where($whe)
                    ->where($wh_arr)
                    ->limit($yp->firstRow, $yp->listRows)
                    ->select();
                $message_news = $alreadyRead;
                break;
            default:
        }
        
        $yps = $yp->show();
        $this->assign('sumCount', $sumCount); // 总数
        $this->assign('unreadCount', $unreadCount); // 未读
        $this->assign('alreadyReadCount', $sumCount - $unreadCount); // 已读
        $this->assign('newData', $message_news);
        $this->assign('pages', $yps);
        $timeName = array(
            'tweek' => '本周', 
            'lweek' => '上周', 
            'aweek' => '更早');
        // user_act_log('进入消息中心','',array('act_code'=>'3.5.3.1'));
        $this->assign('timeName', $timeName);
        $this->assign('readstatus', $status);
        $this->display();
    }
    
    // 设为已读
    public function readed() {
        $wh_arr = " node_id='{$this->nodeId}' ";
        $Model = new Model();
        
        // 查询tmessage_news条件为id不在 send_to_who字段为1的tmessage_recored表的message_id之中
        $sql = "SELECT id,add_time FROM tmessage_news WHERE id NOT IN
        (  SELECT message_id FROM `tmessage_recored` WHERE $wh_arr )
        AND send_to_who = '1';
        ";
        $toAllUser = $Model->query($sql);
        
        $tmessage_recored = M('tmessage_recored');
        if ($toAllUser) {
            // 遍历数组 挨个插入数据库 将所有未读数据插入数据库
            foreach ($toAllUser as $k => $v) {
                $data = array(
                    "message_id" => $v['id'], 
                    "node_id" => $this->nodeId, 
                    "send_status" => '0', 
                    "status" => '0', 
                    "add_time" => $v['add_time']);
                $tmessage_recored->add($data);
            }
        }
        
        // 将表中所有数据更新为已读状态
        $result = M('tmessage_recored')->where(
            array(
                'status' => 0))
            ->where($wh_arr)
            ->save(
            array(
                'status' => 1, 
                'read_time' => date('YmdHis', time())));
        
        // 将tmessage_stat表中系统消息中的新消息条数清零
        M()->query(
            'UPDATE `tmessage_stat`
			SET `last_time`="' .
                 date('YmdHis') . '",`new_message_cnt`=0 WHERE
			( `node_id` = "' .
                 $this->node_id . '" ) AND ( `message_type` = 1 )');
        
        if ($result)
            $this->success('1');
        else
            $this->success('0');
    }
    
    // 在线留言
    public function message_new() {
        import('@.ORG.Util.Page'); // 导入分页类
        $sql = "(select @tnode_name:='我的帖子' hnode_name ,ti2.node_name,ti2.head_photo,
				r2.contents , r2.status,b.liuyan_title hcontents,r2.add_time,r2.BoardID, r2.id,r2.node_id
			    from twc_board b, twc_restoer r2  
				left join tnode_info ti2 on ti2.node_id = r2.node_id 
				where b.screen = 0  and r2.screen = 0   
 				and b.node_id=$this->node_id
			    and  r2.peID = 0  and r2.BoardID=b.id  order by r2.add_time desc ) 
				union 
				(select ti1.node_name hnode_name,ti2.node_name,ti2.head_photo,
				r2.contents ,@status:='1' status,r1.contents hcontents,r2.add_time,r2.BoardID, r2.id,r2.node_id
			    from twc_board b, twc_restoer r1,twc_restoer r2  
				left join tnode_info ti1 on ti1.node_id = r2.quilt_user  
				left join tnode_info ti2 on ti2.node_id = r2.node_id 
				where b.screen = 0  and r1.screen = 0  and r2.screen = 0   
 				and b.node_id=$this->node_id and r2.quilt_user <> 0
				and r1.BoardID = r2.BoardID and  r2.peID = r1.id and r2.BoardID=b.id
				and r2.quilt_user <>  '$this->node_id' order by r2.add_time desc ) 
					union 
			    (select ti1.node_name hnode_name,ti2.node_name,ti2.head_photo,
				r2.contents,r2.status, r1.contents hcontents,r2.add_time,r2.BoardID, r2.id,r2.node_id
			    from twc_board b, twc_restoer r1,twc_restoer r2  
				left join tnode_info ti1 on ti1.node_id = r2.quilt_user  
				left join tnode_info ti2 on ti2.node_id = r2.node_id 
				where b.screen = 0  and r1.screen = 0  and r2.screen = 0   
 				and r1.BoardID = b.id  and r2.BoardID = b.id 
				and r2.peID = r1.id and r2.quilt_user ='$this->node_id' order by r2.add_time desc)";
        $count = M()->query("select count(*) c from ($sql)t");
        $count = $count[0][c];
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $pageShow = $Page->show(); // 分页显示输出
        $list = M()->query(
            "select *  from ($sql)t order by add_time desc limit $Page->firstRow,$Page->listRows");
        user_act_log('在线留言', '', 
            array(
                'act_code' => '3.5.3.4'));
        $this->assign('message_feedback', $list);
        $this->assign('page', $pageShow);
        $this->display();
    }
    
    // 商户留言
    public function node_msg() {
        $node_id = $this->node_id;
        
        $in_Where1 = " and status in(0,1,2)";
        $in_Where2 = " and t.status in(0,1,2)";
        import("@.ORG.Util.Page"); // 导入分页类
        $queryPara = "receive_node_id";
        $queryPara2 = "send_node_id";
        $sql = "SELECT count(*) as count FROM (SELECT * FROM tmessage_info where  laiyuan_type in(0,1)   and  (" .
             $queryPara . " ='" . $node_id . "' or    " . $queryPara2 . " ='" .
             $node_id . "')  $in_Where1   group by reply_id) as f";
        $mapcount = M()->query($sql);
        $p = new Page($mapcount[0]['count'], 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $p->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $p->setConfig('prev', '«');
        $p->setConfig('next', '»');
        $show = $p->show(); // 分页显示输出
        $sql = "SELECT   reply_id ,send_node_id,receive_node_id,node_name,head_photo FROM (SELECT t.*,n.node_name,head_photo FROM tmessage_info t left join tnode_info n on n.node_id=t." .
             $queryPara2 . " where  t.laiyuan_type in(0,1)    and  (t." .
             $queryPara . " ='" . $node_id . "' or  t." . $queryPara2 . " ='" .
             $node_id .
             "' )  $in_Where2 group by reply_id  ORDER BY id DESC)  message   ORDER BY id  DESC limit " .
             $p->firstRow . "," . $p->listRows;
        $list = M()->query($sql);
        if (! empty($list)) {
            foreach ($list as $ck => $cal) {
                // 查询此机构下的交流信息
                $sql = "SELECT t.*,n.node_name,d.node_name as receive_node_name from tmessage_info t
					left join tnode_info n on n.node_id=t.send_node_id
					left join tnode_info d on d.node_id=t.receive_node_id
					where t.laiyuan_type in (0,1)  and t.reply_id = " .
                     $cal['reply_id'] . " and  ((t.send_node_id='" .
                     $cal['send_node_id'] . "' and t.receive_node_id='" .
                     $cal['receive_node_id'] . "' ) OR ( t.send_node_id='" .
                     $cal['receive_node_id'] . "' and  t.receive_node_id='" .
                     $cal['send_node_id'] . "')) $in_Where2 order by t.id desc";
                $childInfo = M()->query($sql);
                $list[$ck]['child'] = $childInfo;
            }
        }
        $this->assign('list', $list);
        
        $this->assign('page', $show);
        $this->assign('node_id', $node_id);
        $this->display();
    }
    
    // 商户回复留言
    function reply_msg() {
        $reply_id = I("reply_id"); // send_node_id
        $reply_message = I("reply_message");
        if (empty($reply_id)) {
            $this->error("回复失败，参数错误！");
        }
        // 查询父留言
        $messageInfo = M('tmessage_info')->where(
            "send_node_id='" . $reply_id . "'")
            ->order("id desc")
            ->find();
        
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        
        $data = array(
            "message_text" => $reply_message, 
            "send_node_id" => $userInfo['node_id'], 
            "send_user_id" => $userInfo['user_id'], 
            "receive_node_id" => $reply_id, 
            "receive_user_id" => $messageInfo['send_user_id'], 
            "m_id" => $messageInfo['m_id'], 
            "ck_status" => '1', 
            "status" => 4, 
            "reply_id" => $_REQUEST['reply_id1'], 
            'laiyuan_type' => $_REQUEST['msgtype'], 
            "add_time" => date("YmdHis"));
        $result = M('tmessage_info')->data($data)->add();
        $returnArr = array();
        if ($result) {
            if ($_REQUEST['msgtype'] == 1)
                $type = 2;
            if ($_REQUEST['msgtype'] == 2)
                $type = 3;
                //
            $statmsgcount = M('tmessage_info')->where(
                array(
                    'receive_node_id' => $userInfo['node_id'], 
                    "reply_id" => $_REQUEST['reply_id1'], 
                    'status' => 0, 
                    'ck_status' => 1))->count();
            M('tmessage_info')->where(
                array(
                    'receive_node_id' => $userInfo['node_id'], 
                    "reply_id" => $_REQUEST['reply_id1'], 
                    'status' => 0, 
                    'ck_status' => 1))->save(
                array(
                    'ck_status' => 2, 
                    'status' => 1));
            $sysmsgcunt = M('tmessage_stat')->field('new_message_cnt')
                ->where(
                array(
                    'node_id' => $userInfo['node_id'], 
                    'message_type' => $type))
                ->find();
            $sysmsgcunt = $sysmsgcunt['new_message_cnt'];
            if ($sysmsgcunt > 0 && ((int) $sysmsgcunt - (int) $statmsgcount) >= 0)
                M()->query(
                    'UPDATE `tmessage_stat`
						SET `last_time`="' . date('YmdHis') .
                         '",`new_message_cnt`=`new_message_cnt`-' . $statmsgcount . ' WHERE
						( `node_id` = "' .
                         $this->node_id . '" ) AND ( `message_type` = ' . $type .
                         ' )');
            else
                M()->query(
                    'UPDATE `tmessage_stat`
						SET `last_time`="' .
                         date('YmdHis') . '",`new_message_cnt`= 0  WHERE
						( `node_id` = "' .
                         $this->node_id . '" ) AND ( `message_type` = ' . $type .
                         ' )');
                
                // 查询回复给谁的机构号名称
            $nodeInfo = M('tnode_info')->field("node_name,head_photo")
                ->where("node_id='" . $reply_id . "'")
                ->find();
            
            $task_info = "";
            // 调用评论任务
            // 这儿调用任务服务开始
            $task = D('Task', 'Service')->getTask('guestbook_submit');
            if ($task) {
                $taskResult = $task->start($reply_message);
                log_write('任务结果' . print_r($taskResult, true));
                if ($taskResult && $taskResult['code'] == '0') {
                    $task_info = $taskResult['msg'];
                }
            }
            // 调用任务结束
            $returnArr = array(
                "receive_node_name" => $nodeInfo['node_name'], 
                "head_photo" => get_upload_url($nodeInfo['head_photo']), 
                "message_text" => $reply_message, 
                "add_time" => date("YmdHis"), 
                "id" => $result, 
                "task_info" => $task_info, 
                "info" => "提交成功", 
                "status" => 1);
            echo json_encode($returnArr);
            exit();
        } else {
            
            $this->error("回复失败，入库异常！");
        }
    }
    
    // 来自留言的私信
    function pmessage_new() {
        $node_id = $this->node_id;
        
        $in_Where1 = " and status in(0,1,2)";
        $in_Where2 = " and t.status in(0,1,2)";
        
        import("@.ORG.Util.Page"); // 导入分页类
        
        $queryPara = "receive_node_id";
        $queryPara2 = "send_node_id";
        
        $sql = "SELECT count(*) as count FROM (SELECT * FROM tmessage_info where  laiyuan_type in(2) and  (" .
             $queryPara . " ='" . $node_id . "' or    " . $queryPara2 . " ='" .
             $node_id . "')  $in_Where1 group by reply_id) as f";
        $mapcount = M()->query($sql);
        $p = new Page($mapcount[0]['count'], 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $p->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $p->setConfig('prev', '«');
        $p->setConfig('next', '»');
        $show = $p->show(); // 分页显示输出
        $sql = "SELECT  reply_id, send_node_id,receive_node_id,node_name,head_photo FROM (SELECT t.*,n.node_name,head_photo FROM tmessage_info t left join tnode_info n on n.node_id=t." .
             $queryPara2 . " where  t.laiyuan_type in(2) and  (t." . $queryPara .
             " ='" . $node_id . "' or  t." . $queryPara2 . " ='" . $node_id .
             "' )  $in_Where2  group by reply_id  ORDER BY id DESC)  message   ORDER BY id  DESC limit " .
             $p->firstRow . "," . $p->listRows;
        $list = M()->query($sql);
        if (! empty($list)) {
            foreach ($list as $ck => $cal) {
                // 查询此机构下的交流信息
                $sql = "SELECT t.*,n.node_name,d.node_name as receive_node_name from tmessage_info t
					left join tnode_info n on n.node_id=t.send_node_id
					left join tnode_info d on d.node_id=t.receive_node_id
					where t.laiyuan_type in (2) and  t.reply_id=" .
                     $cal['reply_id'] . "  and  ((t.send_node_id='" .
                     $cal['send_node_id'] . "' and t.receive_node_id='" .
                     $cal['receive_node_id'] . "' ) OR ( t.send_node_id='" .
                     $cal['receive_node_id'] . "' and  t.receive_node_id='" .
                     $cal['send_node_id'] . "')) $in_Where2 order by t.id desc";
                $childInfo = M()->query($sql);
                
                $list[$ck]['child'] = $childInfo;
            }
        }
        $this->assign('list', $list);
        
        $this->assign('page', $show);
        $this->assign('node_id', $node_id);
        $this->display();
    }
    
    // 查询 O2O在线活动
    public function batch_msg() {
        $node_id = $this->node_id;
        
        // 更新发给我的留言为已经阅读
        /*
         * $saveData=array( "ck_status"=>1, );
         * M('tbatch_guestbook')->where("touser='".$node_id."'")->save($saveData);
         */
        
        // 查询我发表的留言
        /*
         * $msgInfo =
         * M('tbatch_guestbook')->field("id")->where("node_id='".$node_id."' or
         * touser='".$node_id."'")->select(); $idArr=array();
         * if(!empty($msgInfo)){ foreach($msgInfo as $k=>$val){
         * $idArr[$k]=$val['id']; } }
         */
        
        $where = "WHERE ( t.touser='" . $node_id . "')";
        
        /*
         * if(!empty($idArr)){ $count=count($idArr); if($count>0){
         * $likewhere=""; for($i=0;$i<$count-1;$i++){ if($likewhere!=""){
         * $likewhere.=" OR "; } $likewhere.=" t.path like '%".$idArr[$i]."%'";
         * } } } if($likewhere!=""){ $likewhere="(".$likewhere.")"; $where.="
         * AND ".$likewhere; }
         */
        
        import("@.ORG.Util.Page"); // 导入分页类
        $sql = "SELECT count(*) as count FROM tbatch_guestbook t  " . $where;
        $mapcount = D()->table("($sql) a")->find();
        $p = new Page($mapcount['count'], 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $p->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $p->setConfig('prev', '«');
        $p->setConfig('next', '»');
        $show = $p->show(); // 分页显示输出
        
        $sql = "
		select * from ((
		SELECT t.*,n.node_name, concat('回复我的评论：',t1.content) hcontent, c.node_name as name ,n.head_photo FROM tbatch_guestbook t1 ,tbatch_guestbook t
		LEFT JOIN tnode_info n on n.node_id=t.node_id 
		LEFT JOIN tnode_info c on c.node_id=t.touser
		" . $where . " and t.pid <> 0 and  t.pid = t1.id
		order by t.add_time desc )union 	
	    (SELECT t.*,n.node_name, concat('回复我的o2o活动：',ti.name) hcontent, c.node_name as name ,n.head_photo FROM tmarketing_info ti , tbatch_guestbook t 
		LEFT JOIN tnode_info n on n.node_id=t.node_id 
		LEFT JOIN tnode_info c on c.node_id=t.touser
		" . $where . " and ti.id = t.m_id and t.pid = 0
		order by t.add_time desc)) t2 order by add_time desc
		limit " . $p->firstRow . "," .
             $p->listRows;
        $list = M()->query($sql);
        /*
         * if(!empty($list)){ foreach($list as $k=>$val){
         * $res=$this->get_tree($val['id'].",");
         * $list[$k]['count']=count($res[0]); $list[$k]['child']=$res; } }
         */
        
        $this->assign('queryList', $list);
        $this->assign('node_id', $node_id);
        $this->assign('page', $show);
        $this->display();
    }
    
    // 回复父评论
    public function replychild() {
        $pid = I("pid");
        
        // 根据父ID查询label_id
        $label_id = M('tbatch_guestbook')->where(
            array(
                'id' => $pid))->getField('label_id');
        
        $childcontent = I("childcontent");
        $hd = M('tbatch_channel')->where(
            array(
                'id' => $label_id))->getField('batch_id');
        if (empty($this->userInfo)) {
            $this->error("评论失败，请先登录！");
        }
        if ($childcontent == "") {
            $this->error("评论内容不能为空！");
        }
        
        // 查询pid数据
        $pathData = M()->table('tbatch_guestbook a ')
            ->field("a.*,n.node_name,n.head_photo")
            ->join('tnode_info n on n.node_id=a.node_id ')
            ->where("a.id='" . $pid . "'")
            ->find();
        
        if ($this->userInfo['node_id'] == $pathData['node_id']) {
            $this->error("评论失败，自己不能评论自己内容！");
        }
        
        $addtime = date('YmdHis');
        
        $data = array(
            "node_id" => $this->userInfo['node_id'], 
            "user_id" => $this->userInfo['user_id'], 
            "pid" => $pid, 
            "m_id" => $hd, 
            "label_id" => $label_id, 
            "ck_status" => '2', 
            "status" => '0', 
            "path" => '', 
            "content" => $childcontent, 
            "support" => 0, 
            "replycount" => 0, 
            "touser" => $pathData['node_id'], 
            "add_time" => $addtime);
        $insertok = M('tbatch_guestbook')->add($data);
        if ($insertok) {
            // $this->success("评论提交成功！");
            // 更新path
            $updata = array(
                "path" => $pathData['path'] . $insertok . ",");
            $pathok = M('tbatch_guestbook')->where("id='" . $insertok . "'")->save(
                $updata);
            if ($pathok === false) {
                
                $this->error("评论提交失败，更新节点失败！");
                
                // $this->success("评论提交成功！");
            } else {
                
                // 更新父节点评论次数
                $upok = M('tbatch_guestbook')->where("id='" . $pid . "'")->setInc(
                    'replycount', 1);
                
                // 查询商户信息
                $nodeData = M()->table('tnode_info n ')
                    ->field("n.node_name,n.head_photo")
                    ->where("node_id='" . $this->userInfo['node_id'] . "'")
                    ->find();
                
                $task_info = '';
                // 调用评论任务
                // 这儿调用任务服务开始
                $task = D('Task', 'Service')->getTask('guestbook_submit');
                if ($task) {
                    $taskResult = $task->start($childcontent);
                    log_write('任务结果' . print_r($taskResult, true));
                    if ($taskResult && $taskResult['code'] == '0') {
                        $task_info = $taskResult['msg'];
                    }
                }
                // 调用任务结束
                
                $returnArr = array(
                    "id" => $insertok, 
                    "pid" => $pathData['pid'], 
                    "node_id" => $this->userInfo['node_id'], 
                    "m_id" => $hd, 
                    "label_id" => $label_id, 
                    "ck_status" => '2', 
                    "status" => '0', 
                    "task_info" => $task_info, 
                    "head_photo" => get_upload_url($pathData['head_photo']), 
                    "node_name" => $nodeData['node_name'], 
                    "content" => $childcontent, 
                    "add_time" => dateformat($addtime), 
                    "info" => "提交成功", 
                    "status" => 1);
                
                echo json_encode($returnArr);
                exit();
            }
        } else {
            
            $this->error("评论提交失败，入库异常！");
        }
    }

    public function get_tree($node) {
        $nodelen = strlen($node);
        $data = M()->table('tbatch_guestbook a ')
            ->field("a.*,n.node_name,n.head_photo")
            ->join('tnode_info n on n.node_id=a.node_id ')
            ->where(
            "pid='" . $node . "' AND pid<>0 AND a.status=0 AND a.node_id='" .
                 $this->node_id . "'")
            ->order("id DESC ")
            ->select();
        
        // echo M()->table('tbatch_guestbook a ')->getLastSql();
        
        foreach ($data as $row) {
            $volume[] = $row['path'];
        }
        
        array_multisort($volume, SORT_DESC, $data);
        
        $newData = array();
        foreach ($data as &$v) {
            $split = explode(',', $v['path']);
            $i = $split[0];
            $level = count($split) - 1;
            $v['lv'] = $level;
            $newData[$i][] = $v;
        }
        
        $newData = array_values($newData);
        
        return $newData;
        // print_r($newData);
    }

    public function del_msg() {
        $id = I("id");
        if ($id == "") {
            $this->error("删除失败，参数错误！");
        }
        $res = M('tmessage_info')->where("id='" . $id . "'")->delete();
        if ($res) {
            $this->success("操作成功！");
        } else {
            $this->error("删除失败，操作数据库异常！");
        }
    }
    
    // 在线反馈
    public function userFeedback() {
        if (IS_POST) {
            $allFeedback = I('post.');
            
            if (empty($allFeedback['questType'])) {
                $this->error('请选择问题类型');
            }
            
            if (empty($allFeedback['questTile'])) {
                $this->error('请填写问题标题');
            }
            
            if (empty($allFeedback['questDescription'])) {
                $this->error('问题描述不能为空');
            }
            
            if (empty($allFeedback['telephone'])) {
                $this->error('联系方式不能为空');
            }
            $isTelephone = preg_match("/^1[3458]{1}\d{9}$/", 
                $allFeedback['telephone']);
            $isEmail = preg_match(
                '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
                $allFeedback['telephone']);
            
            if ($isTelephone != 1 && $isEmail != 1) {
                $this->error("请输入正确的联系方式");
            }
            
            $title = '【' . $allFeedback['questType'] . '】' .
                 $allFeedback['questTile'];
            
            // 图片名称
            $img_node = str_replace('..', '', I('post.img_node')); // 安全过滤一下
            
            $way = 'Upload/upload_img';
            // 移动图片
            if ($img_node != '') {
                $img_move = rename(
                    APP_PATH . 'Upload/img_tmp/' . $this->node_id . "/" .
                         $img_node, APP_PATH . $way . '/' . basename($img_node));
                if ($img_move !== true) {
                    $this->error('图片上传失败！');
                }
            }
            $error = '';
            if (! check_str($allFeedback['questDescription'], 
                array(
                    'null' => false, 
                    'maxlen_cn' => '1000'), $error)) {
                $this->error("内容{$error}");
            }
            $data = array(
                'node_id' => '0', 
                'leave_title' => $title,  // 【 类型 】标题
                'leave_content' => htmlspecialchars(
                    addslashes($allFeedback['questDescription'])), 
                'leave_phone' => $allFeedback['telephone'], 
                'leave_time' => date('YmdHis'), 
                'upload_img' => $img_node, 
                'type' => '1');
            $userName = '游客';
            // 用户已登录的情况下
            if (isset($this->nodeId)) {
                $node_info = M('tnode_info')->field('node_name,contact_phone')
                    ->where("node_id='" . $this->nodeId . "'")
                    ->find();
                $data['node_id'] = $this->nodeId;
                $data['type'] = '0';
                $userName = $node_info['node_name'];
            }
            // 入库
            $result = M('tmessage_feedback')->data($data)->add();
            
            // 邮箱发送
            $content = "商户名：{$userName}<br/>联系方式：{$allFeedback['telephone']}<br/>留言标题：{$title}<br/>留言内容：{$allFeedback['questDescription']}<br/>图片：{$img_node}<br/>日期：" .
                 date('Y-m-d H:i:s');
            $ps = array(
                "subject" => "旺财营销平台-商户留言", 
                "content" => $content, 
                "email" => "7005@imageco.com.cn"); // 原邮箱wuqx@imageco.com.cn
            
            $resp = send_mail($ps);
            if ($result && $resp['sucess'] == '1') {
                $this->success('提交成功！');
            } else {
                $this->error('系统错误,您的反馈添加失败');
            }
        }
    }
    
    // 业务开通留言
    public function bopenMessage() {
        $phone = I('phone');
        if (! check_str($phone, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号码{$error}");
        }
        $name = I('name');
        $data = array(
            'node_id' => $this->nodeId, 
            'leave_title' => $name . '业务开通', 
            'leave_phone' => $phone, 
            'leave_content' => $name . '业务开通', 
            'leave_time' => date('YmdHis'), 
            'type' => '2');
        $result = M('tmessage_feedback')->data($data)->add();
        if (! $result) {
            $this->error('系统错误');
        }
        $this->success('提交成功，我们会尽快与您联系');
    }

    public function update_status() {
        $id = $_REQUEST['id'];
        $type = $_REQUEST['type'];
        if (empty($id)) {
            $this->error("参数错误");
        }
        $result = M('tmessage_info')->field('receive_node_id,send_node_id')
            ->where("id = '{$id}' and status = 0 ")
            ->find();
        if (! empty($result)) {
            M('tmessage_info')->where("id = '{$id}'")->save(
                array(
                    'status' => 1, 
                    'ck_status' => 2));
            if ($result['receive_node_id'] != $result['send_node_id']) {
                $sysmsgcunt = M('tmessage_stat')->field('new_message_cnt')
                    ->where(
                    array(
                        'node_id' => $this->node_id, 
                        'message_type' => $type))
                    ->find();
                $sysmsgcunt = $sysmsgcunt['new_message_cnt'];
                if ($sysmsgcunt > 0)
                    M()->query(
                        'UPDATE `tmessage_stat`
						SET `last_time`="' .
                             date('YmdHis') . '",`new_message_cnt`=`new_message_cnt`-1 WHERE
						( `node_id` = "' .
                             $this->node_id . '" ) AND ( `message_type` = ' .
                             $type . ' )');
            }
        }
    }

    /*
     * AJAX修改读取留言状态
     */
    public function replay_status() {
        $id = I('get.id');
        $type = $_REQUEST['type'];
        if (! $id) {
            $this->error("参数错误");
        }
        $result = M('tmessage_feedback')->where("id = '{$id}'")->save(
            array(
                "status" => "1"));
        if ($result === false) {
            echo 1;
            exit();
        } else {
            echo 0;
            exit();
        }
    }

    public function reply_view() {
        $id = I('get.id');
        if (! $id) {
            $this->error("参数错误");
        }
        $read_info = M('tmessage_feedback')->where(
            "id = '{$id}' and reply_content is not null")->find();
        if ($read_info == false) {
            $this->error("参数错误");
        }
        if ($read_info['status'] != '1') {
            $result = M('tmessage_feedback')->where("id = '{$id}'")->save(
                array(
                    "status" => "1"));
        }
        if ($result === false) {
            $this->error("参数错误");
        } else {
            $this->assign('info', $read_info);
            $this->display();
        }
    }

    /*
     * AJAX修改读取系统消息状态并跳转到内容页
     */
    public function message_view() {
        $id = I('get.id');
        $seq = I('get.seq');
        if (! $id && ! $seq) {
            $this->error("参数错误");
        }
        
        if (! $seq) {
            $message = M('tmessage_news')->where("id = $id")->select();
            if (count($message) == 0) {
                $this->error("系统繁忙，请刷刷新后重试");
            }
            
            $tmessage_recored = M('tmessage_recored');
            
            // 将status设为已读
            foreach ($message as $k => $v) {
                $data = array(
                    "message_id" => $id, 
                    "node_id" => $this->nodeId, 
                    "send_status" => '0', 
                    "status" => '1', 
                    "add_time" => $v['add_time'], 
                    "read_time" => date('YmdHis', time()));
                $tmessage_recored->add($data);
            }
        }

        $read_info = M('tmessage_recored')->where(
            "message_id = '{$id}' and seq_id = '{$seq}' and node_id = '{$this->nodeId}'")
            ->field('status')
            ->find();

        if ($read_info['status'] != '1') {
            $result = M('tmessage_recored')->where(
                "message_id = '{$id}' and seq_id = '{$seq}' and node_id = '{$this->nodeId}'")->save(
                array(
                    "status" => "1", 
                    "read_time" => date('YmdHis')));
            $sysmsgcunt = M('tmessage_stat')->field('new_message_cnt')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'message_type' => 1))
                ->find();
            $sysmsgcunt = $sysmsgcunt['new_message_cnt'];
            if ($sysmsgcunt > 0){
                $info = array('last_time'=>date('YmdHis'));
                M('tmessage_stat')->where(array('node_id'=>$this->node_id, 'message_type' => 1))->save($info);
                M('tmessage_stat')->where(array('node_id'=>$this->node_id, 'message_type' => 1))->setDec('new_message_cnt');
            }    
        }

        $info = M('tmessage_news')->field('content,title,add_time')
            ->where("id = '{$id}' ")
            ->find();
        $this->assign("info", $info);
        $this->display();
    }
    
    // 获取今天,昨天,上周,上周以前的数据
    public function getDateInfo($data, $timeField) {
        
        // 本周开始和结束日期
        $tWeeks = date('Ymd', 
            mktime(0, 0, 0, date('m'), date('d') - date('N'), date('Y')));
        $tWeeke = date('Ymd', 
            mktime(23, 59, 59, date('m'), date('d') - date('N') + 6, date('Y')));
        // 上周开始和结束日期
        $lWeeks = date('Ymd', 
            mktime(0, 0, 0, date('m'), date('d') - date('N') - 7, date('Y')));
        $lWeeke = date('Ymd', 
            mktime(23, 59, 59, date('m'), date('d') - date('N') - 1, date('Y')));
        // dump($data);exit;
        $dataArr = array();
        foreach ($data as $k => $v) {
            $fieldDate = date('Ymd', strtotime($v[$timeField]));
            if ($fieldDate >= $tWeeks && $fieldDate <= $tWeeke) {
                $dataArr['tweek'][] = $v;
            } elseif ($fieldDate >= $lWeeks && $fieldDate <= $lWeeke) {
                $dataArr['lweek'][] = $v;
            } else {
                $dataArr['aweek'][] = $v; // 更早
            }
        }
        return $dataArr;
    }

    /**
     * [finishReg 完成PC端注册]
     *
     * @return [type] [description]
     */
    public function finishReg() {
        if ($this->isPost()) {
            $newPassword = I('post.newPassword', '');
            $contactEml = I('post.contact_eml', '');
            $contactName = I('post.contact_name', '');
            $provinceCode = I('post.province_code', '');
            $cityCode = I('post.city_code', '');
            $nodeId = $this->nodeId;
            $userId = $this->userInfo['user_id'];
            $WheelInfo = D('Wheel')->getWheelInfo($this->userInfo['user_name']);
            // 检查字段是否为空
            (empty($newPassword) || empty($contactEml) || empty($contactName) ||
                 empty($provinceCode) || empty($cityCode)) &&
                 $this->error("所有字段不得为空");
            // 验证密码信息
            if (strlen($newPassword) < 6 && strlen($newPassword) > 16) {
                $this->error("密码必须在6-16位之间");
            }
            // 验证邮箱格式
            if (! preg_match(
                "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", 
                $contactEml)) {
                $this->error("邮箱格式不正确");
            }
            // 修改密码
            $reqArr = array(
                "node_id" => $nodeId, 
                "email" => $this->userInfo['user_name'], 
                "new_password1" => $newPassword, 
                "old_password" => $WheelInfo['tmp_password']);
            // 请求接口修改
            $UserSess = D('UserSess', 'Service');
            $reqResult = $UserSess->EditPwd($reqArr);
            if ($reqResult->resp_id != '0000') {
                $this->error('提交失败');
            }
            // 同步修改临时表中的密码,这里就不做判断成功还是失败了
            M('twheel_tmp')->where(
                array(
                    'phone_no' => $this->userInfo['user_name']))->save(
                array(
                    'tmp_password' => $newPassword));
            node_log("密码修改");
            // 修改企业负责人姓名和邮箱,这里主要是修改邮箱，其他纯属附带
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            $editAuzArr = array(
                "EditAUZInfoReq" => array(
                    "TransactionID" => $TransactionID, 
                    "ClientId" => $this->nodeInfo['client_id'], 
                    "NodeId" => $nodeId, 
                    "Name" => $this->userInfo['user_name'], 
                    "UserName" => $contactName, 
                    "UserEmail" => $contactEml, 
                    "RegFrom" => $this->nodeInfo['reg_from']));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $respResult = $RemoteRequest->requestYzServ($editAuzArr);
            $retMsg = $respResult['Status'];
            if (! $respResult || ($retMsg['StatusCode'] != '0000' &&
                 $retMsg['StatusCode'] != '0001')) {
                log_write("Yz商户信息修改失败，原因：{$retMsg['StatusText']}");
                $this->error('提交失败:' . $retMsg['StatusText']);
            }
            
            // 修改本地旺财的相关信息
            $nodeUpdateData = array(
                'node_citycode' => $provinceCode . $cityCode, 
                'contact_name' => $contactName, 
                'contact_eml' => $contactEml);
            if (false === M('tnode_info')->where(
                array(
                    'node_id' => $nodeId))->save($nodeUpdateData)) {
                $this->error("提交失败");
            }
            
            $this->success("修改成功");
        }
        // 获取查询省份
        $provinceInfo = M('tcity_code')->field('province_code,province')
            ->where("city_level=1")
            ->select();
        $provinceArr = array_valtokey($provinceInfo, 'province_code', 
            'province');
        $this->assign("provinceInfo", $provinceInfo);
        $this->assign("username", $this->userInfo['user_name']);
        $this->assign("nodeName", $this->nodeInfo['node_name']);
        $this->display();
    }
    
    // 完善信息以后，显示的页面
    public function finishResult() {
        $this->assign('email', 
            M('tnode_info')->getFieldByNode_id($this->node_id, 'contact_eml'));
        $this->display();
    }
    
    // 更新 nodeinfo ajax
    public function editNodeInfo() {
        // 定义常量
        define("PERMIT", 1);
        $node_id = $this->node_id;
        // 实例化
        $nodeModel = D('node');
        // 查询结果
        $nodeInfo = $nodeModel->getNodeInfo($node_id);
        // 更新入表的字段
        $dataArr['contact_name'] = I('contact_name');
        $dataArr['contact_phone'] = I('contact_phone');
        $dataArr['node_service_hotline'] = I('node_service_hotline');
        $dataArr['node_short_name'] = I('node_short_name');
        $flag = 0;
        // 判断是否有无更新企业简称
        if (I('node_short_name') != $nodeInfo['node_short_name']) {
            // 判断原来的cfg_data是否有数据
            if (! empty($nodeInfo['cfg_data'])) {
                $cfgData = unserialize($nodeInfo['cfg_data']);
                // 判断原来是否有更改时间
                if (! empty($cfgData['node_short_name_updatetime'])) {
                    // 判断是否已经满一个月
                    $strto_time = strtotime(
                        $cfgData['node_short_name_updatetime']);
                    $time = (time() - $strto_time) / (60 * 60 * 24 * 30);
                    // 超过30天，更新cfg_data
                    if ($time >= 1) {
                        $cfgData['node_short_name_updatetime'] = date('YmdHis', 
                            time());
                        $dataArr['cfg_data'] = serialize($cfgData);
                        $flag = PERMIT;
                    } else {
                        // 不满1个月不更新企业简称
                        $dataArr['node_short_name'] = $nodeInfo['node_short_name'];
                    }
                } else {
                    // 原来没有更新时间，直接写入cfg_data
                    $cfgData['node_short_name_updatetime'] = date('YmdHis', 
                        time());
                    $dataArr['cfg_data'] = serialize($cfgData);
                    $flag = PERMIT;
                }
            } else {
                // 原来cfg_data没有数据，直接写入
                $cfgData['node_short_name_updatetime'] = date('YmdHis', time());
                $dataArr['cfg_data'] = serialize($cfgData);
                $flag = PERMIT;
            }
        }
        $editNodeYzReq = array(
                'NodeId'        => $this->nodeId,
                'TransactionID' => $this->contractId,
                'ClientId'      => $this->clientId,
                'UserName'      => $dataArr['contact_name'],
                'UserTel'       => $dataArr['contact_phone'],
                'ServiceTel'    => $dataArr['node_service_hotline'],
                'Name'          => $this->userInfo['user_name']
        );
        $editNodeZcReq = array(
                'NodeId'        => $this->nodeId,
                'ContactName'   => $dataArr['contact_name'],
                'ContactPhone'  => $dataArr['contact_phone'],
                'ServiceLine'   => $dataArr['node_service_hotline'],
        );
        if($flag == 1){
            $editNodeZcReq['NodeShortName'] = $dataArr['node_short_name'];
        }

        $RemoteRequest = D('RemoteRequest', 'Service');
        // 通知营帐
        $yz_array = array(
                'EditAUZInfoReq' => $editNodeYzReq);
        $resp_array = $RemoteRequest->requestYzServ($yz_array);
        $ret_msg = $resp_array['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&$ret_msg['StatusCode'] != '0001')) {
            log_write("Yz商户信息修改失败，原因：{$ret_msg['StatusText']}");
            $this->error('Yz修改失败:' . $ret_msg['StatusText']);
        }
        // 通知支撑
        $zc_array = array(
                'EditNodeInfoReq' => $editNodeZcReq);
        $resp_array = $RemoteRequest->requestIssForImageco($zc_array);
        $ret_msg = $resp_array['EditNodeInfoRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&$ret_msg['StatusCode'] != '0001')) {
            log_write("Zc商户信息修改失败，原因：{$ret_msg['StatusText']}");
            $this->error('Zc修改失败:' . $ret_msg['StatusText']);
        }

        // 更新操作
        $result = $nodeModel->updateNodeInfo($node_id, $dataArr, $flag);
        // result返回值，2为失败，1为成功
        if ($result == 2) {
            $this->error("提交错误");
        } elseif ($result == 1) {
            $_SESSION['userSessInfo']['node_short_name']=$dataArr['node_short_name'];
            // print_r($dataArr);
            $this->ajaxReturn($dataArr, "返回成功", 1);
        }
    }

    /**
     * 我的收款账户列表
     */
    public function accountList(){
        $data = [
                '0' => [
                        'id'           => '100',
                        'account_name' => '电商银行收款账户',
                        'account_type' => '0',
                        'app_scence'   => '多宝电商<br/>多米收单',
                        'add_time'     => '20160602065555',
                ],
                '1' => [
                        'id'           => '101',
                        'account_name' => '电商银行收款账户',
                        'account_type' => '0',
                        'app_scence'   => '多宝电商<br/>多米收单',
                        'add_time'     => '20160602065555',
                ],
                '2' => [
                        'id'           => '102',
                        'account_name' => '电商银行收款账户',
                        'account_type' => '1',
                        'app_scence'   => '多宝电商<br/>多米收单',
                        'add_time'     => '20160602065555',
                ],
                '3' => [
                        'id'           => '103',
                        'account_name' => '电商银行收款账户',
                        'account_type' => '0',
                        'app_scence'   => '多宝电商<br/>多米收单',
                        'add_time'     => '20160602065555',
                ],
        ];
        $acc_type_arr = ['银行账户','微信自有账户'];
        $this->assign('list',$data);
        $this->assign('acctype',$acc_type_arr);
        $this->display();
    }

    public function accountAdd(){
        $tab_type = I('post.type');
        switch ($tab_type) {
            case '0':
                self::funcBankAdd();
                break;
            case '1':
                self::funcWeixinAdd();
                break;
            default:
                $this->error('参数错误');
                break;
        }
    }

    private function funcBankAdd(){
        $bk_account_alias   = I('post.bk_account_alias');
        $bank_type          = I('post.bank_type');
        $account_bank       = I('post.account_bank');
        $account_bank_ex    = I('post.account_bank_ex');
        $account_name       = I('post.account_name');
        $account_no         = I('post.account_no');
        $account_no_confirm = I('post.account_no_confirm');
        if(!in_array($bank_type,['0','1']))
            $this->error('参数有误，请重新提交');
        if(empty($bk_account_alias) || empty($account_bank)
                || empty($account_bank_ex) || empty($account_name))
            $this->error('请检查所填写是否有空值');
        if($account_no != $account_no_confirm)
            $this->error('银行卡号有误，请重新输入');
        if(!preg_match("/^[\\d]+$/", $account_no))
            $this->error('银行卡号必须是数字');

        $this->success('提交成功');
    }

    private function funcWeixinAdd(){

    }
}