<?php

/**
 * 修改店铺分组行为
 * @author Jeff.Liu<liuwy@imageco.com.cn>
 * Class UpdateStoreGroupBehavior
 */
class UpdateStoreGroupBehavior extends Behavior
{

    /**
     * @var RemoteRequestService
     */
    private $RemoteRequestService;

    public function run(&$params)
    {
        $operate = isset($params['operate']) ? $params['operate'] : '';
        switch ($operate) {
            case 'groupSet':
                return $this->groupSet($params);
            case 'storeEdit':
                return $this->storeEdit($params);
            case 'editGroup':
                return $this->editGroup($params);
            default:
                return $this->success('未知操作');
        }
    }

    /**
     * 修改分组
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $params
     *
     * @return array
     */
    public function editGroup($params)
    {
        $nodeId  = isset($params['node_id']) ? $params['node_id'] : '';
        $groupId = isset($params['groupId']) ? $params['groupId'] : '';

        $cancelPosIdList = [];
        $bindedPosIdList = [];

        if ($params['delStoreIdList']) {
            $where             = [];
            $where['store_id'] = ['in', $params['delStoreIdList']];
            $where['node_id']  = $nodeId;
            $posIdList         = M('tpos_info')->field('pos_id')->where($where)->select();
            file_debug(M('tpos_info')->_sql(), 'delStoreIdList sql', 'request.log');
            $finalPosIdList = [];
            if ($posIdList) {
                foreach ($posIdList as $item) {
                    $finalPosIdList[] = $item['pos_id'];
                }
            }
            $cancelPosIdList = $finalPosIdList;
        }
        if ($params['addStoreIdList']) {
            $where             = [];
            $where['store_id'] = ['in', $params['addStoreIdList']];
            $where['node_id']  = $nodeId;
            $posIdList         = M('tpos_info')->field('pos_id')->where($where)->select();
            file_debug(M('tpos_info')->_sql(), 'addStoreIdList sql', 'request.log');
            $finalPosIdList = [];
            if ($posIdList) {
                foreach ($posIdList as $item) {
                    $finalPosIdList[] = $item['pos_id'];
                }
            }
            $bindedPosIdList = $finalPosIdList;
        }

        $where                        = [];
        $where['node_id']             = $nodeId;
        $where['store_group_id_list'] = ['like', '%,' . $groupId . ',%'];
        $accountList                  = M('tzfb_offline_pay_info')->where($where)->select();
        if ($accountList) {//需要通知支撑
            foreach ($accountList as $payInfo) {
                $ret = $this->notifyISS($cancelPosIdList, $payInfo, '0');
                if ($ret['status'] == 0) {
                    return $ret;
                }
                $ret = $this->notifyISS($bindedPosIdList, $payInfo, '1');
                if ($ret['status'] == 0) {
                    return $ret;
                }
            }
        }
        return $this->success('success');
    }

    /**
     * 修改店铺分组
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $params
     *
     * @return array
     */
    public function storeEdit($params)
    {
        $node_id      = isset($params['node_id']) ? $params['node_id'] : '';
        $old_group_id = isset($params['oldGroupId']) ? $params['oldGroupId'] : '';
        $new_group_id = isset($params['newGroupId']) ? $params['newGroupId'] : '';
        $storeId      = isset($params['store_id']) ? $params['store_id'] : '';

        $posIdList = M('tpos_info')->field('pos_id')->where(['store_id' => $storeId, 'node_id' => $node_id])->select();
        file_debug(M('tpos_info')->_sql(), 'tpos_info sql', 'request.log');
        $finalPosIdList = [];
        if ($posIdList) {
            foreach ($posIdList as $item) {
                $finalPosIdList[] = $item['pos_id'];
            }
        }

        //以前绑定的帐号 解绑  start
        $where['node_id']             = $node_id;
        $where['store_group_id_list'] = ['like', '%,' . $old_group_id . ',%'];
        $oldAccountList               = M('tzfb_offline_pay_info')->where($where)->select();
        if ($oldAccountList) {//需要通知支撑
            foreach ($oldAccountList as $payInfo) {
                $ret = $this->notifyISS($finalPosIdList, $payInfo, '0');
                if ($ret['status'] == 0) {
                    return $ret;
                }
            }
        }
        //以前绑定的帐号 解绑  end

        //绑定新的帐号  start
        $where['node_id']             = $node_id;
        $where['store_group_id_list'] = ['like', '%,' . $new_group_id . ',%'];
        $newAccountList               = M('tzfb_offline_pay_info')->where($where)->select();
        if ($newAccountList) {//需要通知支撑
            foreach ($newAccountList as $payInfo) {
                $ret = $this->notifyISS($finalPosIdList, $payInfo, '1');
                if ($ret['status'] == 0) {
                    return $ret;
                }
            }
        }
        //绑定新的帐号  end

        return $this->success('success');
    }

    /**
     * 设置分组信息
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $params
     *
     * @return array
     */
    public function groupSet($params)
    {
        $userId  = get_val($params, 'userId');
        $idList  = get_val($params, 'idList');
        $payInfo = M('tzfb_offline_pay_info')->where(array('id' => $userId))->find();
        if (empty($idList)) {
            $idList = [];
        }

        //通知支撑 start
        //清除取消选中的分组 start
        $storeGroupIdList = explode(',', $payInfo['store_group_id_list']);
        $originGroupList  = [];
        foreach ($storeGroupIdList as $item) {
            if ($item) {
                $originGroupList[] = $item;
            }
        }
        if ($originGroupList == $idList) {
            return $this->success("不需要更新");
        }
        if ($originGroupList) {
            $needCancelGroupList = array_diff($originGroupList, $idList);
            if ($needCancelGroupList) {
                $needCanceledposIdList = $this->getPosId($needCancelGroupList);
                $ret                   = $this->notifyISS($needCanceledposIdList, $payInfo, '0');
                if ($ret['status'] == 0) {
                    return $ret;
                }
            }
        }
        //清除取消选中的分组 end

        //设置选中的分组 start
        if ($idList) {
            $newBindedGroupList = array_diff($idList, $originGroupList);
            if ($newBindedGroupList) {
                $needBindedposIdList = $this->getPosId($newBindedGroupList);
                $ret                 = $this->notifyISS($needBindedposIdList, $payInfo, '1');
                if ($ret['status'] == 0) {
                    return $ret;
                }
            }
        }
        //设置选中的分组 end
        //通知支撑 end

        return $this->success('success');
    }

    private function notifyISS(array $posIdList, array $payInfo, $alipayFlag)
    {
        if ($posIdList) {
            $payType     = get_val($payInfo, 'pay_type');
            $token       = '';
            $frate       = '0.006';
            $realPayType = '';
            $version     = '1';
            if ($payType == 0 || $payType == 50) {//支付宝
                $applyData   = json_decode($payInfo['apply_data'], true);
                $token       = $applyData['app_auth_token'];
                $frate       = '0.0055';
                $realPayType = 0;
                $version     = '2.0';
            } else if ($payType == 1 || $payType == 51) { //微信
                $realPayType = 1;
            } else if ($payType == 2 || $payType == 52) {//翼支付
                $realPayType = 2;
            } else if ($payType == 5 || $payType == 55) {//qq钱包
                $realPayType = 3;//支撑就是3
            }
            $posIdStr = $this->generatePosIdStrByPosIdList($posIdList);
            $reqArray = array(
                    'PosAlipaySellerAccountSetReq' => array(
                            'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),
                            'PosID'         => $posIdStr,
                            'PartnerID'     => $payInfo['zfb_account'],//只有qq钱包和微信需要
                            'Md5Key'        => '',//不需要
                            'SellerID'      => $payInfo['account_pid'],//只有qq钱包和微信需要
                            'SellerEmail'   => $payInfo['account_pid'],//只有qq钱包和微信需要
                            'AlipayFlag'    => $alipayFlag,
                            'Frate'         => $frate,
                            'Brate'         => '0',
                            'PayType'       => $realPayType,
                            'Version'       => $version,
                            'Token'         => $token,
                    )
            );
            if (empty($this->RemoteRequestService)) {
                $this->RemoteRequestService = D('RemoteRequest', 'Service');
            }
            $resp_array = $this->RemoteRequestService->requestIssForImageco($reqArray);
            file_debug($reqArray, 'final $req_array', 'request.log');
            file_debug($resp_array, 'final $resp_array', 'request.log');
            $ret_msg = $resp_array['PosAlipaySellerAccountSetRes']['Status'];
            if (!$resp_array || ($ret_msg['StatusCode'] != '0000')) {
                return $this->error("通知支撑失败！原因:{$ret_msg['StatusText']}");
            }
        }
        return $this->success("通知支撑成功");
    }

    public function success($msg)
    {
        return ['status' => 1, 'msg' => $msg];
    }

    public function error($msg)
    {
        return ['status' => 0, 'msg' => $msg];
    }

    private function getPosListByGroupList($groupIdList)
    {
        foreach ($groupIdList as $index => $item) {
            $groupIdList[$index] = (int)$item;
        }
        $sql       = sprintf(
                'SELECT pos_id FROM `tgroup_store_relation` a 
                LEFT JOIN tpos_info b ON a.`store_id`  = b.`store_id`
                WHERE a.store_group_id in(%s)',
                implode(',', $groupIdList)
        );
        $list      = M()->query($sql);
        $posIdList = [];
        if (is_array($list)) {
            foreach ($list as $item) {
                $pos_id = $item['pos_id'];
                if ($pos_id) {
                    $posIdList[] = $pos_id;
                }
            }
        }
        return $posIdList;
    }

    private function getPosId($groupIdList)
    {
        return $this->getPosListByGroupList($groupIdList);
    }

    /**
     *
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $posIdList
     *
     * @return string
     */
    private function generatePosIdStrByPosIdList($posIdList)
    {
        if (is_scalar($posIdList)) {
            return $posIdList;
        } elseif (is_array($posIdList)) {
            return implode('|', $posIdList);
        }
        return '';
    }
}