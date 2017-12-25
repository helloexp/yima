<?php

/* 主动通知接口 */
class PayGiveForPosAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public $TradeNo;

    public function index() {
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $req_arr['NodeID'] = I('post.NodeID');
        $req_arr['PosID'] = I('post.PosID');
        $req_arr['TradeNo'] = I('post.TradeNo');
        $req_arr['PayAmt'] = I('post.PayAmt');
        $req_arr['PayType'] = I('post.PayType');
        $this->TradeNo = $req_arr['TradeNo'];
        $this->paygive($req_arr);
    }
    // 付满送接口
    private function paygive($req_arr) {
        // 查找是否已有数据，YES 直接返回 原URL
        $where = "order_id ='" . $req_arr['TradeNo'] . "' and pay_type = '" .
             $req_arr['PayType'] . "'";
        $order_info = M('tpay_give_order')->where($where)->find();
        if ($order_info) {
            // 直接返回 原URL
            $this->log("流水已同步...");
            $channel_info = M('tchannel')->where(
                'id = ' . $order_info['channel_id'])->find();
            if (! $channel_info) {
                $this->log("没有符合的渠道记录..." . M()->_sql());
                $this->notifyreturn('1001', '没有符合的渠道记录', '');
            }
            if ($channel_info['status'] == 2 ||
                 strtotime($channel_info['end_time']) < time() ||
                 strtotime($channel_info['begin_time']) > time())
                $codestatus = '1010';
            else
                $codestatus = '0000';
            $this->notifyreturn($codestatus, '交易成功', $order_info['short_url'], 
                mb_substr($channel_info['memo'], 0, 50, "utf8"));
        }
        $pay_token = md5($req_arr['TradeNo'] . $req_arr['PayType']);
        $now_time = date('YmdHis');
        // 查找门店号
        $store_info = M('tpos_info')->where(
            "pos_id = '" . $req_arr['PosID'] . "'")
            ->field('store_id')
            ->find();
        if (! $store_info) {
            $this->log("没有符合的终端记录..." . M()->_sql());
            $this->notifyreturn('1004', '没有符合的终端记录', '');
        }
        $store_id = $store_info['store_id'];
        // 查找符合业务逻辑的tchannel记录
        $where = "node_id = '" . $req_arr['NodeID'] .
             "' and status = '1' and begin_time < '" . $now_time .
             "' and end_time > '" . $now_time .
             "' and (join_flag = '0' or join_flag like '%" . $req_arr['PayType'] .
             "%') and limit_amt <=" . $req_arr['PayAmt'] .
             " and upper_limit_amt > " . $req_arr['PayAmt'] .
             " and (store_join_flag = '1' or (store_join_flag = '2' and p.store_id = '" .
             $store_id . "'))";
        $channel_info = M()->table("tchannel c")->join(
            "left join tpay_give_store_list p on c.id = p.channel_id")
            ->field("c.id as id, status, begin_time, end_time, memo")
            ->where($where)
            ->find();
        if (! $channel_info) {
            $this->log("没有符合的渠道记录..." . M()->_sql());
            $this->notifyreturn('1001', '没有符合的渠道记录', '');
        }
        // 查找tbatch_channel记录 前端确保一个合适的渠道只有一个记录
        $where = "status = '1' and channel_id = " . $channel_info['id'];
        $batch_channel_info = M('tbatch_channel')->where($where)->find();
        if (! $batch_channel_info) {
            $this->log("返回没有符合的渠道活动记录..." . M()->_sql());
            $this->notifyreturn('1002', '没有符合的渠道活动记录', '');
        }
        // 生成URL
        // http://test.wangcaio2o.com/index.php?g=Label&m=Label&a=index&id=7587
        $url = C('PAYGIVE_DOMAIN') . U('Label/Label/index', 
            array(
                'id' => $batch_channel_info['id'], 
                'pay_token' => $pay_token));
        // 转短链接
        $RemoteRequest = D('RemoteRequest', 'Service');
        $arr = array(
            'CreateShortUrlReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'TransactionID' => time() . rand(10000, 99999), 
                'OriginUrl' => "<![CDATA[$url]]>"));
        $short_url_arr = $RemoteRequest->GetShortUrl($arr);
        $this->log("短链生成失败..." . print_r($short_url_arr, true));
        if ($short_url_arr['Status']['StatusCode'] !== '0000') {
            // 返回短链生成失败
            $this->log("短链生成失败..." . $url);
            $this->notifyreturn('1003', '短链生成失败', '');
        } else
            $short_url = $short_url_arr['ShortUrl'];
            
            // 插入流水表
        $new_order_info['node_id'] = $req_arr['NodeID'];
        $new_order_info['order_id'] = $req_arr['TradeNo'];
        $new_order_info['pos_id'] = $req_arr['PosID'];
        // $new_order_info['pos_seq'] = $req_arr['PosSeq'];
        $new_order_info['pay_type'] = $req_arr['PayType'];
        $new_order_info['add_time'] = $now_time;
        $new_order_info['pay_token'] = $pay_token;
        $new_order_info['amt'] = $req_arr['PayAmt'];
        $new_order_info['status'] = '1';
        $new_order_info['use_times'] = 0;
        $new_order_info['short_url'] = $short_url;
        $new_order_info['channel_id'] = $channel_info['id'];
        $new_order_info['batch_channel_id'] = $batch_channel_info['id'];
        
        $rs = M('tpay_give_order')->add($new_order_info);
        if ($rs === false) {
            $this->log(print_r($new_order_info, true));
            $this->log("记录流水信息[new_order_info]失败" . M()->_sql());
            $this->notifyreturn('1004', '记录流水信息失败', '', '');
        }
        if ($channel_info['status'] == 2 ||
             strtotime($channel_info['end_time']) < time() ||
             strtotime($channel_info['begin_time']) > time())
            $codestatus = '1010';
        else
            $codestatus = '0000';
        $this->notifyreturn($codestatus, '交易成功', $short_url, 
            mb_substr($channel_info['memo'], 0, 50, "utf8"));
    }
    // 通知应答
    private function notifyreturn($resp_id, $resp_desc, $url = '', $memo = '') {
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><StatusCode>' . $resp_id . '</StatusCode><StatusText>' .
             iconv('utf8', 'gbk', $resp_desc) . '</StatusText><TradeNo>' .
             $this->TradeNo . '</TradeNo><URLInfo><![CDATA[' . $url .
             ']]></URLInfo><Memo>' . iconv('utf8', 'gbk', $memo) . '</Memo></' .
             $this->responseType . '>';
        $resp_arr['StatusCode'] = $resp_id;
        $resp_arr['StatusText'] = $resp_desc;
        $resp_arr['URLInfo'] = $url;
        $resp_arr['Memo'] = $memo;
        $resp_str = json_encode($resp_arr);
        $this->log($resp_str, 'RESPONSE');
        echo $resp_str;
        exit();
    }
    
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        log_write($msg);
    }
}
