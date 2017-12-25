<?php
// 抽奖规则列表
class CjRuleListAction extends BaseAction {
    
    /**
     * @var cjRuleListModel
     */
    public $cjRuleListModel;
    public $sendAwardTraceModel;
    
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->cjRuleListModel = D('CjRuleList');
        $mId = I('batch_id');
        if ($mId && (ACTION_NAME == 'index' || ACTION_NAME == 'getSendPrizeTrace' || ACTION_NAME == 'getBackRecord')) {
            //奖品发放失败记录
            $this->sendAwardTraceModel = D('SendAwardTrace');
            $failedCount = $this->sendAwardTraceModel->getFailedRecord($this->node_id, $mId);
            $this->assign('failedCount', $failedCount);
            //奖品回退数
            $backCount = $this->cjRuleListModel->getBackRecord($this->node_id, $mId, true);
            $this->assign('backCount', $backCount);
        }
    }

    public function index() {
        $batch_id = I('batch_id');
        if (empty($batch_id))
            $this->error('参数错误！');
        
        $node_id = $this->node_id;
        $prizeSetUrl = $this->cjRuleListModel->getPrizeSetUrl($batch_id);//每个种类的活动的奖品配置页的url不一样，取一下
        $this->assign('prizeSetUrl', $prizeSetUrl);
        $list = $this->cjRuleListModel->getJpList($node_id, $batch_id);//奖品总览
        $this->assign('list', $list);
        $this->assign('batch_id', $batch_id);
        $this->display();
    }

    public function index2() {
        $batch_id = I('batch_id');
        $batch_type = I('batch_type');
        
        if (empty($batch_id) || empty($batch_type))
            $this->error('参数错误！');
        
        $mod = M('tmarketing_info');
        $node_id = $mod->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $batch_id, 
                'batch_type' => $batch_type))
            ->getField('node_id');
        
        $model = M('tcj_rule');
        $data = array(
            'batch_id' => $batch_id, 
            'batch_type' => $batch_type, 
            'node_id' => $node_id);
        $query_x = $model->where($data)
            ->order('id desc')
            ->select();
        if ($query_x) {
            $resp = array();
            $m_ = M('tcj_trace');
            $model_list = M('tcj_batch');
            
            $nodemodel = M('tbatch_info');
            
            foreach ($query_x as $key => $row_x) {
                
                $wh = array(
                    'cj_rule_id' => $row_x['id']);
                $row_x['prize_list'] = $model_list->where($wh)
                    ->order('award_level asc')
                    ->select();
                $row_x['end_time'] = $query_x[($key + 1)]['add_time'];
                
                if (! empty($row_x['prize_list'])) {
                    foreach ($row_x['prize_list'] as $k => $v) {
                        $cj_count = $m_->where(
                            array(
                                'cj_rule_id' => $row_x['id'], 
                                'prize_level' => $v['award_level'], 
                                'status' => '2'))->count();
                        $row_x['prize_list'][$k]['cj_count'] = $cj_count;
                        $row_x['prize_list'][$k]['batch_name'] = $nodemodel->where(
                            array(
                                'node_id' => $node_id, 
                                'batch_no' => $v['activity_no']))->getField(
                            'batch_name');
                    }
                }
                $resp[] = $row_x;
            }
        }
        
        $this->assign('resp', $resp);
        $this->display();
    }
    
    /**
     * 奖品发放使用情况
     */
    public function getSendPrizeTrace() {
        $mId = I('batch_id');//活动号
        $phoneNo = I('activ_tel');//手机号
        $nickname = I('wx_name');//微信昵称
        $time = I('ff_date');//时间
        $sendStatus = I('send_status');//发送状态
        $sendAwardTraceModel = D('SendAwardTrace');
        $result = $sendAwardTraceModel->getTraceList($this->node_id, $mId, $phoneNo, $nickname, $time, $sendStatus);
        $trace = $result['trace'];
        $p = $result['p'];
        foreach ($trace as $k => &$v) {
            $v['send_time'] = date('Y-m-d H:i:s', strtotime($v['send_time']));
            $v['ret_desc'] = ($v['ret_code'] == '0000') ? '' : $v['ret_desc'];
            if (!$v['phone_no']) {
                $v['phone_no'] = $v['send_mobile'];
            }
            if (strlen($v['phone_no']) > 11) {
                $wxInfo = D('TweixinInfo')->getWxUser($v['phone_no'], 'nickname');
                $v['wx_name'] = $wxInfo['nickname'];
                $v['mobile'] = '';
            } elseif ($v['phone_no'] == '13900000000') {//这种情况需要把tsend_award_trace的phone_no改掉，从cj_trace表取send_mobile
                $sendAwardTraceModel->savePhoneNumberById($this->node_id, $v['id'], $v['send_mobile']);
                $v['mobile'] = $v['send_mobile'];
                //$v['wx_name'] = '';
            } else {
                $v['mobile'] = $v['phone_no'];
                //$v['wx_name'] = '';
            }
            //话费，Q币
            $v['status_txt'] = $this->cjRuleListModel->getTraceStatus($v['request_id'], $v['batch_class'], $v['goods_id']);
        }unset($v);
        $this->assign('page', $p->show());
        $this->assign('trace', $trace);
        $this->assign('batch_id', $mId);
        $this->display();
    }
    
    public function resendPrize() {
        $batch_id = I('request.batch_id');
        if (empty($batch_id))
            $this->error('参数错误！');
        $result = D('SendAwardTrace')->reviseDealFlagByMid($this->node_id, $batch_id);
        $this->success();
    }

    /**
     * 点击参与会员数 跳这里 新会员显示
     */
    public function members()
    {
        $res = I('get.');
        $batch_id = $res['batch_id'];
        $batch_type = $res['batch_type'];
        $member_sum = $res['member_sum'];//会员总数量
        $node_id = $this->nodeId;

        import('ORG.Util.Page'); // 导入分页类

        $whe['m.node_id'] = $node_id;
        $whe['t.m_id'] = $batch_id;
        $whe['m.add_time'] = array("exp","=t.add_time");

        $pageCount = $this->getMemberInfo($whe,false);

        $Page = new Page($pageCount, 10); // 实例化分页类 传入总记录数和每页显示的记录数

        $list = $this->getMemberInfo($whe,true,$Page->firstRow,$Page->listRows);

        $show = $Page->show(); // 分页显示输出

        $newCount = count($list);//新会员数量
        if(!$newCount){
            log_write('new member is null,sql = ',M()->_sql());
        }
        if(!$member_sum || $member_sum<0){
            $oldCount = 0;
        }else{
            $oldCount = $member_sum-$newCount;
        }

        $this->assign('page', $show); // 赋值分页输出
        $this->assign('member_sum',$member_sum);
        $this->assign('batch_id',$batch_id);
        $this->assign('oldCount',$oldCount);
        $this->assign('newCount',$newCount);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     *  点击老会员 跳这里
     */
    public function oldMember()
    {
        $res = I('get.');
        $batch_id = $res['batch_id'];
        $newCount = $res['newCount'];
        $oldCount = $res['oldCount'];
        $member_sum = $res['member_sum'];
        $node_id = $this->nodeId;

        import('ORG.Util.Page'); // 导入分页类

        $whe['m.node_id'] = $node_id;
        $whe['t.m_id'] = $batch_id;
        $whe['t.add_time'] = array(array('exp','<> m.add_time'),array('exp','is null'),'or');

        $pageCount = $this->getMemberInfo($whe,false);

        $Page = new Page($pageCount, 10); // 实例化分页类 传入总记录数和每页显示的记录数

        $list = $this->getMemberInfo($whe,true,$Page->firstRow,$Page->listRows);
        if(!$pageCount){
            log_write('old member is null,sql = ',M()->_sql());
        }

        $show = $Page->show(); // 分页显示输出

        $this->assign('page', $show); // 赋值分页输出
        $this->assign('member_sum',$member_sum);
        $this->assign('batch_id',$batch_id);
        $this->assign('oldCount',$oldCount);
        $this->assign('newCount',$newCount);
        $this->assign('list',$list);
        $this->display();
    }
    
    public function getBackRecord() {
        $batch_id = I('batch_id');
        if (empty($batch_id))
            $this->error('参数错误！');
        $re = $this->cjRuleListModel->getBackRecord($this->node_id, $batch_id);
        $list = $re['trace'];
        $p = $re['p'];
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        $this->assign('batch_id', $batch_id);
        $this->display();
    }

    /**
     * 查询会员信息
     * @param           $whe
     * @param bool|true $status  true为查询数据 false为查询条数
     * @param           $limitStart
     * @param           $limitEnd
     *
     * @return string
     */
    private function getMemberInfo($whe,$status = true,$limitStart = '',$limitEnd = '')
    {
        $list = '';
        switch($status){
            case true:
                $list = M('tmember_join_trace')->alias('t')
                        ->join('LEFT JOIN tmember_info m ON m.id=t.member_id')
                        ->join('LEFT JOIN tmember_cards c ON m.org_card_id=c.id')
                        ->where($whe)
                        ->field('m.phone_no,m.nickname,m.add_time,m.update_time,c.card_name')
                        ->limit($limitStart . ',' . $limitEnd)
                        ->select();
                break;
            case false:
                $list = M('tmember_join_trace')->alias('t')
                        ->join('LEFT JOIN tmember_info m ON m.id=t.member_id')
                        ->join('LEFT JOIN tmember_cards c ON m.org_card_id=c.id')
                        ->where($whe)
                        ->field('m.phone_no')
                        ->count();
                break;
        }

        return $list;
    }
}
