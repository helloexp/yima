<?php

class FxglAction extends BaseAction {
    public $meiHuiModel='';
    public function _initialize() {
        parent::_initialize();
        self::checkChannel();
        $this->meiHuiModel=D('MeiHui','Model');
    }
    public $_authAccessMap = '*';
    public function beforeCheckAuth() {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = "*";
        } elseif (! $this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        } else {
            if (ACTION_NAME == 'index') {
                $this->_authAccessMap = "*";
            }
        }
    }
    public function index() {
        // 定义经销商和销售员的查询条件
        $id = I('get.id', null, "trim,htmlspecialchars");
        $agencyMap = array(
            'node_id' => $this->node_id, 
            'status' => array(
                'NEQ', 
                5), 
            'role' => '2');
        $salerMap = array(
            'node_id' => $this->node_id, 
            'status' => array(
                'NEQ', 
                5), 
            'role' => '1');
        if (! empty($id)) {
            $agencyMap['id|parent_path'] = array(
                $id, 
                array(
                    'like', 
                    '%,' . $id . ',%'), 
                '_multi' => true);
            $salerMap['parent_path'] = array(
                'like', 
                '%,' . $id . ',%');
            session('selectid', $id);
        } else {
            session('selectid', null);
        }
        // 经销商查询
        if (I('postFrom') == "agency") {
            $name = I('agency_name', "", "trim,htmlspecialchars");
            $status = I('agency_status', "", "trim,htmlspecialchars");
            $phone_no = I('agency_phone_no', "", "trim,htmlspecialchars");
            if (! empty($name)) {
                $agencyMap['name'] = array(
                    "like", 
                    '%' . $name . '%');
            }
            if (! empty($status)) {
                $agencyMap['status'] = $status;
            }
            if (! empty($phone_no)) {
                $agencyMap['phone_no'] = array(
                    "like", 
                    '%' . $phone_no . '%');
            }
            $this->assign("agency_name", $name);
            $this->assign("agency_status", $status);
            $this->assign("agency_phone_no", $phone_no);
        }
        // 销售员查询
        if (I('postFrom') == "saler") {
            $name = I('saler_name', "", "trim,htmlspecialchars");
            $status = I('saler_status', "", "trim,htmlspecialchars");
            $phone_no = I('saler_phone_no', "", "trim,htmlspecialchars");
            if (! empty($name)) {
                $salerMap['name'] = array(
                    "like", 
                    '%' . $name . '%');
            }
            if (! empty($status)) {
                $salerMap['status'] = $status;
            }
            if (! empty($phone_no)) {
                $salerMap['phone_no'] = array(
                    "like", 
                    '%' . $phone_no . '%');
            }
            $this->assign("saler_name", $name);
            $this->assign("saler_status", $status);
            $this->assign("saler_phone_no", $phone_no);
        }
        // 获取左侧树状结构的信息
        $salerLevelInfo = M('twfx_saler')->field('id,parent_id,level,name')
            ->where(
            array(
                'role' => '2', 
                'node_id' => $this->node_id, 
                'status' => '3', 
                'level' => array(
                    'ELT', 
                    5)))
            ->select();
        // 获取经销商和销售员的总数量
        if (! empty($id)) {
            $agencyCount = M('twfx_saler')->where(
                array(
                    'role' => '2', 
                    'status' => '3', 
                    'node_id' => $this->node_id, 
                    'parent_path' => array(
                        'like', 
                        '%,' . $id . ',%')))->count() + 1;
            $salerCount = M('twfx_saler')->where(
                array(
                    'role' => '1', 
                    'status' => '3', 
                    'node_id' => $this->node_id, 
                    'parent_path' => array(
                        'like', 
                        '%,' . $id . ',%')))->count();
            $node_name = M('twfx_saler')->getFieldById($id, 'name');
        } else {
            $agencyCount = M('twfx_saler')->where(
                array(
                    'role' => '2', 
                    'status' => '3', 
                    'node_id' => $this->node_id))->count();
            $salerCount = M('twfx_saler')->where(
                array(
                    'role' => '1', 
                    'status' => '3', 
                    'node_id' => $this->node_id))->count();
            $node_name = $this->nodeInfo['node_name'];
        }
        
        // 导入分页类
        import('ORG.Util.Page');
        // 根据条件查询经销商和销售员的总数量
        $agencyCountForP = M('twfx_saler')->where($agencyMap)->count();
        $salerCountForP = M('twfx_saler')->where($salerMap)->count();
        // 经销商分页部分
        C('VAR_PAGE', 'pa');
        $PageA = new Page($agencyCountForP, 10);
        $agencyList = M('twfx_saler')->where($agencyMap)
            ->order('add_time desc')
            ->limit($PageA->firstRow . ',' . $PageA->listRows)
            ->select();
        $showA = $PageA->show();
        // 销售员分页部分
        C('VAR_PAGE', 'ps');
        $PageS = new Page($salerCountForP, 10);
        $salerList = M('twfx_saler')->where($salerMap)
            ->order('add_time desc')
            ->limit($PageS->firstRow . ',' . $PageS->listRows)
            ->select();
        $showS = $PageS->show();
        // 层级数组，预处理
        if($this->node_id==C('meihui.node_id')){
            $levelArr = array(
                    '1' => '<i class="grade">门店</i>',
                    '2' => '<i class="grade">钻石</i>',
                    '3' => '<i class="grade">金牌</i>',
                    '4' => '<i class="grade">银牌</i>');
        }else{
            $levelArr = array(
                    '1' => '<i class="grade">一级</i>',
                    '2' => '<i class="grade">二级</i>',
                    '3' => '<i class="grade">三级</i>',
                    '4' => '<i class="grade">四级</i>',
                    '5' => '<i class="grade">五级</i>');
        }
        // 状态数组，预处理
        $statusArr = array(
            '1' => '审核中', 
            '2' => '未通过审核', 
            '3' => '正常', 
            '4' => '停用');
        // 对经销商的数据进行处理
        if (! empty($agencyList)) {
            foreach ($agencyList as $ka => $va) {
                $agencyList[$ka]['parent_name'] = M('twfx_saler')->getFieldById(
                    $va['parent_id'], 'name');
                $agencyList[$ka]['sonsaler_amt'] = M('twfx_saler')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'parent_id' => $va['id'],
                        'role' => '1'))->count();
                $agencyList[$ka]['status'] = $statusArr[$va['status']];
                $agencyList[$ka]['show_name'] = $levelArr[$va['level']] .
                     $va['name'];
            }
        }
        // 对销售员的数据进行处理
        if (! empty($salerList)) {
            foreach ($salerList as $ks => $vs) {
                $salerList[$ks]['parent_agency'] = M('twfx_saler')->getFieldById(
                    $vs['parent_id'], 'name');
                $salerList[$ks]['status'] = $statusArr[$vs['status']];
            }
        }
        // 判断是否首次加载页面
        // (I('get.ps') || I('get.pa')) or $this->assign('firstload','1');
        
        $this->assign('node_name', $node_name);
        $this->assign('tree_node_name', $this->nodeInfo['node_name']);
        $this->assign('agencyCount', $agencyCount);
        $this->assign('salerCount', $salerCount);
        $this->assign('agencyList', $agencyList);
        $this->assign('salerList', $salerList);
        $this->assign('pageA', $showA);
        $this->assign('pageS', $showS);
        $this->assign('default_sale_percent', session('default_sale_percent'));
        $this->assign('salerLevelInfo', $salerLevelInfo);
        if(C('meihui.node_id')== $this->node_id){
            $totalMap=array(
                    'node_id'=>$this->node_id,
                    'status'=>'3'
            );
            $total=$this->meiHuiModel->getTotalCount($totalMap);
            if($total){
                $totalArr='';
                foreach($total as $key=>$val){
                    $totalArr[$val['level']]=$val['total'];
                }
                if(!$totalArr[1]){
                    $totalArr[1]='0';
                }else if(!$totalArr[2]){
                    $totalArr[2]=0;
                }else if(!$totalArr[3]){
                    $totalArr[3]=0;
                }else if(!$totalArr[4]){
                    $totalArr[4]=0;
                }
                $this->assign('total',$totalArr);
            }
            $leftTree=$this->getLeftTree();
            $this->assign('leftTree',$leftTree);
            $this->display('meihui/Fxgl_index');
        }else{
            $this->display();
        }
    }
    public function add() {
        self::checkNodeInfo();
        $wfx = D('Wfx');
        if($this->node_id==C('meihui.node_id')){
            $res=$this->meiHuiModel->getBelongAgency($this->node_id);
        }else{
            $res = $wfx->getBelongAgency($this->node_id, false);
        }
        $res2 = $wfx->getBelongAgency($this->node_id, true);
        if($this->node_id==C('meihui.node_id')){
            $levelArr = array(
                    '1' => '门店',
                    '2' => '钻石',
                    '3' => '金牌',
                    '4' => '金牌');
        }else{
            $levelArr = array(
                    '1' => '一级',
                    '2' => '二级',
                    '3' => '三级',
                    '4' => '四级',
                    '5' => '五级');
        }
        $agencyList = "顶级-00000000000-" . $this->nodeInfo['node_name'] . ' ';
        // 判断是否有默认选择
        $selectid = session('selectid');
        if (! empty($selectid)) {
            $selectInfo = M('twfx_saler')->getById($selectid);
            $selectAgencyName = $levelArr[$selectInfo['level']] . '-' .
                 $selectInfo['phone_no'] . '-' . $selectInfo['name'];
        } else {
            $selectAgencyName = "";
        }
        $salerList = "";
        foreach ($res as $k => $v) {
            $agencyList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                 mb_substr($v['name'], 0, 10, "UTF8") . ' ';
        }
        foreach ($res2 as $k => $v) {
            $salerList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                 mb_substr($v['name'], 0, 10, "UTF8") . ' ';
        }
        if($this->node_id==C('meihui.node_id')){
            $mhList='';
            $res3 = D('MeiHui','Model')->getMemberLevel1($this->node_id);
            foreach ($res3 as $k => $v) {
                $mhList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                        mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
            $mhListAll='';
            $res4 = D('MeiHui','Model')->getMemberLevel2($this->node_id);
            foreach ($res4 as $k => $v) {
                $mhListAll .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                        mb_substr($v['name'], 0, 10, "UTF8") . ' ';

            }
        }
        // 当前商户的账户类型
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->node_id, 'account_type');
        $this->assign('agencyList', $agencyList);
        $this->assign('salerList', $salerList);
        $this->assign('mhList', $mhList);
        $this->assign('mhListAll', $mhListAll);
        $this->assign('selectAgencyName', $selectAgencyName);
        $this->assign('defaultBankName', C('defaultBankName'));
        $this->assign('accountType', $accountType);
        if($this->node_id==C('meihui.node_id')){
            $this->display('meihui/Fxgl_add');
        }else{
            $this->display();
        }
    }

    /**
     * ajax新增经销商/销售员
     */
    public function add_ajax() {
        // 两者共有的部分
        $insertArr = array(
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'add_user_id' => $this->userInfo['user_id'], 
            'add_user_name' => $this->userInfo['name']);
        // type为1，表示经销商ajax提交
        if ($_GET['type'] == '1') {
            $name = I('name', null, "trim,htmlspecialchars");
            $contact_name = I('contact_name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            $alipay_account = I('alipay_account', null, "trim,htmlspecialchars");
            $bankName = I('bank_name', null, "trim,htmlspecialchars");
            $bankAccount = I('bank_account', null, "trim,htmlspecialchars");
            $default_sale_percent = I('default_sale_percent', null, 
                "trim,htmlspecialchars");
            $default_manage_percent = I('default_manage_percent', null, 
                "trim,htmlspecialchars");
            $level_info = I('level_info', null, "trim,htmlspecialchars");
            $business_licence = I('business_licence');
            ! empty($name) or $this->error("经销商名称不得为空");
            ! empty($contact_name) or $this->error("负责人姓名不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            ! empty($level_info) or $this->error("所属经销商不得为空");
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");
            // 对提成进行周密的判断
            if (empty($default_sale_percent) && $default_sale_percent != '0') {
                $this->error("默认销售提成不得为空");
            } else {
                if (! is_numeric($default_sale_percent) &&
                     ! is_float($default_sale_percent)) {
                    $this->error("默认销售提成必须是数字");
                }
            }
            if (empty($default_manage_percent) && $default_manage_percent != '0') {
                $this->error("默认管理提成不得为空");
            } else {
                if (! is_numeric($default_manage_percent) &&
                     ! is_float($default_manage_percent)) {
                    $this->error("默认管理提成必须是数字");
                }
            }
            if ($default_manage_percent > 100 || $default_sale_percent > 100) {
                $this->error("提成不得大于100%");
            }
            if ($default_manage_percent < 0 || $default_sale_percent < 0) {
                $this->error("提成不得小于0");
            }
            $insertArr['name'] = $name;
            $insertArr['contact_name'] = $contact_name;
            $insertArr['phone_no'] = $phone_no;
            $insertArr['alipay_account'] = $alipay_account;
            $insertArr['bank_name'] = $bankName;
            $insertArr['bank_account'] = $bankAccount;
            // 判断银行账号与名称是否同时存在或同时不存在
            ((empty($bankAccount) && empty($bankName)) ||
                 (! empty($bankAccount) && ! empty($bankName))) or
                 $this->error("银行名称与账号必须同时存在或不存在");
            if (! empty($bankName) && ! in_array($bankName, 
                C('defaultBankName'))) {
                $this->error("银行名称有误");
            }
            // 判断银行账号是否为16-19位
            if (! empty($bankAccount)) {
                (strlen($bankAccount) > 15 && strlen($bankAccount) < 20 &&
                     preg_match('/^\d+$/i', $bankAccount)) or
                     $this->error("银行账号必须为数字，且为16-19位");
            }
            $insertArr['default_sale_percent'] = $default_sale_percent;
            $insertArr['default_manage_percent'] = $default_manage_percent;
            $temp_level_arr = explode('-', $level_info);
            if ($temp_level_arr[1] != '00000000000') {
                $parentInfo = M('twfx_saler')->where(
                    array(
                        'node_id' => $this->nodeId))->getByPhone_no(
                    $temp_level_arr[1]);
                $insertArr['parent_id'] = $parentInfo['id'];
                $insertArr['level'] = $parentInfo['level'] + 1;
                $insertArr['parent_path'] = $parentInfo['parent_path'] .
                     $parentInfo['id'] . ',';
            } else {
                $insertArr['parent_id'] = 0;
                $insertArr['level'] = 1;
                $insertArr['parent_path'] = '0,';
            }
            $insertArr['role'] = '2';
            $insertArr['business_licence'] = $business_licence;
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id))->getByPhone_no($phone_no)) {
                $this->error("手机号码已存在");
            }
        }
        // type为2表示销售员ajax提交
        if ($_GET['type'] == '2') {
            $name = I('name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            $custom_no = I('custom_no', null, "trim,htmlspecialchars");
            $alipay_account = I('alipay_account', null, "trim,htmlspecialchars");
            $bankName = I('bank_name', null, "trim,htmlspecialchars");
            $bankAccount = I('bank_account', null, "trim,htmlspecialchars");
            $default_sale_percent = I('default_sale_percent', null, 
                "trim,htmlspecialchars");
            $level_info = I('level_info', null, "trim,htmlspecialchars");
            ! empty($name) or $this->error("销售员名称不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");
            ! empty($level_info) or $this->error("必须选择经销商");
            if (empty($default_sale_percent) && $default_sale_percent != '0') {
                $this->error("默认销售提成不得为空");
            } else {
                if (! is_numeric($default_sale_percent) &&
                     ! is_float($default_sale_percent)) {
                    $this->error("默认销售提成必须是数字");
                }
            }
            if ($default_sale_percent > 100) {
                $this->error("提成不得大于100%");
            }
            if ($default_sale_percent < 0) {
                $this->error("提成不得小于0");
            }
            $insertArr['name'] = $name;
            $insertArr['phone_no'] = $phone_no;
            empty($custom_no) or $insertArr['custom_no'] = $custom_no;
            $insertArr['alipay_account'] = $alipay_account;
            $insertArr['bank_name'] = $bankName;
            $insertArr['bank_account'] = $bankAccount;
            $insertArr['default_sale_percent'] = $default_sale_percent;
            // 判断银行账号与名称是否同时存在或同时不存在
            ((empty($bankAccount) && empty($bankName)) ||
                 (! empty($bankAccount) && ! empty($bankName))) or
                 $this->error("银行名称与账号必须同时存在或不存在");
            if (! empty($bankName) && ! in_array($bankName, 
                C('defaultBankName'))) {
                $this->error("银行名称有误");
            }
            // 判断银行账号是否为16-19位
            if (! empty($bankAccount)) {
                (strlen($bankAccount) > 15 && strlen($bankAccount) < 20 &&
                     preg_match('/^\d+$/i', $bankAccount)) or
                     $this->error("银行账号必须为数字，且为16-19位");
            }
            if (! empty($level_info)) {
                $temp_level_arr = explode('-', $level_info);
                $parentInfo = M('twfx_saler')->where(
                    array(
                        'node_id' => $this->nodeId))->getByPhone_no(
                    $temp_level_arr[1]);
                $insertArr['parent_id'] = $parentInfo['id'];
                $insertArr['level'] = $parentInfo['level'];
                $insertArr['parent_path'] = $parentInfo['parent_path'] .
                     $parentInfo['id'] . ',';
            }
            $insertArr['role'] = '2';
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id))->getByPhone_no($phone_no)) {
                $this->error("手机号码已存在");
            }
            // 判断销售员编号是否重复
            if (! empty($custom_no) &&
                 M('twfx_saler')->where(
                    array(
                        'node_id' => $this->node_id))->getByCustom_no($custom_no)) {
                $this->error("销售员编号已存在");
            }
        }
        M()->startTrans();
        if ($id = M('twfx_saler')->data($insertArr)->add()) {
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $id;
            $logData['type'] = 2;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = "";
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                M()->rollback();
                $this->error("新增失败");
            }
            M()->commit();
            $this->success("新增成功");
        } else {
            M()->rollback();
            $this->error("新增失败");
        }
    }
    /**
     * 显示对经销商和销售员的修改
     */
    public function edit() {
        $role = I("role");
        $id = I("id");
        (! empty($role) && ! empty($id)) or $this->error("参数错误");
        $list = M('twfx_saler')->getById($id);
        // 选择经销商的下拉列表
        $agencyLevelInfo = M('twfx_saler')->field(
            'id,parent_id,level,parent_path,name')
            ->where(
            array(
                'role' => '2', 
                'node_id' => $this->node_id, 
                'status' => '3', 
                'level' => array(
                    'ELT', 
                    4)))
            ->select();
        $salerLevelInfo = M('twfx_saler')->field(
            'id,parent_id,level,parent_path,name')
            ->where(
            array(
                'role' => '2', 
                'node_id' => $this->node_id, 
                'status' => '3', 
                'level' => array(
                    'ELT', 
                    5)))
            ->select();
        // 当前商户的账户类型
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->node_id, 'account_type');
        $this->assign('role', $role);
        $this->assign('id', $id);
        $this->assign('list', $list);
        $this->assign('flag',I("flag",0));// 是否修改并重新审核标识,0为否，1为是
        $this->assign('accountType', $accountType);
        $this->assign('defaultBankName', C('defaultBankName'));
        $this->display();
    }
    /**
     * ajax修改经销商和销售员
     */
    public function edit_ajax() {
        // 需要修改的saler_id
        $id = I('id');
        $flag = I('flag',0);
        $prevSalerInfo = M('twfx_saler')->getById($id);
        
        // 需要插入的数据
        $insertArr = array();
        if ($_GET['type'] == '1') {
            $name = I('name', null, "trim,htmlspecialchars");
            $contact_name = I('contact_name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            $alipay_account = I('alipay_account', null, "trim,htmlspecialchars");
            $bankName = I('bank_name', null, "trim,htmlspecialchars");
            $bankAccount = I('bank_account', null, "trim,htmlspecialchars");
            $default_sale_percent = I('default_sale_percent', null, 
                "trim,htmlspecialchars");
            $default_manage_percent = I('default_manage_percent', null, 
                "trim,htmlspecialchars");
            $business_licence = I('business_licence', null, 
                "trim,htmlspecialchars");
            ! empty($name) or $this->error("经销商名称不得为空");
            ! empty($contact_name) or $this->error("负责人姓名不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            // 对提成进行周密的判断
            if (empty($default_sale_percent) && $default_sale_percent != '0') {
                $this->error("默认销售提成不得为空");
            } else {
                if (! is_numeric($default_sale_percent) &&
                     ! is_float($default_sale_percent)) {
                    $this->error("默认销售提成必须是数字");
                }
            }
            if (empty($default_manage_percent) && $default_manage_percent != '0') {
                $this->error("默认管理提成不得为空");
            } else {
                if (! is_numeric($default_manage_percent) &&
                     ! is_float($default_manage_percent)) {
                    $this->error("默认管理提成必须是数字");
                }
            }
            if ($default_manage_percent > 100 || $default_sale_percent > 100) {
                $this->error("提成不得大于100%");
            }
            if ($default_manage_percent < 0 || $default_sale_percent < 0) {
                $this->error("提成不得小于0");
            }
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");
            // 判断银行账号与名称是否同时存在或同时不存在
            ((empty($bankAccount) && empty($bankName)) ||
                 (! empty($bankAccount) && ! empty($bankName))) or
                 $this->error("银行名称与账号必须同时存在或不存在");
            if (! empty($bankName) && ! in_array($bankName, 
                C('defaultBankName'))) {
                $this->error("银行名称有误");
            }
            // 判断银行账号是否为16-19位
            if (! empty($bankAccount)) {
                (strlen($bankAccount) > 15 && strlen($bankAccount) < 20 &&
                     preg_match('/^\d+$/i', $bankAccount)) or
                     $this->error("银行账号必须为数字，且为16-19位");
            }
            $insertArr['name'] = $name;
            $insertArr['contact_name'] = $contact_name;
            $insertArr['phone_no'] = $phone_no;
            $insertArr['business_licence'] = $business_licence;
            $insertArr['alipay_account'] = $alipay_account;
            $insertArr['bank_name'] = $bankName;
            if($flag == '1')
                $insertArr['status'] = '3';
            $insertArr['bank_account'] = $bankAccount;
            $insertArr['default_sale_percent'] = $default_sale_percent;
            $insertArr['default_manage_percent'] = $default_manage_percent;
            
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'phone_no' => $phone_no, 
                    'id' => array(
                        'NEQ', 
                        $id)))->select()) {
                $this->error("手机号码已存在");
            }
        }
        if ($_GET['type'] == '2') {
            $name = I('name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            $custom_no = I('custom_no', null, "trim,htmlspecialchars");
            $alipay_account = I('alipay_account', null, "trim,htmlspecialchars");
            $bankName = I('bank_name', null, "trim,htmlspecialchars");
            $bankAccount = I('bank_account', null, "trim,htmlspecialchars");
            $default_sale_percent = I('default_sale_percent', null, 
                "trim,htmlspecialchars");
            ! empty($name) or $this->error("销售员名称不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");
            if (strlen($custom_no) > 10) {
                $this->error("销售员编号不得大于10位!");
            }
            if (empty($default_sale_percent) && $default_sale_percent != '0') {
                $this->error("默认销售提成不得为空");
            } else {
                if (! is_numeric($default_sale_percent) &&
                     ! is_float($default_sale_percent)) {
                    $this->error("默认销售提成必须是数字");
                }
            }
            if ($default_sale_percent > 100) {
                $this->error("提成不得大于100%");
            }
            if ($default_sale_percent < 0) {
                $this->error("提成不得小于0");
            }
            // 判断银行账号与名称是否同时存在或同时不存在
            ((empty($bankAccount) && empty($bankName)) ||
                 (! empty($bankAccount) && ! empty($bankName))) or
                 $this->error("银行名称与账号必须同时存在或不存在");
            if (! empty($bankName) && ! in_array($bankName, 
                C('defaultBankName'))) {
                $this->error("银行名称有误");
            }
            // 判断银行账号是否为16-19位
            if (! empty($bankAccount)) {
                (strlen($bankAccount) > 15 && strlen($bankAccount) < 20 &&
                     preg_match('/^\d+$/i', $bankAccount)) or
                     $this->error("银行账号必须为数字，且为16-19位");
            }
            $insertArr['name'] = $name;
            $insertArr['phone_no'] = $phone_no;
            $insertArr['custom_no'] = $custom_no;
            $insertArr['alipay_account'] = $alipay_account;
            $insertArr['bank_name'] = $bankName;
            if($flag == '1')
                $insertArr['status'] = '3';
            $insertArr['bank_account'] = $bankAccount;
            $insertArr['default_sale_percent'] = $default_sale_percent;
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'phone_no' => $phone_no, 
                    'id' => array(
                        'NEQ', 
                        $id)))->select()) {
                $this->error("手机号码已存在");
            }
            // 判断销售员编号是否重复
            if (! empty($custom_no) && M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'custom_no' => $custom_no, 
                    'role' => '1', 
                    'id' => array(
                        'NEQ', 
                        $id)))->select()) {
                $this->error("销售员编号已存在");
            }
        }
        M()->startTrans();
        if (false === M('twfx_saler')->where(
            array(
                'id' => $id))
            ->data($insertArr)
            ->save()) {
            M()->rollback();
            $this->error("修改失败");
        } else {
            $log = self::writeLog($prevSalerInfo, $insertArr);
            if (! empty($log)) {
                $userService = D('UserSess', 'Service');
                $userInfo = $userService->getUserInfo();
                $logData = array();
                $logData['log_index'] = $id;
                $logData['type'] = 1;
                $logData['user_id'] = $userInfo['user_id'];
                $logData['json_data'] = json_encode($log);
                $logData['add_time'] = date('YmdHis');
                if (false === M('twfx_edit_log')->add($logData)) {
                    M()->rollback();
                    $this->error("修改失败");
                }
            }
            M()->commit();
            $this->success("修改成功");
        }
    }
    /**
     * [checkAllStatus 批量审核经销商/销售员]
     *
     * @return [type] [description]
     */
    public function checkAllStatus() {
        if ($this->isPost()) {
            $role = I('role', "");
            if (! in_array($role, 
                array(
                    '1', 
                    '2'))) {
                $this->error('提交失败');
            }
            if ($role == 1) {
                $salePercent = I('salePercent', '');
                if (empty($salePercent)) {
                    $this->error('销售提成不得为空');
                }
                if (false === M('twfx_saler')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'status' => 1, 
                        'role' => 1))->save(
                    array(
                        'status' => 3, 
                        'default_sale_percent' => $salePercent))) {
                    $this->error('审核失败');
                }
            } else {
                if (false === M('twfx_saler')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'status' => 1, 
                        'role' => 2))->save(
                    array(
                        'status' => 3))) {
                    $this->error('审核失败');
                }
            }
            $this->success("审核成功");
        }
        $salerNumber = M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => 1, 
                'role' => 1))->count();
        $agencyNumber = M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => 1, 
                'role' => 2))->count();
        $this->assign('salerNumber', $salerNumber);
        $this->assign('agencyNumber', $agencyNumber);
        $this->display();
    }
    /**
     * 对经销商/销售员进行审核
     */
    public function checkStatus() {
        $id = I('id', '');
        $role = I('role', '');
        if ($this->isPost()) {
            $status = I('status');
            // 经销商审核
            if ($role == '2') {
                M()->startTrans();
                if (false === M('twfx_saler')->where(
                    array(
                        'id' => $id))
                    ->data(
                    array(
                        'status' => $status, 
                        'audit_id' => $this->userInfo['user_id'], 
                        'audit_user_name' => $this->userInfo['name'], 
                        'audit_time' => date("YmdHis")))
                    ->save()) {
                    M()->rollback();
                    $this->error("审核失败!");
                } else {
                    $userService = D('UserSess', 'Service');
                    $userInfo = $userService->getUserInfo();
                    $logData = array();
                    $logData['log_index'] = $id;
                    $logData['type'] = 5;
                    $logData['user_id'] = $userInfo['user_id'];
                    $logData['json_data'] = $status == 2 ? "未通过审核" : "审核通过";
                    $logData['add_time'] = date('YmdHis');
                    if (false === M('twfx_edit_log')->add($logData)) {
                        M()->rollback();
                        $this->error("审核失败");
                    }
                    M()->commit();
                    $this->success("操作成功!");
                }
            }
            // 销售员审核
            if ($role == '1') {
                if ($status == 3) {
                    $custom_no = I('custom_no', "", "trim,htmlspecialchars");
                    $default_sale_percent = I('salePercent', "", 
                        "trim,htmlspecialchars");
                    ! empty($default_sale_percent) or $this->error("销售员提成不得为空");
                    if ($default_sale_percent < 0 || $default_sale_percent > 100) {
                        $this->error("销售员提成设置的范围为0~100%!");
                    }
                    // 判断销售员编号是否重复
                    if (! empty($custom_no) && M('twfx_saler')->where(
                        array(
                            'node_id' => $this->node_id, 
                            'custom_no' => $custom_no, 
                            'role' => '1', 
                            'id' => array(
                                'NEQ', 
                                $id)))->select()) {
                        $this->error("销售员编号已存在");
                    }
                    if (! empty($custom_no) && strlen($custom_no) > 10) {
                        $this->error("销售员编号限10位");
                    }
                    session('default_sale_percent', $default_sale_percent);
                } else {
                    $custom_no = "";
                    $default_sale_percent = "";
                }
                M()->startTrans();
                if (false === M('twfx_saler')->where(
                    array(
                        'id' => $id))
                    ->data(
                    array(
                        'custom_no' => $custom_no, 
                        'default_sale_percent' => $default_sale_percent, 
                        'status' => $status, 
                        'audit_id' => $this->userInfo['user_id'], 
                        'audit_user_name' => $this->userInfo['name'], 
                        'audit_time' => date("YmdHis")))
                    ->save()) {
                    M()->rollback();
                    $this->error("审核失败!");
                } else {
                    $userService = D('UserSess', 'Service');
                    $userInfo = $userService->getUserInfo();
                    $logData = array();
                    $logData['log_index'] = $id;
                    $logData['type'] = 5;
                    $logData['user_id'] = $userInfo['user_id'];
                    $logData['json_data'] = $status == 2 ? "未通过审核" : "审核通过";
                    $logData['add_time'] = date('YmdHis');
                    if (false === M('twfx_edit_log')->add($logData)) {
                        M()->rollback();
                        $this->error("审核失败");
                    }
                    M()->commit();
                    $this->success("操作成功!");
                }
            }
        }
        $salerPercent = M('twfx_saler')->getFieldById($id, 'default_sale_percent');
        $this->assign('id', $id);
        $this->assign('salerPercent', $salerPercent);
        $this->assign('role', $role);
        $this->display();
    }

    /**
     * [move 销售员、经销商转移层级]
     *
     * @return [type] [description]
     */
    public function move() {
        $id = I('id');
        $role = I("role");
        if (empty($id)) {
            exit("参数错误");
        }
        if (! in_array($role, 
            array(
                '1', 
                '2'))) {
            exit("参数错误");
        }
        $wfx = D('Wfx');
        $res = $wfx->getBelongAgency($this->node_id, false);
        $res2 = $wfx->getBelongAgency($this->node_id, true);
        $levelArr = array(
            '1' => '一级', 
            '2' => '二级', 
            '3' => '三级', 
            '4' => '四级', 
            '5' => '五级');
        $agencyList = "顶级-00000000000-" . $this->nodeInfo['node_name'] . ' ';
        $salerList = "";
        foreach ($res as $k => $v) {
            if ($v['id'] != $id) {
                $agencyList .= $levelArr[$v['level']] . '-' . $v['phone_no'] .
                     '-' . mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
        }
        foreach ($res2 as $k => $v) {
            if ($v['id'] != $id) {
                $salerList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                     mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
        }
        $this->assign('id', $id);
        $this->assign('role', $role);
        $this->assign('agencyList', trim($agencyList));
        $this->assign('salerList', trim($salerList));
        $this->display();
    }

    /**
     * [moveAjax ａｊａｘ提交转移]
     *
     * @return [type] [description]
     */
    public function moveAjax() {
        $salerId = I('get.salerId', null);
        $getAgencyInfo = I('get.agency', null);
        if (! $salerId || ! $getAgencyInfo) {
            $this->error("参数错误");
        }
        // 被转移的信息
        $salerInfo = M('twfx_saler')->where(
            array(
                'id' => $salerId, 
                'node_id' => $this->node_id))->find();
        $curSalerParentInfo = M('twfx_saler')->where(
            array(
                'id' => $salerInfo['parent_id'], 
                'node_id' => $this->node_id))->find();
        $getAgencyInfoArr = explode('-', $getAgencyInfo);
        // 选择的经销商信息
        if ($getAgencyInfoArr[1] == '0000000000') {
            $agencyInfo['level'] = 0;
            $agencyInfo['id'] = 0;
            $agencyInfo['parent_path'] = '';
        } else {
            $agencyInfo = M('twfx_saler')->where(
                array(
                    'phone_no' => $getAgencyInfoArr[1], 
                    'node_id' => $this->node_id))->find();
        }
        
        if (empty($salerInfo)) {
            $this->error("销售员/经销商信息有误");
        }
        if (empty($agencyInfo)) {
            $this->error("经销商信息有误");
        }
        if ($curSalerParentInfo['id'] == $agencyInfo['id']) {
            $this->error("该销售员/经销商已经在" . $agencyInfo['name'] . '的下面');
        }
        $log['preName'] = $curSalerParentInfo['name'];
        $log['curName'] = $agencyInfo['name'];
        // 销售员转移
        if ($salerInfo['role'] == 1) {
            // 销售员要更新的信息
            $data['level'] = $agencyInfo['level'];
            $data['parent_id'] = $agencyInfo['id'];
            $data['parent_path'] = $agencyInfo['parent_path'] . $agencyInfo['id'] .
                 ',';
            M()->startTrans();
            if (M('twfx_saler')->where(
                array(
                    'id' => $salerInfo['id'], 
                    'node_id' => $this->node_id))->save($data)) {
                $userService = D('UserSess', 'Service');
                $userInfo = $userService->getUserInfo();
                $logData = array();
                $logData['log_index'] = $salerId;
                $logData['type'] = 6;
                $logData['user_id'] = $userInfo['user_id'];
                $logData['json_data'] = json_encode($log);
                $logData['add_time'] = date('YmdHis');
                if (false === M('twfx_edit_log')->add($logData)) {
                    M()->rollback();
                    $this->error("转移失败");
                }
                M()->commit();
                $this->success("转移成功");
            } else {
                M()->rollback();
                $this->error("转移失败");
            }
            // 经销商转移
        } elseif ($salerInfo['role'] == 2) {
            // 经销商层级判断，不能超过五级
            if ($agencyInfo['level'] > 4) {
                $this->error('选择的经销商不能大于四级');
            }
            M()->startTrans();
            $sonInfo = M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'parent_path' => array(
                        'like', 
                        $salerInfo['parent_path'] . $salerInfo['id'] . ',%')))
                ->order('level desc')
                ->select();
            if ((4 - ($sonInfo[0]['level'] - $salerInfo['level'])) <
                 $agencyInfo['level']) {
                M()->rollback();
                $this->error("当前经销商不能转移到'" . $agencyInfo['name'] . "'经销商下");
            }
            // 更改当前的经销商信息
            $curAgencyData['level'] = $agencyInfo['level'] + 1;
            $curAgencyData['parent_id'] = $agencyInfo['id'];
            $curAgencyData['parent_path'] = $agencyInfo['parent_path'] .
                 $agencyInfo['id'] . ',';
            if (false === M('twfx_saler')->where(
                array(
                    'id' => $salerInfo['id'], 
                    'node_id' => $this->node_id, 
                    'role' => 2))->save($curAgencyData)) {
                M()->rollback();
                $this->error("转移失败");
            }
            // 当前经销商下面的所有的销售员和经销商都要转移
            if (! empty($sonInfo)) {
                foreach ($sonInfo as $k => $v) {
                    $sonData['level'] = $v['level'] +
                         ($curAgencyData['level'] - $salerInfo['level']);
                    $sonData['parent_path'] = str_replace(
                        $salerInfo['parent_path'], $curAgencyData['parent_path'], 
                        $v['parent_path']);
                    if (false === M('twfx_saler')->where(
                        array(
                            'id' => $v['id'], 
                            'node_id' => $this->node_id))->save($sonData)) {
                        M()->rollback();
                        $this->error("转移失败");
                    }
                }
            }
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $salerId;
            $logData['type'] = 6;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = json_encode($log);
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                M()->rollback();
                $this->error("修改失败");
            }
            M()->commit();
            $this->success("转移成功");
        } else {
            M()->rollback();
            $this->error("转移失败");
        }
    }
    /**
     * [start 启用销售员或者经销商]
     *
     * @return [type] [description]
     */
    public function start() {
        $id = I('get.id');
        $role = I('get.role');
        if ($this->isPost()) {
            M()->startTrans();
            if (false === M('twfx_saler')->where(
                array(
                    'id' => $id))->save(
                array(
                    'status' => 3))) {
                M()->rollback();
                $this->error('启用失败');
            } else {
                $userService = D('UserSess', 'Service');
                $userInfo = $userService->getUserInfo();
                $logData = array();
                $logData['log_index'] = $id;
                $logData['type'] = 3;
                $logData['user_id'] = $userInfo['user_id'];
                $logData['json_data'] = "启用成功";
                $logData['add_time'] = date('YmdHis');
                if (false === M('twfx_edit_log')->add($logData)) {
                    M()->rollback();
                    $this->error("启用失败");
                }
                M()->commit();
                $this->success("启用成功");
            }
        }
        if($this->node_id==C('meihui.node_id')){
            $this->assign('meihuiFlag', 1);
        }
        $name = M('twfx_saler')->getFieldById($id, 'name');
        $this->assign('id', $id);
        $this->assign('name', $name);
        $this->assign('role', $role);
        $this->display();
    }
    
    // 对销售员/经销商停用操作
    public function stop() {
        $id = I('get.id');
        $role = I('get.role');
        $curSalerInfo = M('twfx_saler')->where(
            array(
                'id' => $id))->find();
        $name = $curSalerInfo['name'];
        if ($role == 2) {
            $sonAgencyAmt = M('twfx_saler')->where(
                array(
                    'role' => 2, 
                    'parent_id' => $id, 
                    'node_id' => $this->node_id))->count();
            $sonSalerAmt = M('twfx_saler')->where(
                array(
                    'role' => 1, 
                    'parent_id' => $id, 
                    'node_id' => $this->node_id))->count();
            $customAmt = M('twfx_customer_relation')->where(
                array(
                    'saler_id' => $id))->count();
            $this->assign('sonAgencyAmt', $sonAgencyAmt);
            $this->assign('sonSalerAmt', $sonSalerAmt);
            $this->assign('customAmt', $customAmt);
        } else {
            $customAmt = M('twfx_customer_relation')->where(
                array(
                    'saler_id' => $id))->count();
            $this->assign('customAmt', $customAmt);
        }
        $wfx = D('Wfx');
        $res = $wfx->getBelongAgency($this->node_id, false);
        $res2 = $wfx->getBelongAgency($this->node_id, true);
        $levelArr = array(
            '1' => '一级', 
            '2' => '二级', 
            '3' => '三级', 
            '4' => '四级', 
            '5' => '五级');
        $agencyList = "";
        $salerList = "";
        foreach ($res as $k => $v) {
            if ($v['id'] != $id) {
                $agencyList .= $levelArr[$v['level']] . '-' . $v['phone_no'] .
                     '-' . mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
        }
        foreach ($res2 as $k => $v) {
            if ($v['id'] != $id) {
                $salerList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                     mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
        }
        $this->assign('id', $id);
        $this->assign('name', $name);
        $this->assign('role', $role);
        $this->assign('agencyList', trim($agencyList));
        $this->assign('salerList', trim($salerList));
        $this->display();
    }
    public function stopAjax() {
        $id = I('get.id');
        $role = I('get.role');
        $phoneNo = I('get.phone_no');
        $selectedAgency = I('get.selected_agency');
        $amount = I('get.amount');
        $isNone = I('get.isNone');
        $log = array();
        // 参数验证
        if (empty($id) || empty($role)) {
            $this->error("非法提交");
        }
        if ($amount != '0') {
            if (empty($phoneNo)) {
                $this->error("请输入销售员或经销商的手机号码");
            }
            // 检查销售员或经销商的号码是否存在,且为正常
            $isExist = M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'phone_no' => $phoneNo, 
                    'status' => 3))->select();
            empty($isExist) && $this->error("销售员或经销商的手机号码不存在,或不可用");
        }
        
        if ($role == "2" && $isNone == "1") {
            if (empty($selectedAgency)) {
                $this->error("请输入所属经销商");
            }
        }
        $tmpInfo = explode("-", $selectedAgency);
        // 要转移的上级经销商
        $agencyInfo = M('twfx_saler')->where(
            array(
                'phone_no' => $tmpInfo[1], 
                'role' => 2, 
                'node_id' => $this->node_id))->find();
        // 判断是否有无聊者转移到自己的下级经销商下面去
        $tmpParentIdArr = explode(",", $agencyInfo['parent_path']);
        if (in_array($id, $tmpParentIdArr)) {
            $this->error("不能选择自己的下级经销商为转移对象");
        }
        // 当前的saler信息
        $curSalerInfo = M('twfx_saler')->getById($id);
        // 消费者要绑定的对象
        $toSalerInfo = M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id, 
                'phone_no' => $phoneNo))->find();
        M()->startTrans();
        // 转移消费者绑定
        if ($amount != '0') {
            $log['moveCustom'] = 1;
            $log['customAmt'] = M('twfx_customer_relation')->where(
                array(
                    'saler_id' => $id, 
                    'node_id' => $this->node_id))->count();
            $log['givedSaler'] = $toSalerInfo['name'];
            if (false === M('twfx_customer_relation')->where(
                array(
                    'saler_id' => $id, 
                    'node_id' => $this->node_id))->save(
                array(
                    'saler_id' => $toSalerInfo['id'], 
                    'add_time' => date('YmdHis')))) {
                M()->rollback();
                $this->error("停用失败");
            }
        } else {
            $log['moveCustom'] = 0;
        }
        // 转移下级所有的saler信息
        if ($role == 1) {
            $salerData['status'] = 4;
            if (false === M('twfx_saler')->where(
                array(
                    'id' => $id))->save($salerData)) {
                M()->rollback();
                $this->error("停用失败");
            }
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $id;
            $logData['type'] = 4;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = json_encode($log);
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                M()->rollback();
                $this->error("启用失败");
            }
            M()->commit();
            $this->success("停用成功");
        } elseif ($role == 2 && $isNone == "1") {
            // 经销商层级判断，不能超过五级
            if ($agencyInfo['level'] > 4) {
                M()->rollback();
                $this->error('选择的经销商不能大于四级');
            }
            $sonInfo = M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'parent_path' => array(
                        'like', 
                        $curSalerInfo['parent_path'] . $curSalerInfo['id'] . ',%')))
                ->order('level desc')
                ->select();
            $dSonInfo = M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'parent_id' => $id))->select();
            $sonIdArr = array();
            foreach ($dSonInfo as $kk => $vv) {
                $sonIdArr[] = $vv['id'];
            }
            if ((5 - ($sonInfo[0]['level'] - $curSalerInfo['level'])) <
                 $agencyInfo['level']) {
                M()->rollback();
                $this->error(
                    "当前经销商下的销售员和经销商不能转移到'" . $agencyInfo['name'] . "'经销商下");
            }
            // 更改当前的经销商信息
            $curAgencyData['status'] = 4;
            if (false === M('twfx_saler')->where(
                array(
                    'id' => $curSalerInfo['id'], 
                    'node_id' => $this->node_id, 
                    'role' => 2))->save($curAgencyData)) {
                M()->rollback();
                $this->error("停用失败");
            }
            // 当前经销商下面的所有的销售员和经销商都要转移
            if (! empty($sonInfo)) {
                foreach ($sonInfo as $k => $v) {
                    $sonData = array();
                    $sonData['level'] = $v['level'] +
                         ($agencyInfo['level'] - $curSalerInfo['level']);
                    if (in_array($v['id'], $sonIdArr)) {
                        $sonData['parent_id'] = $agencyInfo['id'];
                    }
                    $sonData['parent_path'] = str_replace(
                        $curSalerInfo['parent_path'] . $curSalerInfo['id'] . ',', 
                        $agencyInfo['parent_path'] . $agencyInfo['id'] . ',', 
                        $v['parent_path']);
                    if (false === M('twfx_saler')->where(
                        array(
                            'id' => $v['id'], 
                            'node_id' => $this->node_id))->save($sonData)) {
                        M()->rollback();
                        $this->error("停用失败");
                    }
                }
            }
            $log['sonMove'] = 1;
            $log['sonGivedSaler'] = $agencyInfo['name'];
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $id;
            $logData['type'] = 4;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = json_encode($log);
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                M()->rollback();
                $this->error("停用失败");
            }
            M()->commit();
            $this->success("停用成功");
        } else {
            // 更改当前的经销商信息
            $curAgencyData['status'] = 4;
            if (false === M('twfx_saler')->where(
                array(
                    'id' => $curSalerInfo['id'], 
                    'node_id' => $this->node_id, 
                    'role' => 2))->save($curAgencyData)) {
                M()->rollback();
                $this->error("停用失败");
            }
            $log['sonMove'] = 0;
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $id;
            $logData['type'] = 4;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = json_encode($log);
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                M()->rollback();
                $this->error("停用失败");
            }
            M()->commit();
            $this->success("停用成功");
        }
        $this->error("非法提交");
    }

    public function import() {
        self::checkNodeInfo();
        C('FXGL_TPL_APPLY', APP_PATH . 'Upload/fxgl_apply/');
        $this->display();
    }

    public function loadModel() {
        C('FXGL_TPL_APPLY', APP_PATH . 'Upload/fxgl_apply/');
        // 新建一个文件夹，下面的函数上传需要用到
        if (! file_exists(APP_PATH . 'Upload/fxgl_apply/')) {
            mkdir($rootpath);
        }
        // 当前商户的账户类型
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->node_id, 
            'account_type');
        header("Content-type:text/csv");
        header("Content-Disposition: attachment;filename=fenxiao_tpl.csv ");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        if ($accountType == 1) {
            $rs = array(
                array(
                    '电话号码(不可重复)', 
                    '经销商/销售员名称', 
                    '经销商负责人', 
                    '支付宝账号', 
                    '销售员编号', 
                    '默认销售提成(%)', 
                    '默认管理提成(%)', 
                    '角色（经销商:2/销售员:1）', 
                    '所属经销商ID'), 
                array(
                    $this->nodeInfo['contact_phone'], 
                    $this->nodeInfo['node_name'], 
                    $this->nodeInfo['contact_name'], 
                    rand(111, 9999) . '@qq.com', 
                    'saler_' . rand(100, 999), 
                    rand(1, 9), 
                    rand(1, 9), 
                    rand(1, 2), 
                    8));
        } else {
            $rs = array(
                array(
                    '电话号码(不可重复)', 
                    '经销商/销售员名称', 
                    '经销商负责人', 
                    '银行名称', 
                    '银行账号', 
                    '销售员编号', 
                    '默认销售提成(%)', 
                    '默认管理提成(%)', 
                    '角色（经销商:2/销售员:1）', 
                    '所属经销商ID'), 
                array(
                    $this->nodeInfo['contact_phone'], 
                    $this->nodeInfo['node_name'], 
                    $this->nodeInfo['contact_name'], 
                    '交通银行', 
                    '6222325689657854', 
                    'saler_' . rand(100, 999), 
                    rand(1, 9), 
                    rand(1, 9), 
                    rand(1, 2), 
                    8));
        }
        $str = '';
        foreach ($rs as $row) {
            $str_arr = array();
            foreach ($row as $column) {
                $str_arr[] = '"' .
                     str_replace('"', '""', iconv('utf-8', 'gb2312', $column)) .
                     '"';
            }
            $str .= implode(',', $str_arr) . PHP_EOL;
        }
        echo $str;
    }

    public function viewDetails() {
        $role = I('role');
        $id = I('id');
        (! empty($role) && ! empty($id)) or $this->error("参数错误");
        self::viewfxyj($role, $id);
        self::viewEditLog($role, $id);
        // 根据id查询信息
        $resultInfo = M('twfx_saler')->getById($id);
        // 状态数组，预处理
        $statusArr = array(
            '1' => '审核中', 
            '2' => '未通过审核', 
            '3' => '正常', 
            '4' => '停用');
        // 对结果数据处理
        $resultInfo['parent_name'] = M('twfx_saler')->getFieldById(
            $resultInfo['parent_id'], 'name');
        if ($role == '2') {
            $resultInfo['sonAgencyAmount'] = M('twfx_saler')->where(
                array(
                    'parent_id' => $resultInfo['id'], 
                    'role' => '2', 
                    'status' => '3'))->count();
            $resultInfo['sonSalerAmount'] = M('twfx_saler')->where(
                array(
                    'parent_id' => $resultInfo['id'], 
                    'role' => '1',
                    'status' => '3'))->count();
        } else {
            $resultInfo['customerAmount'] = M('twfx_customer_relation')->where(
                array(
                    'saler_id' => $resultInfo['id']))->count();
        }
        // 如果所在地区存在
        if (! empty($resultInfo['area'])) {
            $areaInfo = M('tcity_code')->getByPath($resultInfo['area']);
            $resultInfo['area'] = $areaInfo['province'] . '/' . $areaInfo['city'] .
                 '/' . $areaInfo['town'];
        }
        // 获取推荐人
        if (! empty($resultInfo['referee_id'])) {
            $resultInfo['referee_name'] = M('twfx_saler')->getFieldById(
                $resultInfo['referee_id'], 'name');
        }
        $resultInfo['codeUrl'] = $this->codePng($id, true);
        $jsonData = file_get_contents(
            'https://api.weibo.com/2/short_url/shorten.json?source=1362404091&url_long=' .
                 urlencode($resultInfo['codeUrl']));
        $shortUrl = json_decode($jsonData, true);
        $resultInfo['codePng'] = U('Wfx/Fxgl/codePng', array('saler_id' => $id));
        // 当前商户的账户类型
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->node_id, 'account_type');
        $this->assign('info', $resultInfo);
        $this->assign('statusArr', $statusArr);
        // $this->assign('paramUrl',urlencode($resultInfo['codeUrl']));
        $this->assign('role', $role);
        $this->assign('jsonData', $shortUrl['urls'][0]['url_short']);
        $this->assign('id', $id);
        $this->assign('accountType', $accountType);
        if($this->node_id==C('meihui.node_id')){
            self::mHstatisticsOrder($id,$this->node_id);
            self::mHstatisticsOrderBySalser($id,$this->node_id);
            $meiHuiInfo=array('1'=>'门店', '2'=>'钻石', '3'=>'金牌', '4'=>'银牌');
            $count=$this->meiHuiModel->getMemberCountByLevelAndId($this->node_id,$id,$resultInfo['level'],$resultInfo['parent_path']);
            $this->assign('count', $count);
            $this->assign('meiHuiInfo', $meiHuiInfo);
            $this->display("meihui/Fxgl_viewDetails");
        }else{
            $this->display();
        }
    }

    public function batchApply() {
        C('FXGL_TPL_APPLY', APP_PATH . 'Upload/fxgl_apply/');
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->maxSize = 3145728;
        $upload->savePath = C('FXGL_TPL_APPLY');
        $info = $upload->uploadOne($_FILES['staff']);
        $flieWay = $upload->savePath . $info['savepath'] . $info[0]['savename'];
        $row = 0;
        $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
        if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
            @unlink($flieWay);
            $this->error('文件类型不符合');
        }
        
        // 当前商户的账户类型
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->node_id, 
            'account_type');
        $result = array();
        $insertArr = array();
        $errorLine = 0;
        if (($handle = fopen($flieWay, "rw")) !== FALSE) {
            while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                ++ $row;
                $arr = utf8Array($arr);
                if ($row == 1) {
                    if ($accountType == 1) {
                        $fileField = array(
                            '电话号码(不可重复)', 
                            '经销商/销售员名称', 
                            '经销商负责人', 
                            '支付宝账号', 
                            '销售员编号', 
                            '默认销售提成(%)', 
                            '默认管理提成(%)', 
                            '角色（经销商:2/销售员:1）', 
                            '所属经销商ID');
                    } else {
                        $fileField = array(
                            '电话号码(不可重复)', 
                            '经销商/销售员名称', 
                            '经销商负责人', 
                            '银行名称', 
                            '银行账号', 
                            '销售员编号', 
                            '默认销售提成(%)', 
                            '默认管理提成(%)', 
                            '角色（经销商:2/销售员:1）', 
                            '所属经销商ID');
                    }
                    $arrDiff = array_diff_assoc($arr, $fileField);
                    if (count($arr) != count($fileField) || ! empty($arrDiff)) {
                        fclose($handle);
                        @unlink($flieWay);
                        $this->error(
                            '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                    }
                    continue;
                }
                $array = array();
                $array['phone_no'] = self::preString($arr[0]);
                $array['name'] = self::preString($arr[1]);
                $array['contact_name'] = self::preString($arr[2]);
                if ($accountType == 1) {
                    $array['alipay_account'] = self::preString($arr[3]);
                    $array['custom_no'] = self::preString($arr[4]);
                    $array['default_sale_percent'] = self::preString($arr[5]);
                    $array['default_manage_percent'] = self::preString($arr[6]);
                    $array['role'] = self::preString($arr[7]);
                    $array['parent_id'] = self::preString($arr[8]);
                } else {
                    $array['bank_name'] = self::preString($arr[3]);
                    $array['bank_account'] = self::preString($arr[4]);
                    $array['custom_no'] = self::preString($arr[5]);
                    $array['default_sale_percent'] = self::preString($arr[6]);
                    $array['default_manage_percent'] = self::preString($arr[7]);
                    $array['role'] = self::preString($arr[8]);
                    $array['parent_id'] = self::preString($arr[9]);
                    // 此处判断银行名称和账号
                    // 判断银行账号与名称是否同时存在或同时不存在
                    ((empty($array['bank_account']) &&
                         empty($array['bank_name'])) || (! empty(
                            $array['bank_account']) &&
                         ! empty($array['bank_name']))) or
                         $this->error('第' . $row . "行的银行名称与账号必须同时存在或不存在");
                    if (! empty($array['bank_name']) &&
                         ! in_array($array['bank_name'], C('defaultBankName'))) {
                        $this->error('第' . $row . "行的银行名称有误");
                    }
                    // 判断银行账号是否为16-19位
                    if (! empty($array['bank_account'])) {
                        (strlen($array['bank_account']) > 15 &&
                             strlen($array['bank_account']) < 20 &&
                             preg_match('/^\d+$/i', $array['bank_account'])) or
                             $this->error('第' . $row . "行的银行账号必须为数字，且为16-19位");
                    }
                }
                
                $array['lineNumber'] = $row;
                
                $result[] = self::checkFileContent($array);
                $errorLine += count($result);
                unset($array['lineNumber']);
                $array['add_time'] = date('YmdHis');
                $array['status'] = '1';
                $array['node_id'] = $this->node_id;
                $array['add_user_id'] = $this->userInfo['user_id'];
                $array['add_user_name'] = $this->userInfo['name'];
                if ($array['role'] == '2') {
                    $array['level'] = M('twfx_saler')->getFieldById(
                        $array['parent_id'], 'level') + 1;
                } else {
                    $array['level'] = M('twfx_saler')->getFieldById(
                        $array['parent_id'], 'level');
                }
                $array['parent_path'] = M('twfx_saler')->getFieldById(
                    $array['parent_id'], 'parent_path') ? (M('twfx_saler')->getFieldById(
                    $array['parent_id'], 'parent_path')) . $array['parent_id'] .
                     ',' : '0,';
                $insertArr[] = $array;
            }
            $isempty = 0;
            foreach ($result as $k1 => $v1) {
                if (! empty($v1)) {
                    $isempty ++;
                }
            }
            if ($isempty != 0) {
                $errorStr = '';
                foreach ($result as $k => $v) {
                    if (! empty($v)) {
                        foreach ($v as $kk => $vv) {
                            $errorStr .= '' . $vv . '<br />';
                        }
                    }
                }
                @unlink($flieWay);
                $this->error('上传文件内容填写有误!<br/>' . $errorStr);
            }
            // 判断要插入的数据中，手机号码彼此是否重复，销售员编号是否重复，通过则开启事务插入
            if (! empty($insertArr)) {
                $phoneArr = array();
                $customArr = array();
                foreach ($insertArr as $kj => $vj) {
                    $phoneArr[] = $vj['phone_no'];
                    if (! empty($vj['custom_no'])) {
                        $customArr[] = $vj['custom_no'];
                    }
                }
                if (count($phoneArr) != count(array_unique($phoneArr))) {
                    @unlink($flieWay);
                    $this->error("文件中的手机号码不得重复");
                }
                if (count($customArr) != count(array_unique($customArr))) {
                    @unlink($flieWay);
                    $this->error("文件中的销售员编号不得重复");
                }
                $User = M('twfx_saler');
                $User->startTrans();
                foreach ($insertArr as $ki => $vi) {
                    if (! $User->data($vi)->add()) {
                        $User->rollback();
                        @unlink($flieWay);
                        $this->error("添加失败！");
                    }
                }
                $User->commit();
            } else {
                @unlink($flieWay);
                $this->error('您尚未填写任何销售员或经销商!');
            }
            @fclose($handle);
            @unlink($flieWay);
            $this->success('提交申请成功');
        }
    }

    private function checkFileContent($arr) {
        $resultArr = array();
        ! empty($arr['phone_no']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的手机号码不能为空！';
        (strlen($arr['phone_no']) == '11') or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的手机号码必须为11位！';
        if (M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id))->getFieldByPhone_no(
            $arr['phone_no'], 'id')) {
            $resultArr[] = '第' . $arr['lineNumber'] . '行的手机号码已存在！';
        }
        ! empty($arr['name']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的名称不能为空！';
        ! empty($arr['role']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的角色不能为空！';
        if (! in_array($arr['role'], 
            array(
                '1', 
                '2'))) {
            $resultArr[] = '第' . $arr['lineNumber'] . '行的角色填写有误！';
        }
        if ($arr['role'] == '1') {
            if (! empty($arr['custom_no']) && (strlen($arr['custom_no']) > 10)) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的销售员编号不得大于10位！';
            }
            if (! empty($arr['custom_no']) && M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'role' => '1'))->getFieldByCustom_no($arr['custom_no'], 'id')) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的销售员编号已存在！';
            }
            if (empty($arr['default_sale_percent']) &&
                 $arr['default_sale_percent'] != '0') {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认销售提成不得为空！';
            } else {
                if (! is_numeric($arr['default_sale_percent']) &&
                     ! is_float($arr['default_sale_percent'])) {
                    $resultArr[] = '第' . $arr['lineNumber'] . '行的默认销售提成必须是数字！';
                }
            }
            if ($arr['default_sale_percent'] > 100) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认提成不得大于100%！';
            }
            if ($arr['default_sale_percent'] < 0) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认提成不得小于0！';
            }
        }
        if ($arr['role'] == '2') {
            if (empty($arr['default_sale_percent']) &&
                 $arr['default_sale_percent'] != '0') {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认销售提成不得为空！';
            } else {
                if (! is_numeric($arr['default_sale_percent']) &&
                     ! is_float($arr['default_sale_percent'])) {
                    $resultArr[] = '第' . $arr['lineNumber'] . '行的默认销售提成必须是数字！';
                }
            }
            if (empty($arr['default_manage_percent']) &&
                 $arr['default_manage_percent'] != '0') {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认管理提成不得为空！';
            } else {
                if (! is_numeric($arr['default_manage_percent']) &&
                     ! is_float($arr['default_manage_percent'])) {
                    $resultArr[] = '第' . $arr['lineNumber'] . '行的默认管理提成必须是数字！';
                }
            }
            if ($arr['default_manage_percent'] > 100 ||
                 $arr['default_sale_percent'] > 100) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认提成不得大于100%！';
            }
            if ($arr['default_manage_percent'] < 0 ||
                 $arr['default_sale_percent'] < 0) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的默认提成不得小于0！';
            }
        }
        ! empty($arr['parent_id']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的所属经销商不能为空！';
        if (! M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $arr['parent_id'], 
                'role' => '2', 
                'status' => '3'))->select()) {
            $resultArr[] = '第' . $arr['lineNumber'] . '行的所属经销商不存在/未审核！';
        }
        if ($arr['role'] == '2') {
            $parent_level = M('twfx_saler')->field('level')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $arr['parent_id'], 
                    'role' => '2', 
                    'status' => '3'))
                ->select();
            if ($parent_level[0]['level'] > 4) {
                $resultArr[] = '第' . $arr['lineNumber'] . '行的所属经销商不能再增加经销商！';
            }
        }
        return $resultArr;
    }

    private function preString($str) {
        return htmlspecialchars(trim($str));
    }

    public function setError($str, $width = "200", $height = "100", $status = false) {
        $type = $status ? "succeed" : "error";
        $this->assign('type', $type);
        $this->assign('width', $width);
        $this->assign('height', $height);
        $this->assign('str', $str);
        $this->display('setError');
        exit();
    }

    /**
     * [viewfxyj 查看分销业绩]
     *
     * @param [type] $role [当前角色]
     * @param [type] $id [当前salerid]
     * @return [type] [null]
     */
    private function viewfxyj($role, $id) {
        // 引入分页类
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = M()->field(
            'count(g.order_id) AS order_amt,SUM(g.amount) AS amount_amt,SUM(g.bonus_amount) AS bonus_amt,DATE_FORMAT(CONCAT(g.add_time,"00000000"),"%Y年%m月") AS add_time')
            ->table(
            '(SELECT
				order_id,
				saler_id,
				SUM(amount) AS amount,
				SUM(bonus_amount) AS bonus_amount,
				substr(add_time,"1","6") AS add_time
			FROM
				twfx_trace
			WHERE
				saler_id = ' . $id . '
			GROUP BY
				order_id) g')
            ->group('g.add_time')
            ->order('g.add_time desc')
            ->select();
        $CPage = new Page(count($mapcount), 6);
        $CPage->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $CPage->setConfig('prev', '«');
        $CPage->setConfig('next', '»');
        $traceArr = M()->field(
            'count(g.order_id) AS order_amt,SUM(g.amount) AS amount_amt,SUM(g.bonus_amount) AS bonus_amt,DATE_FORMAT(CONCAT(g.add_time,"00000000"),"%Y年%m月") AS add_time')
            ->table(
            '(SELECT
				order_id,
				saler_id,
				SUM(amount) AS amount,
				SUM(bonus_amount) AS bonus_amount,
				substr(add_time,"1","6") AS add_time
			FROM
				twfx_trace
			WHERE
				saler_id = ' . $id . '
			GROUP BY
				order_id) g')
            ->group('g.add_time')
            ->order('g.add_time desc')
            ->limit($CPage->firstRow . ',' . $CPage->listRows)
            ->select();
        $page = $CPage->show();
        $this->assign('traceArr', $traceArr);
        $this->assign('page', $page);
    }

    private function viewEditLog($role, $id) {
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = M('twfx_edit_log')->where(
            array(
                'type' => array(
                    'BETWEEN', 
                    '1,7'), 
                'log_index' => $id))
            ->order('add_time desc')
            ->count();
        $EPage = new Page($mapcount, 6);
        $EPage->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $EPage->setConfig('prev', '«');
        $EPage->setConfig('next', '»');
        $logInfo = M('twfx_edit_log')->where(
            array(
                'type' => array(
                    'BETWEEN', 
                    '1,7'), 
                'log_index' => $id))
            ->order('add_time desc')
            ->limit($EPage->firstRow . ',' . $EPage->listRows)
            ->select();
        $pageEx = $EPage->show();
        $typeArr = array(
            '1' => '编辑', 
            '2' => '创建', 
            '3' => '启用', 
            '4' => '停用', 
            '5' => '审核', 
            '6' => '转移', 
            '7' => '转化');
        if (! empty($logInfo)) {
            foreach ($logInfo as $k => $v) {
                $jsonData = json_decode($v['json_data'], true);
                $logInfo[$k]['detail'] = "";
                if ($v['type'] == 1) {
                    $modifyAmt = 0;
                    if ($jsonData['name'] == 1) {
                        $tmpName = $role == 1 ? "销售员名称" : "经销商名称";
                        $logInfo[$k]['detail'] .= $tmpName . ' 由 ' .
                             $jsonData['preName'] . ' 改为 ' . $jsonData['curName'] .
                             '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['contact_name'] == 1) {
                        $logInfo[$k]['detail'] .= '负责人 由 ' .
                             $jsonData['preContactName'] . ' 改为 ' .
                             $jsonData['curContactName'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['phone_no'] == 1) {
                        $logInfo[$k]['detail'] .= '手机 由 ' .
                             $jsonData['prePhoneNo'] . ' 改为 ' .
                             $jsonData['curPhoneNo'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['custom_no'] == 1) {
                        $logInfo[$k]['detail'] .= '销售员编号 由 ' .
                             $jsonData['preCustomNo'] . ' 改为 ' .
                             $jsonData['curCustomNo'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['alipay_account'] == 1) {
                        $logInfo[$k]['detail'] .= '支付宝账号 由 ' .
                             $jsonData['preAlipayAccount'] . ' 改为 ' .
                             $jsonData['curAlipayAccount'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['bank_name'] == 1) {
                        $logInfo[$k]['detail'] .= '银行名称 由 ' .
                             $jsonData['preBankName'] . ' 改为 ' .
                             $jsonData['curBankName'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['bank_account'] == 1) {
                        $logInfo[$k]['detail'] .= '银行账号 由 ' .
                             $jsonData['preBankAccount'] . ' 改为 ' .
                             $jsonData['curBankAccount'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['default_sale_percent'] == 1) {
                        $logInfo[$k]['detail'] .= '销售默认提成 由 ' .
                             $jsonData['preSalePrecent'] . ' 改为 ' .
                             $jsonData['curSalePrecent'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['default_manage_percent'] == 1) {
                        $logInfo[$k]['detail'] .= '管理默认提成 由 ' .
                             $jsonData['preManagePercent'] . ' 改为 ' .
                             $jsonData['curManagePercent'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['business_licence'] == 1) {
                        $logInfo[$k]['detail'] .= '营业执照 由 ' .
                             $jsonData['preBusinessLicence'] . ' 改为 ' .
                             $jsonData['curBusinessLicence'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($modifyAmt == 0) {
                        $logInfo[$k]['detail'] .= "没有修改";
                    }
                }
                if ($v['type'] == 2) {
                    $logInfo[$k]['detail'] .= "无";
                }
                if (in_array($v['type'], 
                    array(
                        '3', 
                        '5', 
                        '7'))) {
                    $logInfo[$k]['detail'] .= $v['json_data'];
                }
                if ($v['type'] == 4) {
                    $modifyAmt = 0;
                    if ($jsonData['moveCustom'] == 1) {
                        $logInfo[$k]['detail'] .= $jsonData['customAmt'] .
                             '个消费者绑定给了' . $jsonData['givedSaler'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($jsonData['sonMove'] == 1) {
                        $logInfo[$k]['detail'] .= '下级销售员/经销商转移给了' .
                             $jsonData['sonGivedSaler'] . '<br/>';
                        $modifyAmt ++;
                    }
                    if ($modifyAmt == 0) {
                        $logInfo[$k]['detail'] .= "停用成功";
                    }
                }
                if ($v['type'] == 6) {
                    $logInfo[$k]['detail'] .= '上级经销商 由 ' . $jsonData['preName'] .
                         ' 改为 ' . $jsonData['curName'];
                }
                $logInfo[$k]['user_name'] = M('tuser_info')->getFieldByUser_id(
                    $v['user_id'], 'user_name');
                $logInfo[$k]['type_name'] = $typeArr[$v['type']];
            }
        }
        $this->assign('logInfo', $logInfo);
        $this->assign('pageEx', $pageEx);
    }
    
    // 下载二维码
    public function loadIcon() {
        $agency_selected = I('post.agency_selected', '');
        $tmp_role = I('post.types');
        if ($tmp_role == 1) {
            $role = 2;
        } else {
            $role = 1;
        }
        if ($agency_selected != '') {
            $start = strpos($agency_selected, '-');
            $phone = substr($agency_selected, $start + 1, 11);
        } else {
            $phone = '';
        }
        $this->WfxModel = D('Wfx');
        $saler_id_arr = $this->WfxModel->getAgencyData($this->nodeId, $role, 
            $phone); // 获得下级经销商或者销售的数据
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        // $saler_id_arr =
        // M('twfx_saler')->field('id,name,phone_no')->where(array('node_id'=>$this->node_id,'status'=>'3'))->select();
        $rootpath = APP_PATH . 'Upload/wfx_codeLoad/';
        $path = $rootpath . $this->node_id . '/';
        $realpath = get_upload_url(
            APP_PATH . 'Upload/wfx_codeLoad/' . $this->node_id . '/');
        if (! is_dir($rootpath)) {
            mkdir($rootpath);
        }
        if (! is_dir($path)) {
            mkdir($path);
        }
        if (! empty($saler_id_arr)) {
            $zip = new ZipArchive();
            $zipfilename = 'code_' . date('YmdHis') . '.zip';
            $zipfile = $path . $zipfilename;
            $zip_path = mb_convert_encoding($zipfile, "GBK", "UTF-8");
            if ($zip->open($zip_path, ZipArchive::OVERWRITE) === TRUE) {
                foreach ($saler_id_arr as $k => $v) {
                    $v['name'] = mb_convert_encoding($v['name'], "GBK", "UTF-8");
                    $file = $path . $v['name'] . '_' . $v['phone_no'] . '.png';
                    $filename = $v['name'] . '_' . $v['phone_no'] . '.png';
                    if (! file_exists($file)) {
                        $this->codePng($v['id'], false, $file, 600);
                    }
                    $zip->addFile($file, $filename);
                }
                $zip->close();
            }
            redirect($zip_path);
        } else {
            $this->error('无数据可下载');
        }
    }
    
    // 下载二维码
    public function loadOneIcon() {
        $saler_id = I('saler_id', '');
        ! empty($saler_id) or $this->error('参数错误！');
        $rootpath = APP_PATH . 'Upload/wfx_codeLoad/';
        $path = $rootpath . $this->node_id . '/';
        $saler_name = M('twfx_saler')->getFieldById($saler_id, 
            '"0",concat(name,"_",phone_no) as name');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header(
            'Content-Disposition: attachment; filename=' . $saler_name[0]['name'] .
                 '.png');
        $this->codePng($saler_id, false, false, 600);
    }
    
    // 二维码
    public function codePng($saler_id, $isUrl = false, $path = false, $size = 1) {
        $m_id = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => 29))->getField('id');
        if (! $m_id) {
            $this->error('获取默认小店活动失败');
        }
        $wfxModel = D('Wfx');
        $c_id = $wfxModel->getChannelId('9', '91', TRUE);
        if (! $c_id) {
            $this->error('获取默认小店渠道失败');
        }
        $mbc_id = get_batch_channel($m_id, $c_id, '29', 
            $_SESSION['userSessInfo']['node_id']);
        if (! $mbc_id) {
            $this->error('获取默认小店地址失败');
        }
        $shopUrl = U('Label/Store/index', 
            array(
                'id' => $mbc_id, 
                'saler_id' => $saler_id), '', '', TRUE);
        $nodeLogo = get_node_info($this->node_id, 'head_photo');
        $imageurl = get_upload_url($nodeLogo);
        $ap_arr = array(
            'is_resp' => '1', 
            'wfx' => '');
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        if ($path) {
            QRcode::png($shopUrl, $path, 'L', $size, 0, false, $imageurl);
        } else {
            if ($isUrl) {
                return $shopUrl;
            } else {
                QRcode::png($shopUrl, false, 'L', $size, 0, false, $imageurl, 
                    '', $ap_arr);
            }
        }
    }

    /**
     *
     * @param $data 操作的数组
     * @param string $fieldPri 唯一键名，如果是表则是表的主键
     * @param string $fieldPid 父ID键名
     * @param int $pid 一级PID的值
     * @param string $sid 子ID用于获得指定指ID的所有父ID
     * @param int $type 操作方式1=>返回多维数组,2=>返回一维数组,3=>得到指定子ID(参数$sid)的所有父
     * @param string $html 名称前缀，用于在视图中显示层次感的列表
     * @param int $level 不需要传参数（执行时调用）
     * @return array
     */
    private function channel($data, $fieldPri = 'cid', $fieldPid = 'pid', $pid = 0, 
        $sid = null, $type = 2, $html = "&nbsp;", $level = 1) {
        if (! $data) {
            return array();
        }
        switch ($type) {
            case 1:
                $arr = array();
                foreach ($data as $v) {
                    if ($v[$fieldPid] == $pid) {
                        $arr[$v[$fieldPri]] = $v;
                        $arr[$v[$fieldPri]]['html'] = str_repeat($html, 
                            $level - 1);
                        $arr[$v[$fieldPri]]["Data"] = self::channel($data, 
                            $fieldPri, $fieldPid, $v[$fieldPri], $sid, $type, 
                            $html, $level + 1);
                    }
                }
                return $arr;
            case 2:
                $arr = array();
                $id = 0;
                foreach ($data as $v) {
                    if ($v[$fieldPid] == $pid) {
                        $arr[$id] = $v;
                        $arr[$id]['level'] = $level;
                        $arr[$id]['html'] = str_repeat($html, $level - 1);
                        $sArr = self::channel($data, $fieldPri, $fieldPid, 
                            $v[$fieldPri], $sid, $type, $html, $level + 1);
                        $arr = array_merge($arr, $sArr);
                        $id = count($arr);
                    }
                }
                return $arr;
            case 3:
                static $arr = array();
                foreach ($data as $v) {
                    if ($v[$fieldPri] == $sid) {
                        $arr[] = $v;
                        $sArr = self::channel($data, $fieldPri, $fieldPid, $pid, 
                            $v[$fieldPid], $type, $html, $level + 1);
                        $arr = array_merge($arr, $sArr);
                    }
                }
                return $arr;
        }
    }

    private function checkChannel() {
        $hasChannel = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => 9))->select();
        if (empty($hasChannel)) {
            $data['name'] = '旺分销默认渠道';
            $data['type'] = '9';
            $data['sns_type'] = '91';
            $data['status'] = '1';
            $data['node_id'] = $this->node_id;
            $data['add_time'] = date('YmdHis');
            M('tchannel')->data($data)->add();
        }
    }

    private function writeLog($prevSalerInfo, $insertArr) {
        $modifyLogArr = array();
        if ($prevSalerInfo['name'] != $insertArr['name']) {
            $modifyLogArr['name'] = 1;
            $modifyLogArr['preName'] = $prevSalerInfo['name'];
            $modifyLogArr['curName'] = $insertArr['name'];
        } else {
            $modifyLogArr['name'] = 0;
        }
        if (! empty($insertArr['contact_name'])) {
            if ($prevSalerInfo['contact_name'] != $insertArr['contact_name']) {
                $modifyLogArr['contact_name'] = 1;
                $modifyLogArr['preContactName'] = $prevSalerInfo['contact_name'];
                $modifyLogArr['curContactName'] = $insertArr['contact_name'];
            } else {
                $modifyLogArr['contact_name'] = 0;
            }
        } else {
            $modifyLogArr['contact_name'] = 0;
        }
        if ($prevSalerInfo['phone_no'] != $insertArr['phone_no']) {
            $modifyLogArr['phone_no'] = 1;
            $modifyLogArr['prePhoneNo'] = $prevSalerInfo['phone_no'];
            $modifyLogArr['curPhoneNo'] = $insertArr['phone_no'];
        } else {
            $modifyLogArr['phone_no'] = 0;
        }
        if ($prevSalerInfo['alipay_account'] != $insertArr['alipay_account']) {
            $modifyLogArr['alipay_account'] = 1;
            $modifyLogArr['preAlipayAccount'] = $prevSalerInfo['alipay_account'];
            $modifyLogArr['curAlipayAccount'] = $insertArr['alipay_account'];
        } else {
            $modifyLogArr['alipay_account'] = 0;
        }
        if ($prevSalerInfo['bank_name'] != $insertArr['bank_name']) {
            $modifyLogArr['bank_name'] = 1;
            $modifyLogArr['preBankName'] = $prevSalerInfo['bank_name'];
            $modifyLogArr['curBankName'] = $insertArr['bank_name'];
        } else {
            $modifyLogArr['bank_name'] = 0;
        }
        if ($prevSalerInfo['bank_account'] != $insertArr['bank_account']) {
            $modifyLogArr['bank_account'] = 1;
            $modifyLogArr['preBankAccount'] = $prevSalerInfo['bank_account'];
            $modifyLogArr['curBankAccount'] = $insertArr['bank_account'];
        } else {
            $modifyLogArr['bank_account'] = 0;
        }
        if ($prevSalerInfo['default_sale_percent'] !=
             $insertArr['default_sale_percent']) {
            $modifyLogArr['default_sale_percent'] = 1;
            $modifyLogArr['preSalePrecent'] = $prevSalerInfo['default_sale_percent'];
            $modifyLogArr['curSalePrecent'] = $insertArr['default_sale_percent'];
        } else {
            $modifyLogArr['default_sale_percent'] = 0;
        }
        if (! empty($insertArr['custom_no'])) {
            if ($prevSalerInfo['custom_no'] != $insertArr['custom_no']) {
                $modifyLogArr['custom_no'] = 1;
                $modifyLogArr['preCustomNo'] = $prevSalerInfo['custom_no'];
                $modifyLogArr['curCustomNo'] = $insertArr['custom_no'];
            } else {
                $modifyLogArr['custom_no'] = 0;
            }
        } else {
            $modifyLogArr['custom_no'] = 0;
        }
        if (! empty($insertArr['business_licence'])) {
            if ($prevSalerInfo['business_licence'] !=
                 $insertArr['business_licence']) {
                $modifyLogArr['business_licence'] = 1;
                $modifyLogArr['preBusinessLicence'] = $prevSalerInfo['business_licence'];
                $modifyLogArr['curBusinessLicence'] = $insertArr['business_licence'];
            } else {
                $modifyLogArr['business_licence'] = 0;
            }
        } else {
            $modifyLogArr['business_licence'] = 0;
        }
        if (! empty($insertArr['default_manage_percent'])) {
            if ($prevSalerInfo['default_manage_percent'] !=
                 $insertArr['default_manage_percent']) {
                $modifyLogArr['default_manage_percent'] = 1;
                $modifyLogArr['preManagePercent'] = $prevSalerInfo['default_manage_percent'];
                $modifyLogArr['curManagePercent'] = $insertArr['default_manage_percent'];
            } else {
                $modifyLogArr['default_manage_percent'] = 0;
            }
        } else {
            $modifyLogArr['default_manage_percent'] = 0;
        }
        return $modifyLogArr;
    }

    /**
     * excel下载功能
     */
    public function exportData() {
        define('Agency', 2);
        define('Saler', 1);
        $tmp_role = I('post.settle_type'); // 页面传来的role_type
        if ($tmp_role == 1) {
            $role = Agency;
        } else {
            $role = Saler;
        }
        // 获得传过来的str,去除最右的空格
        $filterStr = rtrim(trim(I('post.data')), ',');
        $this->WfxModel = D('Wfx'); // 实例化model
        if ($role == Agency) {
            // 获得经销商数据
            $data = $this->WfxModel->getAgencyExportData($this->nodeId, 
                $filterStr);
        } else {
            // 获得销售员数据
            $filterStr = str_replace("saler_", "", $filterStr);
            $data = $this->WfxModel->getSaleExportData($this->nodeId, 
                $filterStr);
        }
        self::exportExcel($data, "data.xls", $role);
    }
    /**
     * excel下载方法
     *
     * @param $data 传入的数据
     * @param $fileName excel文件名
     * @param $role 经销商or销售员
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    private function exportExcel($data, $fileName, $role) {
        import('@.Vendor.PHPExcel', '', '.php');
        // 实例化
        $objPHPExcel = new PHPExcel();
        // 设置文档属性
        $objPHPExcel->getProperties()
            ->setCreator("wangcaio2o")
            ->setLastModifiedBy("wfx")
            ->setTitle("wfx")
            ->setSubject("wfx")
            ->setDescription("wfx")
            ->setKeywords("wfx")
            ->setCategory("wfx");
        
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        // 获得array数据的key
        $col_arr = array_keys($data[0]);
        // 预定的标题数组
        if ($role == 2) {
            // 经销商标题数组
            $tmp_arr = array(
                'id' => 'id', 
                'name' => '经销商名称', 
                'parent_id' => '上级经销商', 
                'status' => '审核状态', 
                'level' => '所在层级', 
                'contact_name' => '负责人姓名', 
                'phone_no' => '负责人手机号码', 
                'default_manage_percent' => '管理提成', 
                'alipay_account' => '支付宝账号', 
                'bank_name' => '银行名称', 
                'bank_account' => '银行账号', 
                'default_sale_percent' => '默认销售提成', 
                'parent_path' => '下级经销商', 
                'add_time' => '创建时间', 
                'sale_down' => '下级销售员');
        } else {
            // 销售员标题数组
            $tmp_arr = array(
                'id' => 'id', 
                'name' => '销售员名称', 
                'parent_id' => '上级经销商', 
                'status' => '审核状态', 
                'phone_no' => '手机号码', 
                'custom_no' => '销售编号', 
                'add_from' => '来源', 
                'alipay_account' => '支付宝账号', 
                'bank_name' => '银行名称', 
                'bank_account' => '银行账号', 
                'default_sale_percent' => '默认销售提成', 
                'sex' => '性别', 
                'area' => '所在地', 
                'job' => '职业', 
                'add_time' => '创建时间', 
                'age' => '年龄', 
                'referee_id' => '推荐人', 
                'home_address' => '家庭住址', 
                'email' => '邮箱', 
                'channel_id' => '渠道', 
                'customer_number' => '绑定客户数');
        }
        
        $exc_arr = array(
            '1' => 'A', 
            '2' => 'B', 
            '3' => 'C', 
            '4' => 'D', 
            '5' => 'E', 
            '6' => 'F', 
            '7' => 'G', 
            '8' => 'H', 
            '9' => 'I', 
            '10' => 'J', 
            '11' => 'K', 
            '12' => 'L', 
            '13' => 'M', 
            '14' => 'N', 
            '15' => 'O', 
            '16' => 'P', 
            '17' => 'Q', 
            '18' => 'R', 
            '19' => 'S', 
            '20' => 'T', 
            '21' => 'U', 
            '22' => 'V', 
            '23' => 'W', 
            '24' => 'X', 
            '25' => 'Y', 
            '26' => 'Z', 
            '27' => 'AA', 
            '28' => 'AB', 
            '29' => 'AC', 
            '30' => 'AD', 
            '31' => 'AE', 
            '32' => 'AF', 
            '33' => 'AG', 
            '34' => 'AH', 
            '35' => 'AI', 
            '36' => 'AJ', 
            '37' => 'AK', 
            '38' => 'AL', 
            '39' => 'AM', 
            '40' => 'AN', 
            '41' => 'AO', 
            '42' => 'AP', 
            '43' => 'AQ', 
            '44' => 'AR', 
            '45' => 'AS', 
            '46' => 'AT', 
            '47' => 'AU', 
            '48' => 'AV', 
            '49' => 'AW', 
            '50' => 'AX', 
            '51' => 'AY', 
            '52' => 'AZ');
        // 获得数据库列名和excel列名的对应数组 并写入excel的表头
        foreach ($col_arr as $k => &$v) {
            $v = $tmp_arr[$v];
            $objSheet->setCellValueExplicit($exc_arr[$k + 1] . '2', $v, 
                PHPExcel_Cell_DataType::TYPE_STRING);
            $objSheet->getStyle($exc_arr[$k + 1] . '2')
                ->getFont()
                ->setBold(true); // 加粗
        }
        // 写入数据
        $data_count = count($data); // 数据行数
        foreach ($data as $dk => $dv) {
            $curr_row = $dk + 3; // 首行备注置空 数组从1开始 加上表头的1行 数据是从A3开始写入
                                 // 每行进行循环
            $dv = array_values($dv);
            foreach ($dv as $kk => $vv) {
                $objSheet->setCellValueExplicit($exc_arr[$kk + 1] . $curr_row, 
                    $vv, PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        
        // setCellValueExplicit 显式指定内容类型
        $objSheet->getDefaultColumnDimension()->setWidth(15); // 设置所有列默认宽度
        if ($role == 2) {
            $objSheet->setTitle('旺分销-经销商下载数据');
        } else {
            $objSheet->setTitle('旺分销-销售员下载数据');
        }
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        // 保存为文件
        // $filename = $this->file_path . $file;
        // $objWriter->save($filename);
        // return array('ret_code' => '0000',
        // 'ret_text' => '保存成功');
        // 浏览器弹框 自定义保存路径
        $filename = "$fileName";
        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit();
    }

    /**
     * download页面,二维码页面的显示处理
     */
    function download() {
        $wfx = D('Wfx');
        $res = $wfx->getBelongAgency($this->node_id, false);
        $res2 = $wfx->getBelongAgency($this->node_id, true);
        $levelArr = array(
            '1' => '一级', 
            '2' => '二级', 
            '3' => '三级', 
            '4' => '四级', 
            '5' => '五级');
        $agencyList = "顶级-00000000000-" . $this->nodeInfo['node_name'] . ' ';
        $salerList = "";
        foreach ($res as $k => $v) {
            
            $agencyList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                 mb_substr($v['name'], 0, 10, "UTF8") . ' ';
        }
        foreach ($res2 as $k => $v) {
            
            $salerList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                 mb_substr($v['name'], 0, 10, "UTF8") . ' ';
        }
        
        $this->assign('agencyList', trim($agencyList));
        $this->assign('salerList', trim($salerList));
        $this->display();
    }
    private function checkNodeInfo()
    {
        $ret = M('twfx_node_info')->where(['node_id'=>$this->nodeId])->find();
        if(empty($ret))
        {
            M('twfx_node_info')->add(['node_id'=>$this->nodeId]);
        }
    }
    /**===============================================
     * ===============================================
     * ===============================================
     * 美惠非标开始代码
    /**
     * download页面,二维码页面的显示处理
     */
    public function mhdownload() {
        $this->display('meihui/Fxgl_download');
    }
    public function mHDowngrade(){
        $level=I('level');
        $salerId=I('id');
        $salerInfo=$this->meiHuiModel->getSalerInfoById($this->node_id,$salerId);
        if(!$level || !$salerId || !$salerInfo){
            $this->error("缺少必要的参数！");
        }
        M()->startTrans();
        $res=$this->meiHuiModel->downgrade($level,$salerId,$this->node_id);
        if($res===false){
            $this->error("降级失败！");
        }
        $new_level=$salerInfo['level']+1;
        $dateGrade=array(
            'saler_id'=>$salerId,
             'trace_time'=>date('YmdHis'),
            'change_type'=>1,
            'old_value'=>$salerInfo['level'].":".$salerInfo['parent_id'],
            'new_value'=>$new_level.":".$salerInfo['parent_id'],
            'user_id'=>$this->user_id,
            'change_flag'=>1
        );
        $resStatus=$this->meiHuiModel->gradeChangeTrace($dateGrade);
        if($resStatus===false){
            $this->error("降级失败！");
        }
        M()->commit();
        $this->success("降级成功！");
    }
    public function mHupgrade(){
        $level=I('level');
        $salerId=I('id');
        if(!$level || !$salerId){
            $this->error("缺少必要的参数！");
        }
        M()->startTrans();
        $res=$this->meiHuiModel->upgrade($level,$salerId,$this->node_id,$this->user_id);
        if($res===false){
            $this->error("升级失败！");
        }
        M()->commit();
        $this->success("升级成功！");
    }
    public function freeMovement(){
        $salerId = I('id', null);
        $newParentId = I('newParentId', null);
        if (! $salerId || ! $newParentId) {
            $this->error("参数错误");
        }
        $salerInfo=$this->meiHuiModel->checkSalerId($this->node_id,$salerId);
        if($salerInfo===false){
            $this->error("当前会员不存在！");
        }
        $targetInfo=$this->meiHuiModel->getSalerInfoByPhone($this->node_id,$newParentId);
        if($targetInfo===false){
            $this->error("转移的机构或者会员不存在！");
        }
        M()->startTrans();
        $res=$this->meiHuiModel->mHTransfer($salerInfo, $targetInfo);
        if($res===false){
            M()->rollback();
            $this->error("转移失败！");
        }
        //添加平移记录
        /*添加升级记录*/
        $change_flag=0;
        $dateGrade=array(
                'saler_id'=>$salerId,
                'trace_time'=>date('YmdHis'),
                'change_type'=>2,
                'old_value'=>$salerInfo['level'].":".$salerInfo['parent_id'],
                'new_value'=>$salerInfo['level'].":".$targetInfo['id'],
                'user_id'=>$this->user_id,
                'change_flag'=>$change_flag
        );
        $resStatus=$this->meiHuiModel->gradeChangeTrace($dateGrade);
        if($resStatus===false){
            return false;
        }
        M()->commit();
        $this->success("转移成功！");
    }
    /**
     * excel下载方法
     *
     * @param $data 传入的数据
     * @param $fileName excel文件名
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    private function mhExportExcel($data, $fileName) {
        import('@.Vendor.PHPExcel', '', '.php');
        // 实例化
        $objPHPExcel = new PHPExcel();
        // 设置文档属性
        $objPHPExcel->getProperties()
                ->setCreator("wangcaio2o")
                ->setLastModifiedBy("wfx")
                ->setTitle("wfx")
                ->setSubject("wfx")
                ->setDescription("wfx")
                ->setKeywords("wfx")
                ->setCategory("wfx");
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        // 获得array数据的key
        $col_arr = array_keys($data[0]);
        // 预定的标题数组
            // 经销商标题数组
        $tmp_arr = array(
            'id' => 'id',
            'name' => '会员名称',
            'parent_id' => '上级会员',
            'status' => '审核状态',
            'level' => '所在层级',
            'contact_name' => '负责人姓名',
            'phone_no' => '负责人手机号码',
            'alipay_account' => '支付宝账号',
            'bank_name' => '银行名称',
            'bank_account' => '银行账号',
            'add_time' => '创建时间',
            'parent_path' => '下级会员');
        $exc_arr = array(
                '1' => 'A',
                '2' => 'B',
                '3' => 'C',
                '4' => 'D',
                '5' => 'E',
                '6' => 'F',
                '7' => 'G',
                '8' => 'H',
                '9' => 'I',
                '10' => 'J',
                '11' => 'K',
                '12' => 'L',
                '13' => 'M',
                '14' => 'N',
                '15' => 'O',
                '16' => 'P',
                '17' => 'Q',
                '18' => 'R',
                '19' => 'S',
                '20' => 'T',
                '21' => 'U',
                '22' => 'V',
                '23' => 'W',
                '24' => 'X',
                '25' => 'Y',
                '26' => 'Z',
                '27' => 'AA',
                '28' => 'AB',
                '29' => 'AC',
                '30' => 'AD',
                '31' => 'AE',
                '32' => 'AF',
                '33' => 'AG',
                '34' => 'AH',
                '35' => 'AI',
                '36' => 'AJ',
                '37' => 'AK',
                '38' => 'AL',
                '39' => 'AM',
                '40' => 'AN',
                '41' => 'AO',
                '42' => 'AP',
                '43' => 'AQ',
                '44' => 'AR',
                '45' => 'AS',
                '46' => 'AT',
                '47' => 'AU',
                '48' => 'AV',
                '49' => 'AW',
                '50' => 'AX',
                '51' => 'AY',
                '52' => 'AZ');
        // 获得数据库列名和excel列名的对应数组 并写入excel的表头
        foreach ($col_arr as $k => &$v) {
            $v = $tmp_arr[$v];
            $objSheet->setCellValueExplicit($exc_arr[$k + 1] . '2', $v,
                    PHPExcel_Cell_DataType::TYPE_STRING);
            $objSheet->getStyle($exc_arr[$k + 1] . '2')
                    ->getFont()
                    ->setBold(true); // 加粗
        }
        // 写入数据
        $data_count = count($data); // 数据行数
        foreach ($data as $dk => $dv) {
            $curr_row = $dk + 3; // 首行备注置空 数组从1开始 加上表头的1行 数据是从A3开始写入
            // 每行进行循环
            $dv = array_values($dv);
            foreach ($dv as $kk => $vv) {
                $objSheet->setCellValueExplicit($exc_arr[$kk + 1] . $curr_row,
                        $vv, PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        $objSheet->getDefaultColumnDimension()->setWidth(15); // 设置所有列默认宽度
        $objSheet->setTitle('旺分销-美惠会员下载数据');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $filename = "$fileName";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit();
    }
    public function getMhMember(){
        $data = $_REQUEST;
        $level=I('level');
        if($level==1){
            $name=I('agencyName');
        }elseif($level==2){
            if(I('diamondsName')){
                $name=I('diamondsName');
            }
            if(I('diamondsPhone')){
                $phone=I('diamondsPhone');
            }
            if(I('diamondsStatus')){
                $status=I('diamondsStatus');
            }
        }elseif($level==3){
            if(I('goldName')){
                $name=I('goldName');
            }
            if(I('goldNamePhone')){
                $phone=I('goldNamePhone');
            }
            if(I('goldStatus')){
                $status=I('goldStatus');
            }
        }elseif($level==4){
            if(I('silverName')){
                $name=I('silverName');
            }
            if(I('silverPhone')){
                $phone=I('silverPhone');
            }
            if(I('silverStatus')){
                $status=I('silverStatus');
            }
        }
        $mhMember=$this->meiHuiModel->getMhMemberByLevel($this->node_id,$level,$data,$name,$phone,$status);
        if($mhMember===false){
            $this->success(array('hasData'=>$mhMember['list'],'page'=>$mhMember['show'],'hasData'=>'0','type'=>$level));
        }
        $this->success(array('info'=>$mhMember['list'],'page'=>$mhMember['show'],'hasData'=>'1','type'=>$level));
    }
    public function mhExportData() {
        // 获得传过来的str,去除最右的空格
        $filterStr = rtrim(trim(I('post.data')), ',');
        $filterStr="id,level,".$filterStr;
        $this->WfxModel = D('Wfx'); // 实例化model
        // 获得经销商数据
        $data = $this->meiHuiModel->getAgencyExportData($this->nodeId, $filterStr);
        $this->mhExportExcel($data,"data.xls");
    }
    // 对销售员/经销商停用操作
    public function mhStop() {
        $id = I('get.id');
        $level= I('get.level');
        $curSalerInfo = M('twfx_saler')->where(array('id' => $id))->find();
        if($level==2){
            //如果是钻，进行算金和银
            $countList=$this->meiHuiModel->goldAndSilverCount($this->node_id,$curSalerInfo['id'],$level);
            $this->assign('goldCount',$countList['gold']);
            $this->assign('silverCount',$countList['silver']);
        }elseif($level==3){
            $countList=$this->meiHuiModel->goldAndSilverCount($this->node_id,$curSalerInfo['id'],$level);
            $this->assign('silverCount',$countList['silver']);
        }
        $levelArr = array(
                '1' => '门店',
                '2' => '钻石',
                '3' => '金牌',
                '4' => '银牌');
        if($level==2 || $level==3){
            //钻和金停用，查询是否有下级
            $res=$this->meiHuiModel->selectParentPathById($id,$this->node_id,$level);
            if($res!=false){
                $this->assign('downFlag','1');
                $mhList='';
                $res3 = D('MeiHui','Model')->getMemberByLevel($this->node_id,$level);
                foreach ($res3 as $k => $v) {
                    $mhList .= $levelArr[$v['level']] . '-' . $v['phone_no'] . '-' .
                            mb_substr($v['name'], 0, 10, "UTF8") . ' ';
                }
                $this->assign('mhList',$mhList);
            }
        }
        $this->assign('id', $id);
        $this->assign('levelName',$levelArr[$curSalerInfo['level']]);
        $this->assign('name', $curSalerInfo['name']);
        $this->assign('level', $level);
        $this->display('meihui/Fxgl_stop');
    }
    public function stopMhAjax() {
        $id = I('get.id');
        $level = I('get.level');
        if (empty($id) || empty($level)) {
            $this->error("非法提交");
        }
        $mHsaler = I('get.mHsaler');
        if($mHsaler && $mHsaler!='undefined'){
            $temp_level_arr = explode('-', $mHsaler);
            $salerMoveInfo=$this->meiHuiModel->getByPhone_no($this->node_id,$temp_level_arr[1]);
            if($salerMoveInfo===false){
                  $this->error("选择转移层级不存在！");
            }
            if($salerMoveInfo['level']<$level){
                $this->error("选择层级必须大于等于当前层级！");
            }
        }
        //金牌停用逻辑
        M()->startTrans();
        $res=$this->meiHuiModel->mHStop($level,$id,$this->node_id,$this->user_id,$salerMoveInfo);
        if($res===false){
            M()->rollback();
            $this->error("停用失败");
        }
        M()->commit();
        $this->success("停用成功！");
    }
    public function mhMove() {
        $id = I('id');
        $level=I("level");
        if (empty($id) || empty($level)) {
            exit("参数错误");
        }
        $levelArr = array(
                '1' => '门店',
                '2' => '钻石',
                '3' => '金牌');
        $salerList = "";
        $res=$this->meiHuiModel->getSalers($this->node_id,$level);
        foreach ($res as $k => $v) {
            if ($v['id'] != $id) {
                $salerList .= $levelArr[$v['level']] . '-' . $v['phone_no'] .
                        '-' . mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
        }
        $this->assign('id', $id);
        $this->assign('level', $level);
        $this->assign('salerList', trim($salerList));
        $this->display("meihui/Fxgl_mhMove");
    }
    public function mhMoveAjax() {
        $salerId = I('get.id', null);
        $getAgencyInfo = I('get.agency', null);
        if (! $salerId || ! $getAgencyInfo) {
            $this->error("参数错误");
        }
        $salerInfo=$this->meiHuiModel->checkSalerId($this->node_id,$salerId);
        if($salerInfo===false){
            $this->error("当前会员不存在！");
        }
        $getAgencyInfoArr = explode('-', $getAgencyInfo);
        $targetInfo=$this->meiHuiModel->getSalerInfoByPhone($this->node_id,$getAgencyInfoArr[1]);
        if($targetInfo===false){
            $this->error("转移的机构或者会员不存在！");
        }
        M()->startTrans();
        $res=$this->meiHuiModel->mHTransfer($salerInfo, $targetInfo);
        if($res===false){
            M()->rollback();
            $this->error("转移失败！");
        }
        //添加平移记录
        /*添加升级记录*/
        $change_flag=0;
        $dateGrade=array(
                'saler_id'=>$salerId,
                'trace_time'=>date('YmdHis'),
                'change_type'=>2,
                'old_value'=>$salerInfo['level'].":".$salerInfo['parent_id'],
                'new_value'=>$salerInfo['level'].":".$targetInfo['id'],
                'user_id'=>$this->user_id,
                'change_flag'=>$change_flag
        );
        $resStatus=$this->meiHuiModel->gradeChangeTrace($dateGrade);
        if($resStatus===false){
            return false;
        }
        M()->commit();
        $this->success("转移成功！");
    }
    public function mhCheckStatus() {
        $id = I('id', '');
        if ($this->isPost()) {
            $status = I('status');
            $data=array('status' => $status,
                    'audit_id' => $this->userInfo['user_id'],
                    'audit_user_name' => $this->userInfo['name'],
                    'audit_time' => date("YmdHis"));
            $where=array('id' => $id);
            M()->startTrans();
            $res=$this->meiHuiModel->checkStatus($where,$data);
            if (false ===$res) {
                M()->rollback();
                $this->error("审核失败!");
            } else {
                $userService = D('UserSess', 'Service');
                $userInfo = $userService->getUserInfo();
                $logData = array();
                $logData['log_index'] = $id;
                $logData['type'] = 5;
                $logData['user_id'] = $userInfo['user_id'];
                $logData['json_data'] = $status == 2 ? "未通过审核" : "审核通过";
                $logData['add_time'] = date('YmdHis');
                $res=$this->meiHuiModel->editLog($logData);
                if (false === $res) {
                    M()->rollback();
                    $this->error("审核失败");
                }
                M()->commit();
                $this->success("操作成功!");
            }
        }
        $this->assign('id', $id);
        $this->display('meihui/Fxgl_mhCheckStatus');
    }
    /**
     * ajax修改经销商和销售员
     */
    public function mhEditAjax() {
        // 需要修改的saler_id
        $id = I('id');
        $prevSalerInfo = M('twfx_saler')->getById($id);

        // 需要插入的数据
        $insertArr = array();
        if ($_GET['type'] == '1') {
            $name = I('name', null, "trim,htmlspecialchars");
            $contact_name = I('contact_name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            ! empty($name) or $this->error("门店名称不得为空");
            ! empty($contact_name) or $this->error("负责人姓名不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");

            $insertArr['name'] = $name;
            $insertArr['contact_name'] = $contact_name;
            $insertArr['phone_no'] = $phone_no;
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->node_id,
                                    'phone_no' => $phone_no,
                                    'id' => array(
                                            'NEQ',
                                            $id)))->select()) {
                $this->error("手机号码已存在");
            }
        }
        if ($_GET['type'] == '2') {
            $name = I('name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            $custom_no = I('custom_no', null, "trim,htmlspecialchars");
            $alipay_account = I('alipay_account', null, "trim,htmlspecialchars");
            $bankName = I('bank_name', null, "trim,htmlspecialchars");
            $bankAccount = I('bank_account', null, "trim,htmlspecialchars");
            ! empty($name) or $this->error("销售员名称不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");
            if (strlen($custom_no) > 10) {
                $this->error("销售员编号不得大于10位!");
            }

            // 判断银行账号与名称是否同时存在或同时不存在
            ((empty($bankAccount) && empty($bankName)) ||
                    (! empty($bankAccount) && ! empty($bankName))) or
            $this->error("银行名称与账号必须同时存在或不存在");
            if (! empty($bankName) && ! in_array($bankName,
                            C('defaultBankName'))) {
                $this->error("银行名称有误");
            }
            // 判断银行账号是否为16-19位
            if (! empty($bankAccount)) {
                (strlen($bankAccount) > 15 && strlen($bankAccount) < 20 &&
                        preg_match('/^\d+$/i', $bankAccount)) or
                $this->error("银行账号必须为数字，且为16-19位");
            }
            $insertArr['business_licence'] = I('business_licence');
            $insertArr['name'] = $name;
            $insertArr['phone_no'] = $phone_no;
            $insertArr['custom_no'] = $custom_no;
            $insertArr['alipay_account'] = $alipay_account;
            $insertArr['bank_name'] = $bankName;
            $insertArr['bank_account'] = $bankAccount;
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->node_id,
                                    'phone_no' => $phone_no,
                                    'id' => array(
                                            'NEQ',
                                            $id)))->select()) {
                $this->error("手机号码已存在");
            }
            // 判断销售员编号是否重复
            if (! empty($custom_no) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->node_id,
                                    'custom_no' => $custom_no,
                                    'role' => '1',
                                    'id' => array(
                                            'NEQ',
                                            $id)))->select()) {
                $this->error("销售员编号已存在");
            }
        }
        M()->startTrans();
        if (false === M('twfx_saler')->where(
                        array(
                                'id' => $id))
                        ->data($insertArr)
                        ->save()) {
            M()->rollback();
            $this->error("修改失败");
        } else {
            $log = self::writeLog($prevSalerInfo, $insertArr);
            if (! empty($log)) {
                $userService = D('UserSess', 'Service');
                $userInfo = $userService->getUserInfo();
                $logData = array();
                $logData['log_index'] = $id;
                $logData['type'] = 1;
                $logData['user_id'] = $userInfo['user_id'];
                $logData['json_data'] = json_encode($log);
                $logData['add_time'] = date('YmdHis');
                if (false === M('twfx_edit_log')->add($logData)) {
                    M()->rollback();
                    $this->error("修改失败");
                }
            }
            M()->commit();
            $this->success("修改成功");
        }
    }
    public function mHEdit() {
        $id = I("id");
        $level = I("level");
        if(empty($id) || empty($level)){
            $this->error("参数错误");
        }
        $list = M('twfx_saler')->getById($id);
        // 当前商户的账户类型
        if($level==1){
            $role=2;
        }else{
            $role=1;
        }
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->node_id, 'account_type');
        $this->assign('id', $id);
        $this->assign('role', $role);
        $this->assign('level', $level);
        $this->assign('list', $list);
        $this->assign('accountType', $accountType);
        $this->assign('defaultBankName', C('defaultBankName'));
        $this->display('meihui/Fxgl_mHEdit');
    }
    public function fbAddMember() {
        // 两者共有的部分
        $insertArr = array(
                'node_id' => $this->node_id,
                'add_time' => date('YmdHis'),
                'add_user_id' => $this->userInfo['user_id'],
                'add_user_name' => $this->userInfo['name']);
        // type为1，表示经销商ajax提交
        if ($_GET['type'] == '1') {
            $name = I('name', null, "trim,htmlspecialchars");
            $contact_name = I('contact_name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            if (! empty($phone_no) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->node_id))->getByPhone_no($phone_no)) {
                $this->error("手机号码已存在");
            }
            $insertArr['name'] = $name;
            $insertArr['contact_name'] = $contact_name;
            $insertArr['phone_no'] = $phone_no;
            $insertArr['parent_id'] = 0;
            $insertArr['level'] = 1;
            $insertArr['parent_path'] = '0,';
            $insertArr['role'] = '2';
        }
        // type为2表示钻石提交
        if ($_GET['type'] == '2' || $_GET['type'] == '3' || $_GET['type'] == '4') {
            $name = I('name', null, "trim,htmlspecialchars");
            $contact_name = I('contact_name', null, "trim,htmlspecialchars");
            $phone_no = I('phone_no', null, "trim,htmlspecialchars");
            $alipay_account = I('alipay_account', null, "trim,htmlspecialchars");
            $bankName = I('bank_name', null, "trim,htmlspecialchars");
            $bankAccount = I('bank_account', null, "trim,htmlspecialchars");
//            $default_manage_percent = I('default_manage_percent', null, "trim,htmlspecialchars");
//            $default_sale_percent = I('default_sale_percent', null, "trim,htmlspecialchars");
            $level_info = I('level_info', null, "trim,htmlspecialchars");
            ! empty($name) or $this->error("销售员名称不得为空");
            ! empty($phone_no) or $this->error("手机号码不得为空");
            strlen($phone_no) == 11 or $this->error("手机号码长度不正确");
            is_numeric($phone_no) or $this->error("手机号码必须是数字");
            ! empty($level_info) or $this->error("必须选择会员");
//            if (empty($default_sale_percent) && $default_sale_percent != '0') {
//                $this->error("默认销售提成不得为空");
//            } else {
//                if (! is_numeric($default_sale_percent) &&
//                        ! is_float($default_sale_percent)) {
//                    $this->error("默认销售提成必须是数字");
//                }
//            }
//            if (empty($default_manage_percent) && $default_manage_percent != '0') {
//                $this->error("默认管理提成不得为空");
//            } else {
//                if (! is_numeric($default_manage_percent) &&
//                        ! is_float($default_manage_percent)) {
//                    $this->error("默认管理提成必须是数字");
//                }
//            }
//            if ($default_manage_percent > 100 || $default_sale_percent > 100) {
//                $this->error("提成不得大于100%");
//            }
//            if ($default_manage_percent < 0 || $default_sale_percent < 0) {
//                $this->error("提成不得小于0");
//            }
            $business_licence = I('business_licence');
//            if ($default_sale_percent > 100) {
//                $this->error("提成不得大于100%");
//            }
//            if ($default_sale_percent < 0) {
//                $this->error("提成不得小于0");
//            }
            // 判断银行账号与名称是否同时存在或同时不存在
            ((empty($bankAccount) && empty($bankName)) ||
                    (! empty($bankAccount) && ! empty($bankName))) or
            $this->error("银行名称与账号必须同时存在或不存在");
            if (! empty($bankName) && ! in_array($bankName,
                            C('defaultBankName'))) {
                $this->error("银行名称有误");
            }
            // 判断银行账号是否为16-19位
            if (! empty($bankAccount)) {
                (strlen($bankAccount) > 15 && strlen($bankAccount) < 20 &&
                        preg_match('/^\d+$/i', $bankAccount)) or
                $this->error("银行账号必须为数字，且为16-19位");
            }
            $insertArr['name'] = $name;
            $insertArr['phone_no'] = $phone_no;
            $insertArr['contact_name'] = $contact_name;
            $insertArr['alipay_account'] = $alipay_account;
            $insertArr['bank_name'] = $bankName;
            $insertArr['bank_account'] = $bankAccount;
//            $insertArr['default_sale_percent'] = $default_sale_percent;
//            $insertArr['default_manage_percent'] = $default_manage_percent;
            $insertArr['business_licence'] = $business_licence;
            if (! empty($level_info)) {
                $temp_level_arr = explode('-', $level_info);
                $parentInfo = M('twfx_saler')->where(array('node_id' => $this->nodeId))->getByPhone_no($temp_level_arr[1]);
                $insertArr['parent_id'] = $parentInfo['id'];
                $insertArr['level'] = $parentInfo['level'];
                $insertArr['parent_path'] = $parentInfo['parent_path'] . $parentInfo['id'] . ',';
            }
            if($_GET['type'] == '2'){
                $insertArr['level'] = 2;
            }
            if($_GET['type'] == '3'){
                $insertArr['level'] = 3;
            }
            if($_GET['type'] == '4'){
                $insertArr['level'] = 4;
            }
            $insertArr['role'] = '2';
            // 判断手机号码是否重复
            if (! empty($phone_no) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->node_id))->getByPhone_no($phone_no)) {
                $this->error("手机号码已存在");
            }
        }
        M()->startTrans();
        if ($id = M('twfx_saler')->data($insertArr)->add()) {
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $id;
            $logData['type'] = 2;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = "";
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                M()->rollback();
                $this->error("新增失败");
            }
            M()->commit();
            $this->success("新增成功");
        } else {
            M()->rollback();
            $this->error("新增失败");
        }
    }
    public function getGradeChange(){
        $map=array();
        $gradeStartTime=I('gradeStartTime');
        if($gradeStartTime){
            $map['a.trace_time'][]=array('EGT',$gradeStartTime.'000000');
        }
        $gradeEndTime=I('gradeEndTime');
        if($gradeEndTime){
            $map['a.trace_time'][]=array('ELT',$gradeEndTime.'235959');
        }
        if(I('gradeName')){
            $map['c.name']=array('like',"%".I('gradeName')."%");
        }
        if(I('gradePhone')){
            $map['c.phone_no']=array('like',"%".I('gradePhone')."%");
        }
        $gradeStatus=I('gradeStatus');
        if($gradeStatus){
            if($gradeStatus==3){
                $gradeStatus=0;
            }
            $map['a.change_flag']=$gradeStatus;
        }
        $data = $_REQUEST;
        $gradeChangeInfo=$this->meiHuiModel->gradeChange($map,$data);
        if($gradeChangeInfo===false){
            $this->success(array('hasData'=>$gradeChangeInfo['list'],'page'=>$gradeChangeInfo['show'],'hasData'=>'0','type'=>5));
        }
        $this->success(array('info'=>$gradeChangeInfo['list'],'page'=>$gradeChangeInfo['show'],'hasData'=>'1','type'=>5));
    }
    public function getLeftTree(){
        // 获取左侧树状结构的信息
        $map=array(
                'role' => '2',
                'node_id' => $this->node_id,
                'status' => '3',
                'level' => array('ELT', 4)
        );
        $salerLevelInfo = M('twfx_saler')->field('id,parent_id,level,name,parent_path')
                ->where($map)
                ->order('level,add_time')
                ->select();
        $arr=array();
        foreach($salerLevelInfo as $key=>$val){
            $temp=array();
            if($val['level']==1){
                $temp['id']=$val['id'];
                $temp['name']=$val['name'];
                $temp['level']=$val['level'];
                $temp['icon']=$val['icon1'];
                $temp['children']='';
                $arr[$val['id']]=$temp;
            }elseif($val['level']==2){
                if($arr[$val['parent_id']]){
                    $temp['id']=$val['id'];
                    $temp['name']=$val['name'];
                    $temp['level']=$val['level'];
                    $temp['icon']="icon2";
                    $temp['children']='';
                    $arr[$val['parent_id']]['children'][]=$temp;
                }
            }elseif($val['level']==3){
                //金挂门店
                if($arr[$val['parent_id']]){
                    $temp['id']=$val['id'];
                    $temp['name']=$val['name'];
                    $temp['level']=$val['level'];
                    $temp['icon']="icon3";
                    $temp['children']='';
                    $arr[$val['parent_id']]['children'][]=$temp;
                }else{
                    //挂钻
                    $pathArr=explode(',',$val['parent_path']);
                    if($pathArr[1] && $pathArr[2]){
                        $temp['id']=$val['id'];
                        $temp['name']=$val['name'];
                        $temp['level']=$val['level'];
                        $temp['icon']="icon3";
                        $temp['children']='';
                        if($arr[$pathArr[1]]['children']){
                            foreach($arr[$pathArr[1]]['children'] as $zKey=>$zValue){
                                if($zValue['id']==$pathArr[2]){
                                    $arr[$pathArr[1]]['children'][$zKey]['children'][]=$temp;
                                }
                            }
                        }
                    }
                }
            }elseif($val['level']==4){
                //银挂门店
                if($arr[$val['parent_id']]){
                    $temp['id']=$val['id'];
                    $temp['name']=$val['name'];
                    $temp['children']='';
                    $temp['level']=$val['level'];
                    $temp['icon']="icon4";
                    $arr[$val['parent_id']]['children'][]=$temp;
                }else{
                    //挂金
                    $pathArr=explode(',',$val['parent_path']);
                    if($pathArr[1] && $pathArr[2] && $pathArr[3]){
                        $temp['id']=$val['id'];
                        $temp['name']=$val['name'];
                        $temp['level']=$val['level'];
                        $temp['icon']="icon4";
                        if($arr[$pathArr[1]]['children']){
                            foreach($arr[$pathArr[1]]['children'] as $zKey=>$zValue){
                                if($pathArr[2]==$zValue['id']){
                                    foreach($arr[$pathArr[1]]['children'][$zKey]['children'] as $gKey=>$gValue){
                                        if($pathArr[3]==$gValue['id']){
                                            $arr[$pathArr[1]]['children'][$zKey]['children'][$gKey]['children'][]=$temp;
                                        }
                                    }
                                }
                            }
                        }
                    }else if($pathArr[1] && $pathArr[2]) {
                        //挂钻
                        $temp['id']=$val['id'];
                        $temp['name']=$val['name'];
                        $temp['level']=$val['level'];
                        $temp['icon']="icon4";
                        if($arr[$pathArr[1]]['children']){
                            foreach($arr[$pathArr[1]]['children'] as $zKey=>$zValue){
                                if($pathArr[2]==$zValue['id']){
                                    $arr[$pathArr[1]]['children'][$zKey]['children'][]=$temp;
                                }
                            }
                        }
                    }
                }
            }
        }
//        return json_encode($arr,JSON_UNESCAPED_UNICODE);
        $returnArr=array();
        $returnArr['name']="商城";
        $returnArr['icon']="icon0";
        foreach($arr as $key=>$val){
            $returnArr['children'][]=$val;
        };
        if(I('getType')==1){
            $this->success($returnArr);
        }else{
            return json_encode($returnArr,JSON_UNESCAPED_UNICODE);
        }
    }
    public function getSalerInfoById($salerLevelInfo){
        $salInfo=array();
        foreach($salerLevelInfo as $key=>$val){
            if($val['level']!=1){
                if($val['level']==2){
                    $temp['id']=$val['id'];
                    $temp['name']=$val['name'];
                    $temp['children']='';
                    $temp['icon']="__PUBLIC__/Image/FbMeihui/treeIcon_01.png";
                }elseif($val['level']==3){
                    $temp['id']=$val['id'];
                    $temp['name']=$val['name'];
                    $temp['children']='';
                    $temp['icon']="icon3";
                }elseif($val['level']==4){
                    $temp['id']=$val['id'];
                    $temp['name']=$val['name'];
                    $temp['icon']="__PUBLIC__/Image/FbMeihui/treeIcon_03.png";
                }
                $salInfo[$val['id']]=$temp;
            }
        }
        return $salInfo;
    }
    private function mHstatisticsOrder($id,$nodeId) {
        // 引入分页类
        // 引入分页类
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = M()->field(
                'count(g.order_id) AS order_amt,SUM(g.amount) AS amount_amt,DATE_FORMAT(CONCAT(g.add_time,"00000000"),"%Y年%m月") AS add_time')
                ->table('(SELECT
    b.order_id,
    c.id,
    SUM(b.order_amt) AS amount,
    SUBSTR(b.add_time, "1", "6") AS add_time
  FROM
      ttg_order_info b
    LEFT JOIN twfx_saler c
      ON c.`phone_no` = b.`order_phone`
  WHERE c.id = ' . $id . '
    AND c.`phone_no` = b.`order_phone`
     and b.node_id=' . $nodeId . '
     and IFNULL(b.saler_id, 0) = 0
     and b.pay_status = 2
     and b.order_type = 2
  GROUP BY b.order_id) g')
                ->group('g.add_time')
                ->order('g.add_time desc')
                ->select();
        $CPage = new Page(count($mapcount), 6);
        $CPage->setConfig('theme',
                '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $CPage->setConfig('prev', '?');
        $CPage->setConfig('next', '?');
        $traceArr = M()->field(
                'count(g.order_id) AS order_amt,SUM(g.amount) AS amount_amt,DATE_FORMAT(CONCAT(g.add_time,"00000000"),"%Y年%m月") AS add_time')
                ->table('(SELECT
    b.order_id,
    c.id,
    SUM(b.order_amt) AS amount,
    SUBSTR(b.add_time, "1", "6") AS add_time
  FROM
      ttg_order_info b
    LEFT JOIN twfx_saler c
      ON c.`phone_no` = b.`order_phone`
  WHERE c.id = ' . $id . '
    AND c.`phone_no` = b.`order_phone`
     and b.node_id=' . $nodeId . '
     and IFNULL(b.saler_id, 0) = 0
     and b.pay_status = 2
     and b.order_type = 2
  GROUP BY b.order_id) g')
                ->group('g.add_time')
                ->order('g.add_time desc')
                ->limit($CPage->firstRow . ',' . $CPage->listRows)
                ->select();
        $page = $CPage->show();
        $this->assign('traceArrMh', $traceArr);
        $this->assign('pageMh', $page);
    }
    private function mHstatisticsOrderBySalser($id,$nodeId) {
        // 引入分页类
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = M()->field(
                'count(g.order_id) AS order_amt,SUM(g.amount) AS amount_amt,DATE_FORMAT(CONCAT(g.add_time,"00000000"),"%Y年%m月") AS add_time')
                ->table('(SELECT
    b.order_id,
    c.id,
    SUM(b.order_amt) AS amount,
    SUBSTR(b.add_time, "1", "6") AS add_time
  FROM
      ttg_order_info b
    LEFT JOIN twfx_saler c
      ON c.id = b.saler_id
  WHERE b.saler_id = ' . $id . '
    and b.node_id=' . $nodeId . '
    and b.pay_status = 2
    and b.order_type = 2
  GROUP BY b.order_id) g')
                ->group('g.add_time')
                ->order('g.add_time desc')
                ->select();
        $CPage = new Page(count($mapcount), 6);
        $CPage->setConfig('theme',
                '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $CPage->setConfig('prev', '?');
        $CPage->setConfig('next', '?');
        $traceArr = M()->field(
                'count(g.order_id) AS order_amt,SUM(g.amount) AS amount_amt,DATE_FORMAT(CONCAT(g.add_time,"00000000"),"%Y年%m月") AS add_time')
                ->table('(SELECT
    b.order_id,
    c.id,
    SUM(b.order_amt) AS amount,
    SUBSTR(b.add_time, "1", "6") AS add_time
  FROM
      ttg_order_info b
    LEFT JOIN twfx_saler c
      ON c.id = b.saler_id
  WHERE b.saler_id = ' . $id . '
  and b.node_id=' . $nodeId . '
  and b.pay_status = 2
  and b.order_type = 2
  GROUP BY b.order_id) g')
                ->group('g.add_time')
                ->order('g.add_time desc')
                ->limit($CPage->firstRow . ',' . $CPage->listRows)
                ->select();
        $page = $CPage->show();
        $this->assign('traceArrMhBy', $traceArr);
        $this->assign('pageMhBy', $page);
    }
    /*
     * ===============================================
     * ===============================================
     * ===============================================
     * 美惠非标结束
     */
}