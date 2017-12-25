<?php

class ActivityAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public function index() {
        $data = I('request.');
        $map = array();
        $name = I('name');
        if ($name != "") {
            $map['a.name'] = array(
                'exp', 
                "like '%$name%'");
        }
        $status = I('status');
        if ($status != "") {
            $map['a.status'] = (int) $status;
        }
        $count = M()->table('tfb_onlinesee_activity_info a')
            ->where($map)
            ->count();
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        foreach ($data as $key => $val) {
            $p->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $page = $p->show();
        $list = M()->table('tfb_onlinesee_activity_info a')
            ->
        // ->join('tfb_onlinesee_activity_info_ex b on a.id = b.info_id')
        where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.id desc')
            ->field(
            'a.*,(select count(*) from tfb_onlinesee_trace where info_id=a.id) as join_count,(select count(*) from tfb_onlinesee_activity_info_ex where info_id=a.id and mount_type=1) as member_count_1, (select count(distinct(staff_number)) from tfb_onlinesee_crew where group_id in (select mount_id from tfb_onlinesee_activity_info_ex where info_id=a.id and mount_type=0 ) and crew_sta=1) as member_count_2')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function add() {
        $this->display();
    }

    public function edit() {
        $id = I('id');
        if ($id == '') {
            $this->error('参数错误');
        } else {
            $info = M()->table('tfb_onlinesee_activity_info')
                ->where('id=' . $id)
                ->find();
            $this->assign('info', $info);
            $this->assign('defined', json_decode($info['defined'], true));
            $this->assign('select_type', 
                json_decode($info['select_type'], true));
            $this->assign('select_check', 
                json_decode($info['config_data'], true));
            
            $item_tab = json_decode($info['item_tab'], true);
            $this->assign('item_id_1', json_encode($item_tab['item_id_1']));
            $this->assign('item_id_2', json_encode($item_tab['item_id_2']));
            $this->assign('item_id_3', json_encode($item_tab['item_id_3']));
            $this->assign('item_id_4', json_encode($item_tab['item_id_4']));
            $this->assign('item_id_5', json_encode($item_tab['item_id_5']));
            
            $checked_item_1 = M()->table('tfb_onlinesee_item a')
                ->where('id in (' . implode(",", $item_tab['item_id_1']) . ')')
                ->field('a.id,a.item_content')
                ->select();
            if (! $checked_item_1) {
                $checked_item_1 = array();
            }
            $this->assign('checked_item_1', json_encode($checked_item_1));
            
            $checked_item_2 = M()->table('tfb_onlinesee_item a')
                ->where('id in (' . implode(",", $item_tab['item_id_2']) . ')')
                ->field('a.id,a.item_content')
                ->select();
            if (! $checked_item_2) {
                $checked_item_2 = array();
            }
            $this->assign('checked_item_2', json_encode($checked_item_2));
            
            $checked_item_3 = M()->table('tfb_onlinesee_item a')
                ->where('id in (' . implode(",", $item_tab['item_id_3']) . ')')
                ->field('a.id,a.item_content')
                ->select();
            if (! $checked_item_3) {
                $checked_item_3 = array();
            }
            $this->assign('checked_item_3', json_encode($checked_item_3));
            
            $checked_item_4 = M()->table('tfb_onlinesee_item a')
                ->where('id in (' . implode(",", $item_tab['item_id_4']) . ')')
                ->field('a.id,a.item_content')
                ->select();
            if (! $checked_item_4) {
                $checked_item_4 = array();
            }
            $this->assign('checked_item_4', json_encode($checked_item_4));
            
            $checked_item_5 = M()->table('tfb_onlinesee_item a')
                ->where('id in (' . implode(",", $item_tab['item_id_5']) . ')')
                ->field('a.id,a.item_content')
                ->select();
            if (! $checked_item_5) {
                $checked_item_5 = array();
            }
            $this->assign('checked_item_5', json_encode($checked_item_5));
            
            $info_exs = M()->table('tfb_onlinesee_activity_info_ex')
                ->where('info_id=' . $id)
                ->select();
            if ($info_exs) {
                $mount_id_group = array();
                $mount_id_member = array();
                foreach ($info_exs as $info_ex) {
                    if ($info_ex['mount_type'] == 0) {
                        $mount_id_group[] = $info_ex['mount_id'];
                    }
                    if ($info_ex['mount_type'] == 1) {
                        $mount_id_member[] = $info_ex['mount_id'];
                    }
                }
                
                $checked_group = M()->table('tfb_onlinesee_group a')
                    ->where('id in (' . implode(",", $mount_id_group) . ')')
                    ->field('a.id,a.group_name')
                    ->select();
                $checked_member = M()->table('tfb_onlinesee_member a')
                    ->where('id in (' . implode(",", $mount_id_member) . ')')
                    ->field('a.id,a.name')
                    ->select();
                if (! $checked_group) {
                    $checked_group = array();
                }
                if (! $checked_member) {
                    $checked_member = array();
                }
                
                $mount_id_group = json_encode($mount_id_group);
                $mount_id_member = json_encode($mount_id_member);
                $this->assign('mount_id_group', $mount_id_group);
                $this->assign('mount_id_member', $mount_id_member);
                $this->assign('checked_group', json_encode($checked_group));
                $this->assign('checked_member', json_encode($checked_member));
            }
            $info_items = M()->table('tfb_onlinesee_activity_info_item')
                ->where('info_id=' . $id)
                ->select();
            if ($info_items) {
                $item_id = array();
                foreach ($info_items as $info_item) {
                    $item_id[] = $info_item['item_id'];
                }
                $checked_item = M()->table('tfb_onlinesee_item a')
                    ->where('id in (' . implode(",", $item_id) . ')')
                    ->field('a.id,a.item_content')
                    ->select();
                if (! $checked_item) {
                    $checked_item = array();
                }
                $item_id = json_encode($item_id);
                $this->assign('item_id', $item_id);
                $this->assign('checked_item', json_encode($checked_item));
            } else {
                $this->assign('item_id', json_encode(array()));
                $this->assign('checked_item', json_encode(array()));
            }
            $info_items = M()->table('tfb_onlinesee_activity_info_item a')
                ->where('info_id=' . $id)
                ->join('tfb_onlinesee_item b on b.id = a.item_id ')
                ->field('a.item_id,b.item_content ')
                ->select();
            $this->assign('info_items', $info_items);
            $mapcount = M()->table('tfb_onlinesee_trace')
                ->where('info_id=' . $id)
                ->count();
            if ($mapcount == 0) {
                $this->assign('is_has_data', false);
            } else {
                $this->assign('is_has_data', true);
            }
        }
        $this->display();
    }

    public function add_save() {
        /* 参数校验 */
        $req = array();
        $rules = array(
            'name' => array(
                'null' => false, 
                'name' => '活动名称'), 
            'start_time' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Ymd', 
                'name' => '活动开始时间'), 
            'end_time' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Ymd', 
                'name' => '活动结束时间'), 
            'see_note' => array(
                'null' => false, 
                'name' => '提示语'), 
            'redirect_url_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否配置外部礼品', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'item_id' => array(
                'null' => false, 
                'name' => '评价项'));
        $req = $this->_verifyReqData($rules, FALSE, 'post');
        if ($req['redirect_url_flag'] == '1') {
            $rules = array(
                'redirect_url' => array(
                    'null' => false, 
                    'name' => '外部礼品地址'));
            $req = array_merge($req, 
                $this->_verifyReqData($rules, FALSE, 'post'));
        }
        $req['start_time'] .= '000000';
        $req['end_time'] .= '235959';
        if ($req['start_time'] > $req['end_time']) {
            $this->error('活动开始时间不能大于活动结束时间');
        }
        $mount_id_group = json_decode(
            htmlspecialchars_decode(I('mount_id_group')), true);
        $mount_id_member = json_decode(
            htmlspecialchars_decode(I('mount_id_member')), true);
        if (empty($mount_id_group) && empty($mount_id_member)) {
            $this->error('请选择员工或分组');
        }
        $defined = array();
        $defined['defined10'] = I('defined10');
        $defined['defined11'] = I('defined11');
        $defined['defined12'] = I('defined12');
        $select_type = I('select_type');
        $select_check = array(
            I('select_check1'),
            I('select_check2'),
            I('select_check3'),
            I('select_check4'),
            I('select_check5'),
            I('select_check6'),
            );
        
        /* 校验分组在时间范围内是否参加其他评价计划 */
        $_string = "a.info_id = b.id and (('{$req['start_time']}' between b.start_time and b.end_time) or ('{$req['end_time']}' between b.start_time and b.end_time) or (b.start_time between '{$req['start_time']}' and '{$req['end_time']}') or (b.end_time between '{$req['start_time']}' and '{$req['end_time']}') )";
        foreach ($mount_id_group as $id) {
            $map = array(
                'a.mount_type' => 0, 
                'a.mount_id' => $id, 
                'b.status' => 1, 
                '_string' => $_string);
            $mapcount = M()->table(
                'tfb_onlinesee_activity_info_ex a, tfb_onlinesee_activity_info b')
                ->where($map)
                ->count();
            if ($mapcount > 0) {
                $group_name = M('tfb_onlinesee_group')->where("id = '{$id}'")->getField(
                    'group_name');
                $this->error('分组[' . $group_name . ']在选择时间区间内已经参与了其他评分活动。');
            }
        }
        foreach ($mount_id_member as $id) {
            $map = array(
                'a.mount_type' => 1, 
                'a.mount_id' => $id, 
                'b.status' => 1, 
                '_string' => $_string);
            $mapcount = M()->table(
                'tfb_onlinesee_activity_info_ex a, tfb_onlinesee_activity_info b')
                ->where($map)
                ->count();
            $sql = M()->_sql();
            if ($mapcount > 0) {
                $emp_name = M('tfb_onlinesee_member')->where("id = '{$id}'")->getField(
                    'name');
                $this->error('员工[' . $emp_name . ']在选择时间区间内已经参与了其他评分活动。');
            }
        }
        
        $item_id_1 = json_decode(htmlspecialchars_decode(I('item_id_1')), true);
        $item_id_2 = json_decode(htmlspecialchars_decode(I('item_id_2')), true);
        $item_id_3 = json_decode(htmlspecialchars_decode(I('item_id_3')), true);
        $item_id_4 = json_decode(htmlspecialchars_decode(I('item_id_4')), true);
        $item_id_5 = json_decode(htmlspecialchars_decode(I('item_id_5')), true);
        $item_tab = array();
        $item_tab['item_id_1'] = $item_id_1;
        $item_tab['item_id_2'] = $item_id_2;
        $item_tab['item_id_3'] = $item_id_3;
        $item_tab['item_id_4'] = $item_id_4;
        $item_tab['item_id_5'] = $item_id_5;
        M()->startTrans();
        try {
            /* 写入基础信息表tfb_onlinesee_activity_info */
            $data = array(
                'name' => $req['name'], 
                'start_time' => $req['start_time'], 
                'end_time' => $req['end_time'], 
                'status' => 1, 
                'node_id' => $this->node_id, 
                'see_note' => $req['see_note'], 
                'redirect_url_flag' => $req['redirect_url_flag'], 
                'redirect_url' => ($req['redirect_url_flag'] == 1) ? $req['redirect_url'] : '', 
                'op_user' => $this->user_name, 
                'select_type' => json_encode($select_type), 
                'defined' => json_encode($defined), 
                'config_data' => json_encode($select_check), 
                'item_tab' => json_encode($item_tab));
            $info_id = M()->table('tfb_onlinesee_activity_info')->add($data);
            if ($info_id === false) {
                M()->rollback();
                throw new Exception("评价计划创建失败[01]！");
            }
            /* 写入活动扩展表--活动和人对应 tfb_onlinesee_activity_info_ex */
            foreach ($mount_id_group as $id) {
                $data = array(
                    'info_id' => $info_id, 
                    'mount_type' => 0, 
                    'mount_id' => $id, 
                    'add_time' => date('YmdHis'));
                $info_ex_id = M()->table('tfb_onlinesee_activity_info_ex')->add(
                    $data);
            }
            foreach ($mount_id_member as $id) {
                $data = array(
                    'info_id' => $info_id, 
                    'mount_type' => 1, 
                    'mount_id' => $id, 
                    'add_time' => date('YmdHis'));
                $info_ex_id = M()->table('tfb_onlinesee_activity_info_ex')->add(
                    $data);
            }
            /* 写入活动选项表---活动和选项对应 tfb_onlinesee_activity_info_item */
            $item_id = json_decode(htmlspecialchars_decode(I('item_id')), true);
            foreach ($item_id as $id) {
                $data = array(
                    'info_id' => $info_id, 
                    'item_id' => $id, 
                    'add_time' => date('YmdHis'));
                $info_item_id = M()->table('tfb_onlinesee_activity_info_item')->add(
                    $data);
            }
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        M()->commit();
        $this->success('评价计划创建成功！', 
            array(
                '返回评价计划列表' => U('index')));
    }

    public function edit_save() {
        /* 参数校验 */
        $info_id = I('id');
        {
            if (! $info_id) {
                $this->error('参数错误');
            }
        }
        $req = array();
        $rules = array(
            'name' => array(
                'null' => false, 
                'name' => '活动名称'), 
            'start_time' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Ymd', 
                'name' => '活动开始时间'), 
            'end_time' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Ymd', 
                'name' => '活动结束时间'), 
            'see_note' => array(
                'null' => false, 
                'name' => '提示语'), 
            'redirect_url_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否配置外部礼品', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'item_id' => array(
                'null' => false, 
                'name' => '评价项'));
        $req = $this->_verifyReqData($rules, FALSE, 'post');
        if ($req['redirect_url_flag'] == '1') {
            $rules = array(
                'redirect_url' => array(
                    'null' => false, 
                    'name' => '外部礼品地址'));
            $req = array_merge($req, 
                $this->_verifyReqData($rules, FALSE, 'post'));
        }
        $req['start_time'] .= '000000';
        $req['end_time'] .= '235959';
        if ($req['start_time'] > $req['end_time']) {
            $this->error('活动开始时间不能大于活动结束时间');
        }
        $mount_id_group = json_decode(
            htmlspecialchars_decode(I('mount_id_group')), true);
        $mount_id_member = json_decode(
            htmlspecialchars_decode(I('mount_id_member')), true);
        if (empty($mount_id_group) && empty($mount_id_member)) {
            $this->error('请选择员工或分组');
        }
        $defined = array();
        $defined['defined10'] = I('defined10');
        $defined['defined11'] = I('defined11');
        $defined['defined12'] = I('defined12');
        $select_type = I('select_type');
        $select_check = array(
            I('select_check1'),
            I('select_check2'),
            I('select_check3'),
            I('select_check4'),
            I('select_check5'),
            I('select_check6'),
            );
        
        /* 校验分组在时间范围内是否参加其他评价计划 */
        $_string = "a.info_id = b.id and (('{$req['start_time']}' between b.start_time and b.end_time) or ('{$req['end_time']}' between b.start_time and b.end_time) or (b.start_time between '{$req['start_time']}' and '{$req['end_time']}') or (b.end_time between '{$req['start_time']}' and '{$req['end_time']}') )";
        foreach ($mount_id_group as $id) {
            $map = array(
                'a.mount_type' => 0, 
                'a.mount_id' => $id, 
                'b.id' => array(
                    'neq', 
                    $info_id), 
                'b.status' => 1, 
                '_string' => $_string);
            $mapcount = M()->table(
                'tfb_onlinesee_activity_info_ex a, tfb_onlinesee_activity_info b')
                ->where($map)
                ->count();
            if ($mapcount > 0) {
                $group_name = M('tfb_onlinesee_group')->where("id = '{$id}'")->getField(
                    'group_name');
                $this->error('分组[' . $group_name . ']在选择时间区间内已经参与了其他评分活动。');
            }
        }
        foreach ($mount_id_member as $id) {
            $map = array(
                'a.mount_type' => 1, 
                'a.mount_id' => $id, 
                'b.id' => array(
                    'neq', 
                    $info_id), 
                'b.status' => 1, 
                '_string' => $_string);
            $mapcount = M()->table(
                'tfb_onlinesee_activity_info_ex a, tfb_onlinesee_activity_info b')
                ->where($map)
                ->count();
            if ($mapcount > 0) {
                $emp_name = M('tfb_onlinesee_member')->where("id = '{$id}'")->getField(
                    'name');
                $this->error('员工[' . $emp_name . ']在选择时间区间内已经参与了其他评分活动。');
            }
        }
        $item_id_1 = json_decode(htmlspecialchars_decode(I('item_id_1')), true);
        $item_id_2 = json_decode(htmlspecialchars_decode(I('item_id_2')), true);
        $item_id_3 = json_decode(htmlspecialchars_decode(I('item_id_3')), true);
        $item_id_4 = json_decode(htmlspecialchars_decode(I('item_id_4')), true);
        $item_id_5 = json_decode(htmlspecialchars_decode(I('item_id_5')), true);
        $item_tab = array();
        $item_tab['item_id_1'] = $item_id_1;
        $item_tab['item_id_2'] = $item_id_2;
        $item_tab['item_id_3'] = $item_id_3;
        $item_tab['item_id_4'] = $item_id_4;
        $item_tab['item_id_5'] = $item_id_5;
        
        M()->startTrans();
        try {
            /* 写入基础信息表tfb_onlinesee_activity_info */
            $data = array(
                'name' => $req['name'], 
                'start_time' => $req['start_time'], 
                'end_time' => $req['end_time'], 
                'status' => 1, 
                'node_id' => $this->node_id, 
                'see_note' => $req['see_note'], 
                'redirect_url_flag' => $req['redirect_url_flag'], 
                'redirect_url' => ($req['redirect_url_flag'] == 1) ? $req['redirect_url'] : '', 
                'op_user' => $this->user_name, 
                'select_type' => json_encode($select_type), 
                'defined' => json_encode($defined), 
                'config_data' => json_encode($select_check), 
                'item_tab' => json_encode($item_tab));
            $flag = M()->table('tfb_onlinesee_activity_info')
                ->where('id=' . $info_id)
                ->save($data);
            if ($flag === false) {
                M()->rollback();
                throw new Exception("评价计划修改失败[01]！");
            }
            /* 写入活动扩展表--活动和人对应 tfb_onlinesee_activity_info_ex */
            
            /* 删除原来的关联关系 */
            M()->table('tfb_onlinesee_activity_info_ex')
                ->where('info_id=' . $info_id)
                ->delete();
            foreach ($mount_id_group as $id) {
                $data = array(
                    'info_id' => $info_id, 
                    'mount_type' => 0, 
                    'mount_id' => $id, 
                    'add_time' => date('YmdHis'));
                $info_ex_id = M()->table('tfb_onlinesee_activity_info_ex')->add(
                    $data);
            }
            foreach ($mount_id_member as $id) {
                $data = array(
                    'info_id' => $info_id, 
                    'mount_type' => 1, 
                    'mount_id' => $id, 
                    'add_time' => date('YmdHis'));
                $info_ex_id = M()->table('tfb_onlinesee_activity_info_ex')->add(
                    $data);
            }
            /* 写入活动选项表---活动和选项对应 tfb_onlinesee_activity_info_item */
            $mapcount = M()->table('tfb_onlinesee_trace')
                ->where('info_id=' . $info_id)
                ->count();
            if ($mapcount == 0) {
                /* 删除原来的关联关系 */
                M()->table('tfb_onlinesee_activity_info_item')
                    ->where('info_id=' . $info_id)
                    ->delete();
                $item_id = json_decode(htmlspecialchars_decode(I('item_id')), 
                    true);
                foreach ($item_id as $id) {
                    $data = array(
                        'info_id' => $info_id, 
                        'item_id' => $id, 
                        'add_time' => date('YmdHis'));
                    $info_item_id = M()->table(
                        'tfb_onlinesee_activity_info_item')->add($data);
                    if ($info_item_id === false) {
                        M()->rollback();
                        throw new Exception(
                            "评价计划修改失败[02]！" . M()->table(
                                'tfb_onlinesee_activity_info_item')
                                ->add($data)
                                ->buildSql());
                    }
                }
            }
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        M()->commit();
        $this->success('评价计划修改成功！', 
            array(
                '返回评价计划列表' => U('index')));
    }

    public function item() {
        $map = array(
            'status' => 1);
        $item_content = I('item_content');
        if ($item_content != "") {
            $map['a.item_content'] = array(
                'exp', 
                "like '%$item_content%'");
        }
        $id = I('id');
        if ($id != "") {
            $map['a.id'] = $id;
        }
        
        $count = M()->table('tfb_onlinesee_item')
            ->where($map)
            ->count();
        
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $Page->parameter .= "&id=" . urlencode($id) . '&';
        $Page->parameter .= "&item_content=" . urlencode($item_content) . '&';
        $page = $p->show();
        $list = M()->table('tfb_onlinesee_item a')
            ->
        // ->join('tfb_onlinesee_activity_info_ex b on a.id = b.info_id')
        where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.id desc')
            ->field('a.*')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function info_status() {
        if (I('status') == 1) {
            $info_id = I('id');
            $info = M()->table("tfb_onlinesee_activity_info a")->where("a.id={$info_id}")
                ->field("a.start_time,a.end_time")
                ->find();
            
            /* 校验分组在时间范围内是否参加其他评价计划 */
            $_string = "a.info_id = b.id and (('{$info['start_time']}' between b.start_time and b.end_time) or ('{$info['end_time']}' between b.start_time and b.end_time) or (b.start_time between '{$info['start_time']}' and '{$info['end_time']}') or (b.end_time between '{$info['start_time']}' and '{$info['end_time']}') )";
            $mount_id_group = M()->table("tfb_onlinesee_activity_info_ex a")->where(
                "a.info_id={$info_id} and a.mount_type=0")
                ->field("a.mount_id")
                ->select();
            $mount_id_member = M()->table("tfb_onlinesee_activity_info_ex a")->where(
                "a.info_id={$info_id} and a.mount_type=1")
                ->field("a.mount_id")
                ->select();
            
            foreach ($mount_id_group as $v) {
                $map = array(
                    'a.mount_type' => 0, 
                    'a.mount_id' => $v['mount_id'], 
                    'b.id' => array(
                        'neq', 
                        $info_id), 
                    'b.status' => 1, 
                    '_string' => $_string);
                $mapcount = M()->table(
                    'tfb_onlinesee_activity_info_ex a, tfb_onlinesee_activity_info b')
                    ->where($map)
                    ->count();
                if ($mapcount > 0) {
                    $group_name = M('tfb_onlinesee_group')->where(
                        "id = '{$v['mount_id']}'")->getField('group_name');
                    $this->error('分组[' . $group_name . ']在选择时间区间内已经参与了其他评分活动。');
                }
            }
            foreach ($mount_id_member as $v) {
                $map = array(
                    'a.mount_type' => 1, 
                    'a.mount_id' => $v['mount_id'], 
                    'b.id' => array(
                        'neq', 
                        $info_id), 
                    'b.status' => 1, 
                    '_string' => $_string);
                $mapcount = M()->table(
                    'tfb_onlinesee_activity_info_ex a, tfb_onlinesee_activity_info b')
                    ->where($map)
                    ->count();
                if ($mapcount > 0) {
                    $emp_name = M('tfb_onlinesee_member')->where(
                        "id = '{$v['mount_id']}'")->getField('name');
                    $this->error('员工[' . $emp_name . ']在选择时间区间内已经参与了其他评分活动。');
                }
            }
        }
        $flag = M('tfb_onlinesee_activity_info')->where(
            array(
                "id" => I('id')))->save(
            array(
                'status' => I('status')));
        $this->success('修改状态成功！');
    }

    public function add_item() {
        $this->display();
    }

    public function edit_item() {
        $item = M('tfb_onlinesee_item')->where(
            array(
                "id" => I('id')))->find();
        $this->assign('item', $item);
        $this->display();
    }

    public function item_add_save() {
        $item_content = I('item_content');
        $flag = M('tfb_onlinesee_item')->add(
            array(
                'item_content' => $item_content, 
                'add_time' => date('YmdHis')));
        if ($flag === false) {
            $this->error('添加记录保存失败！');
        } else {
            $this->success('添加记录保存成功！');
            exit();
        }
    }

    public function item_edit_save() {
        $item_content = I('item_content');
        $flag = M('tfb_onlinesee_item')->where(
            array(
                "id" => I('id')))->save(
            array(
                'item_content' => $item_content));
        if ($flag === false) {
            $this->error('修改记录保存失败！');
        } else {
            $this->success('修改记录保存成功！');
            exit();
        }
    }

    public function item_delete() {
        $cur_time = date("YmdHis");
        $map = array();
        $map['a.item_id'] = I('id');
        $map['b.status'] = 1;
        $map['b.end_time'] = array(
            'egt', 
            $cur_time);
        
        $mapcount = M()->table('tfb_onlinesee_activity_info_item a')
            ->join('tfb_onlinesee_activity_info b on b.id = a.info_id ')
            ->where($map)
            ->count();
        if ($mapcount > 0) {
            $this->error('有活动正在使用，不能删除！');
        } else {
            $flag = M('tfb_onlinesee_item')->where(
                array(
                    "id" => I('id')))->save(
                array(
                    'status' => 0));
            if ($flag === false) {
                $this->error('删除记录失败！');
            } else {
                $this->success('删除记录成功！');
                exit();
            }
        }
    }

    public function select_user() {
        import('ORG.Util.Page'); // 导入分页类
        $staff_number = I('staff_number', '', 'mysql_real_escape_string');
        $name = I('name', '', 'mysql_real_escape_string');
        $map = array(
            'status' => 1);
        if ($staff_number != '') {
            $map['staff_number'] = $staff_number;
        }
        if ($name != '') {
            $map['name'] = array(
                'like', 
                "%{$name}%");
        }
        $mapcount = M()->table('tfb_onlinesee_member')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $page_member = $Page->show(); // 分页显示输出
        
        $ungroup_member = M()->table('tfb_onlinesee_member a')
            ->where($map)
            ->order('a.id desc')
            ->field('a.id,a.name,a.staff_number')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('ungroup_member', $ungroup_member);
        $this->assign('page_member', $page_member); // 赋值分页输出
        $this->display();
    }

    public function select_group() {
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tfb_onlinesee_group')
            ->where("id>0")
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $page_group = $Page->show(); // 分页显示输出
        
        $groups = M()->table('tfb_onlinesee_group a')
            ->where("a.id>0")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->field('a.id,a.group_name')
            ->select();
        $this->assign('groups', $groups);
        $this->assign('page_group', $page_group); // 赋值分页输出
        $this->display();
    }

    public function get_item() {
        $item = M('tfb_onlinesee_item')->where(
            array(
                "id" => I('id')))->find();
        echo $item['item_content'];
    }

    public function to_json() {
        $old_value = json_decode(htmlspecialchars_decode(I('old_value')));
        if (I('option') == 'del') {
            if (empty($old_value)) {
                echo json_encode(array());
            } else {
                $new_value = array();
                foreach ($old_value as $key => $val) {
                    if ($val != I('id')) {
                        $new_value[] = $val;
                    }
                }
                $new_array = $new_value;
                $new_array = array_unique($new_array);
                echo json_encode($new_array);
            }
        }
        if (I('option') == 'add') {
            if (empty($old_value)) {
                $new_array = array(
                    I('id'));
            } else {
                $new_array = array_merge($old_value, 
                    array(
                        I('id')));
                $new_array = array_unique($new_array);
            }
            
            echo json_encode($new_array);
        }
    }

    public function select_item() {
        $map = array(
            'status' => 1);
        $item_content = I('item_content');
        if ($item_content != "") {
            $map['a.item_content'] = array(
                'exp', 
                "like '%$item_content%'");
        }
        $id = I('id');
        if ($id != "") {
            $map['a.id'] = $id;
        }
        $count = M()->table('tfb_onlinesee_item')
            ->where($map)
            ->count();
        
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $Page->parameter .= "&id=" . urlencode($id) . '&';
        $Page->parameter .= "&item_content=" . urlencode($item_content) . '&';
        $page = $p->show();
        $list = M()->table('tfb_onlinesee_item a')
            ->
        // ->join('tfb_onlinesee_activity_info_ex b on a.id = b.info_id')
        where($map)
            ->
        // ->limit($p->firstRow . ',' . $p->listRows)
        order('a.id desc')
            ->field('a.id,a.item_content')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function download() {
        /* 评价活动名称、评价者姓名、联系电话、车牌号、等 */
        $map = array(
            'a.info_id' => I('info_id'));
        $list = M()->table('tfb_onlinesee_trace a')
            ->join('tfb_onlinesee_activity_info info on info.id = a.info_id')
            ->where($map)
            ->field('a.*,info.name as info_name')
            ->select();
        $info = M()->table('tfb_onlinesee_activity_info')
            ->where(array('id'=>I('info_id')))
            ->find();
        
        $count = count($list);
        if ($count <= 0) {
            $this->error('无数据可下载');
        } else {
            $fileName = '评价者清单.csv';
            $fileName = iconv('utf-8', 'gb2312', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $start_num = 0;
            $page_count = 5000;
            $cj_title = "评价活动名称,评价者姓名,提交时间,联系电话,车牌号";
            $select_type=json_decode($info['select_type'],true);
            $defined=json_decode($info['defined'],true);
            if(in_array("10",$select_type))
            {
                $cj_title.=",".$defined['defined10'];
            }
            if(in_array("11",$select_type))
            {
                $cj_title.=",".$defined['defined11'];
            }
            if(in_array("12",$select_type))
            {
                $cj_title.=",".$defined['defined12'];
            }
            $cj_title.="\r\n";
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            foreach ($list as $v) {
                $line_data = "{$v['info_name']}," . "{$v['from_name']}," .date('Y-m-d H:i:s', strtotime($v['add_time']))."," .
                     "{$v['from_mobile']}," . "{$v['from_car_number']}";
                if(in_array("10",$select_type))
                {
                    $line_data.=",".$v['custom_1'];
                }
                if(in_array("11",$select_type))
                {
                    $line_data.=",".$v['custom_2'];
                }
                if(in_array("12",$select_type))
                {
                    $line_data.=",".$v['custom_3'];
                }
                $line_data.="\r\n";
                echo iconv('utf-8', 'gbk', $line_data);
            }
        }
    }

    public function recommend() {
        $list = M()->table('tfb_onlinesee_recommend a')
            ->field('a.value,a.label,a.status')
            ->select();
        $label = array();
        $status = array();
        foreach ($list as $v) {
            $label[$v['value']] = $v['label'];
            $status[$v['value']] = $v['status'];
        }
        $this->assign('label', $label);
        $this->assign('status', $status);
        $this->display();
    }

    public function recommend_save() {
        $value = trim(I('value'));
        $label = trim(I('label'));
        $count = M()->table('tfb_onlinesee_recommend')
            ->where("label='" . $label . "' and value != '" . $value . "'")
            ->count();
        if ($count > 0) {
            echo "1";
        } else {
            $count = M()->table('tfb_onlinesee_recommend')
                ->where("value ='" . $value . "'")
                ->count();
            if ($count == 0) {
                $data = array(
                    'label' => $label, 
                    'value' => $value, 
                    'add_time' => date('YmdHis'));
                $info_id = M()->table('tfb_onlinesee_recommend')->add($data);
                if ($info_id) {
                    echo "修改成功！";
                }
            } else {
                $data = array(
                    'label' => $label);
                $flag = M()->table('tfb_onlinesee_recommend')
                    ->where('value=' . $value)
                    ->save($data);
                if ($flag === false) {
                    
                    echo "修改失败[01]！";
                } else {
                    echo "修改成功！";
                }
            }
        }
    }

    public function recommend_status() {
        $value = I('value');
        $data['status'] = I('status');
        $map = array(
            'value' => $value);
        $res = M("tfb_onlinesee_recommend")->where($map)->save($data);
        if ($res) {
            $this->ajaxReturn(1, "修改成功！", 1);
        } else {
            $this->ajaxReturn(0, "修改失败！", 0);
        }
    }
}
