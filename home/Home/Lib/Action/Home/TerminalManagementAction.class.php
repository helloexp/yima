<?php

/**
 * 旺财终端操作
 *
 * @author bao
 */
class TerminalManagementAction extends IndexAction {

    /**
     * 终端列表(接口)
     */
    public function index() {
    }

    /**
     * 开通服务
     */
    public function openServices() {
        // 判断用户是否够买了终端
        if ($this->terminalCount <= 0)
            $this->error('您还没有购买我们的旺财终端设备', 'Index/index');
        $chargeId = $this->_param('charge_id');
        if (is_null($chargeId))
            $this->error('参数错误');
            // 获取服务信息
        $chargeInfoModel = D('TchargeInfo');
        $servicesInfo = $chargeInfoModel->getChargeInfo($chargeId);
        if (! $servicesInfo)
            $this->error('无效服务');
        if ($this->isPost()) {
            // 判断服务的所属级别 1 商户 2 终端
            if ($servicesInfo['charge_level'] == '1') { // 开通商户服务(接口)
                
                $this->success('商户服务开通成功');
            } else {
                $posInfo = $this->_param('pos_info');
                if (empty($posInfo) || ! is_array($posInfo)) {
                    $this->error('请选择要开通服务的旺财终端');
                }
                foreach ($posInfo as $v) { // 开通终端服务(接口)
                }
                $this->success('旺财终端服务开通成功');
            }
            exit();
        } else {
            // 判断服务的所属级别 1 商户 2 终端
            if ($servicesInfo['charge_level'] == '2') {
                // 获取用户正常已激活的终端信息
                $terminalModel = D('TerminalManagement');
                $terminalList = $terminalModel->getTerminalList($this->nodeId, 
                    2);
                if ($terminalList) { // 去除本月和次月已经开通该服务的终端(接口)
                    foreach ($terminalList as $k => $v) {}
                } else {
                    $this->error('未找到符合条件的旺财终端');
                }
                $this->assign('terminalList', $terminalList);
            }
            $this->assign('chargeId', $chargeId);
            $this->assign('servicesInfo', $servicesInfo);
            $this->display();
        }
    }
}