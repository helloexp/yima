<?php

class IntroduceAction extends Action {

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
    }

    public function index() {
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->userInfo['node_id']))->find();
        if ($nodeInfo['node_type'] == 0 || $nodeInfo['node_type'] == 1)
            $node_type = true;
        if(strpos($nodeInfo['pay_module'], 'm1')){
            $pay_module = 'm1';
        }else{
            $pay_module = '';
        }
        $nodeCharge = M('tnode_charge')->where(
            array(
                'node_id' => $this->userInfo['node_id'], 
                'charge_id' => '3060'))->find();
        node_log("首页+产品介绍");
        
        $this->assign("node_type", $node_type);
        $this->assign("leftMenuId", 1);
        $this->assign('pay_module', $pay_module);
        $this->assign('nodeInfo', $nodeInfo);
        $this->assign('nodeCharge', $nodeCharge);
        $this->display();
    }

    public function activity() {
        $this->assign("leftMenuId", 2);
        $this->display();
    }

    public function goods() {
        $this->assign("leftMenuId", 3);
        $this->display();
    }

    public function channel() {
        $this->assign("leftMenuId", 4);
        $this->display();
    }

    public function flowers() {
        $this->assign("leftMenuId", 5);
        $this->display();
    }

    public function datacenter() {
        $this->assign("leftMenuId", 6);
        $this->display();
    }

    public function aiPai() {
        $this->assign("leftMenuId", 7);
        $this->display();
    }
    
    // 市场调研
    public function bm() {
        $this->assign("leftMenuId", 8);
        $this->display();
    }
    
    // 抽奖
    public function newShow() {
        $this->assign("leftMenuId", 9);
        $this->display();
    }
    
    // 有奖答题
    public function prizes() {
        $this->assign("leftMenuId", 10);
        $this->display();
    }
    
    // 订座
    public function bookMeal() {
        $this->assign("leftMenuId", 11);
        $this->display();
    }
    // 优惠券发放
    public function coupon() {
        $this->assign("leftMenuId", 12);
        $this->display();
    }
    // 外卖
    public function delivery() {
        $this->assign("leftMenuId", 13);
        $this->display();
    }
    // 团购
    public function groupBuy() {
        $this->assign("leftMenuId", 14);
        $this->display();
    }
    // 新产品推荐
    public function newgoods() {
        $this->assign("leftMenuId", 15);
        $this->display();
    }
    
    // 新手指引
    public function newHelp() {
        node_log("首页+新手引导");
        $this->display();
    }
}