<?php

class SaleProAction extends BaseAction {

    public $BATCH_TYPE = '41';
    // 随机红包类型
    public $FIX_BATCH_TYPE = '47';
    // 定额红包类型
    public $img_path = "";

    public $tmp_path = "";

    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    /**
     * 随机红包 列表
     */
    public function index() {
        $map = array(
            'm.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $data = $_REQUEST;
        
        if ($data['batch_name'] != '') {
            $map['m.name'] = array(
                'like', 
                '%' . $data['batch_name'] . '%');
        }
        if ($data['batch_status'] != '') {
            $map['m.status'] = $data['batch_status'];
        }
        // 处理特殊查询字段
        $beginDate = I('begin_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['m.start_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' m.end_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['m.batch_type'] = $this->BATCH_TYPE;
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tbonus_info b")->join('tmarketing_info m on b.m_id=m.id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M()->table("tbonus_info b")->field(
            'b.*,m.id as batch_id,m.name,m.batch_type,m.node_id,m.click_count,m.status,m.start_time,m.end_time')
            ->join('tmarketing_info m on b.m_id=m.id')
            ->where($map)
            ->order('m.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if (! empty($list)) {
            foreach ($list as $k => $val) {
                // 查询红包使用情况
                // 查询已发(已领取)
                $map = array(
                    'node_id' => $val['node_id'], 
                    'm_id' => $val['m_id'], 
                    'bonus_id' => $val['id']);
                $sumInfo = M('tbonus_detail')->field(
                    'sum(get_num) as get_num,sum(amount*get_num) as get_amount,sum(use_num) as use_num,sum(use_num*amount) as use_amount')
                    ->where($map)
                    ->find();
                
                $list[$k]['get_num'] = empty($sumInfo['get_num']) ? 0 : $sumInfo['get_num'];
                $list[$k]['get_amount'] = empty($sumInfo['get_amount']) ? '0.00' : sprintf(
                    "%.2f", $sumInfo['get_amount']);
                $list[$k]['use_num'] = empty($sumInfo['use_num']) ? 0 : $sumInfo['use_num'];
                $list[$k]['use_amount'] = empty($sumInfo['use_amount']) ? '0.00' : sprintf(
                    "%.2f", $sumInfo['use_amount']);
                $list[$k]['remain_num'] = $val['bonus_num'] - $sumInfo['get_num'];
                $list[$k]['remain_amount'] = sprintf("%.2f", 
                    $val['bonus_amount'] - $sumInfo['get_amount']);
            }
        }
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('data', $data);
        $batchStatusArr = array(
            '1' => '正常', 
            '2' => '停用');
        $this->assign('batchStatusArr', $batchStatusArr);
        
        $this->display();
    }

    public function add() {
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->display();
    }

    public function addSubmit() {
        $name = I("name", '', 'trim');
        $node_name = I("node_name", '', 'trim');
        $start_time = I("start_time", '', 'trim') . "00";
        $end_time = I("end_time", '', 'trim') . "59";
        $bonus_start_time = I("bonus_start_time", '', 'trim') . "000000";
        $bonus_end_time = I("bonus_end_time", '', 'trim') . "235959";
        $bonus_amount = I("bonus_amount", '', 'trim');
        $bonus_num = I("bonus_num", '', 'trim');
        $min_amount = I("min_amount", '', 'trim');
        $max_amount = I("max_amount", '', 'trim');
        $limit_flag = I("limit_flag", '', 'trim');
        $limit_num = I("limit_num", '', 'trim');
        $memo = I("memo", '', 'trim');
        $wap_info = I("wap_info", '', 'trim');
        $share_pic_val = I("share_pic_val", '', 'trim');
        $logo_pic_val = I("logo_pic_val", '', 'trim');
        // 跳转地址
        $link_flag = I("link_flag", 0, 'intval');
        $url_type = I("url_type", 0, 'intval');
        $link_url = I("link_url", '', 'trim');
        $button_name = I("button_name", '', 'trim');
        if (empty($name))
            $this->ajaxReturn('error', '页面名称不能为空！', 0);
        if (empty($node_name))
            $this->ajaxReturn('error', '商户名称不能为空！', 0);
        if (empty($start_time))
            $this->ajaxReturn('error', '活动有效期开始时间不能为空！', 0);
        if (empty($end_time))
            $this->ajaxReturn('error', '活动有效期结束时间不能为空！', 0);
        if (empty($bonus_start_time))
            $this->ajaxReturn('error', '红包有效期开始时间不能为空！', 0);
        if (empty($bonus_end_time))
            $this->ajaxReturn('error', '红包有效期结束时间不能为空！', 0);
        if ($bonus_amount <= 0)
            $this->ajaxReturn('error', '红包金额必须大于零！', 0);
        if ($bonus_num <= 0)
            $this->ajaxReturn('error', '红包数量必须大于零！', 0);
        
        if (empty($min_amount))
            $this->ajaxReturn('error', '红包最小面额不能为空！', 0);
        if (empty($max_amount))
            $this->ajaxReturn('error', '红包最大面额不能为空！', 0);
        if ($min_amount > $max_amount) {
            $this->ajaxReturn('error', '红包最大面额不能小于最小面额！', 0);
        }
        
        if ($limit_flag == '1') {
            if ($limit_num <= 0) {
                $this->ajaxReturn('error', '个人限领份数不能为空！', 0);
            }
        } else {
            $limit_num = 0;
        }
        // 最小面额*份数<=总金额<=最大面额*份数
        if (($bonus_num * $min_amount) > $bonus_amount) {
            $this->ajaxReturn('error', '最小面额*份数必须小于等于红包金额！', 0);
        }
        // 判断最大面额*份数必须大于红包金额
        if (($bonus_num * $max_amount) < $bonus_amount) {
            $this->ajaxReturn('error', '最大面额*份数必须大于红包金额！', 0);
        }
        
        // 移动图片
        if ($share_pic_val) {
            /*
             * $img_move =
             * move_batch_image($share_pic_val,$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true)
             * $this->error('商品图片上传失败！'.$share_pic_val,array('返回'=>"javascript:history.go(-1)"));
             */
            $share_pic_val = str_replace('..', '', $share_pic_val);
        }
        if ($logo_pic_val) {
            $logo_pic_val = str_replace('..', '', $logo_pic_val);
        }
        if ($link_flag == '1') {
            if ($url_type == 0)
                $link_url = $this->_getShopUrl('1');
            else {
                if (empty($link_url))
                    $this->ajaxReturn('error', '链接地址不能为空！', 0);
            }
            if (empty($button_name))
                $button_name = "去使用";
        }
        M()->startTrans();
        $data = array(
            'batch_type' => $this->BATCH_TYPE, 
            'name' => $name, 
            'start_time' => $start_time, 
            'end_time' => $end_time, 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id, 
            'status' => '1',  // 活动状态默认正常
            'node_name' => $node_name,  // 商户名称
            'memo' => $memo, 
            'wap_info' => $wap_info, 
            'share_pic' => $share_pic_val, 
            'log_img' => $logo_pic_val);
        $m_id = M('tmarketing_info')->data($data)->add();
        if (! $m_id) {
            M()->rollback();
            $this->ajaxReturn('error', '随机红包活动创建失败！', 0);
        }
        
        // 插入红包表
        $bdata = array(
            'node_id' => $this->node_id, 
            'm_id' => $m_id, 
            'bonus_page_name' => $name, 
            'bonus_amount' => $bonus_amount, 
            'bonus_num' => $bonus_num, 
            'min_amount' => $min_amount, 
            'max_amount' => $max_amount, 
            'bonus_start_time' => $bonus_start_time . '000000', 
            'bonus_end_time' => $bonus_end_time . '235959', 
            'limit_flag' => $limit_flag, 
            'limit_num' => $limit_num, 
            'add_time' => date('YmdHis'), 
            "link_flag" => $link_flag, 
            "url_type" => $url_type, 
            "link_url" => $link_url, 
            "button_name" => $button_name);
        $bonus_id = M('tbonus_info')->data($bdata)->add();
        if (! $bonus_id) {
            M()->rollback();
            $this->ajaxReturn('error', '随机红包入库创建失败！', 0);
        }
        
        // 创建红包明细
        $bonusDetail = $this->get_bonus_detail($m_id, $bonus_id, $bonus_num, 
            $bonus_amount, $min_amount, $max_amount);
        
        if (! empty($bonusDetail)) {
            foreach ($bonusDetail as $k => $val) {
                
                // 判断同等面额红包是否存在，存在则更新数量，否则插入新的
                $mod = array(
                    'node_id' => $this->node_id, 
                    'bonus_id' => $bonus_id, 
                    'amount' => $val);
                $isAmountExist = M('tbonus_detail')->where($mod)->getField(
                    'bonus_id');
                
                if (! $isAmountExist) {
                    $Detaildata = array(
                        'node_id' => $this->node_id, 
                        'm_id' => $m_id, 
                        'bonus_id' => $bonus_id, 
                        'amount' => $val, 
                        'num' => 1, 
                        'get_num' => 0, 
                        'use_num' => 0);
                    $res = M('tbonus_detail')->data($Detaildata)->add();
                    if (! $res) {
                        M()->rollback();
                        $this->ajaxReturn('error', '随机红包明细入库创建失败！', 0);
                    }
                } else {
                    
                    $mape = array(
                        'node_id' => $this->node_id, 
                        'bonus_id' => $bonus_id, 
                        'm_id' => $m_id, 
                        'amount' => $val);
                    $res = M('tbonus_detail')->where($mape)->setInc('num');
                    
                    if ($res === false) {
                        M()->rollback();
                        $this->ajaxReturn('error', '随机红包明细更新失败！', 0);
                    }
                }
            }
        }
        
        M()->commit();
        $this->ajaxReturn('success', "随机红包创建成功！", 1);
    }

    public function edit() {
        $id = I('id', '', 'trim');
        if ($id == '') {
            $this->error('参数错误！');
        }
        
        $map = array(
            'b.id' => $id);
        
        $bonusInfo = M()->table("tbonus_info b")->field('b.*,m.*,m.id as batch_id')
            ->join('tmarketing_info m on b.m_id=m.id')
            ->where($map)
            ->find();
        $this->assign('id', $id);
        $this->assign('bonusInfo', $bonusInfo);
        $this->display();
    }

    public function editSubmit() {
        $id = I("id", '', 'trim');
        $batch_id = I("batch_id", '', 'trim');
        if ($batch_id == "" || $id == "") {
            $this->ajaxReturn('error', '参数错误！', 0);
        }
        $name = I("name", '', 'trim');
        $node_name = I("node_name", '', 'trim');
        
        $start_time = I("start_time", '', 'trim') . "00";
        $end_time = I("end_time", '', 'trim') . "59";
        $bonus_start_time = I("bonus_start_time", '', 'trim') . "000000";
        $bonus_end_time = I("bonus_end_time", '', 'trim') . "235959";
        
        $memo = I("memo", '', 'trim');
        $wap_info = I("wap_info", '', 'trim');
        $share_pic_val = I("share_pic_val", '', 'trim');
        $logo_pic_val = I("logo_pic_val", '', 'trim');
        // 跳转地址
        $link_flag = I("link_flag", 0, 'intval');
        $url_type = I("url_type", 0, 'intval');
        $link_url = I("link_url", '', 'trim');
        $button_name = I("button_name", '', 'trim');
        
        if (empty($name))
            $this->ajaxReturn('error', '页面名称不能为空！', 0);
        if (empty($node_name))
            $this->ajaxReturn('error', '商户名称不能为空！', 0);
        if (empty($start_time))
            $this->ajaxReturn('error', '活动有效期开始时间不能为空！', 0);
        if (empty($end_time))
            $this->ajaxReturn('error', '活动有效期结束时间不能为空！', 0);
        if (empty($bonus_start_time))
            $this->ajaxReturn('error', '红包有效期开始时间不能为空！', 0);
        if (empty($bonus_end_time))
            $this->ajaxReturn('error', '红包有效期结束时间不能为空！', 0);
            
            // 移动图片
        if ($share_pic_val) {
            /*
             * $img_move =
             * move_batch_image($share_pic_val,$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true)
             * $this->error('商品图片上传失败！'.$share_pic_val,array('返回'=>"javascript:history.go(-1)"));
             * $share_pic_val = $this->img_path .$share_pic_val;
             */
            
            $share_pic_val = str_replace('..', '', $share_pic_val);
        }
        if ($logo_pic_val) {
            $logo_pic_val = str_replace('..', '', $logo_pic_val);
        }
        if ($link_flag == '1') {
            if ($url_type == 0)
                $link_url = $this->_getShopUrl('1');
            else {
                if (empty($link_url))
                    $this->ajaxReturn('error', '链接地址不能为空！', 0);
            }
            if (empty($button_name))
                $button_name = "去使用";
        }
        M()->startTrans();
        
        // 更新红包tbonus_info
        $update_data = array(
            "bonus_page_name" => $name, 
            "bonus_start_time" => $bonus_start_time, 
            "bonus_end_time" => $bonus_end_time, 
            "link_flag" => $link_flag, 
            "url_type" => $url_type, 
            "link_url" => $link_url, 
            "button_name" => $button_name);
        
        $Where['id'] = $id;
        $res = M('tbonus_info')->where($Where)->save($update_data);
        if ($res === false) {
            M()->rollback();
            $this->ajaxReturn('error', '更新随机红包失败！', 0);
        }
        
        // 更新tmarketing_info
        $updata = array(
            "name" => $name, 
            "node_name" => $node_name, 
            "start_time" => $start_time, 
            "end_time" => $end_time, 
            "memo" => $memo, 
            "wap_info" => $wap_info, 
            "share_pic" => $share_pic_val, 
            'log_img' => $logo_pic_val);
        $map['id'] = $batch_id;
        $re = M('tmarketing_info')->where($map)->save($updata);
        if ($re === false) {
            M()->rollback();
            $this->ajaxReturn('error', '更新红包活动失败！', 0);
        }
        M()->commit();
        $this->ajaxReturn('success', "随机红包更新成功！", 1);
    }
    
    // 规则列表
    public function rulelist() {
        $data = $_REQUEST;
        if ($data['name'] != '')
            $map['m.rule_name'] = array(
                'like', 
                '%' . $data['name'] . '%');
        
        if ($data['status'] != '') {
            $map['m.status'] = $data['status'];
        } else {
            $map['m.status'] = array(
                'neq', 
                '3');
        }
        $map['m.node_id'] = $this->node_id;
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount =  M()->table("tbonus_rules m")->where($map)->count(); // 查询满足要求的总记录数
                                            // 取得总规则
        $ruleServer = D('SalePro', 'Service');
        $ruleType = $ruleServer->getNodeRule($this->node_id);
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list =  M()->table("tbonus_rules m")->field('m.*')
            ->where($map)
            ->order('m.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $this->assign('status', $data['status']);
        $this->assign('name', $data['name']);
        $this->assign('ruleType', $ruleType);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display();
    }

    public function addRule() {
        $this->display();
    }

    public function addRuleSubmit() {
        $rule_name = I("rule_name", '', 'trim');
        $rev_amount = I("rev_amount", '', 'trim');
        $use_amount = I("use_amount", '', 'trim');
        $rule_memo = I("rule_memo", '', 'trim');
        $status = I("status", '', 'trim');
        if (empty($rule_name)) {
            $this->error('规则名称不能为空！', 
                array(
                    '返回列表' => U('index')));
        }
        if (empty($rev_amount)) {
            $this->error('满多少元不能为空！', 
                array(
                    '返回列表' => U('index')));
        }
        if (empty($use_amount)) {
            $this->error('可使用多少元不能为空！', 
                array(
                    '返回列表' => U('index')));
        }
        if ($rev_amount < $use_amount) {
            $this->error('可用红包金额不得大于购物金额！', 
                array(
                    '返回列表' => U('index')));
        }
        
        if (empty($rule_memo)) {
            $this->error('规则描述不能为空！', 
                array(
                    '返回列表' => U('index')));
        }
        
        // 插入红包规则表
        $bdata = array(
            'node_id' => $this->node_id, 
            'rule_name' => $rule_name, 
            'rev_amount' => $rev_amount, 
            'use_amount' => $use_amount, 
            'rule_memo' => $rule_memo, 
            'status' => $status, 
            'add_time' => date('YmdHis'));
        $ruleid = M('tbonus_rules')->data($bdata)->add();
        if ($ruleid) {
            $this->success('新增红包使用规则成功！');
        } else {
            $this->error('新增红包使用规则失败！');
        }
    }

    public function changeStatus() {
        $id = I('id', '', 'trim');
        $status = I('status', '', 'trim');
        if ($status == "" || $id == "") {
            $this->error('参数错误！');
        }
        
        $where = array(
            'id' => $id);
        
        $data = array(
            'status' => $status);
        $res = M("tbonus_rules")->where($where)->save($data);
        if ($res !== false) {
            
            $this->success("红包使用规则状态更改成功！");
        } else {
            $this->error("红包使用规则状态更改失败！");
        }
    }

    public function checkStatus() {
        $id = I('id');
        if (! $id)
            $this->error('数据错误');
        $status = M('tmarketing_info')->where(
            array(
                'id' => $id, 
                'node_id' => $this->node_id))->getField('status');
        if ($status == 1) {
            $this->success('该活动正常');
        } else {
            $this->error('该活动已经停用');
        }
    }

    public function ruledelete() {
        $id = I('id', '', 'trim');
        if (! $id) {
            $this->error('参数错误！');
        }
        $where = array(
            'id' => $id);
        
        $data = array(
            'status' => '3');
        $res = M("tbonus_rules")->where($where)->save($data);
        if ($res !== false) {
            
            $this->success("红包使用规则状态删除成功！");
        } else {
            $this->error("红包使用规则状态删除失败！");
        }
    }

    /**
     * Description of SkuService 更改红包使用规则
     *
     * @author john_zeng
     */
    public function ruleChangeType() {
        $typeId = I('type', '', 'trim') ? (int) I('type', '', 'trim') : 0;
        // 实例化红包模块
        $saleProModel = D('SalePro', 'Service');
        // 添加红包总规则
        $res = $saleProModel->addNodeRule($this->node_id, $typeId);
        if (false === $res) {
            $this->error($saleProModel->getError());
        } else {
            $this->success("红包规则更改成功！");
        }
    }

    public function changeBatchStatus() {
        $batch_id = I('batch_id', '', 'trim');
        $status = I('status', '', 'trim');
        if ($status == "" || $batch_id == "") {
            $this->error('参数错误！');
        }
        
        $where = array(
            'id' => $batch_id);
        
        $data = array(
            'status' => $status);
        $res = M("tmarketing_info")->where($where)->save($data);
        if ($res !== false) {
            /*
             * //更新未试用的红包 $str=array( "node_id"=>$this->node_id,
             * "m_id"=>$batch_id, ); $res =
             * M('tbonus_use_detail')->where($str)->save(array('status'=>$status));
             * if($res !== false) $this->success("状态更改成功！"); else
             */
            $this->success("红包状态更改成功！");
        } else {
            $this->error("状态更改失败！");
        }
    }
    
    // 红包面额明细
    public function bonusDetail() {
        $id = I('id', '', 'trim');
        if ($id == "") {
            $this->error('参数错误！');
        }
        $map['b.bonus_id'] = $id;
        
        // 查询红包明细
        $list = M()->table("tbonus_detail b")->field(
            'b.*,m.bonus_page_name,m.bonus_amount')
            ->join('tbonus_info m on b.bonus_id=m.id')
            ->where($map)
            ->order('b.id asc')
            ->select();
        
        $this->assign('list', $list);
        
        $this->display();
    }
    
    // 红包面额明细
    public function bonusUseDetail() {
        $id = I('id', '', 'trim');
        if ($id == "") {
            $this->error('参数错误！');
        }
        $map['b.bonus_id'] = $id;
        
        // 查询红包明细
        $list = M()->table("tbonus_use_detail b")->field(
            'b.*,d.amount,m.bonus_page_name,m.bonus_amount')
            ->join('tbonus_info m on b.bonus_id=m.id')
            ->join('tbonus_detail d ON d.id=b.bonus_detail_id')
            ->where($map)
            ->order('b.id asc')
            ->select();
        
        $this->assign('list', $list);
        
        $this->display();
    }
    
    // 计算红包明细
    public function get_bonus_detail($m_id, $bonus_id, $bonus_num, $amount, 
        $min_amount, $max_amount) {
        $total = $amount;
        
        if ($bonus_num > 0) {
            for ($i = $bonus_num; $i > 0; $i --) {
                
                $rand = $total / $i;
                // 如果最后的数据大于最大面额，则平均分配到每个面额
                if ($i == 1) {
                    
                    if ($total > $max_amount) {
                        
                        $x = $max_amount;
                        // 把最大面额分配到最后一个
                        $remainAmount = $total - $max_amount;
                        $wallet[] = $x;
                        
                        $count = count($wallet) - 1;
                        if ($remainAmount > 0) {
                            
                            // 平均分配后，计算最后剩的金额
                            $lastone = $remainAmount -
                                 intval($remainAmount / $count) * $count;
                            
                            // 平均分配的金额
                            $avg = intval($remainAmount / $count);
                            asort($wallet);
                            
                            for ($j = 0; $j < $count; $j ++) {
                                if ($j == 0) {
                                    
                                    $oneAmt = $wallet[$j] + $lastone;
                                    if ($oneAmt < $max_amount) {
                                        $wallet[$j] = $wallet[$j] + $lastone;
                                        $remainAmount -= $lastone;
                                    }
                                    
                                    continue;
                                }
                                $maxv = $wallet[$j] + $avg;
                                if ($maxv > $max_amount) {
                                    continue;
                                }
                                $wallet[$j] = $wallet[$j] + $avg;
                                $remainAmount -= $avg;
                            }
                            
                            // 最后判断如果还剩余金额没分配完
                            if ($remainAmount > 0) {
                                asort($wallet);
                                $count = count($wallet) - 1;
                                $haveAmt = array_sum($wallet);
                                
                                for ($k = 0; $k < $count; $k ++) {
                                    
                                    $oneAmt = $max_amount - $wallet[$k];
                                    // 如果小于0修正数字，把多余的减掉
                                    if ($remainAmount <= 0) {
                                        
                                        if ($remainAmount < 0) {
                                            arsort($wallet);
                                            $wallet[$count] = $wallet[$count] -
                                                 abs($remainAmount);
                                        }
                                        return $wallet;
                                        break;
                                    }
                                    
                                    // 每个金额加到最大金额
                                    if ($oneAmt > 0) {
                                        $wallet[$k] = $wallet[$k] + $oneAmt;
                                        $haveAmt = array_sum($wallet);
                                        $remainAmount -= $oneAmt;
                                    }
                                }
                            }
                        } else {
                            
                            return $wallet;
                        }
                    } else {
                        
                        $x = bcadd($total, 0, 2);
                        $wallet[] = $x;
                        $total -= $x;
                    }
                } else {
                    
                    // 随机分配
                    /*
                     * $x = mt_rand($min_amount, $rand); if ($x < $min_amount ||
                     * $x > $max_amount) { $x = mt_rand($min_amount,
                     * $max_amount); }
                     */
                    $x = bcadd($min_amount, 
                        mt_rand() / mt_getrandmax() * ($rand - $min_amount), 2);
                    if ($x < $min_amount || $x > $max_amount) {
                        $x = bcadd($min_amount, 
                            mt_rand() / mt_getrandmax() *
                                 ($max_amount - $min_amount), 2);
                    }
                    $total -= $x;
                    $wallet[] = $x;
                }
            }
        }
        
        return $wallet;
    }

    /* 获取该商户默认小店地址 */
    private function _getShopUrl($type) {
        $m_id = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => 29))->getField('id');
        if (! $m_id)
            return '';
            // $channel_id = M('tchannel')->where(array('node_id' =>
            // $this->node_id, 'type' => 4, 'sns_type' => 46))->getField('id');
        $channel_id = $this->_getCHannelId($type);
        if (! $channel_id)
            return '';
        $label_id = get_batch_channel($m_id, $channel_id, 29, $this->node_id);
        if (! $label_id)
            return '';
        $link_url = U('Label/Label/index', 
            array(
                'id' => $label_id), '', '', true);
        return $link_url;
    }

    /**
     * 定额红包 列表
     */
    public function fixBonusList() {
        $map = array(
            'm.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $data = $_REQUEST;
        
        if (isset($data['batch_name']) && $data['batch_name'] != '') {
            $map['m.name'] = array(
                'like', 
                '%' . $data['batch_name'] . '%');
        }
        if (isset($data['batch_status']) && $data['batch_status'] != '') {
            // $map['m.status'] = $data['batch_status'];
            if ($data['batch_status'] == '2')
                $map['b.bonus_end_time'] = array(
                    'lt', 
                    date('YmdHis'));
            if ($data['batch_status'] == '1')
                $map['b.bonus_end_time'] = array(
                    'egt', 
                    date('YmdHis'));
        }
        // 处理特殊查询字段
        $beginDate = I('begin_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['m.start_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' m.end_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['m.batch_type'] = $this->FIX_BATCH_TYPE;
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tbonus_info b")->join('tmarketing_info m on b.m_id=m.id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M()->table('tbonus_info b')
            ->field(
            'b.*,m.id as batch_id,m.name,m.batch_type,m.click_count,m.status,m.start_time,m.end_time,d.get_num as dget_num,d.use_num as duse_num,IFNULL((SELECT SUM(IFNULL(u.order_amt_per,0)) FROM tbonus_use_detail u WHERE u.bonus_id=b.id),0.00) AS sale_amt')
            ->join('tmarketing_info m on b.m_id=m.id')
            ->join('tbonus_detail d on d.bonus_id=b.id')
            ->where($map)
            ->order('m.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('data', $data);
        $batchStatusArr = array(
            '1' => '正常', 
            '2' => '过期');
        $this->assign('batchStatusArr', $batchStatusArr);
        $this->display();
    }

    /**
     * 添加定额红包
     */
    public function addFixBonus() {
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        // 提交保存
        if ($this->isPost()) {
            $rules = array(
                'bonus_name' => array(
                    'null' => false, 
                    'name' => '红包名称'), 
                'img_resp' => array(
                    'null' => false, 
                    'strtype' => 'string', 
                    'name' => '红包图片'), 
                'bonus_amt' => array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'name' => '单个红包面额'), 
                'num_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '红包总份数限制', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'begin_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '红包有效期开始时间'), 
                'end_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '红包有效期结束时间'), 
                'link_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '是否提示使用', 
                    'inarr' => array(
                        '0', 
                        '1')));
            // 'url_type' => array('null'=>false,'strtype'=>'int',
            // 'name'=>'链接地址类型', 'inarr'=>array('0','1')),
            // 'memo'=>array('null'=>false,'maxlen_cn'=>'10000','name'=>'红包领取介绍')
            
            $reqData = $this->_verifyReqData($rules);
            if ($reqData['num_type'] == '1')
                $rules['bonus_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'maxval' => 100000000, 
                    'name' => '红包总份数');
            if ($reqData['link_type'] == '1') {
                $rules['url_type'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '链接地址类型', 
                    'inarr' => array(
                        '0', 
                        '1'));
                $rules['button_name'] = array(
                    'null' => false, 
                    'strtype' => 'string', 
                    'maxlen_cn' => '50', 
                    'name' => '提示按钮名称');
            }
            if (I('url_type') == '1')
                $rules['link_url'] = array(
                    'null' => false, 
                    'strtype' => 'string', 
                    'name' => '链接地址');
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            if ($reqData['end_date'] < date('Ymd'))
                $this->error('红包有效期结束时间不得小于今天');
                // 插表
            M()->startTrans();
            // 支撑创建终端组
            M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->setInc('posgroup_seq'); // posgroup_seq
                                                                           // +1;
            $nodeList = M()->query($this->nodeIn(null, true));
            $nodeArr = array();
            foreach ($nodeList as $v) {
                $nodeArr[] = $v['node_id'];
            }
            $dataList = implode(',', $nodeArr);
            
            M()->startTrans();
            $req_array = array(
                'CreatePosGroupReq' => array(
                    'NodeId' => $this->node_id, 
                    'GroupType' => '0', 
                    'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', 
                        STR_PAD_LEFT) . $nodeInfo['posgroup_seq'], 
                    'GroupDesc' => '', 
                    'DataList' => $dataList));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                log::write("创建终端组失败，原因：{$ret_msg['StatusText']}");
                M()->rollback();
                $this->error('创建门店失败:' . $ret_msg['StatusText']);
            }
            $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
            // 插入终端组信息
            $num = M('tpos_group')->where(
                array(
                    'group_id' => $groupId))->count();
            if ($num == '0') { // 不存在终端组去创建
                $data = array( // tpos_group
                    'node_id' => $this->node_id, 
                    'group_id' => $groupId, 
                    'group_name' => $req_array['CreatePosGroupReq']['GroupName'], 
                    'group_type' => '0', 
                    'status' => '0');
                $result = M('tpos_group')->add($data);
                if (! $result) {
                    M()->rollback();
                    $this->error('终端数据创建失败');
                }
                foreach ($nodeList as $v) {
                    $data = array(
                        'group_id' => $groupId, 
                        'node_id' => $v['node_id']);
                    $result = M('tgroup_pos_relation')->add($data);
                    if (! $result) {
                        M()->rollback();
                        $this->error('终端数据创建失败');
                    }
                }
            }
            $goods_id = get_goods_id();
            // 创建tgoods_info数据
            $goods_data = array(
                'goods_id' => $goods_id, 
                'goods_name' => $reqData['bonus_name'], 
                'goods_desc' => $reqData['bonus_name'], 
                'goods_image' => $reqData['img_resp'], 
                'node_id' => $this->node_id, 
                'user_id' => $this->userId, 
                'pos_group' => $groupId, 
                'pos_group_type' => '1', 
                'goods_type' => '12',  // 定额红包类型
                'market_price' => $reqData['bonus_amt'], 
                'goods_amt' => $reqData['bonus_amt'], 
                'storage_type' => $reqData['num_type'], 
                'storage_num' => $reqData['num_type'] ? $reqData['bonus_num'] : - 1, 
                'remain_num' => $reqData['num_type'] ? $reqData['bonus_num'] : - 1, 
                'mms_title' => '定额红包', 
                'mms_text' => $reqData['bonus_name'], 
                'sms_text' => $reqData['bonus_name'], 
                'print_text' => $reqData['bonus_name'], 
                'validate_times' => 1, 
                'begin_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                // 'send_begin_date' => $startDate.'000000',
                // 'send_end_date' => $endDate.'235959',
                'verify_begin_date' => $reqData['begin_date'] . '000000', 
                'verify_end_date' => $reqData['end_date'] . '235959', 
                'verify_begin_type' => '0', 
                'verify_end_type' => '0', 
                'add_time' => date('YmdHis'), 
                'status' => '0', 
                'check_status' => '0', 
                'validate_type' => '0', 
                'source' => '0');
            $goodsId = M('tgoods_info')->data($goods_data)->add();
            if (! $goodsId) {
                M()->rollback();
                $this->error('系统出错,新建商品失败');
            }
            // 支撑创建活动
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->node_id, 
                    'RelationID' => $this->node_id, 
                    'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),  // 请求单号
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => iconv("utf-8", "gbk", 
                            $reqData['bonus_name']), 
                        'ActivityShortName' => iconv("utf-8", "gbk", 
                            $reqData['bonus_name']), 
                        'BeginTime' => $reqData['begin_date'] . '000000', 
                        'EndTime' => '20301231235959', 
                        'UseRangeID' => $groupId), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => 1, 
                        'UseAmtLimit' => 0), 
                    'GoodsInfo' => array(
                        'GoodsName' => iconv("utf-8", "gbk", 
                            $reqData['bonus_name']), 
                        'GoodsShortName' => iconv("utf-8", "gbk", 
                            $reqData['bonus_name'])), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '')));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                $this->error("创建支撑活动失败:{$ret_msg['StatusText']}");
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
            $result = M('tgoods_info')->where("id={$goodsId}")->save(
                array(
                    'batch_no' => $batchNo));
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,更新支撑活动号失败');
            }
            
            // tmarketing_info
            $m_data = array(
                'batch_type' => $this->FIX_BATCH_TYPE, 
                'name' => $reqData['bonus_name'], 
                'start_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->node_id, 
                'status' => '1',  // 活动状态默认正常
                'node_name' => $nodeInfo['node_name'],  // 商户名称
                                                       // 'memo'=>$reqData['memo'],
                'add_time' => date('YmdHis'), 
                'log_img' => $reqData['img_resp']);
            $m_id = M('tmarketing_info')->add($m_data);
            if (! $m_id) {
                M()->rollback();
                $this->error('系统出错,新建营销活动失败');
            }
            // tbatch_info
            // 创建batch_info数据
            $b_data = array(
                'batch_no' => $batchNo, 
                'batch_short_name' => $reqData['bonus_name'], 
                'batch_name' => $reqData['bonus_name'], 
                'node_id' => $this->node_id, 
                'user_id' => $this->user_id, 
                'batch_class' => '12',  // 定额红包类型
                'join_rule' => $reqData['bonus_name'], 
                'use_rule' => $reqData['bonus_name'], 
                'sms_text' => $reqData['bonus_name'], 
                'info_title' => $reqData['bonus_name'], 
                'batch_img' => $reqData['img_resp'], 
                'batch_amt' => $reqData['bonus_amt'], 
                'begin_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                'verify_begin_date' => $reqData['begin_date'] . '000000', 
                'verify_end_date' => $reqData['end_date'] . '235959', 
                'verify_begin_type' => '0', 
                'verify_end_type' => '0', 
                'add_time' => date('YmdHis'), 
                'status' => '0', 
                'goods_id' => $goods_id, 
                'storage_num' => 0,  // 定额红包本身不可用 0库存
                'remain_num' => 0, 
                'batch_desc' => $reqData['bonus_name'], 
                'm_id' => $m_id);
            $b_id = M('tbatch_info')->add($b_data);
            if (! $b_id) {
                M()->rollback();
                $this->error('系统出错,新建营销活动关联信息失败');
            }
            // tbonus_info
            if ($reqData['link_type'] == '1' && $reqData['url_type'] == '0') {
                // $channel_id = $this->_getChannelId('2');
                // $bc_id =
                // get_batch_channel($reqData['batch_id'],$channel_id,$reqData['batch_type'],$this->node_id);
                $reqData['link_url'] = $this->_getShopUrl('2');
            }
            $bonus_data = array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'bonus_page_name' => $reqData['bonus_name'], 
                'bonus_amount' => $reqData['bonus_amt'], 
                'bonus_num' => $reqData['num_type'] ? $reqData['bonus_num'] : - 1, 
                'min_amount' => $reqData['bonus_amt'], 
                'max_amount' => $reqData['bonus_amt'], 
                'bonus_start_time' => $reqData['begin_date'] . '000000', 
                'bonus_end_time' => $reqData['end_date'] . '235959', 
                'limit_flag' => 2, 
                'limit_num' => 0, 
                'add_time' => date('YmdHis'), 
                'link_flag' => $reqData['link_type'], 
                'url_type' => $reqData['url_type'], 
                'link_url' => htmlspecialchars_decode($reqData['link_url']), 
                // 'batch_id' => $reqData['batch_id'],
                // 'batch_type' => $reqData['batch_type'],
                'button_name' => $reqData['button_name']);
            $bonus_id = M('tbonus_info')->add($bonus_data);
            if (! $bonus_id) {
                M()->rollback();
                $this->error('系统出错,新建定额红包信息失败');
            }
            // tgoods_info更新bonus_id
            $result = M('tgoods_info')->where(
                array(
                    'id' => $goodsId))->save(
                array(
                    'bonus_id' => $bonus_id));
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,新建定额红包关联信息失败');
            }
            // tbonus_detail
            $bd_data = array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'bonus_id' => $bonus_id, 
                'amount' => $reqData['bonus_amt'], 
                'num' => $reqData['num_type'] ? $reqData['bonus_num'] : - 1, 
                'get_num' => 0, 
                'use_num' => 0);
            $bd_id = M('tbonus_detail')->add($bd_data);
            if (! $bd_id) {
                M()->rollback();
                $this->error('系统出错,新建定额红包明细数据失败');
            }
            
            M()->commit();
            $this->success('创建成功');
        }
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->display();
    }

    /**
     * 编辑定额红包
     */
    public function editFixBonus() {
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $id = I('id', 0, 'intval');
        if (! $id)
            $this->error('参数错误');
        $where = array(
            'bi.node_id' => $this->node_id, 
            'bi.id' => $id);
        $bonusInfo = M()->table('tbonus_info bi')
            ->join('tmarketing_info m on m.id=bi.m_id')
            ->join('tbatch_info b on b.m_id=bi.m_id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->where($where)
            ->field('bi.*,g.goods_image,g.goods_id')
            ->find();
        // 提交保存
        if ($this->isPost()) {
            $rules = array(
                'img_resp' => array(
                    'null' => false, 
                    'strtype' => 'string', 
                    'name' => '红包图片'), 
                'begin_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '红包有效期开始时间'), 
                'end_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '红包有效期结束时间'), 
                'link_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '是否提示使用', 
                    'inarr' => array(
                        '0', 
                        '1')));
            $reqData = $this->_verifyReqData($rules);
            /*
             * if($reqData['num_type'] == '1') $rules['bonus_num'] =
             * array('null'=>false,'strtype'=>'int', 'name'=>'红包总份数');
             */
            if ($reqData['link_type'] == '1') {
                $rules['url_type'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '链接地址类型', 
                    'inarr' => array(
                        '0', 
                        '1'));
                $rules['button_name'] = array(
                    'null' => false, 
                    'strtype' => 'string', 
                    'maxlen_cn' => '50', 
                    'name' => '提示按钮名称');
            }
            if (I('url_type') == '1')
                $rules['link_url'] = array(
                    'null' => false, 
                    'strtype' => 'string', 
                    'name' => '链接地址');
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            
            // 更新表记录
            M()->startTrans();
            // tgoods_info
            $g_data = array(
                'goods_image' => $reqData['img_resp'], 
                'begin_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                'verify_begin_date' => $reqData['begin_date'] . '000000', 
                'verify_end_date' => $reqData['end_date'] . '235959');
            if ($reqData['end_date'] . '235959' < date('YmdHis'))
                $g_data['status'] = '2';
            else
                $g_data['status'] = '0';
            $result = M('tgoods_info')->where(
                array(
                    'goods_id' => $bonusInfo['goods_id']))->save($g_data);
            if ($result === false) {
                M()->rollback();
                $this->error('商品更新失败');
            }
            // tbatch_info
            $b_data = array(
                'batch_img' => $reqData['img_resp'], 
                'begin_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                'verify_begin_date' => $reqData['begin_date'] . '000000', 
                'verify_end_date' => $reqData['end_date'] . '235959');
            $result = M('tbatch_info')->where(
                array(
                    'm_id' => $bonusInfo['m_id']))->save($b_data);
            if ($result === false) {
                M()->rollback();
                $this->error('营销活动关联信息更新失败');
            }
            // tmarketing_info
            $m_data = array(
                'start_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                'log_img' => $reqData['img_resp']);
            $result = M('tmarketing_info')->where(
                array(
                    'id' => $bonusInfo['m_id']))->save($m_data);
            if ($result === false) {
                M()->rollback();
                $this->error('营销活动信息更新失败');
            }
            // tbonus_info
            if ($reqData['link_type'] == '1' && $reqData['url_type'] == '0') {
                // $channel_id = $this->_getChannelId('2');
                // $bc_id =
                // get_batch_channel($reqData['batch_id'],$channel_id,$reqData['batch_type'],$this->node_id);
                $reqData['link_url'] = $this->_getShopUrl('2');
            }
            $bonus_data = array(
                'bonus_start_time' => $reqData['begin_date'] . '000000', 
                'bonus_end_time' => $reqData['end_date'] . '235959', 
                'link_flag' => $reqData['link_type'], 
                'url_type' => $reqData['url_type'], 
                'link_url' => htmlspecialchars_decode($reqData['link_url']), 
                // 'batch_id' => $reqData['batch_id'],
                // 'batch_type' => $reqData['batch_type'],
                'button_name' => $reqData['button_name']);
            $result = M('tbonus_info')->where(
                array(
                    'id' => $bonusInfo['id']))->save($bonus_data);
            if ($result === false) {
                M()->rollback();
                $this->error('定额红包信息更新失败');
            }
            
            M()->commit();
            $this->success('编辑成功');
        }
        // 显示链接活动名称
        $batchTypeName = C('BATCH_TYPE_NAME');
        $batchName = M('tmarketing_info')->where(
            array(
                'id' => $bonusInfo['batch_id']))->getField('name');
        $link_name = $batchTypeName[$bonusInfo['batch_type']] . " > " .
             $batchName;
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('link_name', $link_name);
        $this->display();
    }

    public function _verifyReqData($rules, $return = false, $method = 'post', $value_array = array()) {
        if (! is_array($rules))
            return;
        $error = '';
        $req_data = array();
        foreach ($rules as $k => $v) {
            $value = I($method . '.' . $k);
            if (! check_str($value, $v, $error)) {
                $msg = $v['name'] . $error;
                if ($return)
                    return $msg;
                else
                    $this->error($msg);
            }
            
            $req_data[$k] = $value;
        }
        return $req_data;
    }

    /*
     * 获取红包引流渠道的id $type 1 随机红包 2 定额红包
     */
    private function _getChannelId($type) {
        if ($type == '1') {
            $c_name = '随机红包默认渠道';
            $sns_type = 59;
        } else {
            $c_name = '定额红包默认渠道';
            $sns_type = 58;
        }
        $c_id = M('tchannel')->where(
            array(
                'type' => 5, 
                'sns_type' => $sns_type, 
                'node_id' => $this->node_id))->getField('id');
        if (! $c_id) {
            $c_data = array(
                'name' => $c_name, 
                'type' => '5', 
                'sns_type' => $sns_type, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            $c_id = M('tchannel')->add($c_data);
        }
        return $c_id;
    }
}