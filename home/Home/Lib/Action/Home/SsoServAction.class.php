<?php
// 这儿是给SSO服务的接口
class SsoServAction extends Action {

    public $token;

    public $node_id;

    public function _initialize() {
        $this->token = $this->_get('token');
    }
    // 商户开通
    public function systemOpen() {
        $token = $this->token;
        $node_id = $this->_get('node_id');
        if (empty($token) || empty($node_id)) {
            $respStr = json_encode(
                array(
                    "resp_id" => "4444", 
                    "resp_str" => "添加失败，传递参数不完整。"));
        } else {
            $node_id = trim($node_id);
            $nodeInfo = M('TnodeInfo')->where(
                array(
                    "node_id" => $node_id))->find();
            // 如果机构不存在
            if (! $nodeInfo) {
                $respStr = json_encode(
                    array(
                        "resp_id" => "1001", 
                        "resp_str" => "开通机构应用失败。请到旺财后台手动绑定机构" . $node_id));
                Log::write(print_r(I('get.')), 'SSO_SERV_REQ');
                Log::write($respStr, 'SSO_SERV_RESP');
            } else {
                $respStr = json_encode(
                    array(
                        "resp_id" => "0000", 
                        "resp_str" => "开通机构应用成功。"));
            }
        }
        echo $respStr;
    }
    
    // 商户关闭
    public function systemClose() {
        $token = $this->token;
        $node_id = I('get.node_id');
        if (empty($token) || empty($node_id)) {
            echo json_encode(
                array(
                    "resp_id" => "4444", 
                    "resp_str" => "传递参数不完整。"));
        } else {
            echo json_encode(
                array(
                    "resp_id" => "0000", 
                    "resp_str" => "关闭机构应用成功。"));
        }
    }
    
    // 用户权限设置
    public function userAuth() {
        $this->error('暂无');
    }
    
    // 用户退出
    public function logout() {
        $token = $this->token;
        $userSess = D('UserSess', 'Service');
        $sso = $userSess->initSso();
        $result = $sso->tokenDelete($token);
        if ($result['resp_id'] == '0000') {
            $info = '注销token与SESSION_ID关系成功。';
            $res = array(
                'resp_id' => '0000', 
                'resp_str' => $info);
        } else {
            $info = '注销token与SESSION_ID关系失败.';
            $res = array(
                'resp_id' => '4404', 
                'resp_str' => $info);
        }
        echo json_encode($res);
        exit();
    }
    
    // 心跳
    public function keepSession() {
        $userSess = D('UserSess', 'Service');
        $sso = $userSess->initSso();
        $token_list = $sso->tokenList();
        if ($token_list) {
            echo implode("\n", (array) $token_list);
        }
    }
}