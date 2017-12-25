<?php

/**
 * 功能：终端信息查看
 *
 * @author wangtr 时间：2013-06-26
 */
class PosInfoAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
    }

    public function run() {
        $pos_serial = $this->pos_serial;
        // 以下是本地查询
        $dao       = M()->table('tpos_info a')->field("a.node_id,a.pos_id,a.pos_name,a.store_name,
				b.node_name,b.contract_no")->join('tnode_info b on b.node_id=a.node_id');
        $posResult = $dao->where("a.pos_id='" . $this->pos_id . "'")->find();
        if (!$posResult) {
            $this->returnError('终端信息不存在');
        }
        $pos_info               = array();
        $pos_info['node_id']    = $posResult['node_id']; // 商户号
        $pos_info['pos_id']     = $posResult['pos_id']; // 终端号
        $pos_info['pos_name']   = $posResult['pos_name']; // 终端名
        $pos_info['node_name']  = $posResult['node_name']; // 商户名
        $pos_info['store_name'] = $posResult['store_name']; // 门店名

        // 这儿从营账查询
        // 商户余额流浪报文参数
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $req_array     = array(
                'QueryShopInfoReq' => array(
                        'SystemID'      => C('YZ_SYSTEM_ID'),
                        'NodeID'        => $this->node_id,
                        'TransactionID' => $TransactionID,
                        'ContractID'    => $posResult['contract_no'],
                ),
        );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult     = $RemoteRequest->requestYzServ($req_array);
        $reqResult     = current($reqResult); // 得到根节点
        $reqStatus     = $reqResult['Status'];
        if ($reqStatus['StatusCode'] == '0000') {
            // 计算套餐剩余流量 = 套餐流量+免费流量-套餐使用数-免费使用数
            $remain_num = $reqResult['TotalFreeNums'] + $reqResult['TotalSetNums'] - $reqResult['TotalFreeNumsUsed'] - $reqResult['TotalSetNumsUsed'];
            $pos_info   = array_merge($pos_info, array(
                            'account_amt' => $reqResult['AccountPrice'],
                            'remain_num'  => $remain_num,
                    ));
        }

        $resp_desc = "请求启动搜索成功";
        $this->returnSuccess($resp_desc, $pos_info);
        exit();
    }
}

?>