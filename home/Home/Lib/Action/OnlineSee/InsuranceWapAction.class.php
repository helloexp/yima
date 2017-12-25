<?php

/*
 * 陕西平安产险wap
 */
class InsuranceWapAction extends Action {
    
    // public $_authAccessMap = '*';
    public function _initialize() {
        C('TMPL_ACTION_ERROR', './Home/Tpl/Label/Public_error.html');
        C('TMPL_ACTION_SUCCESS', './Home/Tpl/Label/Public_msg.html');
    }

    public function index() {
        $id = I("id", "");
        $type = I("type", "");
        $key = I("key", "");
        if ($type != 3) {
            if (empty($id) || $type == "" || $key == "") {
                $this->error(
                    array(
                        'errorTxt' => '错误访问！', 
                        'errorSoftTxt' => '您的访问地址出错啦~'));
            }
            /* 校验key有效性 */
            $map = array();
            $map['a.url_key'] = $key;
            $map['a.status'] = 1;
            $key_list = M()->table('tfb_onlinesee_link a')
                ->join("tfb_onlinesee_link_crontab b on a.crontab_id=b.id")
                ->where($map)
                ->field('a.*,b.url_start_time,b.url_useful_life')
                ->select();
            if (count($key_list) > 0) {
                $this->error(
                    array(
                        'errorTxt' => '错误访问！', 
                        'errorSoftTxt' => '尊敬的车主，您已经填写过问卷了，同一问卷不可重复填写，感谢您的支持！'));
            }
            
            $map['a.status'] = 0;
            $key_list = M()->table('tfb_onlinesee_link a')
                ->join("tfb_onlinesee_link_crontab b on a.crontab_id=b.id")
                ->where($map)
                ->field('a.*,b.url_start_time,b.url_useful_life')
                ->select();
            if (count($key_list) != 1) {
                $this->error(
                    array(
                        'errorTxt' => '错误访问！', 
                        'errorSoftTxt' => 'url参数key错误！'));
            }
            foreach ($key_list as $v) {
                if (strtotime($v['url_start_time']) >
                     strtotime(date("Y-m-d H:i:s"))) {
                    $this->error(
                        array(
                            'errorTxt' => '错误访问！', 
                            'errorSoftTxt' => '尊敬的车主，您来早了，问卷还没开始。'));
                }
                if (strtotime(date("Y-m-d H:i:s")) -
                     strtotime($v['url_start_time']) >
                     $v['url_useful_life'] * 3600) {
                    $this->error(
                        array(
                            'errorTxt' => '错误访问！', 
                            'errorSoftTxt' => '尊敬的车主，您来晚了，问卷已失效。问卷' .
                             $v['url_useful_life'] . '小时内有效，感谢您的支持！'));
                }
            }
            /* 校验key有效性结束 */
        }
        
        $list = array();
        $info = array();
        $point = array();
        if ($type != 3) {
            if ($type == 1) {
                // 获取人信息
                $list = M("tfb_onlinesee_member")->where("id={$id}")->find();
                
                // 获取活动信息
                $info_date = date("Ymdhis");
                
                $info = M()->table("tfb_onlinesee_activity_info_ex e")->join(
                    "tfb_onlinesee_activity_info i on e.info_id=i.id")
                    ->where(
                    "e.mount_type=1 and e.mount_id={$id} and i.start_time <= {$info_date} and i.end_time >= {$info_date}")
                    ->field("e.info_id, i.*")
                    ->select();
                
                if (! $info) {
                    $this->error(
                        array(
                            'errorTxt' => '错误提示！', 
                            'errorSoftTxt' => '未找到相应的活动，活动未开始或者已结束~'));
                }
                $info = end($info);
                
                // 获取平均评分
                $point = M("tfb_onlinesee_trace")->where(
                    "info_id={$info['info_id']} and staff_number='{$list['staff_number']}'")
                    ->field("avg(see_point) as point")
                    ->select();
            }
            if ($type == 0) {
                // 获取人信息
                $list = M()->table("tfb_onlinesee_crew c")->join(
                    "tfb_onlinesee_member m on c.staff_number=m.staff_number")
                    ->where("c.id={$id}")
                    ->field(
                    "c.group_id, c.staff_number, m.name, m.staff_service_dec, m.image_link")
                    ->find();
                
                // 获取活动信息
                $info_date = date("Ymdhis");
                
                $info = M()->table("tfb_onlinesee_activity_info_ex e")->join(
                    "tfb_onlinesee_activity_info i on e.info_id=i.id")
                    ->where(
                    "e.mount_type=0 and e.mount_id={$list['group_id']} and i.start_time <= {$info_date} and i.end_time >= {$info_date}")
                    ->field("e.info_id, i.*")
                    ->select();
                
                if (! $info) {
                    $this->error(
                        array(
                            'errorTxt' => '错误提示！', 
                            'errorSoftTxt' => '未找到相应的活动，活动未开始或者已结束~'));
                }
                $info = end($info);
                
                // 获取平均评分
                $point = M("tfb_onlinesee_trace")->where(
                    "info_id={$info['info_id']} and staff_number='{$list['staff_number']}'")
                    ->field("avg(see_point) as point")
                    ->select();
            }
            
            // if ($info_date < $info["start_time"])
            // {
            // $this->error(array(
            // 'errorTxt' => '活动还未开始',
            // 'errorSoftTxt' => '您的访问的活动还未开始~'
            // ));
            // } else if ($info_date > $info["end_time"])
            // {
            // $this->error(array(
            // 'errorTxt' => '活动已结束',
            // 'errorSoftTxt' => '您的访问活动已结束~'
            // ));
            // }
            
            if ($info["status"] == 0) {
                $this->error(
                    array(
                        'errorTxt' => '活动已停用', 
                        'errorSoftTxt' => '该活动已停用~'));
            }
            
            // 获取不满意选项
            $items = M()->table("tfb_onlinesee_item i")->join(
                "tfb_onlinesee_activity_info_item f on i.id=f.item_id")
                ->field("i.id, i.item_content")
                ->where("f.info_id={$info['info_id']} and i.status<>0")
                ->select();
            
            $itemArr = array();
            $itemSelArr = array();
            if ($info['item_tab'] != "") {
                foreach ($items as $k => $v) {
                    $itemArr["{$v['id']}"] = $v['item_content'];
                }
                // 不同星的选择集合id
                $itemSelArr = get_object_vars(json_decode($info['item_tab']));
            }
            
            $pnum = 0;
            if ($point[0]['point'] > 0) {
                $pnum = round($point[0]['point']);
            }
            
            // 人员头像
            if (empty($list['image_link'])) {
                $list['image_link'] = "__PUBLIC__/Label/Image/pinganSx/photo_03.png";
            } else {
                $list['image_link'] = get_upload_url($list['image_link']);
            }
        }
        
        if ($type == 3) {
            $list = array();
            $list['name'] = '测试员工';
            $list['staff_number'] = 'A010101';
            $list['staff_service_dec'] = '服务宣言是员工状态的一种展示。';
            $list['image_link'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                 '/Image/portrait.gif';
            
            $pnum = 8.5;
            
            $info = M("tfb_onlinesee_activity_info")->where("id={$id}")
                ->field("*")
                ->find();
            
            $items = M()->table("tfb_onlinesee_item i")->join(
                "tfb_onlinesee_activity_info_item f on i.id=f.item_id")
                ->field("i.id, i.item_content")
                ->where("f.info_id={$id}")
                ->select();
        } else {
            $cNum = $info['click_count'] + 1;
            M("tfb_onlinesee_activity_info")->where("id={$info['id']}")->save(
                array(
                    "click_count" => $cNum));
        }
        
        $defArr = json_decode($info['defined']);
        $this->assign("memberList", $list);
        $this->assign("id", $id);
        $this->assign("type", $type);
        $this->assign("info", $info);
        $this->assign("infoSel", json_decode($info['select_type']));
        $this->assign("infoDef", get_object_vars($defArr));
        $this->assign("infoChe", json_decode($info['config_data']));
        $this->assign("point", $pnum);
        $this->assign("itemSel", $itemSelArr);
        $this->assign("itemArr", $itemArr);
        $this->display();
    }

    public function evaluation() {
        $id = I("id", "");
        $type = I("type", "");
        $info_id = I("infoId", "");
        $staff_number = I("staffNumber", "");
        $scoreVal = I("scoreVal", "");
        $pingfen = I("score", "");
        $opinion = I("opinion", "");
        $sat = I("checkV", "");
        $name = I("user_name", "");
        $phone = I("phone_number", "");
        $carNum = I("car_number", "");
        $defOne = I("defined10", "");
        $defTwo = I("defined11", "");
        $defThree = I("defined12", "");
        $url_key = I('url_key');
        /* 校验key有效性 */
        $map = array();
        $map['a.url_key'] = $url_key;
        $map['a.status'] = 1;
        $key_list = M()->table('tfb_onlinesee_link a')
            ->join("tfb_onlinesee_link_crontab b on a.crontab_id=b.id")
            ->where($map)
            ->field('a.*,b.url_start_time,b.url_useful_life')
            ->select();
        if (count($key_list) > 0) {
            $this->error(
                array(
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '尊敬的车主，您已经填写过问卷了，同一问卷不可重复填写，感谢您的支持！', 
                    'errorFlg' => 5));
        }
        
        if (empty($id) || $type == "" || empty($info_id) || empty($staff_number)) {
            $this->error(
                array(
                    'errorTxt' => '错误请求！', 
                    'errorSoftTxt' => '网页数据出错啦~'));
        }
        
        $group_id = 0;
        $group_name = "单个员工";
        if ($type == 0) {
            $cArr = M()->table("tfb_onlinesee_crew c")->join(
                "tfb_onlinesee_group g on c.group_id=g.id")
                ->where("c.id={$id}")
                ->field("c.group_id, g.group_name")
                ->find();
            
            if (! $cArr) {
                $this->error(
                    array(
                        'errorTxt' => '错误请求！', 
                        'errorSoftTxt' => '网页数据出错啦~'));
            }
            $group_id = $cArr['group_id'];
            $group_name = $cArr['group_name'];
        }
        
        $pingfen = $pingfen * 2;
        $data = array(
            'info_id' => $info_id, 
            'staff_number' => $staff_number, 
            'group_id' => $group_id, 
            'group_name' => $group_name, 
            'see_point' => $pingfen, 
            'from_mobile' => $phone, 
            'from_name' => $name, 
            'from_car_number' => $carNum, 
            'from_ip' => GetIP(), 
            'custom_1' => $defOne, 
            'custom_2' => $defTwo, 
            'custom_3' => $defThree, 
            'add_time' => date('YmdHis'), 
            'customer_note' => $opinion, 
            'recommend' => $scoreVal);
        $result = M("tfb_onlinesee_trace")->add($data);
        if (! $result) {
            $this->error(
                array(
                    'errorTxt' => '错误请求！', 
                    'errorSoftTxt' => '评分失败啦~'));
        }
        
        if (! empty($sat)) {
            $itemArr = explode(",", $sat);
            array_pop($itemArr);
            if (is_array($itemArr)) {
                foreach ($itemArr as $k => $v) {
                    $itemName = M("tfb_onlinesee_item")->where("id={$v}")
                        ->field("item_content")
                        ->find();
                    
                    $iData = array(
                        'trace_id' => $result, 
                        'item_id' => $v, 
                        'item_content' => $itemName['item_content']);
                    $res = M("tfb_onlinesee_trace_ex")->add($iData);
                    
                    if (! $result) {
                        $this->error(
                            array(
                                'errorTxt' => '错误请求！', 
                                'errorSoftTxt' => '选择不满意意见评价失败啦~'));
                    }
                }
            }
        }
        
        $url = "";
        $red = M("tfb_onlinesee_activity_info")->where("id={$info_id}")
            ->field("redirect_url, redirect_url_flag")
            ->find();
        
        if ($red["redirect_url_flag"] == 1) {
            $url = $red["redirect_url"];
        }
        $update_data = array(
            "status" => 1);
        $Where['url_key'] = $url_key;
        $res = M('tfb_onlinesee_link')->where($Where)->save($update_data);
        
        $this->ajaxReturn($url, "评分成功！感谢你的参与。。", 1);
        exit();
    }
}
