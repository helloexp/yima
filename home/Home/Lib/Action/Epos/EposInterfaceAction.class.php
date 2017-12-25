<?php
// EPos接口服务
class EposInterfaceAction extends BaseAction {

    public function _initialize() {
        C('LOG_PATH', LOG_PATH . 'EPOS_'); // 重新定义目志目录
    }

    /* 入口函数 */
    public function index() {
    }
	
	//获取app更新地址，如果不需要更新则resp_id返回非0000
    public function get_app_version() {
        $ver = I('ver','');
        $rev = I('rev','');
        $pos_id = I('pos_id','');

        $where = "app_ver= '". $ver ."' and app_rev > '". $rev ."' and status = '0'";    
        $download_param = M('tepos_app_version')->where($where)->find();
		if (!$download_param)
		{
			 $resp_arr['resp_id'] = '0001';	
		}
		else
		{
			$resp_arr['resp_id'] = '0000';
			$resp_arr['app_ver'] = $download_param['app_ver'];
			$resp_arr['app_rev'] = $download_param['app_rev'];
			$resp_arr['app_download_url'] = $download_param['app_download_url'];
			$resp_arr['update_flag'] = $download_param['update_flag']; // 1-可选更新 2-强制更新
			$resp_arr['update_detail'] = $download_param['update_detail'];
		}
        echo json_encode($resp_arr);
    }


    public function check_app_version() {
        $device = I('device','');
        if ($device == 'M71FIRST') {
            $where = "param_name = 'WCAPP_M71FIRST_DOWNLOAD_PARAM'";
        } else if ($device == 'N900_SALE') {
            $where = "param_name = 'WCAPP_N900_SALE_DOWNLOAD_PARAM'";
        } else {
            $where = "param_name = 'WCAPP_DOWNLOAD_PARAM'";
        }
        
        $download_param = M('tsystem_param')->where($where)->find();
        $download_param_arr = explode("#", $download_param['param_value']);
        // 2004#http://222.44.51.34/test/EPOS_WEB.apk#1#更新说明
        $resp_arr['resp_id'] = '0000';
        $resp_arr['now_version'] = $download_param_arr[0];
        $resp_arr['update_flag'] = $download_param_arr[2]; // 1-可选更新 2-强制更新
        $resp_arr['app_url'] = $download_param_arr[1];
        $resp_arr['update_detail'] = $download_param_arr[3];
        echo json_encode($resp_arr);
    }

    public function check_app_allinpay_version() {
        $download_param = M('tsystem_param')->where(
            "param_name = 'WCAPP_ALLINPAY_DOWNLOAD_PARAM'")->find();
        $download_param_arr = explode("#", $download_param['param_value']);
        // 2004#http://222.44.51.34/test/EPOS_WEB.apk#1#更新说明
        $resp_arr['resp_id'] = '0000';
        $resp_arr['now_version'] = $download_param_arr[0];
        $resp_arr['update_flag'] = $download_param_arr[2]; // 1-可选更新 2-强制更新
        $resp_arr['app_url'] = $download_param_arr[1];
        $resp_arr['update_detail'] = $download_param_arr[3];
        echo json_encode($resp_arr);
    }

    private function get_send_list() {
        $pos_id = I('pos_id', '0', 'string');
        $start = I('start', '0', 'string');
        $limit = I('limit', '8', 'string');
        $like = I('like', '', 'string');
        $postStr = file_get_contents('php://input');
        $this->log("微信 component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $Epos = D('Epos', 'Service');
        echo $Epos->get_send_list($pos_id, $start, $limit, $like);
    }

    private function get_send_info() {
        $pos_id = I('pos_id', '0', 'string');
        $req_seq = I('req_seq', '0', 'string');
        $postStr = file_get_contents('php://input');
        $this->log("微信 component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $Epos = D('Epos', 'Service');
        echo $Epos->get_send_info($pos_id, $req_seq);
    }

    public function get_send_stat() {
        $pos_id = I('pos_id', '0', 'string');
        $postStr = file_get_contents('php://input');
        $this->log("微信 component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $Epos = D('Epos', 'Service');
        echo $Epos->get_send_stat($pos_id);
    }

    public function wcapp_Install_num() {
        $app_version = I('app_version', '0', 'string');
        $Epos = D('Epos', 'Service');
        echo $Epos->wcapp_Install_num($app_version);
    }

    private function resend() {
        $pos_id = I('pos_id', '0', 'string');
        $req_seq = I('req_seq', '0', 'string');
        $epos_acount = I('epos_acount', '0', 'string');
        $postStr = file_get_contents('php://input');
        $this->log("微信 component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $Epos = D('Epos', 'Service');
        echo $Epos->resend($pos_id, $req_seq, $epos_acount);
    }

    private function cancel() {
        $pos_id = I('pos_id', '0', 'string');
        $req_seq = I('req_seq', '0', 'string');
        $epos_acount = I('epos_acount', '0', 'string');
        $postStr = file_get_contents('php://input');
        $this->log("微信 component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $Epos = D('Epos', 'Service');
        echo $Epos->cancel($pos_id, $req_seq, $epos_acount);
    }

    public function _getUserinfo($sid) {
        $json = file_get_contents(C('USER_URL') . $sid);
        $result = (get_magic_quotes_gpc()) ? stripcslashes($json) : $json;
        $userinfo = json_decode($result, TRUE);
        if ($userinfo['retCode'] != 0000) {
            header('Location: ' . C('INDEX_URL'));
        }
        
        // $userinfo['loginInfo']['user_info']['POS_ID'] = '0001027585';
        // $userinfo['loginInfo']['user_info']['USER_ID'] = 'user01';
        
        $user_info = $userinfo['loginInfo']['user_info'];
        return $user_info;
    }

    public function batchInfoList() {
        $sid = I('sid', '0', 'string');
        // $currentPage = I('currentPage', '1', 'string');
        // $limit = I('limit', '10', 'string');
        $like = I('like','');
        $user_info = $this->_getUserinfo($sid);
        $pos_id = $user_info['POS_ID'];
        
        $result = D('Epos')->get_batch_info_list($pos_id, $like);
        echo $result;
    }

    public function batchInfo() {
        $sid = I('sid', '0', 'string');
        $user_info = $this->_getUserinfo($sid);
        log_write('$user_info:'.var_export($user_info,1));
        $pos_id = $user_info['POS_ID'];
        
        $result = D('Epos')->get_batch_info($pos_id, $_REQUEST['batch_info_id']);
        log_write('$result:'.var_export($result,1));
        echo $result;
    }

    public function batch_info_send() {
        $sid = I('sid', '0', 'string');
        $user_info = $this->_getUserinfo($sid);
        $pos_id = $user_info['POS_ID'];
        $user_id = $user_info['USER_ID'];
        
        $result = D('Epos')->batch_info_send($pos_id, $user_id, 
            $_REQUEST['batch_info_id'], $_REQUEST['phone_no'], 
            $_REQUEST['send_num']);
        echo $result;
    }

    public function batch_channel_list() {
        $sid = I('sid', '0', 'string');
        $currentPage = I('currentPage', '1', 'string');
        $limit = I('limit', '8', 'string');
        $user_info = $this->_getUserinfo($sid);
        log_write('$user_info:'.var_export($user_info,1));
        $pos_id = $user_info['POS_ID'];

        $result = D('Epos')->getWcAppBatchChannelList($pos_id, $currentPage,$limit);
        echo $result;
    }

//查看门店
    public function storeList(){




    }


//添加门店
    public function addStore(){
        //$a='{"code":2004,"msg":"\'\'\u63a5\u53e3\u62a5\u6587\u8981\u6c42\u6709\u503c\u7684xml\u6807\u7b7e\u4e0d\u80fd\u4e3a\u7a7a\'\'"}';
       //  var_dump(json_decode($a));exit;
        $storeGroupModel = D('StoresGroup');
        $storeService = D('Store','Service');
        // 必须传值机构号
        $node_id = I('node_id','','mysql_real_escape_string');
        $userId = I('userId','','mysql_real_escape_string');
        $fjcbcFlag = I('fjcbcFlag','','mysql_real_escape_string');
        if($fjcbcFlag==''){
            $fjcbcFlag = false;
        }

        if($node_id==''){
            $this->ajaxReturn(['code'=>2001,'msg'=>'商户号为空'],"JSON");
            exit;
        }
        if($userId==''){
            $this->ajaxReturn(['code'=>2002,'msg'=>'商户ID为空'],"JSON");
            exit;
        }

        $nodeInfo = M('tnode_info')->where(
                array(
                        'node_id' => array(
                                'exp',
                                'in ('.$this->nodeIn($node_id).") and node_id = '".$node_id.
                                "'", ), ))->find();
        if (!$nodeInfo) {
            $this->ajaxReturn(['code'=>2003,'msg'=>'您没有操作该商户的权限'],"JSON");
            exit;//$this->error('您没有操作该商户的权限。');
        }
        //$req_result = D('RemoteRequest', 'Service');
        // 判断是否允许添加该机构
        if (IS_POST) {
            // 获取表单数据
            $getPost = I('post.');
            $res = $storeService->add($getPost,['fjcbcFlag'=>$fjcbcFlag,'node_id'=>$node_id,'userId'=>$userId]);
            // $dog = '/Label/Image/Shop/dog.png';

            if($res == 'success'){
                $this->ajaxReturn(['code'=>2000,'msg'=>$res],"JSON");
                exit;
            }else{
                $this->ajaxReturn(['code'=>2004,'msg'=>"'".var_export($res,1)."'"],"JSON");
                exit;//$this->error("'".var_export($res,1)."'");
            }

        }
        // 输出分组门店
        $group = $storeGroupModel->getPopGroupStoreId($node_id, true);

    }
















    //创建卡券
    public function addNumGoods(){
        $param = I('param.isWcadd');
        $nodeId = I('node_id','','mysql_real_escape_string');
        $userId = I('userId','','mysql_real_escape_string');
        if($param == 1){
            $map = array(
                    'node_id' => $nodeId,
                    'account_type' => array('in', ['2','4']),
                    'status' => '0'
            );
            $winxinInfo = M('tweixin_info')->where($map)->find();
            // 微信已认证服务号并且状态正常的
            if (!$winxinInfo) {
                $this->ajaxReturn(['code'=>2003,'msg'=>"请先配置微信公众账号。",'url'=> U('Weixin/Weixin/index')],"JSON");
                //$this->error("请先配置微信公众账号。", array('立即绑定' => U('Weixin/Weixin/index')));
            }
        }

        node_log("首页+创建卡券");
        // 商户信息
        if(IS_POST){

            $res = D('Store','Service')->addNumGoods($nodeId,$this->nodeIn($nodeId, true),$this->nodeIn($nodeId),$userId);

            if($res['status'] == 'success'){
                $this->ajaxReturn(['code'=>2000,'msg'=>$res['status']],"JSON");
                exit;
            }else{
                $this->ajaxReturn(['code'=>2004,'msg'=>"'".var_export($res,1)."'"],"JSON");
                exit;
            }

        }









    }





    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        log_write($msg, '[' . getmypid() . ']' . $level);
    }
}