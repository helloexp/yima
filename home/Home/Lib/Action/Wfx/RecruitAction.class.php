<?php

class RecruitAction extends BaseAction {

    public $node_id;

    public function _initialize() {
        parent::_initialize();
    }

    public function beforeCheckAuth() {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = "*";
        } elseif (! $this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        }
    }

    public function index() {
        $this->error("禁止访问");
        $this->display();
    }

    /**
     * [recruitSaler 招募销售员]
     *
     * @return [type] [列出所有旺消息]
     */
    public function recruitSaler() {
        // 查询旺分销-招募活动
        $marketingInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->nodeId, 
                'batch_type' => 3001))->find();
        // ajax提交
        if ($this->isPost()) {
            $data['node_id'] = $this->nodeId;
            $data['batch_type'] = 3001; // 表示招募活动的活动类型
            $data['name'] = "旺分销-招募";
            $data['bg_pic'] = I("post.set_bg");
            $data['node_name'] = I("post.node_name");
            $data['log_img'] = I("post.node_logo");
            $data['config_data'] = implode(',', I("post.active"));
            $data['wap_info'] = I("post.wap_info", "", "");
            $data['status'] = I("post.is_recruit");
            $data['add_time'] = date('YmdHis');
            // 判断该活动是否不存在
            if (empty($marketingInfo)) {
                if (M('tmarketing_info')->add($data)) {
                    $this->success("保存成功");
                } else {
                    $this->error("保存失败");
                }
            } else {
                unset($data['add_time']);
                if (false === M('tmarketing_info')->where(
                    array(
                        'id' => $marketingInfo['id']))->save($data)) {
                    $this->error("保存失败");
                } else {
                    $this->success("保存成功");
                }
            }
        }
        if (! empty($marketingInfo)) {
            $marketingInfo['config_data_arr'] = explode(',', 
                $marketingInfo['config_data']);
        }
        $nonTrans = M('twfx_saler')->where(
            array(
                'node_id' => $this->nodeId, 
                'status' => 5, 
                'add_from' => 3))->count();
        $this->assign('marketingInfo', $marketingInfo);
        $this->assign('node_name', $this->nodeInfo['node_name']);
        $this->assign('nonTrans', $nonTrans);
        $this->assign('node_logo', $this->nodeInfo['head_photo']);
        $this->display();
    }

    /**
     * [translateSaler 转化销售员的界面]
     *
     * @return [type] [无]
     */
    public function translateSaler() {
        $map['a.node_id'] = $this->nodeId;
        $map['a.add_from'] = 3;
        $salerName = I('saler_name');
        $status = I('status');
        empty($salerName) or $map['a.name'] = array(
            'like', 
            '%' . $salerName . '%');
        empty($status) or $map['a.status'] = $status;
        // 选择经销商
        $wfx = D('Wfx');
        if($this->node_id==C('meihui.node_id')){
            $this->assign('meihuiFlag',1);
            $levelArr = array(
                    '1' => '门店',
                    '2' => '钻石',
                    '3' => '金牌',
                    '4' => '银牌');
        }else{
            $levelArr = array(
                    '1' => '一级',
                    '2' => '二级',
                    '3' => '三级',
                    '4' => '四级',
                    '5' => '五级');
        }
        $res = $wfx->getBelongAgency($this->node_id, true);
        $agencyList = " ";
        foreach ($res as $k => $v) {
            if ($v['id'] != $id) {
                $agencyList .= $levelArr[$v['level']] . '-' . $v['phone_no'] .
                     '-' . mb_substr($v['name'], 0, 10, "UTF8") . ' ';
            }
        }
        // 未转化销售员的信息
        $nonTranslateInfo = M()->table("twfx_saler a")->field(
            "a.*,b.name AS referee_name,CONCAT(c.province,'/',c.city,'/',c.town )AS addressInfo")
            ->join('twfx_saler b ON a.referee_id=b.id')
            ->join("tcity_code c ON c.path=a.area")
            ->where($map)
            ->order("a.apply_time desc")
            ->select();
        // 导入分页类
        import('ORG.Util.Page');
        $Page = new Page(count($nonTranslateInfo), 10);
        $list = array_slice($nonTranslateInfo, $Page->firstRow, $Page->listRows);
        $show = $Page->show();
        $nonTrans = M('twfx_saler')->where(
            array(
                'node_id' => $this->nodeId, 
                'status' => 5, 
                'add_from' => 3))->count();
        $this->assign('list', $list);
        $this->assign('nonTrans', $nonTrans);
        $this->assign('saler_name', $salerName);
        $this->assign('status', $status);
        $this->assign('agencyList', $agencyList);
        $this->assign('page', $show);
        $this->display();
    }

    public function getAgencyPost() {
        if($this->node_id==C('meihui.node_id')){
            $levelArr = array(
                    '1' => '门店',
                    '2' => '钻石',
                    '3' => '金牌',
                    '4' => '银牌');
        }else{
            $levelArr = array(
                    '1' => '一级',
                    '2' => '二级',
                    '3' => '三级',
                    '4' => '四级',
                    '5' => '五级');
        }
        $salerId = I('get.saler_id');
        $refereeId = M('twfx_saler')->getFieldById($salerId, 'referee_id');
        if (! empty($refereeId)) {
            $refereeInfo = M('twfx_saler')->getById($refereeId);
            $agency = $levelArr[$refereeInfo['level']] . '-' .
                 $refereeInfo['phone_no'] . '-' .
                 mb_substr($refereeInfo['name'], 0, 10, "UTF8");
            $this->success($agency);
        } else {
            $this->success("");
        }
    }

    /**
     * [viewSaler 销售员详情]
     *
     * @return [type] [description]
     */
    public function viewSaler() {
        $map['a.node_id'] = $this->nodeId;
        $map['a.add_from'] = 3;
        $map['a.id'] = I('post.id');
        $nonTranslateInfo = M()->table("twfx_saler a")->field(
            "a.*,b.name AS referee_name,d.true_name,CONCAT(c.province,'/',c.city,'/',c.town )AS addressInfo")
            ->join('twfx_saler b ON a.referee_id=b.id')
            ->join('tuser_info d ON a.add_user_id=d.user_id')
            ->join("tcity_code c ON c.path=a.area")
            ->where($map)
            ->order("a.add_time desc")
            ->find();
        $nonTranslateInfo['apply_time'] = date('Y-m-d', 
            strtotime($nonTranslateInfo['apply_time']));
        if (! empty($nonTranslateInfo['add_time'])) {
            $nonTranslateInfo['add_time'] = date('Y-m-d', 
                strtotime($nonTranslateInfo['add_time']));
        } else {
            $nonTranslateInfo['add_time'] = "";
        }
        $this->success($nonTranslateInfo);
    }

    /**
     * [modifySaler 转化销售员]
     *
     * @return [type] [description]
     */
    public function modifySaler() {
        $salerId = I('trsalerid');
        $agency = I('agency');
        $customNo = I('custom_no');
        $salerPercent = I('saler_percent');
        $isSendMsg = I('isSendMsg');
        // 判断销售员编号是否正确
        if (! empty($customNo) && (strlen($customNo) > 10)) {
            $this->error("销售员编号不得大于10位！");
        }
        if($this->node_id==C('meihui.node_id')){
            if (! empty($customNo) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->nodeId,
                                    'role' => '2'))->getFieldByCustom_no($customNo, 'id')) {
                $this->error("销售员编号已存在！");
            }
        }else{
            if (! empty($customNo) && M('twfx_saler')->where(
                            array(
                                    'node_id' => $this->nodeId,
                                    'role' => '1'))->getFieldByCustom_no($customNo, 'id')) {
                $this->error("销售员编号已存在！");
            }
            // 判断提成是否正确
            ! empty($salerPercent) or $this->error("提成不得为空");
            is_numeric($salerPercent) or $this->error("提成必须是数字");
            ($salerPercent >= 0 && $salerPercent < 100) or
            $this->error("提成必须在0-100之间");
        }
        // 判断经销商选择是否正确
        $agencyTmpArr = explode('-', $agency);
        $agencyPhone = $agencyTmpArr[1];
        $agencyName = $agencyTmpArr[2];
        if (! $agencyInfo = M('twfx_saler')->where(
            array(
                'phone_no' => $agencyPhone, 
                'node_id' => $this->nodeId, 
                'name' => array(
                    'like', 
                    '%' . $agencyName . '%')))->find()) {
            $this->error("经销商选择有误");
        }
        // 判断销售员是否存在
        $salerInfo = M('twfx_saler')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $salerId))->find();
        if (empty($salerInfo)) {
            $this->error("待转化的销售员不存在");
        }
        $user_name = M('tuser_info')->getFieldByUser_id(
            $this->userInfo['user_id'], 'true_name');
        $data['parent_id'] = $agencyInfo['id'];
        $data['parent_path'] = $agencyInfo['parent_path'] . $agencyInfo['id'] .
             ',';
        $data['default_sale_percent'] = $salerPercent;
        $data['role'] = 1;
        $data['level'] = $agencyInfo['level'];
        if($this->node_id==C('meihui.node_id')){
            $data['role'] = 2;
            $data['level'] =4;
        }
        $data['custom_no'] = $customNo;
        $data['add_time'] = date('YmdHis');
        $data['audit_time'] = date('YmdHis');
        $data['add_user_id'] = $this->userInfo['user_id'];
        $data['add_user_name'] = $user_name;
        $data['audit_user_id'] = $this->userInfo['user_id'];
        $data['audit_user_name'] = $user_name;
        $data['status'] = '3';
        if (false === M('twfx_saler')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $salerId))->save($data)) {
            $this->error("转化销售员失败");
        } else {
            if ($isSendMsg == 1) {
                // 调用别的模块的发短信接口
                $batchChannelId = M('tbatch_channel')->where(
                    array(
                        'node_id' => $this->nodeId, 
                        'batch_type' => 29))->getField('id');
                $cururl = U('Label/O2OLogin/index', 'id=' . $batchChannelId, 
                    true, false, true);
                $jsonData = file_get_contents(
                    'https://api.weibo.com/2/short_url/shorten.json?source=1362404091&url_long=' .
                         urlencode($cururl));
                $shortUrl = json_decode($jsonData, true);
                $text = $salerInfo['name'] . "，恭喜您通过审核正式成为" .
                     $this->nodeInfo['node_name'] . "的销售员。您的上级经销商是" .
                     $agencyInfo['name'] . "。访问" .
                     $shortUrl['urls'][0]['url_short'] . " 即刻获取专属推广链接赚提成吧！";
                try {
                    D("Wfx")->sendMsgInfo($salerInfo['phone_no'], $text);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            // 存入转化日志
            $userService = D('UserSess', 'Service');
            $userInfo = $userService->getUserInfo();
            $logData = array();
            $logData['log_index'] = $salerId;
            $logData['type'] = 7;
            $logData['user_id'] = $userInfo['user_id'];
            $logData['json_data'] = "招募销售员转化成功";
            $logData['add_time'] = date('YmdHis');
            if (false === M('twfx_edit_log')->add($logData)) {
                $this->error("转化失败");
            }
            try {
                // 统计转化量
                $refereeId = $salerInfo['referee_id'];
                if (empty($refereeId)) {
                    $refereeId = 0;
                }
                D('Wfx')->setRecruitDayStat($this->nodeId, $refereeId,
                    array(
                        'trans_count' => 1));
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success("转化成功");
        }
    }

    /**
     * [getUrlCode 获取二维码]
     *
     * @return [type] [传回连接]
     */
    public function getUrlCode() {
        // 查询旺分销-招募活动
        $marketingInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->nodeId, 
                'batch_type' => 3001))->find();
        // 预览二维码
        $urlCode = D('PreViewChannel')->getPreviewChannelCode($this->nodeId, 
            $marketingInfo['id'], 3001);
        $this->ajaxReturn($urlCode);
    }
}

