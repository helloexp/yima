<?php

/* 主动通知接口 */
class IndexAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public function index() {
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        
        $this->log($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        
        if ($this->transType == 'QueryHomepageReq') { // 官网活动查询
            $this->responseType = 'QueryHomepageRes';
            $this->QueryHomepageRes();
        } else {
            $this->responseType = 'ErrorRes';
            $this->errorreturn('1000');
        }
    }
    
    // 官网活动查询
    private function QueryHomepageRes() {
        $req = $this->ReqArr['QueryHomepageReq'];
        $limit_count = $req['QueryCount'];
        
        if ($limit_count < 0 or $limit_count > 100) // 一次只返回不超过100条记录
{
            $this->errorreturn('0002', '查询数超过100');
        }
        
        // 一，取全渠道信息
        $where = "node_id ='" . $req['NodeId'] .
             "' and type = '1' and sns_type = 11";
        $channel_list = M('Tchannel')->where($where)->select();
        if (! $channel_list) {
            $this->errorreturn('0003', '尚无官网渠道');
        }
        
        $resp = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><StatusCode>0000</StatusCode><StatusText>查询成功</StatusText><BatchList>';
        $icount = 0;
        foreach ($channel_list as $channel_info) {
            $where = "node_id ='" . $req['NodeId'] . "' and channel_id = '" .
                 $channel_info[id] . "' and batch_type in ('2','3')";
            $batch_list = M('TbatchChannel')->where($where)->select();
            if (! $batch_list) {
                continue;
                // $this->errorreturn('0000','尚无渠道活动');
            }
            
            foreach ($batch_list as $batch_info) {
                if ($batch_info['batch_type'] == '3') {
                    $url = 'http://10.10.1.34/wangcai_new/index.php?&g=Label&m=Bm&a=index&id=' .
                         $batch_info['id'];
                    $where = "id='" . $batch_info['batch_id'] . "'";
                    $bm_batch = M('TbmBatch')->where($where)->find();
                    $name = $bm_batch['name'];
                } else {
                    $url = 'http://10.10.1.34/wangcai_new/index.php?&g=Label&m=News&a=index&id=' .
                         $batch_info['id'];
                    $where = "id='" . $batch_info['batch_id'] . "'";
                    $news_batch = M('TnewsBatch')->where($where)->find();
                    $name = $news_batch['name'];
                }
                $resp = $resp . '<BatchInfo><BatchName>' . $name .
                     '</BatchName><Url>' . $url . '</Url><Batchtype>' .
                     $batch_info['batch_type'] . '</Batchtype><BatchImg>' .
                     $batch_info['code_img'] . '</BatchImg></BatchInfo>';
                $icount ++;
                if ($icount >= $limit_count) {
                    break;
                }
            }
        }
        $resp = $resp . '</BatchList></' . $this->responseType . '>';
        $this->log($resp, 'RESPONSE');
        echo $resp;
        exit();
    }
    
    // 通知应答
    private function errorreturn($resp_id = '0000', $resp_text = '无效的请求') {
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><StatusCode>' . $resp_id . '</StatusCode><StatusText>' .
             $resp_text . '</StatusText></' . $this->responseType . '>';
        $this->log($resp_xml, 'RESPONSE');
        echo $resp_xml;
        exit();
    }
    
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        Log::write($msg, $level);
    }
}