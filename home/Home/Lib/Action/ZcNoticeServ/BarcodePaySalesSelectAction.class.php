<?php

/**
 * Task : #15733 付满送营业员查询接口 Author: Zhaobl Date: 2015/12/7
 */
class BarcodePaySalesSelectAction extends Action {

    public $ReqArr;
    // 请求数组
    public $transType;
    // 传递标识
    public $clerk_id = '';

    public function index() {
        $reqXml = file_get_contents('php://input');
        
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        $this->log($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        
        $this->transType = $xml->getRootName();
        log_write('【'.__LINE__.'】请求标识为：'.$this->transType);
        if ($this->transType == 'QueryClerkReq') {
            $this->disposeSalesDataInfo();
        } else {
            $this->returnSalesDataInfo('1000', false);
        }
    }
    
    // 处理营业员数据信息
    private function disposeSalesDataInfo() {
        $clerkId = $this->ReqArr['QueryClerkReq']; // 营业员数组
        $this->clerk_id = $clerkId['ClerkId'];
        $data['clerk_id'] = $clerkId['ClerkId'];
        $data['status'] = 0;
        $result = M('tpay_give_clerk')->where($data)->find();
        if (! $result) {
            $this->returnSalesDataInfo('1000', false);
        } else {
            $this->returnSalesDataInfo('0000', true, $result);
        }
    }
    
    // 返回营业员数据信息
    private function returnSalesDataInfo($statusCode, $status, 
        $salesInfoArr = array()) {
        $clerk_name = $status ? $salesInfoArr['clerk_name'] : '';
        $store_id = $status ? $salesInfoArr['store_id'] : '';
        $custom_no = $status ? $salesInfoArr['custom_no'] : '';
        $statusText = $status ? '成功' : '失败';
        
        $theReplyInfo = '<?xml version="1.0" encoding="GBK"?>' . '
                        <QueryClerkRes>
                        <ClerkId>' .
             $this->clerk_id . '</ClerkId>
                        <ClerkName>' .
             $clerk_name . '</ClerkName>
                        <StoreId>' . $store_id . '</StoreId>
                        <CustomNo>' . $custom_no . '</CustomNo>
                        <Status>
                        <StatusCode>' .
             $statusCode . '</StatusCode>
                        <StatusText>' .
             $statusText . '</StatusText>
                        </Status>
                        </QueryClerkRes>';
        echo $theReplyInfo;
        $this->log($theReplyInfo, 'RESPONSE');
        exit();
    }
    
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        log_write($msg, $level);
    }
}