<?php
// 登陆 弹窗
class PopwindowAction extends BaseAction {

    public $user_id;

    public $node_id;

    public function _initialize() {
        // 获取node_id,user_id
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        if (! $userService->isLogin()) {
            $this->error("请先登录");
        }
        $this->user_id = $userInfo['user_id'];
        $this->node_id = $userInfo['node_id'];
    }
    // 弹窗url
    public function pop_up() {
        $userService = D('UserSess', 'Service');
        $userService->setUserInfo('popWinFlag', '0');
        $popWinFlag = $userService->getUserInfo('popWinFlag');
        if ($popWinFlag) {
            echo json_encode(array(
                'data' => array()));
            exit();
        }
        $userService->setUserInfo('popWinFlag', '1');
        $row = M('tpop_window_param')->field(
            'window_content,window_id,pop_rule,control_type')
            ->where(
            array(
                'begin_time' => array(
                    'LT', 
                    date('YmdHis')), 
                'end_time' => array(
                    'GT', 
                    date('YmdHis'))))
            ->select();
        $arr = array();
        foreach ($row as $v) {
            // 判定是否不再提示或完成任务
            $map = array(
                'node_id' => $this->node_id, 
                'user_id' => $this->user_id, 
                'window_id' => $v['window_id']);
            $m = M('tpop_window_control')->where($map)->find();
            // 黑名单规则
            if ($m && $v['control_type'] == 0) {
                continue;
            }
            
            // 白名单规则
            if (($v['control_type'] == 1 && ! $m) || // 不在控制表中
                 ($v['control_type'] == 1 && $m && $m['close_flag'] != '3')) // 非关闭的
{
                continue; // to-do debug
            }
            
            // 判定规则
            $rule_flag = 1;
            $pop_rule = json_decode($v['pop_rule'], true) or $pop_rule = array();
            
            foreach ($pop_rule as $rule_key => $rule) {
                if ($rule_key == 'role_id') {
                    $user_role_id = M('tuser_info')->where(
                        array(
                            'user_id' => $this->user_id))->getField('role_id');
                    
                    if ($rule != $user_role_id) {
                        $rule_flag = 0;
                        break;
                    }
                }
            }
            
            if (! $rule_flag) {
                continue;
            }
            
            $arr[] = $v['window_content'] . '&popid=' . $v['window_id'];
        }
        // 是否当日第一次打开
        $today_first_popwin = ! session('today_opened_popwin');
        session('today_opened_popwin', true);
        
        // 非标，或者特殊弹窗
        // 判断是否有母亲节弹窗
        // 是否当日第一次加载本页
        $flag = date('Ymd') <= '20150509' && $today_first_popwin &&
             ($this->wc_version == 'v0' || $this->wc_version == 'v0.5');
        if ($flag) {
            $count = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => '46'))->find();
            if ($count) {
                $flag = false;
            }
        }
        
        if ($flag) {
            $arr[] = array(
                'width' => 520, 
                'height' => 520, 
                'url' => U('LabelAdmin/MamaSjb/index_pop'));
        }
        
        echo json_encode(array(
            'data' => $arr));
    }
    
    // 是否不再提示此窗口
    public function wind() {
        $id = I('get.id');
        $id2 = $_GET['id'];
        $data = array(
            'window_id' => $id, 
            'node_id' => $this->node_id, 
            'user_id' => $this->user_id, 
            'close_flag' => '0');
        $row = M('tpop_window_control')->add($data);
        if ($row) {
            echo "操作成功，以后将不再提示此窗口！" . $id . gettype($id) . $id2 . gettype($id2);
        } else {
            echo "操作失败！" . $id . gettype($id) . $id2 . gettype($id2);
        }
    }
    // 弹窗跳转
    public function jump() {
        $id = I('get.id');
        // 获取弹窗配置
        $winInfo = M('tpop_window_param')->where(
            array(
                'id' => $id));
        if (! $winInfo) {
            $this->error("页面不存在");
        }
        // 格式化规则
        $ruleArr = json_decode($winInfo['pop_rule'], true);
        // 判断是否关闭
        $closeRule = empty($ruleArr['close_rule']) ? array() : $ruleArr['close_rule'];
    }
}