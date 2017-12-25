<?php

/**
 * 审核处理
 */
class ApplyAction extends YhbAction {

    public $_authAccessMap = '*';
    // 正常
    CONST PUB_ADD = 0;
    // 审核申请
    CONST PUB_APPLY = 1;
    // 审核通过
    CONST PUB_PASSED = 2;
    // 审核拒绝
    CONST PUB_NOT_PASS = 3;
    
    // 审核流水账状态
    // 通过
    CONST CHECK_STATUS_PASSED = 1;
    // 拒绝
    CONST CHECK_STATUS_NOT_PASS = 2;
    // 审核类型
    CONST CHECK_TYPE = 0;
    // 渠道ID
    private $CHANNEL_ID = '';

    const BATCH_TYPE_WEIGUANWANG = 13;

    const OFF_LINE_TYPE = 2;

    const ON_LINE_TYPE = 1;

    const ELSE_CHANNEL = 5;

    const MEMBER_RECRUIT_BATCH_TYPE = 52;

    public function _initialize() {
        parent::_initialize();
        import("@.Vendor.CommonConst");
        $this->CHANNEL_ID = CommonConst::SNS_TYPE_YHB;
    }

    /**
     * 申请审核
     *
     * @param int $cid 审核配置ID
     * @return string 状态信息
     */
    public function apply() {
        $cid = I('cid');
        if (! is_numeric($cid) || $cid < 0) {
            $this->error('参数错误');
        }
        if ($this->is_admin) {
            $this->error('您没有权限操作！');
        }
        
        $market_model = M('tfb_yhb_mconfig');
        $where['id'] = $cid;
        
        $tminfo = $market_model->where($where)->find();
        // 是否是翼蕙宝的活动
        if (empty($tminfo)) {
            $this->error('系统错误');
        }
        $data['pub_status'] = self::PUB_APPLY;
        $data['last_apply_time'] = time();
        $res = $market_model->where($where)->save($data);
        if ($res == false) {
            $this->error('申请审核失败');
        }
        $this->success('申请审核成功');
    }
    
    // 审核
    public function check() {
        if ($this->isPost()) {
            if (! $this->is_admin) {
                $this->error('您没有权限操作！');
            }
            $id = I('id', '');
            $type = I('type', '', 'strtolower');
            $memo = I('memo', '', 'htmlspecialchars,trim');
            if (! is_numeric($id) || $id <= 0) {
                $this->error('非法参数');
            }
            // 判断操作
            switch ($type) {
                case 'passed': // 通过
                    $bind = true;
                    $status = self::PUB_PASSED;
                    $check_status = self::CHECK_STATUS_PASSED;
                    $msg_type = '通过';
                    break;
                case 'notpass': // 拒绝
                    $bind = false;
                    $status = self::PUB_NOT_PASS;
                    $check_status = self::CHECK_STATUS_NOT_PASS;
                    $msg_type = '拒绝';
                    if (empty($memo)) {
                        $this->error('拒绝原因不能为空');
                        exit();
                    }
                    break;
                default:
                    $this->error('非法参数');
            }
            $market_model = M('tfb_yhb_mconfig');
            $m_where['id'] = $id;
            $tminfo = $market_model->where($m_where)->find();
            // 是否是翼蕙宝的活动
            if (empty($tminfo)) {
                $this->error('系统错误');
            }
            if ($tminfo['pub_status'] != self::PUB_APPLY) {
                $this->error('活动已审核');
            }
            
            $tranDb = new Model();
            $tranDb->startTrans();
            $userService = D('UserSess', 'Service');
            $user_info = $userService->getUserInfo();
            $check_trace_model = M('tfb_yhb_check_trace');
            $c_data = array(
                'type' => self::CHECK_TYPE, 
                'relation_id' => $tminfo['mid'], 
                'check_status' => $check_status, 
                'check_user_id' => $user_info['user_id'], 
                'check_time' => time(), 
                'check_memo' => $memo, 
                'user_name' => $user_info['user_name']);
            // 记录审核操作
            $cid = $check_trace_model->add($c_data);
            if (empty($cid)) {
                $tranDb->rollback();
                $this->error('操作失败');
            }
            $label = null;
            // 发布活动TODO：：
            if ($bind) {
                $sns_type = $this->CHANNEL_ID;
                $t_where = array(
                    'sns_type' => $sns_type, 
                    'node_id' => $this->node_id);
                $tchannel_info = M('tchannel')->field('id')
                    ->where($t_where)
                    ->find();
                $channel = array(
                    $tchannel_info['id']);
                $batch_id = $tminfo['mid'];
                
                $model = M('tmarketing_info');
                $map = array(
                    'node_id' => array(
                        'exp', 
                        "in (" . $this->nodeIn() . ")"), 
                    'id' => $batch_id);
                $info = $model->where($map)
                    ->Field('id,node_id,batch_type')
                    ->find();
                if (! $info) {
                    $tranDb->rollback();
                    $this->error('未创建活动！');
                }
                $batch_id = $info['id'];
                $node_id = $info['node_id'];
                $batch_type = $info['batch_type'];
                $data = array();
                $exec = M('tbatch_channel');
                $search_map = array(
                    'node_id' => $node_id, 
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id);
                // 官网渠道
                $sns_type_arr = array();
                // 绑定成功渠道id
                // (2腾讯,3QQ空间,4人人网,5开心网,6豆瓣,11自定义渠道)->(社交渠道+自定义渠道)
                $onLineChannel = M('tchannel')->where(
                    array(
                        'type' => self::ON_LINE_TYPE, 
                        'sns_type' => array(
                            'in', 
                            '2,3,4,5,6,11'), 
                        'status' => '1', 
                        'node_id' => $this->node_id))->getField('id', true);
                if (! $onLineChannel) {
                    $onLineChannel = array();
                }
                // 查看(社交渠道+自定义渠道)之前有没有被绑定过,绑定过的都改状态为2，然后新的channel_id过来时，要么新增，要么改状态为1
                $where = array(
                    'node_id' => $node_id, 
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id);
                foreach ($onLineChannel as $val) {
                    $where['channel_id'] = $val;
                    $is_bind = $exec->where($where)->find();
                    if ($is_bind) {
                        $exec->where($where)->save(
                            array(
                                'status' => '2'));
                    }
                }
                // 标签渠道解绑，后面重新绑定
                $offLineMap = array(
                    'node_id' => $node_id, 
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id, 
                    'type' => self::OFF_LINE_TYPE);
                // 找到这个活动的 标签渠道 的渠道号
                $channelId = M('tchannel')->where($offLineMap)->getField('id', 
                    true);
                // 解绑channel_batch表
                if ($channelId) {
                    $re = $exec->where(
                        array(
                            'channel_id' => array(
                                'in', 
                                $channelId)))->save(
                        array(
                            'status' => '2'));
                    if (false === $re) {
                        $tranDb->rollback();
                        $this->error('解绑渠道失败！');
                    }
                }
                // 解绑channel表//下面的循环里如果有会重新绑定
                if ($channelId) {
                    $re = M('tchannel')->where($offLineMap)->save(
                        array(
                            'batch_type' => '', 
                            'batch_id' => ''));
                    if (false === $re) {
                        $tranDb->rollback();
                        $this->error('解绑渠道失败！');
                    }
                }
                
                foreach ($channel as $k => $v) {
                    // 判断该渠道是否应绑定
                    $is_ = M('tchannel')->where(
                        array(
                            'node_id' => $node_id, 
                            'id' => $v))->find();
                    // 线下渠道
                    
                    if ($is_['type'] != '1') {
                        if (! empty($is_['batch_type']) &&
                             ! empty($is_['batch_id'])) {
                            // 更新标签状态
                            $modbc = M('tbatch_channel');
                            $whbc = array(
                                'node_id' => $node_id, 
                                'batch_type' => $is_['batch_type'], 
                                'batch_id' => $is_['batch_id'], 
                                'channel_id' => $v);
                            $query = $modbc->where($whbc)->save(
                                array(
                                    'status' => '2', 
                                    'change_time' => Date('YmdHis')));
                            if ($query === false) {
                                $tranDb->rollback();
                                $this->error('发布渠道失败！');
                            }
                        }
                        // 更新渠道绑定
                        $data = array(
                            'batch_type' => $batch_type, 
                            'batch_id' => $batch_id);
                        $query = M('tchannel')->where(
                            array(
                                'node_id' => $node_id, 
                                'id' => $v))->save($data);
                        if ($query === false) {
                            $tranDb->rollback();
                            $this->error('发布渠道失败！');
                        }
                        // 绑定
                        $mod = M('tbatch_channel');
                        $data = array(
                            'batch_type' => $batch_type, 
                            'batch_id' => $batch_id, 
                            'channel_id' => $v, 
                            'add_time' => Date('YmdHis'), 
                            'node_id' => $node_id);
                        $tbatchChannelInfo = $mod->where(
                            array(
                                'batch_type' => $batch_type, 
                                'batch_id' => $batch_id, 
                                'channel_id' => $v, 
                                'node_id' => $node_id, 
                                'status' => '2'))->find();
                        if ($tbatchChannelInfo) {
                            $query = $mod->where(
                                array(
                                    'id' => $tbatchChannelInfo['id']))->save(
                                array(
                                    'status' => '1'));
                        } else {
                            $query = $mod->add($data);
                        }
                        
                        if (! $query) {
                            $tranDb->rollback();
                            $this->error('绑定失败！');
                        }
                        $label_id = $query;
                    } else {
                        // 判断如果选择了O2O案例渠道，则不插入我的活动渠道，否则插入O2O案例渠道
                        // if($is_['sns_type']=='13'&&$haveo2o!=""){
                        // continue;
                        // }
                        // 获取商户客户号
                        $client_id = M('tnode_info')->where(
                            array(
                                'node_id' => $this->node_id))->getField(
                            'client_id');
                        
                        if (! $client_id) {
                            $tranDb->rollback();
                            $this->error('旺号错误！');
                        }
                        $seq_id = 'client_' . $client_id;
                        $seq_str = sprintf('%06s', $client_id);
                        $sql = "SELECT _nextval('" . $seq_id . "') as label_id";
                        $label_arr = M()->query($sql);
                        if ($label_arr[0]['label_id'] == '0') {
                            $tranDb->rollback();
                            $this->error('标签号生成失败！');
                        }
                        $label_id = $label_arr[0]['label_id'];
                        $label_id = $seq_str . sprintf('%06s', $label_id);
                        
                        // 互联网渠道和自定义渠道
                        $search_map['channel_id'] = $v;
                        $is_exits = $exec->where($search_map)->select();
                        if ($is_exits) {
                            $re = $exec->where($search_map)->save(
                                array(
                                    'status' => '1')); // 如果是绑定过的，由于这个循环之前把所有的（互联网渠道和自定义渠道）改为了2，现在改回来
                            if (false === $re) {
                                $tranDb->rollback();
                                $this->error('更新自定义渠道和社交渠道状态失败！');
                            }
                        } else { // 没绑定过的新增
                            $data['batch_type'] = $batch_type;
                            $data['batch_id'] = $batch_id;
                            $data['channel_id'] = $v;
                            $data['add_time'] = Date('YmdHis');
                            $data['node_id'] = $node_id;
                            $data['label_id'] = $label_id;
                            $query = $exec->data($data)->add();
                            if (! $query) {
                                $tranDb->rollback();
                                $this->error('系统错误！');
                            }
                        }
                    }
                }
            }
            $m_data = array(
                'pub_status' => $status, 
                'label_id' => $label_id, 
                'last_check_id' => $cid);
            $m_res = $market_model->where($m_where)->save($m_data);
            
            if ($m_res === false) {
                $tranDb->rollback();
                $this->error('操作失败');
            }
            node_log('活动发布|活动类型-活动号' . $batch_type . '-' . $batch_id);
            $tranDb->commit();
            $this->success($msg_type . '审核成功');
        } else {
            $this->error('非法操作');
        }
    }
}

