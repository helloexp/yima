<?php

/* ƾ֤���ͱ��淴�� */
class DeliverReportAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public function index() {
        $reqXml = file_get_contents('php://input');
        
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]����ʧ��');
        $xml = new Xml();
        
        $this->log($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        
        // dump($this->ReqArr);exit;
        
        if ($this->transType == 'DeliverReportSyncReq') { // ����ͬ��
            $this->responseType = 'DeliverReportSyncRes';
            $this->deliverreportsyncreq();
        } else {
            $this->responseType = 'ErrorRes';
            $this->notifyreturn('1000');
        }
    }
    // ����ͬ��
    private function deliverreportsyncreq() {
        $deliver_info = $this->ReqArr['DeliverReportSyncReq'];
        $deliver_info['DeliverDetail'] = iconv("gbk", "utf-8", 
            $deliver_info['DeliverDetail']);
        
        if ($deliver_info['DeliverStatus'] == '0000') {
            $data['send_status'] = '3';
            $sendData['status'] = '3';
        } else {
            $data['send_status'] = '4';
            $sendData['status'] = '4';
        }
        ;
        
        $req_seq = $deliver_info['SpSeq'];
        $tbarcode_trace = M('tbarcode_trace_send')->where(
            "req_seq = '" . $req_seq . "'")->find();
        
        $org_req_seq = $tbarcode_trace['org_req_seq'];
        
        if ($tbarcode_trace['send_status'] != '3') {
            $map['req_seq'] = $org_req_seq;
            $trace = M('tbarcode_trace')->where($map)->save($data);
            
            if (! $trace) {
                $this->log(print_r($data, true));
                $this->log("[tbarcode_trace]ƾ֤�ύ״̬����ʧ��");
            }
        }
        
        $sendData['deliver_status'] = $deliver_info['DeliverStatus'];
        $sendData['deliver_desc'] = $deliver_info['DeliverDetail'];
        $sendData['deliver_time'] = $deliver_info['DeliverTime'];
        $map['req_seq'] = $req_seq;
        $trace_send = M('tbarcode_trace_send')->where($map)->save($sendData);
        if (! $trace_send) {
            $this->log(print_r($sendData, true));
            $this->log("[tbarcode_trace_send]����ʧ��");
        }
        $this->notifyreturn();
    }
    
    // ֪ͨӦ��
    private function notifyreturn($resp_id = '0000') {
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><StatusCode>' . $resp_id . '</StatusCode></' . $this->responseType .
             '>';
        echo $resp_xml;
        $this->log($resp_xml, 'RESPONSE');
        exit();
    }
    
    // ��¼��־
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        log_write($msg, $level);
    }
}
