<?php
// 抽奖设置
class CjSetAction extends YhbAction {

    private $hbtpybx_flag = false;

    private $cjSetModel;

    public $_authAccessMap = '*';

    const MEMBER_RECRUIT_BATCH_TYPE = '52';

    public function _initialize() {
        parent::_initialize();
        $this->cjSetModel = D('CjSet');
        $this->hbtpybx_flag = $this->node_id == C('hbtpybx.node_id') ||
             $this->node_id == C('gstpybx.node_id') ||
             $this->node_id == C('sxtpybx.node_id');
        $this->assign('hbtpybx_flag', $this->hbtpybx_flag);
    }

    public function index() {
        $cj_button_type = array(
            '2', 
            '3', 
            '10', 
            '20');
        $batch_id = I('batch_id', NULL, 'trim');
        
        if (empty($batch_id))
            $this->error('活动id不能为空！');
            
            // 校验活动
        $query_arr = M('tmarketing_info')->field(
            'id,cj_phone_type,member_join_flag,member_reg_mid,member_batch_award_id,fans_collect_url,batch_type,defined_one_name,member_batch_id,join_mode')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batch_id))
            ->find();
        
        if (! $query_arr)
            $this->error('参数错误！');
            
            // 微信限制下线
        if (in_array($query_arr['batch_type'], 
            array(
                '42', 
                '34', 
                '30', 
                '36'))) {
            $this->error("该活动已下线");
            exit();
        }
        
        if (in_array($query_arr['batch_type'], $cj_button_type))
            $isShowCjButton = true;
            
            // 设置
        $cj_rule_arr = M('tcj_rule')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id, 
                'status' => '1'))->find();
        
        $jp_arr = array();
        // 分类
        if ($cj_rule_arr) {
            $cj_cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_id, 
                    'cj_rule_id' => $cj_rule_arr['id']))->select();
            
            // 奖品
            $jp_arr = M()->table('tcj_batch a')
                ->field('a.*,b.batch_name')
                ->join('tbatch_info b on a.b_id=b.id')
                ->where(
                "a.node_id='" . $this->node_id . "' and a.batch_id='" . $batch_id .
                     "' and cj_rule_id = '" . $cj_rule_arr['id'] . "'")
                ->select();
        }
        
        // 获取商户会员卡信息
        $mem_batch = $this->cjSetModel->getMemberBatch($this->node_id);
        $this->assign('mem_batch', $mem_batch);
        
        // 查询该机构的微信分组
        $user_wx_group = $this->cjSetModel->getWxGroup($this->node_id);
        $this->assign('user_wx_group', $user_wx_group);
        
        // 查询 点击“微信号”是否需要弹框//window_id为12表示这里要用到的弹窗号
        $wx_bd = M('tpop_window_control')->where(
            array(
                'node_id' => $this->node_id, 
                'window_id' => 12))->find() ? 1 : 0;
        $this->assign('wx_bd', $wx_bd);
        // 选中的粉丝
        $this->_assignWxGoupOrBatch($query_arr['member_batch_id'], 
            $query_arr['member_batch_award_id'], $query_arr['join_mode'], 
            $mem_batch, $user_wx_group);
        // 是否有招募活动
        $phone_recruit = $query_arr['member_reg_mid'] ? 1 : 0;
        $this->assign('phone_recruit', $phone_recruit);
        $wx_recruit = $query_arr['fans_collect_url'] ? 1 : 0;
        $this->assign('wx_recruit', $wx_recruit);
        $this->assign('fans_collect_url', $query_arr['fans_collect_url']);
        
        // 是否限制
        $this->assign('member_batch_id_flag', 
            ($query_arr['member_batch_id'] == - 1 ? 0 : 1));
        $member_zj_flag = $query_arr['member_batch_award_id'] == - 1 ? 0 : 1;
        $this->assign('member_zj_flag', $member_zj_flag);
        
        // 未中奖选项学处理
        $cj_rule_arr['no_award_notice'] = explode('|', 
            $cj_rule_arr['no_award_notice']);
        $this->assign('batch_type', $query_arr['batch_type']);
        $this->assign('cj_phone_type', $query_arr['cj_phone_type']);
        $this->assign('query_arr', $query_arr);
        $this->assign('jp_arr', $jp_arr);
        $this->assign('cj_cate_arr', $cj_cate_arr);
        $this->assign('cj_rule_arr', $cj_rule_arr);
        $this->assign('batch_id', $batch_id);
        $this->assign('isShowCjButton', $isShowCjButton);
        $this->assign('defined_one_name', $query_arr['defined_one_name']);
        $this->assign('regName', 
            M('tmarketing_info')->where(
                "id='{$query_arr['member_reg_mid']}' and batch_type=" .
                     self::MEMBER_RECRUIT_BATCH_TYPE)
                ->getField('name'));
        $this->display("MarketActive:CjSet_index");
    }
    
    // 设置奖项
    public function jpType() {
        $cj_cate_id = I('cj_cate_id', NULL, 'trim');
        $batch_id = I('batch_id', NULL, 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
        
        $batchInfo = M('tmarketing_info')->where("id='{$batch_id}'")->find();
        if (! $batchInfo) {
            $this->error('活动id无效！');
        }
        
        if ($cj_cate_id) {
            $cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $cj_cate_id, 
                    'batch_id' => $batch_id))->find();
        }
        // 获取商户名称
        $nodeName = M('tnode_info')->where("node_id='{$this->node_id}'")->getField(
            'node_name');
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        $this->assign('mem_batch', $mem_batch);
        $this->assign('cate_arr', $cate_arr);
        $this->assign('cj_cate_id', $cj_cate_id);
        $this->assign('batch_id', $batch_id);
        $this->assign('batchInfo', $batchInfo);
        $this->display("MarketActive:CjSet_jpType");
    }
    
    // 设置奖品
    public function selectJp() {
        $batch_id = I('batch_id', '', 'trim,intval');
        $cj_batch_id = I('cj_batch_id', '', 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
        
        $batchInfo = M('tmarketing_info')->where("id='{$batch_id}'")->find();
        if (! $batchInfo) {
            $this->error('活动id无效！');
        }
        
        if ($cj_batch_id) {
            // 奖品
            $jp_arr = M()->table('tcj_batch a')
                ->join('tbatch_info b on a.b_id=b.id')
                ->join('tgoods_info c on b.goods_id=c.goods_id')
                ->join('tcj_cate d on d.id = a.cj_cate_id')
                ->field(
                "a.*, b.*, c.goods_type, a.cj_cate_id, b.card_id, c.remain_num as goods_remain_num")
                ->where(
                "a.node_id='" . $this->node_id . "' and a.id='" . $cj_batch_id .
                     "'")
                ->find();
        }
        // 查询所有奖项
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $batch_id);
        $cate_arr = M('tcj_cate')->where($map)->getField('id, name', true);
        $this->assign('cj_batch_id', $cj_batch_id);
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batchInfo['batch_type']);
        $this->assign('cate_arr', $cate_arr);
        $this->assign('jp_arr', $jp_arr);
        
        if (in_array($batchInfo['batch_type'], 
            array(
                '2', 
                '3', 
                '10', 
                '20', 
                '50', 
                '1004', 
                '49'))) {
            $this->display('MarketActive:CjSet_selectJp');
        }
        exit();
    }
    
    // 选择粉丝招募活动
    public function selectMemreg() {
        $map = array(
            'm.node_id' => $this->nodeId, 
            'm.batch_type' => self::MEMBER_RECRUIT_BATCH_TYPE, 
            'm.status' => '1', 
            'm.re_type' => '0', 
            'm.start_time' => array(
                'elt', 
                date('YmdHis')), 
            'm.end_time' => array(
                'egt', 
                date('YmdHis')), 
            'c.channel_id' => array(
                'exp', 
                'is not null'), 
            'c.status' => '1');
        import("ORG.Util.Page");
        $count = M()->table("tmarketing_info m")->join(
            'tbatch_channel c ON m.id=c.batch_id and c.status=1')
            ->where($map)
            ->count('distinct m.id');
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table("tmarketing_info m")->field('m.*')
            ->join('tbatch_channel c ON m.id=c.batch_id and c.status=1')
            ->where($map)
            ->group('m.id')
            ->order('m.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $today = date("YmdHis", time());
        foreach ($list as &$v) {
            if (mb_strlen($v['name'], 'UTF-8') > 18) {
                $v['smallname'] = mb_substr($v['name'], 0, 18, 'UTF-8') . "...";
            } else {
                $v['smallname'] = $v['name'];
            }
            if ($v['start_time'] <= $today && $v['end_time'] >= $today) {
                $v['batch_status'] = '2';
            }
        }
        unset($v);
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign("page", $page);
        // print_r($list);exit;
        if (empty($list)) {
            $this->display("selectMemregesnull");
        } else {
            $this->display("selectMemreges");
        }
    }

    public function selectMemregesFinish() {
        $this->display();
    }

    /**
     * 已阅读抽奖活动中点击微信号后弹出的框并确认后的操作
     */
    public function ajaxAgreeSelectWxNotice() {
        // 先判断一下是否有插入过这个弹窗记录，防止差错
        $winModel = M('tpop_window_control');
        $data = array(
            'node_id' => $this->node_id, 
            'window_id' => 12);
        $result = $winModel->where($data)->find();
        if (! $result) {
            $addResult = $winModel->add($data);
        }
        exit();
    }

    /**
     * 设置已经保存的被选中的分组
     *
     * @param string $join_group_id_arr 数据库查出来的参加抽奖活动的分组
     * @param string $zj_group_id_arr 数据库查出来的限制中奖的分组
     * @param int $join_mode 0手机 1微信
     * @param array $mem_batch 所有手机分组
     * @param array $user_wx_group 所有微信分组
     */
    private function _assignWxGoupOrBatch($join_group_id_arr, $zj_group_id_arr, 
        $join_mode, $mem_batch, $user_wx_group) {
        $result = $this->cjSetModel->getSelectedGroup($join_group_id_arr, 
            $zj_group_id_arr, $join_mode, $mem_batch, $user_wx_group);
        $this->assign('phone_selected', $result['phone_selected']);
        $this->assign('wx_selected', $result['wx_selected']);
        $this->assign('phone_selected_zj', $result['phone_selected_zj']);
        $this->assign('wx_selected_zj', $result['wx_selected_zj']);
    }
}
?>