<?php

/**
 * 功能：门店编辑
 *
 * @author wtr 时间：2013-06-26
 */
class EditStoreInfoAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    // 以下是特殊请求参数
    public $store_name;
    // 门店名称
    public $store_address;
    // 门店地址
    public $LBS_X;
    // 位置信息纬度
    public $LBS_Y;

    // 位置信息经度
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->store_name    = I('store_name');
        $this->store_address = I('store_address');
        $this->LBS_X         = I('LBS_X');
        $this->LBS_Y         = I('LBS_Y');
    }

    public function run() {
        $rs = $this->check();
        if ($rs !== true) {
            $resp_desc = $rs;
            $this->returnError($resp_desc);
        }
        // 更新终端中的门店信息
        $rs = M('TposInfo')->where("pos_id='" . $this->pos_id . "'")->save(array(
                'store_name' => $this->store_name,
                'pos_name'   => $this->store_name,
        ));
        // 更新本地门店
        $dao = M('TstoreInfo');
        // 数据隔离，只能查本机构
        $store_id = M('TposInfo')->where("node_id ='" . $this->node_id . "' and pos_id='" . $this->pos_id . "'")->getField("store_id");

        if (!$store_id) {
            $this->returnError('更新门店信息失败，门店号不存在');
        }
        // 要更新的数据
        $set_data = array(
                'store_name' => $this->store_name,
                'address'    => $this->store_address,
        );
        // 如果有经纬度，初始化经纬度
        $lbs = '';
        if ($this->LBS_X && $this->LBS_Y) {
            $lbs = $this->LBS_Y . ',' . $this->LBS_X;
        }
        if ($libs) {
            $set_data['store_lbs'] = $lbs;
        }
        // 如果门店不存在，则创建门店
        if (!$dao->where("store_id='{$store_id}'")->count()) {
            $set_data = array_merge($set_data, array(
                            'store_id' => $store_id,
                            'node_id'  => $this->node_id,
                    ));
            $rs       = $dao->add($set_data);
        }  // 如果门店存在，更新门店
        else {
            $where = "node_id ='" . $this->node_id . "' and store_id = '" . $store_id . "'";
            $rs    = $dao->where($where)->save($set_data);
        }

        if ($rs === false) {
            $resp_desc = "更新门店失败";
            $this->returnError($resp_desc);
        }

        // 更新支撑门店信息
        $reqServ = D('RemoteRequest', 'Service');

        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号

        // 请求参数
        $req_arr = array(
                'MotifyPosStoreReq' => array(
                        'SystemID'       => C('ISS_SYSTEM_ID'),
                        'ISSPID'         => $this->node_id,
                        'TransactionID'  => $TransactionID,
                        'PosID'          => $this->pos_id,
                        'PosName'        => $this->store_name,
                        'StoreID'        => $store_id,
                        'StoreName'      => $this->store_name,
                        'StoreShortName' => '',
                        'StoreLbs'       => $lbs,
                        'StoreAddress'   => $this->store_address,
                ),
        );
        $rs      = $reqServ->requestIssServ($req_arr);
        $rs      = current($rs);
        if (!$rs || $rs['Status']['StatusCode'] != '0000') {
            $resp_desc = "更新支撑平台门店失败";
            $this->returnError($resp_desc);
        }

        // 更新渠道名信息
        $rs = M('Tchannel')->where("pos_id='" . $this->pos_id . "' and type='3'")->save(array(
                'name' => $this->store_name,
        ));

        $resp_desc = "更新门店成功";
        $this->returnSuccess($resp_desc);
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id)) {
            return "机构号，终端号，用户号不能为空";
        }

        return true;
    }
}

?>