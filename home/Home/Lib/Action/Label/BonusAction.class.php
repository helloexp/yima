<?php

class BonusAction extends MyBaseAction {

    public $upload_path;

    const BATCH_TYPE = 41;

    public $node_short_name = '';

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $id = $this->id; // channel_id
        $batch_id = $this->batch_id; // tmaketing_info id
                                     
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 获取活动红包信息
        $map['m.id'] = $batch_id;
        $map['b.m_id'] = $batch_id;
        $map['b.node_id'] = $this->node_id;
        $bonusInfo = M()->table("tbonus_info b")->field(
            'b.*,m.id as batch_id,m.node_name,m.memo,m.name,m.batch_type,m.node_id,m.click_count,m.status,m.start_time,m.end_time,m.share_pic,m.wap_info,m.log_img')
            ->join('tmarketing_info m on b.m_id=m.id')
            ->where($map)
            ->find();
        
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => U('index', 
                array(
                    'id' => $this->id, 
                    'saler_id' => I('get.saler_id')), '', '', TRUE), 
            'title' => $bonusInfo['name'], 
            // 'shareNote'=>$row['wap_info'],
            'desc' => $bonusInfo['wap_info']);
        // 'imgUrl'=> get_upload_url($row['share_pic'])
        
        if ($bonusInfo['share_pic'])
            $shareArr['imgUrl'] = get_upload_url($bonusInfo['share_pic']);
        else
            $shareArr['imgUrl'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                 '/Label/Image/weixin_share.jpg';
        
        $bonusGetList = M()->table('tbonus_use_detail t')
            ->join('tbonus_detail d ON d.id=t.bonus_detail_id')
            ->field('t.phone,d.amount')
            ->where(array(
            't.bonus_id' => $bonusInfo['id']))
            ->select();
        $this->assign('bonusGetList', $bonusGetList);
        $this->assign('shareData', $shareArr);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('id', $id);
        $this->assign('saler_id', I('get.saler_id'));
        $this->assign('batch_id', $batch_id);
        $this->display(); // 输出模板
    }
    
    // 提交抢红包
    public function addSubmit() {
        $phone = I('post.phone');
        $validatcode = I('post.validatcode');
        $id = I('post.id');
        $bonus_id = I('post.bonus_id');
        $batch_id = I('post.batch_id');
        $salerId = I('post.saler_id');
        
        if ($phone == "") {
            $this->ajaxReturn("error", 
                array(
                    'msg' => "手机号码不能为空！"), 0);
        }
        if ($validatcode == "") {
            $this->ajaxReturn("error", 
                array(
                    'msg' => "验证码不能为空！"), 0);
        }
        if (session('verify_cj') != md5($validatcode)) {
            $this->ajaxReturn("error", 
                array(
                    'msg' => "验证码错误！"), 0);
        }
        // 新增抽红包绑定关系功能（旺分销客户关系中查看）
        if (! empty($salerId)) {
            $wfxService = D('Wfx', 'Service');
            $wfxService->bind_customer($this->node_id, $phone, $salerId, 3);
        }
        // 记录cookie bonus_mid
        cookie('bonus_' . $batch_id, $phone, 86400); // 指定cookie保存时间 1天
        
        M()->startTrans();
        
        // 判断member_info_tmp用户是否存在，不存在插入
        $userId = addMemberByO2o($phone, $this->node_id, $this->channel_id, $batch_id);
        
        // 判断是否限制领取，如果限制判断用户是否超出限制
        $map['m.id'] = $batch_id;
        $map['b.m_id'] = $batch_id;
        $map['b.node_id'] = $this->node_id;
        $bonusInfo = M()->table("tbonus_info b")->field(
            'b.*,m.id as batch_id,m.node_name,m.memo,m.name,m.batch_type,m.node_id,m.click_count,m.status,m.start_time,m.end_time')
            ->join('tmarketing_info m on b.m_id=m.id')
            ->where($map)
            ->find();
        
        // 判断如果活动状态停用、活动时间过期，红包时间过期
        $curDate = date('YmdHis');
        if ($bonusInfo['start_time'] > $curDate) {
            M()->rollback();
            $this->ajaxReturn("error", 
                array(
                    'msg' => "别着急哦！活动还没开始呢！"), 0);
        }
        if ($bonusInfo['end_time'] < $curDate) {
            M()->rollback();
            $this->ajaxReturn("error", 
                array(
                    'msg' => "红包已经过期，无法领取！"), 0);
        }
        if ($bonusInfo['status'] == '2' ||
             $bonusInfo['bonus_end_time'] < $curDate ||
             $bonusInfo['bonus_end_time'] < $curDate) {
            M()->rollback();
            $this->ajaxReturn("error", 
                array(
                    'msg' => "对不起，红包活动已过期或已停用！"), 0);
        }
        
        // 查询已经领取的红包数量是否超出限制
        $limitmap['b.m_id'] = $batch_id;
        $limitmap['b.node_id'] = $this->node_id;
        $limitmap['b.bonus_id'] = $bonus_id;
        $limitmap['b.member_id'] = $userId;
        $getInfo = M()->table("tbonus_use_detail b")->field(
            'sum(bonus_num) as bonus_num')
            ->where($limitmap)
            ->find();
        
        // 如果限制领取数量
        if ($bonusInfo['limit_flag'] == '1') {
            $haveNum = empty($getInfo['bonus_num']) ? 0 : $getInfo['bonus_num'];
            if (($haveNum + 1) > $bonusInfo['limit_num']) {
                M()->rollback();
                $this->ajaxReturn("error", 
                    array(
                        'msg' => "对不起，你已经领过红包！"), 0);
            }
        }
        
        // 查询未使用的红包面额,活动状态正常、时间正常
        
        $unmap['b.m_id'] = $batch_id;
        $unmap['b.node_id'] = $this->node_id;
        $unmap['b.id'] = $bonus_id;
        $currentDay = date('YmdHis');
        $unmap['_string'] = "(t.num-t.get_num)>0 AND m.status=1 AND m.end_time>='" .
             $currentDay . "' AND b.bonus_end_time>='" . $currentDay . "'";
        $unUseInfo = M()->table("tbonus_info b")->field('t.id,t.bonus_id,t.amount')
            ->join('tmarketing_info m on b.m_id=m.id')
            ->join('tbonus_detail t on t.bonus_id=b.id')
            ->where($unmap)
            ->order('rand()')
            ->find();
        
        // $unUseInfo=M('tbonus_detail
        // b')->field('id,bonus_id,amount')->where($unmap)->order('b.id
        // asc')->find();
        
        // 查询到有可用红包可领，更新领用数量，以及插入已领表
        if (! empty($unUseInfo)) {
            
            $wmap['bonus_id'] = $unUseInfo['bonus_id'];
            $wmap['m_id'] = $batch_id;
            $wmap['node_id'] = $this->node_id;
            $wmap['id'] = $unUseInfo['id'];
            $wmap['amount'] = $unUseInfo['amount'];
            $res = M('tbonus_detail')->where($wmap)->setInc('get_num');
            if ($res === false) {
                M()->rollback();
                $this->ajaxReturn("error", 
                    array(
                        'msg' => "更新领取数量失败！"), 0);
            } else {
                // 插入会员领取表，如果存在统一面额的则更新，否则插入
                $newmap['m_id'] = $batch_id;
                $newmap['node_id'] = $this->node_id;
                $newmap['bonus_id'] = $bonus_id;
                $newmap['bonus_detail_id'] = $unUseInfo['id'];
                $detailInfo = M('tbonus_use_detail')->where($newmap)->find();
                
                // 不存在插入新的领取红包数据，否则更新数量
                /*
                 * if(!empty($detailInfo)){ $dmap['m_id']=$batch_id;
                 * $dmap['node_id']=$this->node_id; $dmap['bonus_id']=$bonus_id;
                 * $dmap['bonus_detail_id']=$unUseInfo['id'];
                 * $dres=M('tbonus_use_detail')->where($dmap)->setInc("bonus_num");
                 * if($dres===false){ M()->rollback();
                 * $this->ajaxReturn("error","更新用户领取失败！",0); } M()->commit();
                 * $this->ajaxReturn("success",
                 * '恭喜你成功领取'.$unUseInfo['amount'].'红包',1); }else{
                 */
                
                $ddata = array(
                    'm_id' => $batch_id, 
                    'node_id' => $this->node_id, 
                    'bonus_id' => $bonus_id, 
                    'bonus_detail_id' => $unUseInfo['id'], 
                    'bonus_num' => 1, 
                    'bonus_use_num' => 0, 
                    'phone' => $phone, 
                    'member_id' => $userId, 
                    'status' => '1');
                $res = M('tbonus_use_detail')->data($ddata)->add();
                if (! $res) {
                    M()->rollback();
                    $this->ajaxReturn("error", 
                        array(
                            'msg' => "插入用户领取失败！"), 0);
                }
                M()->commit();
                
                $ret_arr = array(
                    'msg' => "恭喜你成功领取" . $unUseInfo['amount'] . "元红包");
                if ($bonusInfo['link_flag'] == '1' && $bonusInfo['link_url']) {
                    $ret_arr['jump_url'] = $bonusInfo['link_url'];
                    $ret_arr['button_name'] = $bonusInfo['button_name'];
                }
                $this->ajaxReturn("success", $ret_arr, 1);
                
                // }
            }
        } else {
            M()->rollback();
            $this->ajaxReturn("error", 
                array(
                    'msg' => "已经被领取完！"), 0);
        }
    }
    
}