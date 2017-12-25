<?php

class WeiXinCardAction extends BaseAction {

    protected $wx;

    public $node_id;

    public $open_id;

    public $batch_info_id;

    public $data_from;

    public $request_id;

    public $ipArr = array(
        '127.0.0.1', 
        '222.44.51.34', 
        '221.181.75.24');

    /*
     * {"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
     */
    public $setting = array();

    public function _initialize() {
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        if (C('WeixinServ.LOG_PATH')) {
            C('LOG_PATH', C('WeixinServ.LOG_PATH'));
        }
        $this->node_id = I('node_id');
        $this->open_id = I('open_id');
        $this->batch_info_id = I('batch_info_id');
        $this->data_from = I('data_from');
        $this->request_id = I('request_id');
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (! in_array($ip, $this->ipArr)) {
            $resp_desc = "IP:" . $ip . "不可访问";
            // $this->returnError($resp_desc);
        }
    }

		public function get_list(){
		 $postStr = file_get_contents ('php://input');
		 $this->log(" component_message :".$postStr);
		 $this->log('http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"]);
		 set_time_limit(0);
		 $wx_grant = D('WeiXinCard','Service');

		 $card_list = M('twx_assist_number')->where("add_time > FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 4 DAY)) , '%Y%m%d000000' ) AND STATUS = '1' ")->select();

		 $count = 0;
		 $err_count = 0;
		 foreach($card_list as $card_info){
		 	$wx_grant->init_by_node_id($card_info['node_id']);
			$this->log("get card no get list scan id: ".$count + $err_count);
			$batch_info = M('tbatch_info')->where("id='".$card_info['card_batch_id']."'")->find();
			$result = $wx_grant->get_code_status($batch_info['card_id'], $card_info['assist_number']);
			if ($result['errcode'] == 0 && $result['openid'] != null){
				$count ++;
				$this->log("get card no get  ".$card_info['assist_number'] . " openid is :". $result['openid']);
				$this->log("reget card :".print_r($wx_grant->create_code($card_info['assist_number'],  $result['openid'],  $batch_info['card_id'], '', 0), true));
			}else if ($result['errcode'] == 40127){
				$count ++;
				$this->log("get card no get  ".$card_info['assist_number'] . " openid is :". $result['openid']);
				$this->log("reget card :".print_r($wx_grant->create_code($card_info['assist_number'],  $card_info['open_id'],  $batch_info['card_id'], '', 0), true));
			}else if ($result['errcode'] == 40056){
				$this->log("get card no get no get ".$card_info['assist_number'] . " openid is :". $result['openid']);
				$err_count++;
			}
		 }
		$this->log("get card no get list count: ".$count);
		$this->log("get card no get list error count: ".$err_count);
		 
	}

    public function add_assist_number() {
        $this->log(
            'http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_card = D('WeiXinCard', 'Service');
        $wx_card->init_by_node_id($this->node_id);
        $rs = $wx_card->add_assist_number_nostore_for_award($this->open_id, 
            $this->batch_info_id, $this->data_from, $this->request_id);
        if ($rs === false) {
            $this->returnError($wx_card->error);
        } else {
            $this->returnSuccess("OK", 
                array(
                    "card_info" => $rs));
        }
    }

    protected function returnError($message, $respId = '9999') {
        if (! is_array($message)) {
            $message = array(
                'resp_id' => $respId, 
                'resp_desc' => $message);
        }
        $this->returnAjax($message);
    }

    protected function returnSuccess($respStr, $respData = null) {
        $respId = '0000';
        if (! is_array($message)) {
            $message = array(
                'resp_id' => $respId, 
                'resp_desc' => $respStr);
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
        log_write($msg);
    }
}
