<?php

/**
 * 微信红包外部调用接口
 *
 * @author bao
 */
class RedPackAction extends BaseAction {

    public $node_id;
    
    public $ym_node_id = '00000000';//翼码代理红包的商户号

    public $open_id;

    public $batch_info_id;

    public $m_id;

    public $request_id;

    public $ipArr = array(
        '127.0.0.1', 
        '222.44.51.34', 
        '221.181.75.24');

    public function _initialize() {
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        if (C('WeixinServ.LOG_PATH')) {
            C('LOG_PATH', C('WeixinServ.LOG_PATH'));
        }
        $this->node_id = I('node_id');
        $this->open_id = I('open_id');
        $this->batch_info_id = I('batch_info_id');
        $this->m_id = I('m_id');
        $this->request_id = I('request_id');
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (! in_array($ip, $this->ipArr)) {
            $resp_desc = "IP:" . $ip . "不可访问";
            // $this->returnError($resp_desc);
        }
    }

    public function sendRedPack() {
        $this->log('http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER["SERVER_PORT"] .$_SERVER["REQUEST_URI"]);
        
        // 获取红包信息
        $map = array(
            'id' => $this->batch_info_id,
            'batch_type' => array('in','6,7')
        );
        $redPackInfo = M('tbatch_info')->where($map)->find();
        if (! $redPackInfo) {
            log_write(
            "WeixinRedPackLog:{$this->node_id}未找到红包信息b_id{$this->batch_info_id}");
            $this->returnError("未找到红包信息");
        }
        $redPack = D('WeixinRedPack', 'Service');
        if($redPackInfo['batch_type'] == '7'){//翼码代理红包获取翼码的证书信息
            $redPack->init($this->ym_node_id);
        }else{
            $redPack->init($this->node_id);
        }
        
        // 活动名称
        $tmarName = M('tmarketing_info')->where("id='{$this->m_id}'")->getField('name');
        if (! $tmarName) {
            log_write("WeixinRedPackLog:{$this->node_id}未找到活动m_id{$this->m_id}");
            $this->returnError("未找到活动");
        }
        
        $sendData = array( // 接口参数全必填
            'nonce_str' => $redPack->getNonceStr(),  // 随机字符串，不长于32位
            //'mch_billno' => $redPack->getMchBillno(),  // 商户订单号（每个订单号必须唯一组成：mch_id+yyyymmdd+10位一天内不能重复的数字。接口根据商户订单号支持重入，如出现超时可再调用
            'send_name' => $redPackInfo['material_code'],  // 红包发送者名称
            're_openid' => $this->open_id,  // 接受红包的用户用户在wxappid下的openid
            'total_amount' => $redPackInfo['batch_amt'] * 100,  // 付款金额，单位分
            'total_num' => '1',  // 红包发放总人数
            'wishing' => $redPackInfo['print_text'],  // 红包祝福语
            'client_ip' => '192.168.0.1',  // 调用接口的机器Ip地址
            'act_name' => $redPackInfo['batch_name'],  // 活动名称
            'remark' => $redPackInfo['batch_desc']	 // 备注信息
        );
        
        //判断request_id是否已存在并且发放失败的红包,存在则用原订单号做重发
        $traceInfo = M('twx_bonus_send_trace')->where("request_id='{$this->request_id}'")->find();
        if($traceInfo){//重发
        	$sendData['mch_billno'] = $traceInfo['mch_billno'];
        }else{
        	$sendData['mch_billno'] = $redPack->getMchBillno();
        }
        $result = $redPack->sendBegin($sendData);
        // 流水数据
        $traceData = array(
            'node_id' => $this->node_id, 
            'openid' => $this->open_id, 
            'bonus_amt' => $redPackInfo['batch_amt'], 
            'add_time' => date('YmdHis'), 
            'request_id' => $this->request_id, 
            'b_id' => $this->batch_info_id, 
            'm_id' => $this->m_id, 
            'mch_billno' => $sendData['mch_billno'], 
            'goods_id' => $redPackInfo['goods_id'],
            'is_ym_agent' => $redPackInfo['batch_type'] == '7' ? '1' : '0'
        );
        if ($result) {
            $traceData['status'] = '0';
            $traceData['wx_ret_msg'] = "[{$result['result_code']}]{$result['return_msg']}";
            $traceData['err_code'] = $result['result_code'];
            if($traceInfo){//重发成功流水
            	$traceResutl = M('twx_bonus_send_trace')->where("request_id='{$this->request_id}'")->save($traceData);//更新成功流水
            }else{
            	$traceResutl = M('twx_bonus_send_trace')->add($traceData);//记录成功流水
            }
            log_write("WeixinRedPackLog:{$this->node_id}红包流水记录:".M()->_sql());
            $this->returnSuccess("OK",array("red_pack_info" => $result));
        } else {
            $traceData['status'] = '1';
            $traceData['wx_ret_msg'] = "[{$redPack->errCode}]{$redPack->error}";
            $traceData['err_code'] = $redPack->errCode;
            if($traceInfo){//重发失败流水
            	M('twx_bonus_send_trace')->where("request_id='{$this->request_id}'")->save($traceData);//更新成功流水
            }else{
            	M('twx_bonus_send_trace')->add($traceData); // 记录失败流水
            }
            log_write("WeixinRedPackLog:{$this->node_id}红包流水记录:".M()->_sql());
            $this->returnError($redPack->error);
        }
    }

    protected function returnError($message, $respId = '9999') {
        if (! is_array($message)) {
            $message = array(
                'resp_id' => $respId, 
                'resp_desc' => $message
            );
        }
        $this->returnAjax($message);
    }

    protected function returnSuccess($message, $respData = null) {
        $respId = '0000';
        if (! is_array($message)) {
            $message = array(
                'resp_id' => $respId, 
                'resp_desc' => $message
            );
            $message['resp_data'] = $respData;
        }
        $this->returnAjax($message);
    }

    protected function returnAjax($arr) {
        // $this->log(print_r($arr,true),'INFO');
        // array_walk_recursive($arr,'BaseAction::Utf8');
        $return = json_encode($arr);
        $this->log($return, 'RESPONSE_INFO');
        if ($this->_get('debug')) {
            tag('view_end');
        }
        G('BeginTime', $GLOBALS['_beginTime']);
        G('EndTime');
        echo $return;
        $this->log('RUNTIME:' . G('BeginTime', 'EndTime') . ' s');
        exit();
    }

    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        Log::write($msg,$level);
    }
}
