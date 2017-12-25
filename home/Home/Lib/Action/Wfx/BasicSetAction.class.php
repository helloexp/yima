<?php

class BasicSetAction extends BaseAction {
    // 旺分销通知模板
    private $templateType = 1;
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
        $node_info = M('twfx_node_info a')
                    ->join('twfx_mh_config b on b.twfx_config_id=a.id')
                    ->where(array('a.node_id' => $this->node_id))
                    ->select();
        // 获取模板状态
        $templatemsgStatus = M('twx_templatemsg_config')->field('status')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'template_type' => $this->templateType))
            ->find();
        
        if (isset($templatemsgStatus['status'])) {
            $node_info[0]['templateStatus'] = $templatemsgStatus['status'];
        } else {
            $node_info[0]['templateStatus'] = '1';
        }
        
        $this->assign('node_info', $node_info[0]);
        if(C('meihui.node_id')== $this->node_id){
            $this->display('meihui/BasicSet_index');
        }else{
            $this->display();
        }
    }

    public function modify() {
        if ($this->isPost()) {
            $charge_type = I('charge_type');
            $settle_type = I('settle_type');
            $charge_notice_flag = I('charge_notice_flag');
            $customer_bind_flag = I('customer_bind_flag');
            $show_saler_name = I('show_saler_name');
            $accountType = I('account_type');
            $lowestGetMoney = I('lowest_get_money');
            $sendTemplateMsg = I('templateStatus');
            $data['node_id'] = $this->node_id;
            $data['charge_type'] = $charge_type;
            if ($charge_type != '1') {
                $this->error('非法操作！', null);
            }
            if (! empty($settle_type)) {
                $data['settle_type'] = $settle_type;
            }
            if ($settle_type == '2' && empty($lowestGetMoney)) {
                $this->error('最低提领金额不得为空');
            } elseif ($settle_type == '2' && ! empty($lowestGetMoney)) {
                $data['lowest_get_money'] = $lowestGetMoney;
            }
            if (($settle_type == '1' || $settle_type == '3') && M(
                'twfx_node_info')->getFieldByNode_id($this->node_id, 
                'settle_type') == '2') {
                $this->error('选择提领后，不可再次修改结算周期', null);
            }
            $data['charge_notice_flag'] = $charge_notice_flag;
            $data['customer_bind_flag'] = $customer_bind_flag;
            $data['show_saler_name'] = $show_saler_name;
            $data['account_type'] = $accountType;
            ! empty($charge_type) or $this->error("非法参数");
            ! empty($charge_notice_flag) or $this->error("非法参数");
            ! empty($customer_bind_flag) or $this->error("非法参数");
            ! empty($show_saler_name) or $this->error("非法参数");
            ! empty($accountType) or $this->error("非法参数");
            $ishave = M('twfx_node_info')->where(array('node_id' => $this->node_id))->select();
            M()->startTrans();
            if (empty($ishave)) {
                $twfxId=M('twfx_node_info')->data($data)->add();
                if ($twfxId===false) {
                    M()->rollback();
                    $this->error("修改失败");
                }
                //美惠非标
                if($this->node_id==C('meihui.node_id')){
                    $mhRes=M("twfx_mh_config")->add(array('twfx_config_id'=>$twfxId,'jl_type'=>'1','jx_notice_flag'=>I('jx_notice_flag')));
                    if($mhRes===false){
                        M()->rollback();
                        $this->error("修改失败");
                    }
                }
            } else {
                if (false === M('twfx_node_info')->where(array('node_id' => $this->node_id))->save($data)) {
                    M()->rollback();
                    $this->error("修改失败");
                }
                if($this->node_id==C('meihui.node_id')){
                    $mhHas=M("twfx_mh_config")->where(array('twfx_config_id'=>$ishave[0]['id']))->find();
                    if($mhHas){
                        $mhResSave=M("twfx_mh_config")->where(array('twfx_config_id'=>$ishave[0]['id']))->save(array('jl_type'=>'1','jx_notice_flag'=>I('jx_notice_flag')));
                    }else{
                        $mhResSave=M("twfx_mh_config")->add(array('twfx_config_id'=>$ishave[0]['id'],'jl_type'=>'1','jx_notice_flag'=>I('jx_notice_flag')));
                    }
                    if($mhResSave===false){
                        M()->rollback();
                        $this->error("修改失败");
                    }
                }
            }
            // 设置 发送微信模板消息
            $upTemplateStatus = D('TweixinInfo');
            $isOk = $upTemplateStatus->templateStatus($this->node_id, 
                $this->templateType, $sendTemplateMsg);
            
            if (! $isOk && $isOk != 0) {
                $this->error("非法参数");
            }
            M()->commit();
            $this->success("保存成功!");
            // redirect(U('Wfx/BasicSet/index'));
        } else {
            $this->error("非法提交");
        }
    }
}