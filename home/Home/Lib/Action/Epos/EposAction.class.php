<?php

class EposAction extends Action {
    
    //根据sid获取用户数据 判断是否登陆
    public function _getUserinfo($sid) {
        $json = file_get_contents(C('USER_URL') . $sid);
        $result = (get_magic_quotes_gpc()) ? stripcslashes($json) : $json;
        $userinfo = json_decode($result, TRUE);
        if ($userinfo['retCode'] != 0000) {
            header('Location: ' . C('INDEX_URL'));
        }
        
        $pos_id = $userinfo['loginInfo']['user_info']['POS_ID'];
        $user_id = $userinfo['loginInfo']['user_info']['USER_ID'];
        $user_name = $userinfo['loginInfo']['user_info']['USER_NAME'];

        // $pos_id = '0001027585';
        // $user_id = 'user01';
        $_SESSION['Epos']['pos_id'] = $pos_id;
        $_SESSION['Epos']['user_id'] = $user_id;
        $_SESSION['Epos']['user_name'] = $user_name;
        $_SESSION['Epos']['sid'] = $sid;
    }

    public function batchInfoList() {
        $sid = I('get.sid', '0', 'string');
        $page = I('get.page', '0', 'string');
        $this->_getUserinfo($sid);
        
        $pos_id = $_SESSION['Epos']['pos_id'];
        $user_id = $_SESSION['Epos']['user_id'];
        
        $like = I('post.like', '');
        if (I('get.like'))
            $like = I('get.like');
        
        $result = D('Epos', 'Service')->get_batch_info_list($pos_id, $page, 
            $like);
        $list = json_decode($result, true);
        $batchInfoList = $list['data']['batch_info_list'];
        $show = $list['data']['page'];
        
        $source = array(
            '自建', 
            '采购', 
            '旺财联盟', 
            '会员类', 
            '分销');
        foreach ($batchInfoList as &$val) {
            $val['end_time'] = dateformat($val['end_time'], 'Y-m-d');
            $val['source'] = $source[$val['source']];
        }
        $this->assign('list', $batchInfoList);
        $this->assign('page', $show);
        $this->assign('sid', $sid);
        $this->assign('like', $like);
        $this->assign('user_id', $user_id);
        $this->assign('pos_id', $pos_id);
        $this->display();
    }

    public function batchInfo() {
        $batch_info_id = I('get.batch_info_id');
        $pos_id = $_SESSION['Epos']['pos_id'];
        $user_id = $_SESSION['Epos']['user_id'];
        $sid = $_SESSION['Epos']['sid'];
        if (! $sid) {
            header('Location: ' . C('INDEX_URL'));
        }
        
        $result = D('Epos', 'Service')->get_batch_info($pos_id, $batch_info_id);
        $list = json_decode($result, true);
        $data = $list['data'];
        $data['verify_end_date'] = dateformat($data['verify_end_date'], 'Y-m-d');
        
        $this->assign('data', $data);
        $this->assign('sid', $sid);
        $this->display();
    }

    public function send() {
        $phone_no = I('post.phone_no', '0', 'string');
        $send_num = I('post.send_num', '0', 'string');
        $batch_info_id = I('post.batch_info_id', '0', 'string');
        $pos_id = I('post.pos_id', '0', 'string');
        $user_id = I('post.user_id', '0', 'string');
        $isMobile = check_str($phone_no, 
            array(
                'strtype' => 'mobile'));
        if (! $isMobile) {
            $this->ajaxReturn('请输入正确手机号码');
        }
        $isNumber = check_str($send_num, 
            array(
                'strtype' => 'int'));
        if (! $isNumber || $send_num < 1) {
            $this->ajaxReturn('请填写数字');
        }
        
        $result = D('Epos', 'Service')->batch_info_send($pos_id, $user_id, 
            $batch_info_id, $phone_no, $send_num);
        $list = json_decode($result, true);
        if ($list['resp_id'] != 0000) {
            $this->ajaxReturn('发送失败');
        } else {
            $fail_num = $send_num - $list['data']['succ_num'];
            $str = '发送结束<br />';
            $str .= '共发送' . $send_num . '张，成功' . $list['data']['succ_num'] . '张';
            if ($fail_num > 0) {
                $str .= '<br />失败' . $fail_num . '张';
            }
            $this->ajaxReturn($str);
        }
    }

    public function sendList() {
        $status = '0';
        $display = 'sendList';
        $this->getListByStatus($status, $display);
    }
    public function failList(){
        $status = '3';
        $display = 'failList';
        $this->getListByStatus($status, $display);
    }

    /**
     * 撤销列表
     */
    public function repealList() {
        $status = '1';
        $display = 'repealList';
        $this->getListByStatus($status, $display);
    }
    

    /**
     * @var EposService
     */
    private $EposService;

    private function getListByStatus($status, $display)
    {
        $sid       = I('get.sid', '0', 'string');
        $page      = I('get.page', '0', 'string');
        $page_type = I('get.page_type', '0');
        $this->_getUserinfo($sid);

        $pos_id = $_SESSION['Epos']['pos_id'];

        $like = I('post.like', '');
        if (I('get.like')) {
            $like = I('get.like');
        }
        if (empty($this->EposService)) {
            $this->EposService = D('Epos', 'Service');
        }
        $result   = $this->EposService->get_send_list($pos_id, $page, $like, $status);
        $list     = json_decode($result, true);
        $sendList = $list['data']['send_list'];
        foreach ($sendList as &$val) {
            $val['trans_time'] = dateformat($val['trans_time'], 'Y-m-d H:i:s');
        }
        $show = $list['data']['page'];
        $this->assign('sendStatus', array(0 => '成功', 1 => '已撤销', 3 => '失败'));
        $this->assign('page', $show);
        $this->assign('list', $sendList);
        $this->assign('sid', $sid);
        $this->assign('like', $like);
        if ($page_type == '1') {
            $this->display('sendList2');
        } else {
            $this->display($display);
        }
    }



    public function sendInfo() {
        $pos_id = $_SESSION['Epos']['pos_id'];
        $user_name = $_SESSION['Epos']['user_name'];
        $req_seq = I('get.req_seq');
        $sid = $_SESSION['Epos']['sid'];
        $page_type = I('get.page_type', '0');
        if (! $sid) {
            header('Location: ' . C('INDEX_URL'));
        }
        $result = D('Epos', 'Service')->get_send_info($pos_id, $req_seq);
        $list = json_decode($result, true);
        $data = $list['data'];
        $data['trans_time'] = dateformat($data['trans_time'], 'Y-m-d H:i:s');
        
        if ($data['status'] == 0) {
            $data['status'] = '发送成功';
        } elseif ($data['status'] == 1) {
            $data['status'] = '已撤销';
        } else {
            $data['status'] = '发送失败';
        }
        
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->assign('sid', $sid);
        $this->assign('pos_id', $pos_id);
        $this->assign('user_name', $user_name);
        
        if ($page_type == '1') {
            $this->display('sendInfo2');
        } else {
            $this->display();
        }
    }

    public function resend() {
        $pos_id = I('post.pos_id');
        $req_seq = I('post.req_seq');
        $epos_acount = I('post.epos_acount');
        $result = D('Epos', 'Service')->resend($pos_id, $req_seq, $epos_acount);
        $list = json_decode($result, true);
        if ($list['resp_id'] == 0000) {
            $this->ajaxReturn('重发成功');
        } else {
            $this->ajaxReturn('重发失败');
        }
    }

    public function cancel() {
        $pos_id = I('post.pos_id');
        $req_seq = I('post.req_seq');
        $epos_acount = I('post.epos_acount');
        
        $result = D('Epos', 'Service')->cancel($pos_id, $req_seq, $epos_acount);
        $list = json_decode($result, true);
        if ($list['resp_id'] == 0000) {
            $this->ajaxReturn('撤销成功');
        } else {
            $this->ajaxReturn('撤销失败');
        }
    }
  
    public function wcapp() {
        $device = I('device','');
        
        $sid = I('get.sid', '0', 'string');
        $result2 = [];
        if ($sid) {
            $this->_getUserinfo($sid);
            $pos_id = isset($_SESSION['Epos']['pos_id']) ? $_SESSION['Epos']['pos_id'] : '0';
            $sql2="SELECT * FROM `tepos_app_recommend` t INNER JOIN tepos_app_recommend_relation a ON a.epos_app_recommend_id=t.id WHERE t.app_show_flag =1 and t.status=1 and pos_id='$pos_id'";
            $result2 = M()->query($sql2);
            if (!is_array($result2)) {
                $result2 = [];
            }
        }
        $sql1="SELECT * FROM `tepos_app_recommend` WHERE STATUS=1 AND app_show_flag =0";

        $result1 = M()->query($sql1);
        if (!is_array($result1)) {
            $result1 = [];
        }

        if ($result2) {
            $list = array_merge($result1,$result2);
         } else {
            $list = $result1;
        }

        $this->assign('list', $list);
        $this->assign('device', $device);
        if($device == 'N900_SALE'){
            $this->display('wcapp2');
        }else{
            $this->display();  
        }
    }

    public function wcappInfo() {
        $id = I('get.id');
        $device = I('device','');
        $list = M('tepos_app_recommend')->find($id);
        $list['download_size'] = ceil($list['download_size'] / 10240) / 100;
        
        $this->assign('list', $list);
        $this->assign('sid', $sid);
        $this->assign('device', $device);
        $this->display();
    }

    public function download() {
        $id = I('post.id');
        $pos_id = $_SESSION['Epos']['pos_id'];
        
        $info = M('tepos_app_recommend')->find($id);
        $data['download_count'] = $info['download_count'] + 1;
        $data['id'] = $id;
        M('tepos_app_recommend')->save($data);
        
        $data2['epos_app_recommend_id'] = $id;
        if($pos_id){
            $pos_info = M('tpos_info')->where(
                array(
                    'pos_id' => $pos_id))->find();
            $data2['node_id'] = $pos_info['node_id'];
            $data2['pos_id'] = $pos_id;
        }else{
            $data2['node_id'] = '00000000';
            $data2['pos_id'] = '0000000000';
        }
        
        $data2['add_time'] = date('YmdHis');
        M('tepos_app_download_trace')->add($data2);
    }
    
    //当使用微信浏览器扫码旺财APP下载二维码时弹出提示页面
    public function bridge_page() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            header(
                'location:http://www.wangcaio2o.com/Home/Upload/apk/wangcai.apk');
        } else {
            $this->display();
        }
    }

    public function cuePage() {
        $this->display();
    }

    public function check_app_version() {
        $resp_arr['resp_id'] = '0000';
        $resp_arr['now_version'] = '2002';
        $resp_arr['update_flag'] = '1'; // 1-可选更新 2-强制更新
        $resp_arr['app_url'] = 'http://222.44.51.34/test/EPOS_WEB.apk';
        $resp_arr['update_detail'] = '更新说明';
        echo json_encode($resp_arr);
    }
}
?>