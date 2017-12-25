<?php

class nodeModel extends Model {

    protected $tableName = 'tnode_info';

    public function getNodeName($node_id) {
        $name = $this->where(array(
            'node_id' => $node_id))->getField('node_name');
        return $name;
    }

    /**
     * 是否是免费用户
     *
     * @param string $node_id
     * @return bool
     */
    public function getNodeVersion($node_id) {
        $wcVersion = $this->where(
            array(
                'node_id' => $node_id))->getField('wc_version');
        $isFree = false;
        if ($wcVersion == 'v0.5' || $wcVersion == 'v0') {
            $isFree = true;
        }
        $hasM1 = $this->hasPayModule('m1', $node_id);
        if ($wcVersion == 'v9' && ! $hasM1) {
            $isFree = true;
        }
        return $isFree;
    }

    /**
     * 核对收款账号信息中必填项是否为空
     *
     * @param string $node_id
     * @return mixed
     */
    public function getAccountInfo($node_id) {
        // 获取账号信息中的必填项
        $requiredFields = $this->where(
            array(
                'tnode_info.node_id' => $node_id))
            ->field(
            '
                tnode_account.account_no as a_no,tnode_account.account_name as a_name,
                tnode_info.receive_phone as i_phone,tnode_info.account_pwd,
                tnode_cash.account_bank as c_bank,tnode_cash.account_bank_ex as c_bank_ex,tnode_cash.account_name as c_name,tnode_cash.account_no as c_no
            ')
            ->join(
            'inner join tnode_account on tnode_info.node_id=tnode_account.node_id inner join tnode_cash on tnode_cash.node_id=tnode_account.node_id ')
            ->find();
        
        return $requiredFields;
    }

    /**
     * 查询用户信息
     */
    public function getNodeInfo($node_id) {
        $NodeModel = M('tnode_info');
        $node = $NodeModel->where(
            array(
                'node_id' => $node_id))->find();
        return $node;
    }

    /**
     * 更新nodeinfo
     *
     * @param $node_id
     * @param $dataArr
     * @param int $flag 是否储存flag 1=>ok
     * @return int 返回值，1=》更新成功 2=》更新失败
     */
    public function updateNodeInfo($node_id, $dataArr, $flag = 0) {
        define("success", 1);
        define('fail', 2);
        $NodeModel = M('tnode_info');
        // 事务
        $NodeModel->startTrans();
        if ($flag == 1) {
            // 如果满足条件(flag = 1 )的话，更新cfg_data和企业简称
            $NodeModel->cfg_data = $dataArr['cfg_data'];
            $NodeModel->node_short_name = $dataArr['node_short_name'];
        }
        $NodeModel->contact_name = $dataArr['contact_name'];
        $NodeModel->contact_phone = $dataArr['contact_phone'];
        $NodeModel->node_service_hotline = $dataArr['node_service_hotline'];
        $result = $NodeModel->where("node_id=$node_id")->save();
        if ($result) {
            $NodeModel->commit(); // 成功则提交
            return success;
        } else {
            $NodeModel->rollback(); // 不成功，则回滚
            return fail;
        }
    }

    /**
     * 获取汇率信息
     *
     * @param string $node_id
     * @param string $orderAmt   //订单金额
     * @param string $payChannel   //支付渠道
     * @return $nodeAccountInfo
     */
    public function getNodeAccountFeeInfo($nodeId, $orderAmt, $payChannel) {
        // 获取费率信息
        $nodeAccountInfo = M('tnode_account')->where(
            "node_id = {$nodeId} AND account_type={$payChannel} AND status=1")
            ->field('account_no,fee_rate')
            ->find();
            
        $seller_account = $nodeAccountInfo['account_no'];
        $payFeeRate = $nodeAccountInfo['fee_rate'];
        // 如果没有配置机构费率，则取系统默认费率
        log_write('pay_Info: SQLINFO:' . M()->_sql());
        if (! $payFeeRate)
            $payFeeRate = C('FEERATE');
        $myPrice = $orderAmt * $payFeeRate;
        // 如果小于1分钱不收费
        if ($myPrice < 0.01) {
            $myPrice = 0;
        }
        $myPrice = round($myPrice, 2);
        $price = $orderAmt - $myPrice;
        
        $orderInfo = array(
            'receive_amount' => $price, 
            'fee_amt' => $orderAmt - $price, 
            'fee_rate' => $payFeeRate);
        
        return $orderInfo;
    }

    /**
     * [hasPayModule 是否有付费模块权限] [strModule 逗号隔开的模块,如'm0,m1,m2']
     *
     * @return boolean [description]
     */
    public function hasPayModule($strModule, $node_id) {
        $nodeInfo = get_node_info($node_id);
        if ($nodeInfo['wc_version'] == 'v4') {
            return true;
        }
        $strModule = trim($strModule, ',');
        if (empty($strModule)) {
            throw_exception('hasMudlePower:参数不得为空');
        }
        $arrModule = explode(',', $strModule);
        $payModule = explode(',', trim($nodeInfo['pay_module'], ','));
        if (empty($payModule)) {
            return false;
        }
        foreach ($arrModule as $k => $v) {
            if (! in_array($v, $payModule)) {
                return false;
            }
        }
        
        return true;
    }
}
