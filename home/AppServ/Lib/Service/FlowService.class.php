<?php

/**
 * �ն���ˮ����
 *
 * @author wtr
 */
class FlowService {

    /**
     * ��ˮ����Ϊ��������֤
     *
     * @var unknown_type
     */
    const FLOW_TYPE_VERIFY = "0";

    /**
     * ��ˮ����Ϊ����
     *
     * @var unknown_type
     */
    const FLOW_TYPE_CANCEL = "1";

    /**
     * ��ˮ����Ϊ�绰��֤
     *
     * @var unknown_type
     */
    const FLOW_TYPE_PHONE = "3";

    /**
     * ���׳ɹ�
     *
     * @var unknown_type
     */
    const FLOW_STATUS_OK = "0";

    /**
     * ����ʧ��
     *
     * @var unknown_type
     */
    const FLOW_STATUS_FAIL = "1";

    /**
     * ���׳���
     *
     * @var unknown_type
     */
    const FLOW_STATUS_REVERSAL = "2";

    /**
     * δ����
     *
     * @var unknown_type
     */
    const FLOW_CANCELED_YES = "1";

    /**
     * �ѳ���
     *
     * @var unknown_type
     */
    const FLOW_CANCELED_NO = "0";

    private $dao;

    private $recCount = 15;

    public function __construct() {
        $this->dao = D('TposTrace');
    }

    /**
     * ����ÿҳ��¼��
     *
     * @param unknown_type $count
     */
    public function setRecCount($count = 15) {
        $this->recCount = $count;
    }

    /**
     * д��֤��Ԥ����ˮ ���뷢��֧������֮ǰ��Ԥ�ȼ�¼һ����ˮ��
     */
    public function writeBeginFlow($flowInfo) {
        $rs = $this->dao->add($flowInfo);

        return $rs;
    }

    /**
     * ������֤��ˮ
     *
     * @param unknown_type $flowInfo
     *
     * @return boolean
     */
    public function writeVerifyFlow($flowInfo) {
        // ����Ԥ�ȼ�¼����ˮ
        $pos_seq = $flowInfo['pos_seq'];
        if ($pos_seq) {
            $where = " pos_seq = '$pos_seq' ";
            $rs    = $this->dao->where($where)->save($flowInfo);
            if ($rs && $flowInfo["status"] === self::FLOW_STATUS_OK) {
                // ����ͳ����Ϣ
                $sdao = D('TposDayCount');
                $sdao->writeStat($flowInfo['pos_id'], self::FLOW_TYPE_VERIFY, $flowInfo['exchange_amt'],
                        $flowInfo['batch_no'], $flowInfo['batch_desc'], '', $flowInfo['node_id']);

                // �����ն˶�������
                /* ����Ƕ����߼� */
            }

            return $rs;
        } else {
            return false;
        }
    }

    /**
     * д����������ˮ
     *
     * @param unknown_type $orgFlowId
     * @param unknown_type $flowInfo
     *
     * @return boolean
     */
    public function writeCancelFlow($orgFlowId, $flowInfo) {
        // �ȸ���ԭ������ˮ�ĳ���״̬Ϊ�ѳ���
        // �ٲ��ڳ���������ˮ��¼
        $set   = array(
                "is_canceled"    => self::FLOW_CANCELED_YES,
                "cancel_pos_seq" => $flowInfo['pos_seq'],
        );
        $where = "id = '{$orgFlowId}'";
        $where .= " and trans_type= '" . self::FLOW_TYPE_VERIFY . "'";
        $where .= " and status = '" . self::FLOW_STATUS_OK . "'";
        $where .= " and is_canceled = '" . self::FLOW_CANCELED_NO . "'";

        // �ж��Ƿ����ɹ�������ɹ�����Դ��ˮ
        if ($flowInfo['status'] == "0") {
            $rs = $this->dao->where($where)->save($set);
            if (!$rs) {
                return false;
            }
        }

        $pos_seq = $flowInfo['pos_seq'];
        if ($pos_seq) {
            $where = " pos_seq = '$pos_seq' ";

            // ����Ԥ��¼�ĳ�����ˮ
            $rs = $this->dao->where($where)->save($flowInfo);
            if ($rs && $flowInfo["status"] === self::FLOW_STATUS_OK) {
                // ����ҵ��ͳ����Ϣ
                $sdao = D('TposDayCount');
                $sdao->writeStat($flowInfo['pos_id'], self::FLOW_TYPE_CANCEL, $flowInfo['exchange_amt'],
                        $flowInfo['batch_no'], $flowInfo['batch_desc'], '', $flowInfo['node_id']);
                // $stat_id = $sdao->getStatId($flowInfo['pos_id'],
                // date('Y-m-d'), $flowInfo['batch_no']);
                // $settle_id = $psdao->getSettleId($flowInfo['pos_id']);

                // if(!empty($stat_id) || !empty($settle_id)){
                // ��ҵ��ͳ��ID���õ�ҵ����ˮ��
                // $this->dao->updateFlow(array("STAT_ID" =>
                // intval($stat_id),"SETTLE_ID" => intval($settle_id)),$where);
                // }
            }

            return $rs;
        } else {
            return false;
        }
    }

    /**
     * ���³���������ˮ
     *
     * @param unknown_type $orgFlowId    ԭʼ��ˮ��
     * @param unknown_type $orgTransType ԭʼ��������
     * @param unknown_type $flowInfo
     *
     * @return boolean
     */
    public function writeReversalFlow($orgFlowId, $orgTransType, $flowInfo) {
        // ����Ԥ�ǵĳ�����ˮ
        // �ȸ���ԭ������֤״̬Ϊ����
        // ���ԭ���������ǳ��������ѯԭ������ˮ������Դ��ˮ������״̬�ĳ�δ��������֤״̬��Ϊδʹ�ã�����Ҫ����

        // �ж��Ƿ�����ɹ�������ɹ�����Դ��ˮ
        if ($flowInfo['status'] == "0") {
            $where = "id = '{$orgFlowId}'";
            $where .= " and trans_type= '" . $orgTransType . "'";
            // ����ԭ������ˮ��״̬Ϊ�ѳ�������
            $set = array(
                // "is_canceled" => self::FLOW_CANCELED_NO, /*���ﲻ��*/
                    "status" => self::FLOW_STATUS_REVERSAL,
            );
            $rs  = $this->dao->where($where)->save($set);
            if ($rs === false) {
                return false;
            }

            // ���ԭ�����ǳ�������Ҫ����ԭԭ��ˮ״̬Ϊ δ����
            /*
             * if($orgTransType == '1'){ $orgFlowInfo =
             * $this->getFlowByFlowId($orgFlowId); $firstFlowId =
             * $orgFlowInfo['id']; $set = array( "is_canceled" =>
             * self::FLOW_CANCELED_NO, "status" => self::FLOW_STATUS_OK, );
             * $where = "id = '{$firstFlowId}'"; $where.= " and trans_type=
             * '0'"; $rs = $this->dao->where($where)->save($set); if($rs ===
             * false){ return false; } }
             */

            // �ⲿ��Ҳ����Ҫ����
            return true;
        }

        $pos_seq = $flowInfo['pos_seq'];
        if ($pos_seq) {
            $where = " pos_seq = '$pos_seq' ";

            // ����Ԥ��¼�ĳ�����ˮ
            $rs = $this->dao->where($where)->save($flowInfo);
            if ($rs && $flowInfo["status"] === self::FLOW_STATUS_OK) {
                // ����ҵ��ͳ����Ϣ
                $sdao = D('TposDayCount');
                $sdao->writeStat($flowInfo['pos_id'], self::FLOW_TYPE_CANCEL, $flowInfo['exchange_amt'],
                        $flowInfo['batch_no'], $flowInfo['batch_desc'], '', $flowInfo['node_id']);
                // $stat_id = $sdao->getStatId($flowInfo['pos_id'],
                // date('Y-m-d'), $flowInfo['batch_no']);
                // $settle_id = $psdao->getSettleId($flowInfo['pos_id']);

                // if(!empty($stat_id) || !empty($settle_id)){
                // ��ҵ��ͳ��ID���õ�ҵ����ˮ��
                // $this->dao->updateFlow(array("STAT_ID" =>
                // intval($stat_id),"SETTLE_ID" => intval($settle_id)),$where);
                // }
            }

            return $rs;
        } else {
            return false;
        }
    }

    /**
     * д���˽�����ˮ
     *
     * @param unknown_type $settleInfo ������Ϣ
     * @param unknown_type $flowInfo   ��ˮ��Ϣ
     *
     * @return boolean
     */
    public function writeSettleFlow($settleInfo, $flowInfo) {
        // ��ʱ��������
        return false;
    }

    /**
     * ��ѯ��ˮ
     *
     * @param String $code
     * @param String $posId
     * @param String $transType
     * @param String $phoneNo
     * @param String $posId
     * @param int    $beginTime
     * @param int    $endTime
     * @param int    $page
     *
     * @return array
     */
    public function queryFlows(
            $posId,
            $transType = "",
            $assCode = "",
            $phoneNo = "",
            $beginTime = "",
            $endTime = "",
            $page = 1,
            $status = "",
            $pos_seq = "",
            $user_id = ""
    ) {
        $condition = array(
                "pos_id"     => $posId,
                "trans_time" => array(
                        'BETWEEN',
                        "$beginTime,$endTime",
                ),
        );
        if ($transType !== "") {
            $condition["trans_type"] = $transType;
        }

        if ($assCode !== "") {
            $condition["assistant_des"] = $assCode;
        }

        if ($phoneNo !== "") {
            $condition["phone_no"] = $phoneNo;
        }
        if ($status != "") {
            $condition["status"] = $status;
        }
        if ($pos_seq != "") {
            $condition['pos_seq'] = $pos_seq;
        }
        if ($user_id) {
            $condition['user_id'] = $user_id;
        }

        return $this->dao->where($condition)->limit(($page - 1) * $this->recCount, $this->recCount)->select();
    }

    public function countFlows(
            $posId,
            $transType = "",
            $assCode = "",
            $phoneNo = "",
            $beginTime = "",
            $endTime = "",
            $page = 1,
            $status = "",
            $pos_seq = "",
            $user_id = "",
            $flow_status = ""
    ) {
        if (!$beginTime) {
            $beginTime = '0';
        }
        if (!$endTime) {
            $endTime = '99999999999999';
        }
        $condition = array(
                "pos_id"     => $posId,
                "trans_time" => array(
                        'between',
                        array(
                                $beginTime,
                                $endTime,
                        ),
                ),
        );

        if ($transType !== "") {
            $condition["trans_type"] = $transType;
        }
        if ($assCode !== "") {
            $condition["assistant_des"] = $assCode;
        }

        if ($phoneNo !== "") {
            $condition["phone_no"] = $phoneNo;
        }
        if ($status != "") {
            $condition["status"] = $status;
        }
        if ($pos_seq) {
            $condition['pos_seq'] = $pos_seq;
        }
        if ($user_id) {
            $condition['user_id'] = $user_id;
        }
        if ($flow_status != '') {
            $condition['flow_status'] = $flow_status;
        }
        $count = $this->dao->where($condition)->count();

        return $count;
    }

    public function countSearchFlows(
            $assCode,
            $assCodeHex,
            $posId,
            $beginTime = "",
            $endTime = "",
            $mobile = ""
    ) {
        $condition = array(
                "pos_id"      => $posId,
                "trans_time"  => array(
                        'BETWEEN',
                        "$beginTime,$endTime",
                ),
                "trans_type"  => "0",
                "status"      => "0",
                "is_canceled" => "0",
        );
        if ($mobile != "") {
            $condition['phone_no'] = $mobile;
        }
        if ($assCode != "") {
            $condition['assistant_no_md5'] = md5($assCode); // ������md5
        }
        if ($assCodeHex != "") {
            $condition['assistant_des'] = md5($assCodeHex); // ����md5s
        }

        return $this->dao->where($condition)->count();
    }

    /**
     * ��ѯ����ɹ�����ˮ
     *
     * @param String $code
     * @param String $posId
     * @param int    $beginTime
     * @param int    $endTime
     * @param int    $page
     *
     * @return array
     */
    public function searchFlows(
            $assCode,
            $assCodeHex,
            $posId,
            $beginTime = "",
            $endTime = "",
            $page = 1,
            $mobile = ""
    ) {
        $condition = array(
                "pos_id"      => $posId,
                "trans_time"  => array(
                        'BETWEEN',
                        "$beginTime,$endTime",
                ),
                "trans_type"  => "0",
                "status"      => "0",
                "is_canceled" => "0",
        );
        if ($mobile != "") {
            $condition['phone_no'] = $mobile;
        }
        if ($assCode != "") {
            $condition['assistant_no_md5'] = md5($assCode); // ������md5
        }
        if ($assCodeHex != "") {
            $condition['assistant_des'] = md5($assCodeHex); // ����md5s
        }

        return $this->dao->where($condition)->limit(($page - 1) * $this->recCount, $this->recCount)->select();
    }

    /**
     * ���������ź�PosId��ȡ��ˮ��¼
     *
     * @param String $flowId
     *
     * @return array
     */
    public function getFlowByFlowId($flowId) {
        return $this->dao->find($flowId);
    }
}

