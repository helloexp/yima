<?php

// 终端处理服务逻辑
class PosService {

    /**
     * pos机开放
     *
     * @var unknown_type
     */
    const POS_STATUS_OPEN = "0";

    /**
     * pos机关闭
     *
     * @var unknown_type
     */
    const POS_STATUS_CLOSE = "1";

    /**
     * 明码验证
     */
    const POS_ENCODE_TYPE_CLEARLY = "099";

    /**
     * 密文验证
     */
    const POS_ENCODE_TYPE_CIPHERTEXT = "098";

    /**
     * 请求的交易类型
     *
     * @var array
     */
    private static $requestTypes = array(
            "0" => "verify_request",
            "1" => "cancel_request",
            "2" => "reversal_request",
            "4" => "settle_request",
            "5" => "batch_up_request",
            "6" => "batch_up_end_request",
            "7" => "batch_down_request",
            "8" => "batch_down_end_request",
    );

    /**
     * 反馈的交易类型
     *
     * @var array
     */
    private static $responseTypes = array(
            "0" => "verify_response",
            "1" => "cancel_response",
            "4" => "settle_response",
            "5" => "batch_up_response",
            "6" => "batch_up_end_response",
            "7" => "down_down_response",
            "8" => "batch_down_end_response",
    );

    private $errMsg;

    private $errInfo;

    private $dao;

    private $respInfo;

    private $printText;

    private $flowService;

    public function __construct() {
        $this->dao         = D('TposInfo');
        $this->flowService = D('Flow', 'Service');
        $this->posSession  = D('UserSess', 'Service')->getSession();
    }

    /**
     * 向支撑提交签到信息。
     */
    public function checkin(
            $posId,
            $userId,
            $posSeq,
            $posSn = "",
            $mKey = "",
            &$wKey = ""
    ) {
        // 请求参数
        $xml_arr     = array(
                "request_type"  => "login_request",
                "pos_seq"       => $posSeq,
                "pos_id"        => $posId,
                "user_id"       => $userId,
                "login_request" => array(
                        "pos_sn"     => $posSn,
                        "pos_ver"    => "NewWebPos",
                        "reader_ver" => "",
                ),
        );
        $inputMacStr = $posId . $userId . $posSeq;
        $request     = D('RemoteRequest', 'Service');
        /* 先用主密钥签到 */
        $arr = $request->requestPosLoginServ($xml_arr, $inputMacStr, $mKey);
        if ($arr['result']['id'] === "0000") {
            $wKey            = $arr['login_response']['work_key'];
            $param_arr       = explode('\\', $arr['login_response']['term_param']);
            $param_batch_arr = explode('=', $param_arr[6]);
            // 更新最大对账流水号
            $result = $this->updateSettleBatch($posId, $param_batch_arr[1]);
            if ($result == false) {
                $this->errMsg  = "更新最大对账流水号失败";
                $this->errInfo = array(
                        'code' => '6001',
                        'msg'  => $this->errMsg,
                );

                return false;
            } else {
                return true;
            }
        } else {
            $this->errMsg  = $arr['result']['comment'];
            $this->errInfo = array(
                    'code' => $arr['result']['id'],
                    'msg'  => $this->errMsg,
            );

            return false;
        }
    }

    /**
     * pos是否开启
     *
     * @param unknown_type $posId
     *
     * @return boolean
     */
    public function posIsOpen($posId) {
        $rs = $this->getPos($posId);
        if (empty($rs) || $rs['pos_status'] === self::POS_STATUS_CLOSE) {
            return false;
        }

        return true;
    }

    /**
     * 激活是否被激活
     *
     * @param unknown_type $posId
     *
     * @return boolean
     */
    public function checkIsActivated($posId) {
        $rs = $this->getPos($posId);
        if ($rs['is_activated'] == 1) {
            return true;
        } else {
            false;
        }
    }

    /**
     * 激活pos
     *
     * @param unknown_type $posId
     *
     * @return boolean
     */
    public function activatePos($posId) {
        $posInfo   = M('TposInfo')->where("pos_id='" . $posId . "'")->field("pos_id,node_id")->find();
        $nodeInfo  = M('TnodeInfo')->where("node_id='" . $posInfo['node_id'] . "'")->field("node_id,contract_no")->find();
        $orderInfo = M('TorderDetail')->where("goods_id='" . $posId . "'")->field("goods_id,order_id")->find();
        // 发往营账激活接口
        $request = array(
                "PosActivateReq" => array(
                        "TransactionID" => microtime(true),
                        "OrderNo"       => $orderInfo['order_id'],
                        "SystemID"      => C('YZ_SYSTEM_ID'),
                        "NodeID"        => $posInfo['node_id'],
                        "ContractID"    => $nodeInfo['contract_no'],
                        "PosCode "      => $posId,
                ),
        );
        $reqServ = D('RemoteRequest', 'Service');
        $result  = $reqServ->requestYzServ($request);
        // 如果营账激活失败
        // $result['Status']['StatusCode'] = '0000';
        $result = current($result);
        if (!$result || $result['Status']['StatusCode'] != '0000') {
            $this->errMsg  = $result['Status']['StatusText'];
            $this->errInfo = array(
                    'code' => '6002',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
        // 更新数据库
        $rs = $this->dao->where(array(
                'pos_id' => $posId,
        ))->save(array(
                'is_activated' => 1,
        ));
        if ($rs !== false) {
            return true;
        } else {
            $this->errMsg  = "数据库更新失败";
            $this->errInfo = array(
                    'code' => '6003',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
    }

    /*
     * 预记录流水
     */
    private function recordFlow(
            $code,
            $posId,
            $posSeq,
            $userId,
            $userName,
            $encodeType = self::POS_ENCODE_TYPE_CLEARLY,
            $tx_amt = 0,
            $trans_type = '0',
            $orgPosSeq = '',
            $node_id = '',
            $storeId
    ) {
        if ($encodeType == '098') {
            // 条码
            $barcode = $code;
        } else {
            // 辅助码验证取 辅助码后4位。
            $assistant_no_back = substr($code, -4);
            // 辅助码
            $auxiliary_code = $code;
        }
        $flowInfo = array(
                "pos_id"            => $posId,
                "pos_seq"           => $posSeq,
                "user_id"           => $userId,
                "user_name"         => $userName,
                "code_type"         => $encodeType,
                "exchange_amt"      => empty($tx_amt) ? "0" : $tx_amt,
                "trans_time"        => date('YmdHis'),
                "barcode"           => $barcode,  // 条码
                "assistant_no_back" => $assistant_no_back,  // 辅助码后4位
                "auxiliary_code"    => $auxiliary_code,  // 辅助码明文
                "flow_status"       => 0,  // 执行状态：0: 执行中；1:返回完成
                "trans_type"        => $trans_type,  // 交易类型 0:验证 1:撤销
                "status"            => 0,  // 交易状态 0:成功 1:失败
                "phone_no"          => '',  // 支撑返回时更新
                "assistant_no_md5"  => '',  // 辅助码md5值， 支撑返回
                "org_posseq"        => $orgPosSeq,
                "node_id"           => $node_id,
        ); // 商户号

        $rs = $this->flowService->writeBeginFlow($flowInfo);
        if (!$rs) {
            $this->errMsg .= " 流水预记录错误！";
            $this->errInfo = array(
                    'code' => '6004',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        return true;
    }

    /**
     * 查询需要冲正记录数量
     */
    private function checkReversalRecord($posId) {
        // FLOW_STATUS 执行状态：0: 执行中；1:返回完成
        $flow_status = '0';

        return $this->flowService->countFlows($posId, '', '', '', '', '', '', '', '', '', $flow_status);
    }

    /**
     * 验证辅助码或条码 (金额和密码 选择性输入)
     *
     * @param string $code
     *
     * @return boolean
     */
    public function checkAssistCode(
            $code,
            $posId,
            $posSeq,
            $userId,
            $userName,
            $encodeType = self::POS_ENCODE_TYPE_CLEARLY,
            $tx_amt = 0,
            $password = '',
            $system_id = '',
            $goods_id = '',
            $node_id = ''
    ) {

        // 检查是否有需要冲正的记录，如果有，禁止下步操作
        $r_count = $this->checkReversalRecord($posId);
        if ($r_count > 0) {
            $this->errMsg  = "系统正在处理冲正交易，请稍候再试！";
            $this->errInfo = array(
                    'code' => '6005',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
        // 预记录流水
        $rs = $this->recordFlow($code, $posId, $posSeq, $userId, $userName, $encodeType, $tx_amt, '0', '', $node_id);
        if (!$rs) {
            $this->errMsg .= " 预先记录流水失败";
            $this->errInfo = array(
                    'code' => '6006',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 验码
        $rs = $this->sendCodeToVerify($code, $posId, $posSeq, $userId, $encodeType, $tx_amt, $password, $system_id,
                $goods_id);
        $sw = true;
        if (!$rs) {
            $this->errMsg .= " 验证失败";
            $sw = false;
        }

        $flowInfo = $this->verifyRespInfoToFlowData($this->respInfo, $code, $userId, $userName, $encodeType, $node_id);

        if (!empty($flowInfo)) {
            $rs = $this->flowService->writeVerifyFlow($flowInfo);
            if (!$rs) {
                $this->errMsg .= " 流水记录错误！";
                $this->errInfo = array(
                        'code' => '6008',
                        'msg'  => $this->errMsg,
                );

                return false;
            }
        }

        if (is_array($this->respInfo['addition_info'])) {
            $tmp = $this->respInfo['addition_info']['print_text'];
        } else {
            $tmp = '';
        }

        while (strpos($tmp, "\\r") === 0) {
            $tmp = substr($tmp, 2, strlen($tmp));
        }

        $this->printText = str_ireplace("\\r", "<BR>", $tmp);

        // print_r($this->respInfo['addition_info']['print_text'] );
        // print_r($this->printText );
        return $sw;
    }

    /**
     * 撤销交易
     *
     * @param unknown_type $code      辅助码
     * @param unknown_type $orgFlowId 原流水自增号
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $userId
     */
    public function cancelAssistCode(
            $code,
            $orgFlowId,
            $posId,
            $posSeq,
            $userId,
            $userName,
            $encodeType = self::POS_ENCODE_TYPE_CLEARLY,
            $node_id = ''
    ) {

        // TODO 根据辅助码，和原流水号查找原流水
        // TODO 检查原流水状态
        // TODO 写撤销流水
        // TODO 更新原有流水
        $info = $this->flowService->getFlowByFlowId($orgFlowId);
        if (empty($info) || $info['assistant_des'] != md5($code) || $info['pos_id'] != $posId || !($info['trans_type'] === FlowService::FLOW_TYPE_VERIFY)) {
            $this->errMsg = " 没有相应的验证交易流水信息";

            return false;
        } else if ($info['status'] !== FlowService::FLOW_STATUS_OK || $info['is_canceled'] !== FlowService::FLOW_CANCELED_NO) {
            $this->errMsg  = " 交易流水当前状态不能被修改";
            $this->errInfo = array(
                    'code' => '6009',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
        // 检查是否有需要冲正的记录，如果有，禁止下步操作
        $r_count = $this->checkReversalRecord($posId);
        if ($r_count > 0) {
            $this->errMsg  = "系统正在处理冲正交易，请稍候再试！";
            $this->errInfo = array(
                    'code' => '6010',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
        // 预记录流水
        $orgPosSeq = $info['org_posseq'];
        $rs        = $this->recordFlow($code, $posId, $posSeq, $userId, $userName, $encodeType, '', '1', $orgPosSeq,
                $node_id);
        if (!$rs) {
            $this->errMsg .= " 预先记录流水失败";
            $this->errInfo = array(
                    'code' => '6011',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        $rs = $this->sendCodeToCancel($code, $posId, $posSeq, $info['pos_seq'], $userId, $encodeType);
        $sw = true;

        if (!$rs) {
            $this->errMsg .= " 撤销失败";
            $this->errInfo = array(
                    'code' => '6012',
                    'msg'  => $this->errMsg,
            );
            $sw            = false;
        }

        $flowInfo = $this->cancelRespInfoToFlowData($this->respInfo, $code, $info['pos_seq'], $info['batch_no'],
                $info['batch_desc'], $info['exchange_amt'], $info['phone_no'], $userId, $userName, $node_id);

        if (!empty($flowInfo)) {
            $rs = $this->flowService->writeCancelFlow($orgFlowId, $flowInfo);
            if (!$rs) {
                $this->errMsg .= " 交易流水更新错误";
                $this->errInfo = array(
                        'code' => '6013',
                        'msg'  => $this->errMsg,
                );

                return false;
            }
        }

        return $sw;
    }

    /*
     * 冲正交易(内部调用) @param int $code 凭证号 @param int $orgPosSeq 原终端流水号 @param int
     * $posId 终端号 @param int $posSeq 终端流水号 @param int $orgFlowId 原系统流水号 @param
     * int $orgTransType 原交易类型 @param int $posSeq 终端流水号 @param int $userId 用户ID
     * @param int $userName 用户名 @param int $encodeType 验证类型 @param int $nodeId
     * 机构号
     */
    public function doReversal(
            $code,
            $orgPosSeq,
            $posId,
            $posSeq,
            $orgFlowId,
            $orgTransType,
            $userId,
            $userName,
            $encodeType,
            $nodeId
    ) {
        // 预记录流水
        $rs = $this->recordFlow($code, $posId, $posSeq, $userId, $userName, $encodeType, '0', $transType = '2',
                $orgPosSeq, $nodeId, $store_id = '');
        if (!$rs) {
            $this->errMsg .= " 预先冲正记录流水失败";
            $this->errInfo = array(
                    'code' => '6014',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
        // 向支撑发起冲正交易，发3次
        $reversalResult = false;
        for ($i = 0; $i < 3; $i++) {
            $reversalResult = $this->sendCodeToReversal($code, $posId, $posSeq, $orgPosSeq, $userId,
                    $encodeType = self::POS_ENCODE_TYPE_CLEARLY);
            if ($reversalResult) {
                break;
            }
            // print_r($this->errMsg);
            sleep(1);
        } // end for
        $sw = true;

        if (!$reversalResult) {
            $this->errMsg .= " 冲正失败";
            $this->errInfo = array(
                    'code' => '6015',
                    'msg'  => $this->errMsg,
            );
            $sw            = false;
        }

        $flowInfo = $this->reversalRespInfoToFlowData($this->respInfo, $code, $orgPosSeq, $userId, $userName, $node_id);

        if (!empty($flowInfo)) {
            $rs = $this->flowService->writeReversalFlow($orgFlowId, $orgTransType, $flowInfo);
            if (!$rs) {
                $this->errMsg .= " 交易流水更新错误";
                $this->errInfo = array(
                        'code' => '6016',
                        'msg'  => $this->errMsg,
                );

                return false;
            }
        }

        return $sw;
    }

    /**
     * 终端对账
     *
     * @param string $code       条码/辅助码
     * @param string $posId      终端号
     * @param string $posSeq     终端流水号
     * @param string $userId     用户ID
     * @param string $userName   用户名
     * @param string $encodeType 条码类型
     * @param string $tx_amt     验证金额
     *
     * @return boolean
     */
    public function doPosSettle(
            $code,
            $posId,
            $posSeq,
            $userId,
            $userName,
            $encodeType = '',
            $tx_amt = 0
    ) {

        // 检查是否有需要冲正的记录，如果有，禁止下步操作
        $r_count = $this->checkReversalRecord($posId);
        if ($r_count > 0) {
            $this->errMsg  = "系统正在处理冲正交易，请稍候再试！";
            $this->errInfo = array(
                    'code' => '6017',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 获取对账数据
        $posInfo = $this->getPosSettle($posId);

        if (!$posInfo) {
            $this->errMsg  = "暂时没有验证和撤销对账数据！";
            $this->errInfo = array(
                    'code' => '6018',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 预记录流水
        $rs = $this->recordFlow($code, $posId, $posSeq, $userId, $userName, $encodeType, $tx_amt, '4');
        if (!$rs) {
            $this->errMsg .= " 预先记录流水失败";
            $this->errInfo = array(
                    'code' => '6019',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 获取最新对账批次号
        $settle_batch = $this->getNewSettleBatch($posId);

        if (!$settle_batch) {
            $this->errMsg .= " 获取最新对账批次号失败";
            $this->errInfo = array(
                    'code' => '6020',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 对账保存信息
        $settleInfo                 = array();
        $settleInfo['POS_ID']       = $posId;
        $settleInfo['USER_ID']      = $userId;
        $settleInfo['USER_NAME']    = $userName;
        $settleInfo['SETTLE_BATCH'] = $settle_batch;
        $settleInfo['VERIFY_AMT']   = empty($posInfo['VERIFY_AMT']) ? 0 : $posInfo['VERIFY_AMT'];
        $settleInfo['VERIFY_NUM']   = empty($posInfo['VERIFY_NUM']) ? 0 : $posInfo['VERIFY_NUM'];
        $settleInfo['CANCEL_AMT']   = empty($posInfo['CANCEL_AMT']) ? 0 : $posInfo['CANCEL_AMT'];
        $settleInfo['CANCEL_NUM']   = empty($posInfo['CANCEL_NUM']) ? 0 : $posInfo['CANCEL_NUM'];

        // 对账
        // $rs1 = $this->sendPosSettle($posId, $posSeq, $userId, $settle_batch,
        // $settleInfo['VERIFY_AMT'], $settleInfo['REVERSAL_NUM'],
        // $settleInfo['CANCEL_AMT'], $settleInfo['CANCEL_NUM']);
        $rs1 = $this->sendPosSettle($posId, $posSeq, $userId, $settle_batch, ($settleInfo['VERIFY_AMT'] * 100),
                $settleInfo['VERIFY_NUM'], ($settleInfo['CANCEL_AMT'] * 100), $settleInfo['CANCEL_NUM']);

        // 将对账返回的报文转成流水数据
        $flowInfo = $this->settleRespInfoToFlowData($this->respInfo);

        if (!empty($flowInfo)) {
            // 写对账交易流水
            $rs = $this->flowService->writeSettleFlow($settleInfo, $flowInfo);
            if (!$rs) {
                $this->errMsg .= " 流水记录错误！";
                $this->errInfo = array(
                        'code' => '6021',
                        'msg'  => $this->errMsg,
                );

                return false;
            }
        }

        // 对账失败
        if (!$rs1) {
            $this->errMsg .= "";
            $this->errInfo = array(
                    'code' => '6022',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        return $settle_batch;
    }

    /**
     * 批上送处理
     *
     * @param string $posId        终端号
     * @param string $posSeq       终端流水号
     * @param string $userId       用户ID
     * @param string $settleBatch  终端对账批次号
     * @param string $settleStatus 终端状态
     * @param string $settleInfo   对账信息
     *
     * @return boolean
     */
    public function doBatchUp(
            $posId,
            $posSeq,
            $userId,
            $settleBatch,
            $settleStatus,
            $settleInfo
    ) {

        // 判断批上送报文参数是否存在
        if ($posId == '' || $posSeq == '' || $userId == '' || $settleBatch == '' || !in_array($settleStatus, array(
                                '1',
                                '2',
                                '4',
                                '5',
                        ))
        ) {
            $this->errMsg  = "批上送报文参数不存在！";
            $this->errInfo = array(
                    'code' => '6023',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 批上送操作
        $rs = $this->sendBatchUp($posId, $posSeq, $userId, $settleBatch);

        if (!$rs) {
            $this->errMsg .= " ";
            $this->errInfo = array(
                    'code' => '6024',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        // 批上送结束操作
        $rs = $this->sendBatchUpEnd($posId, $posSeq, $userId, $settleBatch);
        if (!$rs) {
            $this->errMsg .= " ";
            $this->errInfo = array(
                    'code' => '6025',
                    'msg'  => $this->errMsg,
            );

            return false;
        } else {
            // 批上送成功后保存流水对账标识和终端对账标识和对账信息
            $rs = $this->flowService->saveSettleInfoFlag($posId, $settleBatch, $settleInfo);
            if (!$rs) {
                $this->errMsg .= " ";
                $this->errInfo = array(
                        'code' => '6026',
                        'msg'  => $this->errMsg,
                );

                return false;
            }
        }

        return true;
    }

    /**
     * 得到对账数据
     */
    public function getPosSettle($posId) {
        include_once 'Modules/Pos/Dao/PosSettleDao.class.php';
        $dao = new PosSettleDao();
        $rs  = $dao->getPosSettle($posId);

        return $rs;
    }

    /**
     * 得到新的终端流水号
     */
    public function getNewPosSeq($posId) {
        $dao = D('TsysSequence');
        $i   = $dao->getNextSeq('pos_seq');
        $seq = "9" . str_pad($i, 11, '0', STR_PAD_LEFT);
        $this->dao->where("POS_ID='{$posId}' and LAST_POS_SEQ<{$seq}")->save(array(
                "last_pos_seq" => $seq,
        ));

        return $seq;
    }

    // 更新终端最大对账流水号
    public function updateSettleBatch($posId, $max_settle_batch) {
        $pos_info           = $this->getPos($posId);
        $_last_settle_batch = intval($pos_info['last_settle_batch']);
        $_max_settle_batch  = intval($max_settle_batch);

        if ($_max_settle_batch > $_last_settle_batch) {
            return $this->updatePosInfo($posId, array(
                            'last_settle_batch' => $max_settle_batch,
                    ));
        } else {
            return true;
        }
    }

    // 获取终端最大对账流水号
    public function getNewSettleBatch($posId) {
        $pos_info          = $this->dao->getPos($posId);
        $last_settle_batch = intval($pos_info['last_settle_batch']);
        $max_settle_batch  = $last_settle_batch + 1;
        $max_settle_batch  = str_pad($max_settle_batch, 6, '0', STR_PAD_LEFT);

        $rs = $this->updatePosInfo($posId, array(
                        'last_settle_batch' => $max_settle_batch,
                ));

        // 更新成功返回最新对账流水号,否则返回false
        if ($rs == true) {
            return $max_settle_batch;
        } else {
            return false;
        }
    }

    /**
     * 更新终端的workKey
     *
     * @param unknown_type $posId
     */
    public function updateWorkKey($posId, $workKey) {
        return $this->dao->where("POS_ID='{$posId}'")->save(array(
                "work_key" => $workKey,
        ));
    }

    /**
     * 向支撑提交验证码用于验证
     *
     * @param unknown_type $code
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $userId
     * @param string       $encodeType 验证编码类型
     *
     * @return boolean
     */
    private function sendCodeToVerify(
            $code,
            $posId,
            $posSeq,
            $userId,
            $encodeType = self::POS_ENCODE_TYPE_CLEARLY,
            $tx_amt = 0,
            $password = '',
            $system_id = '',
            $goods_id = ''
    ) {
        $password_md5 = '';
        if ($password) {
            // md5加密
            $password_md5 = md5($password);
        }
        // 请求参数
        $reqArr = array(
                "request_type"   => self::$requestTypes["0"],
                "pos_id"         => $posId,
                "pos_seq"        => $posSeq,
                "user_id"        => $userId,
                "verify_request" => array(
                        "valid_info"   => $code,
                        "encode_type"  => $encodeType,
                        "tx_amt"       => $tx_amt,
                        "password_md5" => $password_md5,
                        "system_id"    => $system_id,
                        "goods_id"     => $goods_id,
                ),
        );

        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg  = "终端不存在或未开启";
            $this->errInfo = array(
                    'code' => '6027',
                    'msg'  => $this->errMsg,
            );

            return false;
        }

        if ($posInfo['work_key'] != $this->posSession->getWorkKey()) {
            $this->errMsg  = "终端辅助密钥不一致！";
            $this->errInfo = array(
                    'code' => '6028',
                    'msg'  => $this->errMsg,
            );

            return false;
        }
        // 计算密钥
        $inputMacStr = '' . $posId . $userId . $encodeType . $code . $posSeq;
        // 请求远程验证
        $reqService = D('RemoteRequest', 'Service');
        $arr        = $reqService->requestPosTransServ($reqArr, $inputMacStr, $posInfo['master_key'],
                $posInfo['work_key']);

        // 过滤掉打印文本后面的活动号和活动名
        if (isset($arr['addition_info']['print_text'])) {
            $arr['addition_info']['print_text'] = get_print_text($arr['addition_info']['print_text']);
            // exit($arr['addition_info']['print_text']);
        }

        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '验证出错!';
            if ($arr['result']['id'] === "3035") {
                $this->errInfo = array(
                        'code'       => $arr['result']['id'],
                        'msg'        => $this->errMsg,
                        'goods_info' => $arr['addition_info']['goods_info'],
                );

                return false;
            }
            $this->errInfo = array(
                    'code' => $arr['result']['id'],
                    'msg'  => $this->errMsg,
            );

            return false;
        }
    }

    /**
     * 将验码返回的报文转成流水数据
     *
     * @param array  $respInfo 返回报文
     * @param String $code     验证码
     *
     * @return array
     */
    private function verifyRespInfoToFlowData(
            $respInfo,
            $code,
            $userId,
            $userName,
            $codeType,
            $nodeId
    ) {
        /*
         * 2012-11-27 添加冲正功能， 添加预先记录流水功能，这样会导致更新流水记录时，字段有些会重复更新（第一次添加时已经记录）。
         * 不影响实现，暂时不做修改。 后期可定义：预定义记录字段，更新时记录字段。
         */
        if (!is_array($respInfo) || !$respInfo['response_type']) {
            // 终端设置错误，验码会跑到第三方验证，返回的非xml数据，导致解析出的$respInfo非数组。
            return array();
        }
        if ($codeType == '098') {
            // 从print_text中获取辅助码后4位明码
            $str = $respInfo['addition_info']['print_text'];
            while (strpos($str, "\\r") === 0) {
                $str = substr($str, 2, strlen($str));
            }
            $str               = str_ireplace("\\r", "==", $str);
            $begin             = strpos($str, "辅助码：");
            $str               = substr($str, $begin + strlen("辅助码："));
            $end               = strpos($str, "==");
            $substr            = substr($str, 0, $end);
            $assistant_no_back = trim(str_ireplace('*', '', $substr));
        } else {
            // 辅助码验证直接取 辅助码后4位。减少逻辑
            $assistant_no_back = substr($code, -4);
        }

        $barcode = $auxiliary_code = '';
        if ($codeType == '098') {
            // 条码
            $barcode = $code;
        } else {
            // 辅助码
            $auxiliary_code = $code;
        }

        $flowInfo = array(
                "pos_id"            => $respInfo['pos_id'],
                "pos_seq"           => $respInfo['pos_seq'],
                "trans_type"        => array_search($respInfo['response_type'], self::$responseTypes),
                "status"            => (is_array($respInfo['result']) && $respInfo['result']['id'] == "0000") ? "0" : "1",
                "exchange_amt"      => empty($respInfo['addition_info']['tx_amt']) ? "0" : $respInfo['addition_info']['tx_amt'],
                "trans_time"        => date('YmdHis'),  // "org_posseq"
                "batch_desc"        => $respInfo['addition_info']['batch_desc'],
                "batch_no"          => $respInfo['addition_info']['batch_no'],
                "phone_no"          => $respInfo['addition_info']['phone_no'],
                "ret_code"          => $respInfo['result']['id'],
                "ret_desc"          => $respInfo['result']['comment'],
                "user_id"           => $userId,
                "user_name"         => $userName,
                "assistant_des"     => md5($code),
                "assistant_no"      => substr($code, -4),  // "batch_no"
                "print_text"        => $respInfo['addition_info']['print_text'],
                "print_times"       => 1,
                "assistant_no_back" => $assistant_no_back,  // 辅助码后4位
                "assistant_no_md5"  => $respInfo['addition_md5'],  // MD5辅助码
                "code_type"         => $codeType,  // 验证类型 099：辅助码 ; 098：条码
                "barcode"           => $barcode,  // 条码明文
                "auxiliary_code"    => $auxiliary_code,  // 辅助码明文
                "flow_status"       => 1,  // 支撑执行返回完成
                "node_id"           => $nodeId,
        );

        return $flowInfo;
    }

    /**
     * 向支撑提交辅助码码用于撤销
     *
     * @param unknown_type $code
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $orgPosSeq
     * @param unknown_type $userId
     *
     * @return boolean
     */
    private function sendCodeToCancel(
            $code,
            $posId,
            $posSeq,
            $orgPosSeq,
            $userId,
            $encodeType = self::POS_ENCODE_TYPE_CLEARLY
    ) {
        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";

            return false;
        }

        if ($posInfo['work_key'] != $this->posSession->getWorkKey()) {
            $this->errMsg = "终端辅助密钥不一致！";

            return false;
        }

        /* 请求参数 */
        $reqArr = array(
                "request_type"   => self::$requestTypes["1"],
                "pos_id"         => $posId,
                "pos_seq"        => $posSeq,
                "user_id"        => $userId,
                "cancel_request" => array(
                        "org_pos_seq" => $orgPosSeq,
                        "valid_info"  => $code,
                        "encode_type" => $encodeType,
                ),
        );

        // 计算密钥
        $inputMacStr = '' . $posId . $userId . $encodeType . $code . $orgPosSeq . $posSeq;
        // 请求远程验证
        $reqService = D('RemoteRequest', 'Service');
        $arr        = $reqService->requestPosTransServ($reqArr, $inputMacStr, $posInfo['master_key'],
                $posInfo['work_key']);

        // 过滤掉打印文本后面的活动号和活动名
        if (isset($arr['addition_info']['print_text'])) {
            include_once 'Common/Function/Function.php';
            $arr['addition_info']['print_text'] = get_print_text($arr['addition_info']['print_text']);
            // exit($arr['addition_info']['print_text']);
        }

        $this->respInfo = $arr;
        if ($arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = $arr['result']['comment'];

            return false;
        }
    }

    /**
     * 向支撑提交原始流水流水号用于冲正
     *
     * @param unknown_type $code
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $orgPosSeq
     * @param unknown_type $userId
     *
     * @return boolean
     */
    private function sendCodeToReversal(
            $code,
            $posId,
            $posSeq,
            $orgPosSeq,
            $userId,
            $encodeType = self::POS_ENCODE_TYPE_CLEARLY
    ) {
        /* 请求参数 */
        $reqArr = array(
                "request_type"     => self::$requestTypes["2"],
                "pos_id"           => $posId,
                "pos_seq"          => $posSeq,
                "user_id"          => $userId,
                "reversal_request" => array(
                        "org_pos_seq" => $orgPosSeq,
                        "valid_info"  => $code,
                        "encode_type" => $encodeType,
                ),
        );

        // 计算密钥
        $inputMacStr = '' . $posId . $userId . $encodeType . $code . $orgPosSeq . $posSeq;
        // 请求远程验证
        $reqService     = D('RemoteRequest', 'Service');
        $arr            = $reqService->requestPosTransServ($reqArr, $inputMacStr, $posInfo['master_key'],
                $posInfo['work_key']);
        $this->respInfo = $arr;
        if ($arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = $arr['result']['comment'];

            return false;
        }
    }

    /**
     * 将撤销返回的报文转成流水数据
     *
     * @param unknown_type $respInfo
     * @param unknown_type $code
     * @param unknown_type $orgPosSeq
     *
     * @return array
     */
    private function cancelRespInfoToFlowData(
            $respInfo,
            $code,
            $orgPosSeq,
            $orgBatchNo,
            $orgBatchDesc,
            $orgExchangeAMT,
            $orgPhoneNo,
            $userId,
            $userName,
            $nodeId
    ) {
        $flowInfo = array(
                "pos_id"        => $respInfo['pos_id'],
                "pos_seq"       => $respInfo['pos_seq'],
                "trans_type"    => array_search($respInfo['response_type'], self::$responseTypes),
                "status"        => $respInfo['result']['id'] == "0000" ? "0" : "1",
                "exchange_amt"  => $orgExchangeAMT,
                "trans_time"    => date('YmdHis'),
                "batch_desc"    => empty($respInfo['addition_info']['batch_desc']) ? $orgBatchDesc : $respInfo['addition_info']['batch_desc'],
                "batch_no"      => empty($respInfo['addition_info']['batch_no']) ? $orgBatchNo : $respInfo['addition_info']['batch_no'],
                "phone_no"      => empty($respInfo['addition_info']['phone_no']) ? $orgPhoneNo : $respInfo['addition_info']['phone_no'],
                "org_posseq"    => $orgPosSeq,
                "ret_code"      => $respInfo['result']['id'],
                "ret_desc"      => $respInfo['result']['comment'],
                "user_id"       => $userId,
                "user_name"     => $userName,
                "assistant_des" => md5($code),
                "assistant_no"  => substr($code, -4),
                "print_text"    => $respInfo['addition_info']['print_text'],
                "flow_status"   => 1,  // 执行状态：0: 执行中；1:返回完成
                "node_id"       => $nodeId,
        );

        return $flowInfo;
    }

    /**
     * 将冲正返回的报文转成流水数据
     *
     * @param unknown_type $respInfo
     * @param unknown_type $code
     * @param unknown_type $orgPosSeq
     *
     * @return array
     */
    private function reversalRespInfoToFlowData(
            $respInfo,
            $code,
            $orgPosSeq,
            $userId,
            $userName,
            $nodeId
    ) {
        $flowInfo = array(
                "pos_id"        => $respInfo['pos_id'],
                "pos_seq"       => $respInfo['pos_seq'],
                "trans_type"    => array_search($respInfo['response_type'], self::$responseTypes),
                "status"        => $respInfo['result']['id'] == "0000" ? "0" : "1",
                "exchange_amt"  => '',
                "trans_time"    => date('YmdHis'),
                "batch_desc"    => empty($respInfo['addition_info']['batch_desc']) ? '' : $respInfo['addition_info']['batch_desc'],
                "batch_no"      => empty($respInfo['addition_info']['batch_no']) ? '' : $respInfo['addition_info']['batch_no'],
                "phone_no"      => empty($respInfo['addition_info']['phone_no']) ? '' : $respInfo['addition_info']['phone_no'],
                "org_posseq"    => $orgPosSeq,
                "ret_code"      => $respInfo['result']['id'],
                "ret_desc"      => $respInfo['result']['comment'],
                "user_id"       => $userId,
                "user_name"     => $userName,
                "assistant_des" => '',
                "assistant_no"  => '',
                "print_text"    => '',
                "flow_status"   => 1,  // 执行状态：0: 执行中；1:返回完成
                "node_id"       => $nodeId,
        );

        return $flowInfo;
    }

    /**
     * 向支撑提交对账
     *
     * @param unknown_type $posId       终端号
     * @param unknown_type $posSeq      终端流水号
     * @param unknown_type $userId      用户ID
     * @param unknown_type $settleBatch 对账流水号
     * @param unknown_type $reversalAmt 验证金额
     * @param unknown_type $reversalNum 验证次数
     * @param unknown_type $cancelAmt   撤销金额
     * @param unknown_type $cancelNum   撤销次数
     * @param string       $encodeType  mac验证辅助
     *
     * @return boolean
     */
    private function sendPosSettle(
            $posId,
            $posSeq,
            $userId,
            $settleBatch,
            $reversalAmt,
            $reversalNum,
            $cancelAmt,
            $cancelNum,
            $encodeType = '003'
    ) {
        $validInfo   = md5(date("YmdHis"));
        $custom1     = str_pad($reversalAmt, 10, '0', STR_PAD_LEFT) . str_pad($reversalNum, 6, '0',
                        STR_PAD_LEFT) . str_pad($cancelAmt, 10, '0', STR_PAD_LEFT) . str_pad($cancelNum, 6, '0',
                        STR_PAD_LEFT);
        $custom1_len = strlen($custom1);
        $arr         = array(
                "request_type"   => self::$requestTypes["4"],
                "pos_id"         => $posId,
                "pos_seq"        => $posSeq,
                "user_id"        => $userId,
                "settle_request" => array(
                        "settle_batch" => $settleBatch,
                        "custom1"      => $custom1,
                        "custom1_len"  => $custom1_len,
                        "valid_info"   => $validInfo,
                        "encode_type"  => $encodeType,
                ),
        );
        include_once 'Common/Document/Xml.class.php';
        $xml = new Xml(XML::ENCODE_TYPE_GBK, false);
        $xml->setXmlRoot('<business_trans version="1.0" >');
        $xml->setXmlFoot('</business_trans>');
        $str = $xml->getXMLFromArray($arr);

        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";

            return false;
        }

        include_once 'Common/Session/PosSession.class.php';
        $posSession = new PosSession();
        if ($posInfo[PosSession::SESSION_WORKKEY_KEY_NAME] != $posSession->getWorkKey()) {
            $this->errMsg = "终端辅助密钥不一致！";

            return false;
        }

        include_once "Common/Document/WebPos3Des.class.php";
        $mObj        = new WebPos3Des();
        $inputMacStr = '' . $posId . $posSeq . $userId . $settleBatch . $encodeType . $validInfo;
        $macStr      = $mObj->trans_mac($inputMacStr, $posInfo['MASTER_KEY'], $posInfo['WORK_KEY']);

        include_once 'Common/Log/SysLog.class.php';
        $log = new SysLog(__FILE__);
        $log->log('trans_mac:' . $inputMacStr . '~' . $posInfo['MASTER_KEY'] . '~' . $posInfo['WORK_KEY']);

        include_once 'Common/Queue/MQFactory.class.php';
        $mq      = MQFactory::newInstance();
        $sendStr = "xml={$str}&mac={$macStr}";
        $mq->write($sendStr . "\0");
        $str = $mq->read();

        $arr = $xml->getArrayFromXmlNoRoot($str); // print_r($arr);exit;

        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '对账出错!';

            return false;
        }
    }

    /**
     * 将对账返回的报文转成流水数据
     *
     * @param array $respInfo 返回报文
     *
     * @return array
     */
    private function settleRespInfoToFlowData($respInfo) {
        $flowInfo = array(
                "pos_id"        => $respInfo['pos_id'],
                "pos_seq"       => $respInfo['pos_seq'],
                "settle_status" => $respInfo['settle_response']['settle_status'],
                "trans_type"    => array_search($respInfo['response_type'], self::$responseTypes),
                "status"        => $respInfo['result']['id'] == "0000" ? "0" : "1",
                "trans_time"    => date('YmdHis'),
                "ret_code"      => $respInfo['result']['id'],
                "ret_desc"      => $respInfo['result']['comment'],
                "flow_status"   => 1,
        ); // 执行状态：0: 执行中；1:返回完成

        return $flowInfo;
    }

    /**
     * 向支撑提交批上送
     *
     * @param string $posId        终端号
     * @param string $posSeq       终端流水号
     * @param string $userId       用户ID
     * @param string $settleBatch  终端对账批次号
     * @param string $settleStatus 终端状态
     *
     * @return boolean
     */
    private function sendBatchUp(
            $posId,
            $posSeq,
            $userId,
            $settleBatch,
            $encodeType = '003'
    ) {
        $validInfo = md5(date("YmdHis"));
        $arr       = array(
                "request_type"     => self::$requestTypes["5"],
                "pos_id"           => $posId,
                "pos_seq"          => $posSeq,
                "user_id"          => $userId,
                "batch_up_request" => array(
                        "settle_batch" => $settleBatch,
                        "file_name"    => $settleBatch . $posSeq,
                        "custom1"      => 'webposbatchupfile',
                        "custom1_len"  => strlen('webposbatchupfile'),
                        "file_type"    => '1',
                        "file_offset"  => '0',
                        "valid_info"   => $validInfo,
                        "encode_type"  => $encodeType,
                ),
        );
        include_once 'Common/Document/Xml.class.php';
        $xml = new Xml(XML::ENCODE_TYPE_GBK, false);
        $xml->setXmlRoot('<business_trans version="1.0" >');
        $xml->setXmlFoot('</business_trans>');
        $str = $xml->getXMLFromArray($arr);

        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";

            return false;
        }

        include_once 'Common/Session/PosSession.class.php';
        $posSession = new PosSession();
        if ($posInfo[PosSession::SESSION_WORKKEY_KEY_NAME] != $posSession->getWorkKey()) {
            $this->errMsg = "终端辅助密钥不一致！";

            return false;
        }

        include_once "Common/Document/WebPos3Des.class.php";
        $mObj        = new WebPos3Des();
        $inputMacStr = '' . $posId . $posSeq . $userId . $settleBatch . $encodeType . $validInfo;
        $macStr      = $mObj->trans_mac($inputMacStr, $posInfo['MASTER_KEY'], $posInfo['WORK_KEY']);

        include_once 'Common/Log/SysLog.class.php';
        $log = new SysLog(__FILE__);
        $log->log('trans_mac:' . $inputMacStr . '~' . $posInfo['MASTER_KEY'] . '~' . $posInfo['WORK_KEY']);

        include_once 'Common/Queue/MQFactory.class.php';
        $mq      = MQFactory::newInstance();
        $sendStr = "xml={$str}&mac={$macStr}";
        $mq->write($sendStr . "\0");
        $str = $mq->read();

        $arr = $xml->getArrayFromXmlNoRoot($str); // print_r($arr);exit;

        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '批上送出错!';

            return false;
        }
    }

    /**
     * 向支撑提交批上送结束
     *
     * @param string $posId        终端号
     * @param string $posSeq       终端流水号
     * @param string $userId       用户ID
     * @param string $settleBatch  终端对账批次号
     * @param string $settleStatus 终端状态
     *
     * @return boolean
     */
    private function sendBatchUpEnd(
            $posId,
            $posSeq,
            $userId,
            $settleBatch,
            $encodeType = '003'
    ) {
        $validInfo = md5(date("YmdHis"));
        // 创建批上送文件
        $myfile = fopen('./Upload/webposbatchupfile', 'w');
        fwrite($myfile, 'webposbatchupfile');
        fclose($myfile);

        $arr = array(
                "request_type"         => self::$requestTypes["6"],
                "pos_id"               => $posId,
                "pos_seq"              => $posSeq,
                "user_id"              => $userId,
                "batch_up_end_request" => array(
                        "settle_batch" => $settleBatch,
                        "file_name"    => $settleBatch . $posSeq,
                        "custom1"      => md5_file('./Upload/webposbatchupfile'),
                        "custom1_len"  => strlen(md5_file('./Upload/webposbatchupfile')),
                        "file_type"    => '1',
                        "valid_info"   => $validInfo,
                        "encode_type"  => $encodeType,
                ),
        );
        include_once 'Common/Document/Xml.class.php';
        $xml = new Xml(XML::ENCODE_TYPE_GBK, false);
        $xml->setXmlRoot('<business_trans version="1.0" >');
        $xml->setXmlFoot('</business_trans>');
        $str = $xml->getXMLFromArray($arr);

        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";

            return false;
        }

        include_once 'Common/Session/PosSession.class.php';
        $posSession = new PosSession();
        if ($posInfo[PosSession::SESSION_WORKKEY_KEY_NAME] != $posSession->getWorkKey()) {
            $this->errMsg = "终端辅助密钥不一致！";

            return false;
        }

        include_once "Common/Document/WebPos3Des.class.php";
        $mObj        = new WebPos3Des();
        $inputMacStr = '' . $posId . $posSeq . $userId . $settleBatch . $encodeType . $validInfo;
        $macStr      = $mObj->trans_mac($inputMacStr, $posInfo['MASTER_KEY'], $posInfo['WORK_KEY']);

        include_once 'Common/Log/SysLog.class.php';
        $log = new SysLog(__FILE__);
        $log->log('trans_mac:' . $inputMacStr . '~' . $posInfo['MASTER_KEY'] . '~' . $posInfo['WORK_KEY']);

        include_once 'Common/Queue/MQFactory.class.php';
        $mq      = MQFactory::newInstance();
        $sendStr = "xml={$str}&mac={$macStr}";
        $mq->write($sendStr . "\0");
        $str = $mq->read();

        $arr = $xml->getArrayFromXmlNoRoot($str); // print_r($arr);exit;

        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '批上送结束出错!';

            return false;
        }
    }

    /**
     * 电话验证记录下载
     *
     * @param string $posId        终端号
     * @param string $posSeq       终端流水号
     * @param string $userId       用户ID
     * @param string $settleBatch  终端对账批次号
     * @param string $fileOffset   下载文件偏移位
     * @param string $settleStatus 终端状态
     *
     * @return boolean
     */
    private function sendBatchDown(
            $posId,
            $posSeq,
            $userId,
            $settleBatch,
            $fileOffset = 0,
            $encodeType = '003'
    ) {
        $validInfo = md5(date("YmdHis"));
        $arr       = array(
                "request_type"       => self::$requestTypes["7"],
                "pos_id"             => $posId,
                "pos_seq"            => $posSeq,
                "user_id"            => $userId,
                "batch_down_request" => array(
                        "settle_batch" => $settleBatch,
                        "file_name"    => $posId . $settleBatch,
                        "file_offset"  => $fileOffset,
                        "file_type"    => 7,
                        "valid_info"   => $validInfo,
                        "encode_type"  => $encodeType,
                ),
        );
        include_once 'Common/Document/Xml.class.php';
        $xml = new Xml(XML::ENCODE_TYPE_GBK, false);
        $xml->setXmlRoot('<business_trans version="1.0" >');
        $xml->setXmlFoot('</business_trans>');
        $str = $xml->getXMLFromArray($arr);

        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";

            return false;
        }

        include_once 'Common/Session/PosSession.class.php';
        $posSession = new PosSession();
        if ($posInfo[PosSession::SESSION_WORKKEY_KEY_NAME] != $posSession->getWorkKey()) {
            $this->errMsg = "终端辅助密钥不一致！";

            return false;
        }

        include_once "Common/Document/WebPos3Des.class.php";
        $mObj        = new WebPos3Des();
        $inputMacStr = '' . $posId . $posSeq . $userId . $settleBatch . $encodeType . $validInfo;
        $macStr      = $mObj->trans_mac($inputMacStr, $posInfo['MASTER_KEY'], $posInfo['WORK_KEY']);

        include_once 'Common/Log/SysLog.class.php';
        $log = new SysLog(__FILE__);
        $log->log('trans_mac:' . $inputMacStr . '~' . $posInfo['MASTER_KEY'] . '~' . $posInfo['WORK_KEY']);

        include_once 'Common/Queue/MQFactory.class.php';
        $mq      = MQFactory::newInstance();
        $sendStr = "xml={$str}&mac={$macStr}";
        $mq->write($sendStr . "\0");
        $str = $mq->read();

        $arr = $xml->getArrayFromXmlNoRoot($str); // print_r($arr);//exit;

        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            // 保存批下载文件的内容
            $posPhoneData = fopen('./Download/' . $posId . $settleBatch, 'a');
            // 判断下载的文件是否分解成多个定制域下载
            if ($arr['batch_down_response']['custom2'] == "NULL") {
                $hex_content = $arr['batch_down_response']['custom1'];
            } else {
                $hex_content = $arr['batch_down_response']['custom1'] . $arr['batch_down_response']['custom2'];
            }
            // 对于下载的16进制asc码进行解码
            $downFileContent = pack("H" . strlen($hex_content), $hex_content);
            // 写入文件
            fwrite($posPhoneData, $downFileContent);
            fclose($posPhoneData);

            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '电话验证记录下载出错!';

            return false;
        }
    }

    /**
     * 电话验证记录下载结束
     *
     * @param string $posId        终端号
     * @param string $posSeq       终端流水号
     * @param string $userId       用户ID
     * @param string $settleBatch  终端对账批次号
     * @param string $settleStatus 终端状态
     *
     * @return boolean
     */
    private function sendBatchDownEnd(
            $posId,
            $posSeq,
            $userId,
            $settleBatch,
            $encodeType = '003'
    ) {
        $validInfo = md5(date("YmdHis"));
        $arr       = array(
                "request_type"           => self::$requestTypes["8"],
                "pos_id"                 => $posId,
                "pos_seq"                => $posSeq,
                "user_id"                => $userId,
                "batch_down_end_request" => array(
                        "settle_batch" => $settleBatch,
                        "file_name"    => $posId . $settleBatch,
                        "custom1"      => md5_file('./Download/' . $posId . $settleBatch),
                        "file_type"    => 7,
                        "valid_info"   => $validInfo,
                        "encode_type"  => $encodeType,
                ),
        );
        include_once 'Common/Document/Xml.class.php';
        $xml = new Xml(XML::ENCODE_TYPE_GBK, false);
        $xml->setXmlRoot('<business_trans version="1.0" >');
        $xml->setXmlFoot('</business_trans>');
        $str = $xml->getXMLFromArray($arr);

        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";

            return false;
        }

        include_once 'Common/Session/PosSession.class.php';
        $posSession = new PosSession();
        if ($posInfo[PosSession::SESSION_WORKKEY_KEY_NAME] != $posSession->getWorkKey()) {
            $this->errMsg = "终端辅助密钥不一致！";

            return false;
        }

        include_once "Common/Document/WebPos3Des.class.php";
        $mObj        = new WebPos3Des();
        $inputMacStr = '' . $posId . $posSeq . $userId . $settleBatch . $encodeType . $validInfo;
        $macStr      = $mObj->trans_mac($inputMacStr, $posInfo['MASTER_KEY'], $posInfo['WORK_KEY']);

        include_once 'Common/Log/SysLog.class.php';
        $log = new SysLog(__FILE__);
        $log->log('trans_mac:' . $inputMacStr . '~' . $posInfo['MASTER_KEY'] . '~' . $posInfo['WORK_KEY']);

        include_once 'Common/Queue/MQFactory.class.php';
        $mq      = MQFactory::newInstance();
        $sendStr = "xml={$str}&mac={$macStr}";
        $mq->write($sendStr . "\0");
        $str = $mq->read();

        $arr = $xml->getArrayFromXmlNoRoot($str); // print_r($arr);exit;

        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '电话验证记录下载结束出错!';

            return false;
        }
    }

    public function getErrMsg() {
        return $this->errMsg;
    }

    public function getErrInfo() {
        return $this->errInfo;
    }

    public function getPrintText() {
        return $this->printText;
    }

    public function createPos($posInfo) {
        return $this->dao->save($posInfo);
    }

    public function updatePos($posId, $posName) {
        return $this->dao->where("POS_ID = '" . $posId . "'")->save(array(
                "pos_name" => $posName,
        ));
    }

    public function updatePosInfo($posId, $array) {
        return $this->dao->where("POS_ID = '" . $posId . "'")->save($array);
    }

    /* 获取终端信息 */
    public function getPos($posId) {
        $posInfo = $this->dao->where("POS_ID='$posId'")->find();

        return $posInfo;
    }
}