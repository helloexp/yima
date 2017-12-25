<?php

class TicketHelperAction extends BaseAction {

    public $_authAccessMap = "*";

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * [validate 验证界面展示]
     *
     * @return [type] [description]
     */
    public function validate() {
        $posId = D('Stores')->getFvPosId($this->nodeId, true);
        if (! $posId) {
            $storeInfo = D('Stores')->getFvStore($this->nodeId, true);
            if ($storeInfo) {
                if ((time() - strtotime($storeInfo['add_time'])) > 10 * 60) {
                    $this->assign('isAppling', 3); // 申请失败
                } else {
                    $this->assign('isAppling', 2); // 申请中
                }
            } else {
                $this->assign('isAppling', 1); // 未申请
            }
            $this->display('hasNoPos');
            exit();
        }
        $this->assign('isAppling', 1); // 未申请
        $this->display();
    }

    /**
     * [applyPos 申请开通卡券验证助手]
     *
     * @return [type] [NULL]
     */
    public function applyPos() {
        $posId = D('Stores')->getFvPosId($this->nodeId);
        if (! $posId) {
            $this->error("卡券验证助手申请失败");
        } else {
            $this->success("您已申请卡券验证助手");
        }
    }

    /**
     * [checkCode post提交核验辅助码]
     *
     * @return [type] [description]
     */
    public function checkCode() {
        $assistCode = I('post.assist_con');
        $txAmt = I('post.tx_amt', 0);
        if (empty($assistCode)) {
            $this->error("辅助码不得为空", "", array(
                'err' => 0));
        }
        if (strlen($assistCode) != 16) {
            $this->error("辅助码必须是16位！", "", 
                array(
                    'err' => 0));
        }
        if ($txAmt && ! is_numeric($txAmt)) {
            $this->error("金额格式，请重新输入");
        }
        
        $mPosVerify = D('PosVerify', 'Service');
        // 检查是否有未冲正的过往订单
        $PosReversalModel = M('tpos_reversal');
        $aReversal = $PosReversalModel->where(
            array(
                'node_id' => $this->nodeId, 
                'status' => '1'))->find();
        if (! empty($aReversal)) {
            $szRetInfo = $mPosVerify->doPosReversal($aReversal['pos_id'], 
                $aReversal['res_seq'], $aReversal['assist_code']);
            if ($szRetInfo['business_trans']['result']['id'] != '0000') {
                $this->error("您还有未冲正的过往记录，请联系客服处理！", "", 
                    array(
                        'err' => 0));
            } else {
                $PosReversalModel->where(
                    array(
                        'id' => $aReversal['id']))->delete();
            }
        }
        
        // 获取posid，若无，则创建
        try {
            $posId = D('Stores')->getFvPosId($this->nodeId, true);
        } catch (Exception $e) {
            $this->error($e->getMessage(), "", 
                array(
                    'err' => 0));
        }
        if (! $posId) {
            $this->error("pos信息不存在,验证失败", "", 
                array(
                    'err' => 0));
        }
        // 初始化流水号
        $mPosVerify->setopt();
        // 提前记录一次
        $aVerifyData = array(
            'pos_id' => $posId, 
            'node_id' => $this->nodeId, 
            'res_seq' => $mPosVerify->posSeq, 
            'assist_code' => $assistCode, 
            'add_time' => date('YmdHis'));
        $bRet = $PosReversalModel->add($aVerifyData);
        if (! $bRet) {
            $this->error("验证失败，请重试！", "", 
                array(
                    'err' => 0));
        }
        // 验证辅助码
        $szRetInfo = $mPosVerify->doPosVerify($posId, $assistCode, true, 
            $txAmt * 100);
        // 超时，删除记录
        if (! $szRetInfo) {
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            $this->error("验证失败，请重试！", "", 
                array(
                    'err' => 0));
        }
        // 处理返回结果
        if ($szRetInfo['business_trans']['result']['id'] == '3035') {
            // 有后续动作，删除记录
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            
            $remainAmt = $szRetInfo['business_trans']['addition_info']['remain_amt'];
            $this->error("还有后续动作", "", 
                array(
                    'err' => 1, 
                    'remain_amt' => $remainAmt));
        } else if ($szRetInfo['business_trans']['result']['id'] == '0000') {
            // 验证成功
            $PosReversalModel->where(array(
                'id' => $bRet))->save(array(
                'status' => 3));
            
            $PosReversalModel->where(array(
                'id' => $bRet))->save(
                array(
                    'status' => '3', 
                    'trans_time' => $szRetInfo['business_trans']['trans_time'], 
                    'tx_amt' => $szRetInfo['business_trans']['addition_info']['tx_amt'], 
                    'phone_no' => $szRetInfo['business_trans']['addition_info']['phone_no']));
            $storeInfo = D('Stores')->getFvStore($this->nodeId);
            $ticket_name = M('tgoods_info')->where(
                array(
                    'node_id' => $this->nodeId))->getFieldByBatch_no(
                $szRetInfo['business_trans']['addition_info']['batch_no'], 
                'goods_name');
            $phone_no = $szRetInfo['business_trans']['addition_info']['phone_no'];
            $phone_no = substr_replace($phone_no, '****', 3, 4);
            $data['msg'] = array(
                'node_name' => $this->nodeInfo['node_name'], 
                'store_name' => $storeInfo['store_name'], 
                'ticket_name' => $ticket_name, 
                'user_name' => M('tuser_info')->getFieldByNode_id($this->nodeId, 
                    'true_name'), 
                'pos_id' => $szRetInfo['business_trans']['pos_id'], 
                'transDate' => date('Y/m/d', 
                    strtotime($szRetInfo['business_trans']['trans_time'])), 
                'transTime' => date('H:i:s', 
                    strtotime($szRetInfo['business_trans']['trans_time'])), 
                'tx_amt' => $szRetInfo['business_trans']['addition_info']['tx_amt'], 
                'remain_amt' => $szRetInfo['business_trans']['addition_info']['remain_amt'], 
                'remain_times' => $szRetInfo['business_trans']['addition_info']['remain_times'], 
                'phone_no' => $phone_no, 
                'pos_seq' => $szRetInfo['business_trans']['pos_seq']);
            $this->success("验证成功", "", $data);
        } else {
            // 验证失败，删除记录
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            
            $this->error($szRetInfo['business_trans']['result']['comment'], "", 
                array(
                    'err' => 0));
        }
    }

    /**
     * [cancel 撤销验证]
     *
     * @return [type] [description]
     */
    public function cancel() {
        $phoneNo = I('post.phone_no');
        $assistCode = I('post.assist_code');
        $map = array(
            'r.node_id' => $this->nodeId, 
            'r.status' => array(
                'in', 
                '3,4'));
        if (! empty($phoneNo)) {
            $map['r.phone_no'] = array(
                'like', 
                '%' . $phoneNo . '%');
        }
        if (! empty($assistCode)) {
            $map['r.assist_code'] = array(
                'like', 
                '%' . $assistCode . '%');
        }
        // 导入分页
        import("ORG.Util.Page");
        // 计算总数
        $nCount = M()->table("tpos_reversal r")->where($map)->count();
        $p = new Page($nCount, 10);
        $aRet =M()->table("tpos_reversal r")->field('r.*,m.name')
            ->join('tbarcode_trace t ON r.assist_code=t.assist_number')
            ->join('tbatch_info b ON b.id=t.b_id')
            ->join('tmarketing_info m ON m.id=b.m_id')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('r.trans_time desc')
            ->select();
        $page = $p->show();
        $this->assign('posReversal', $aRet);
        $this->assign("page", $page);
        $this->assign("phone_no", $phoneNo);
        $this->assign("assist_code", $assistCode);
        $this->display();
    }

    /**
     * [cancelPost post提交撤销数据]
     *
     * @return [type] [description]
     */
    public function cancelPost() {
        $revId = I('post.hrevid');
        if (! $revId) {
            $this->error("参数错误");
        }
        $aRet = M('tpos_reversal')->where(
            array(
                'status' => 3))->getById($revId);
        if (! $aRet) {
            $this->error("无法撤销");
        }
        $mPosVerify = D('PosVerify', 'Service');
        $bRet = $mPosVerify->doPosCancel($aRet['pos_id'], $aRet['res_seq'], 
            $aRet['assist_code']);
        
        if ($bRet) {
            M('tpos_reversal')->where(
                array(
                    'id' => $revId))->save(
                array(
                    'status' => '4'));
            $this->success("撤销成功");
        } else {
            $this->error("撤销失败");
        }
    }
}