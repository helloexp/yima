<?php

/**
 * 功能：门店LBS编辑
 *
 * @author wtr 时间：2013-06-26
 */
class EditStoreLBSAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    // 以下是特殊请求参数
    public $LBS_X;
    // 位置信息纬度
    public $LBS_Y;

    // 位置信息经度
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->LBS_X = I('LBS_X');
        $this->LBS_Y = I('LBS_Y');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号";
            $this->returnError($resp_desc);
        }
        // 初始化经纬度
        $lbs = '';
        if ($this->LBS_X && $this->LBS_Y) {
            $lbs = $this->LBS_Y . ',' . $this->LBS_X;
        }

        // 数据隔离，只能查本机构
        $store_id = M('TposInfo')->where("node_id ='" . $this->node_id . "' and pos_id='" . $this->pos_id . "'")->getField("store_id");
        if (!$store_id) {
            $this->returnError('更新门店信息失败，门店号不存在');
        }
        $where = "node_id ='" . $this->node_id . "' and store_id = '" . $store_id . "'";
        $rs    = M('TstoreInfo')->where($where)->save(array(
                'store_lbs' => $lbs,
        ));

        if (!$rs) {
            $resp_desc = "更新门店LBS失败";
            $this->returnError($resp_desc);
        }

        // 更新支撑门店信息
        $reqServ = D('RemoteRequest', 'Service');

        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号

        // 请求参数
        $req_arr = array(
                'MotifyPosStoreReq' => array(
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'ISSPID'        => $this->node_id,
                        'TransactionID' => $TransactionID,
                        'PosID'         => $this->pos_id,
                        'StoreID'       => $store_id,
                        'StoreLbs'      => $lbs,
                ),
        );
        $rs      = $reqServ->requestIssServ($req_arr);
        $rs      = current($rs);
        if (!$rs || $rs['Status']['StatusCode'] != '0000') {
            $resp_desc = "更新支撑平台门店失败";
            $this->returnError($resp_desc);
        }

        $resp_desc = "更新门店LBS成功";
        $this->returnSuccess($resp_desc);
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id)) {
            return false;
        }

        return true;
    }
}

?>