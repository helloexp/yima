<?php

/**
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> @date 2015/08/28 Class ToolsAction
 */
class ToolsAction extends BaseAction {

    /**
     *
     * @var ToolsModel
     */
    public $ToolsModel;

    /**
     *
     * @var RemoteRequestService
     */
    public $RemoteRequestService;

    public function _initialize() {
        if ((function_exists('is_production') && is_production()) ||
             (C('PRODUCTION_FLAG'))) { // 生产环境不能使用这个功能
            header("HTTP/1.1 404 Not Found");
            exit();
        }
        
        $this->ToolsModel = D('Tools');
        $this->RemoteRequestService = D('RemoteRequest', 'Service');
    }

    /**
     * 重置旺币
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function rechargeWB() {
        $nodeId = I('request.nodeid', '00030592');
        $wbnumber = I('request.wbnumber', 1000);
        $contractNo = $this->ToolsModel->getNodeInfo(
            array(
                'node_id' => $nodeId), BaseModel::SELECT_TYPE_FIELD, 
            'contract_no');
        $reqData = array(
            'SetWbReq' => array(
                'SystemID' => 1000, 
                'TransactionID' => time() . rand(10000, 99999), 
                'ContractID' => $contractNo, 
                'WbType' => 1, 
                'BeginTime' => date('Ymd'), 
                'EndTime' => date('Ymd', strtotime('+1 year')), 
                'ReasonID' => 1, 
                'Amount' => 0.00, 
                'WbNumber' => $wbnumber, 
                'Remark' => "From soft"));
        $wcInfo = $this->RemoteRequestService->requestYzServ($reqData);
        echo json_encode($wcInfo);
        exit();
    }
}