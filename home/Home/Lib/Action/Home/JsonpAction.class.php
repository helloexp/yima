<?php
// 接收jsonp请求
class JsonpAction extends Action {

    public function index() {
        $callback = isset($_GET['callback']) ? trim($_GET['callback']) : ''; // jsonp回调参数，必需
        $data = array();
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        if ($userInfo != NULL && $userInfo['node_id'] != C('NEW_POSTER_VISITER.node_id')) {
            $data["user_name"] = $userInfo["user_name"];
            
            $query_arr = array(
                'node_id' => $userInfo["node_id"], 
                'type' => '8');
            
            $result = M('tmessage_apply')->where($query_arr)->find();
            if ($result == "") {
                $data["wfx_flag"] = 0;
            } else {
                $data["wfx_flag"] = 1;
            }
            $dataArr = array(
                'node_id' => $userInfo['node_id'], 
                'batch_type' => '56');
            $dataInfo = M('tmarketing_info')->where($dataArr)->find();
            if ($dataInfo == '') {
                $data['nationalday_status'] = '3';
            } else {
                $data['nationalday_status'] = $dataInfo['status'];
            }
            $tnode_info = M('tnode_info')->where(
                array(
                    'node_id' => $userInfo['node_id']))->find();
            $data['client_id'] = str_pad($tnode_info['client_id'], 6, '0', 
                STR_PAD_LEFT);
            $alipayInfo = M('tzfb_offline_pay_info')->where(
                array(
                    'node_id' => $userInfo['node_id'], 
                    'pay_type' => '0'))->find();
            $weixinInfo = M('tzfb_offline_pay_info')->where(
                array(
                    'node_id' => $userInfo['node_id'], 
                    'pay_type' => '1'))->find();
            $data['alipay_status'] = $alipayInfo['status'];
            $data['alipay_check_status'] = $alipayInfo['check_status'];
            $data['weixin_status'] = $weixinInfo['status'];
            $data['weixin_check_status'] = $weixinInfo['check_status'];
        } else {
            $data["user_name"] = "";
        }
        $tmp = json_encode($data); // json 数据
        echo $callback . '(' . $tmp . ')'; // 返回格式，必需
    }
}