<?php

/**
 * 注册接口
 *
 * @author lwb
 */
class WcInterfaceAction extends Action {

    public $model;

    public function _initialize() {
        $this->model = D('WcInterface');
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 旺财注册获取session接口
     */
    public function wc_register_session() {
        $result = $this->model->getSessionId();
        $result = json_encode($result);
        log_write($result, Log::INFO, 'WC_INTERFACE');
        echo $result;
        exit();
    }

    /**
     * 旺财注册校验码接口
     */
    public function wc_check_img() {
        log_write($_REQUEST['session_id'], Log::INFO, 'WC_INTERFACE');
        if ($_REQUEST['session_id'] == session('sid')) {
//            import('ORG.Util.Image');
//            Image::buildImageVerify(4, 1, 'png', 48, 22, 'verify_imgcode');
            import('@.Service.ImageVerifyService');
            ImageVerifyService::buildImageCodeByParam('verify_imgcode');
            exit();
        } else {
            echo json_encode(
                array(
                    'resp_id' => '1001', 
                    'resp_desc' => '验证码图片失败'));
            exit();
        }
    }

    /**
     * 旺财注册
     */
    public function wc_register() {
        $data = $_REQUEST;
        if (! $data['reg_from']) {
            $data['reg_from'] = CommonConst::REG_FROM_APP;
        }
        $nodeRegService = D('NodeReg', 'Service');
        $result = $nodeRegService->wcReg($data);
        $result = json_encode($result);
        log_write(print_r($data, true), Log::INFO, 'WC_INTERFACE');
        log_write($result, Log::INFO, 'WC_INTERFACE');
        echo $result;
        exit();
    }

    /**
     * 旺财帐号详情接口
     */
    public function wc_acount_detail() {
        $posId = $_REQUEST['pos_id'];
        $sId = $_REQUEST['session_id'];
        $result = $this->model->getPayStatus($posId, $sId);
        $result = json_encode($result);
        log_write($result, Log::INFO, 'WC_INTERFACE');
        echo $result;
        exit();
    }
}