<?php

class ManageAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public function index() {
        $data = I('request.');
        $action = $data['ac'];
        $map = array();
        $name = I('name');
        if ($name != "") {
            $map['m.name'] = array(
                'exp', 
                "like '%$name%'");
        }
        $staff_number = I('staff_number');
        if ($staff_number != "") {
            $map['m.staff_number'] = array(
                'exp', 
                "like '%$staff_number%'");
        }
        $group_id = I('group_id');
        if ($group_id != "") {
            $map['a.group_id'] = $group_id;
        }
        $info_id = I('info_id');
        if ($info_id != "") {
            $map['info.id'] = $info_id;
        }
        $count_sql = M()->table('tfb_onlinesee_trace a')
            ->join('tfb_onlinesee_group b on a.group_id = b.id')
            ->join('tfb_onlinesee_member m on m.staff_number = a.staff_number')
            ->join('tfb_onlinesee_activity_info info on info.id = a.info_id')
            ->where($map)
            ->field(
            'a.*,sum(see_point) as see_point_all,count(*) as join_count,m.name,info.name as info_name')
            ->group('a.staff_number,a.group_id')
            ->buildSql();
        $count = M()->table("($count_sql) a")->count();
        
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        if ($action != 'download') {
            $list = M()->table('tfb_onlinesee_trace a')
                ->join('tfb_onlinesee_group b on a.group_id = b.id')
                ->join(
                'tfb_onlinesee_member m on m.staff_number = a.staff_number')
                ->join(
                'tfb_onlinesee_activity_info info on info.id = a.info_id')
                ->where($map)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->field(
                'a.*,sum(see_point) as see_point_all,count(*) as join_count,m.name,info.name as info_name')
                ->group('a.staff_number,a.group_id')
                ->select();
        } else {
            /*
             * $list = M()->table('tfb_onlinesee_trace a')
             * ->join('tfb_onlinesee_group b on a.group_id = b.id')
             * ->join('tfb_onlinesee_member m on m.staff_number =
             * a.staff_number') ->join('tfb_onlinesee_activity_info info on
             * info.id = a.info_id') ->where($map) ->field('a.*,sum(see_point)
             * as see_point_all,count(*) as join_count,m.name,info.name as
             * info_name') ->group('a.staff_number,a.group_id') ->select();
             */
            $list = M()->table('tfb_onlinesee_trace a')
                ->join('tfb_onlinesee_group b on a.group_id = b.id')
                ->join(
                'tfb_onlinesee_member m on m.staff_number = a.staff_number')
                ->join(
                'tfb_onlinesee_activity_info info on info.id = a.info_id')
                ->join('tfb_onlinesee_recommend r on a.recommend = r.value')
                ->where($map)
                ->field(
                'a.*,m.name,info.name as info_name,info.id as info_id,b.id as group_id,r.label as r_label,r.status as r_status')
                ->select();
            $count = count($list);
            if ($count <= 0) {
                $this->error('无数据可下载');
            } else {
                $fileName = '所查询员工信息的评价明细.csv';
                $fileName = iconv('utf-8', 'gb2312', $fileName);
                header("Content-type: text/plain");
                header("Accept-Ranges: bytes");
                header("Content-Disposition: attachment; filename=" . $fileName);
                header(
                    "Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                header("Expires: 0");
                $start_num = 0;
                $page_count = 5000;
                // $cj_title = "活动员工编号,员工姓名,所属分组,活动名称,参与评价人数,整体服务(均)\r\n";
                $cj_title = "员工编号,员工姓名,所属分组,活动名称,整体服务,用户推荐值,用户推荐值内容,评价时间,评价者IP,评价者姓名,联系方式,车牌号";
                
                $info_items = M()->table('tfb_onlinesee_activity_info_item a')
                    ->where('info_id=' . I('info_id'))
                    ->join('tfb_onlinesee_item b on a.item_id = b.id')
                    ->field("a.*,b.item_content")
                    ->select();
                foreach ($info_items as $item) {
                    $cj_title .= "," . $item['item_content'];
                }
                $cj_title .= ",其他意见与建议\r\n";
                echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
                // $status = array('0' => "停用", '1' => "正常");
                foreach ($list as $v) {
                    $checked_items = M()->table('tfb_onlinesee_trace_ex a')
                        ->where('trace_id=' . $v['id'])
                        ->select();
                    $checked = array();
                    foreach ($checked_items as $item) {
                        $checked[$item['item_id']] = $item['item_content'];
                    }
                    if ($v['r_status'] == 0) {
                        $v['r_label'] = '';
                        // $v['recommend'] = '';
                    }
                    $line_data = "{$v['staff_number']}," . "{$v['name']}," .
                         "{$v['group_name']}," . "{$v['info_name']}," .
                         "{$v['see_point']}," . "{$v['recommend']}," .
                         "{$v['r_label']}," .
                         date('Y-m-d H:i:s', strtotime($v['add_time'])) . "," .
                         "{$v['from_ip']}," . "{$v['from_name']}," .
                         "{$v['from_mobile']}," . "{$v['from_car_number']}";
                    
                    foreach ($info_items as $item) {
                        if (isset($checked[$item['item_id']])) {
                            $line_data .= ",是";
                        } else {
                            $line_data .= ",否";
                        }
                    }
                    $find_str = array(
                        ",", 
                        "\r\n", 
                        "\n", 
                        "\r");
                    $replace = '，';
                    $v['customer_note'] = str_replace($find_str, $replace, 
                        trim($v['customer_note']));
                    $line_data .= "," . $v['customer_note'] . "\r\n";
                    
                    echo iconv('utf-8', 'gbk', $line_data);
                }
            }
            exit();
        }
        
        $groups = M()->table('tfb_onlinesee_group')->select();
        $activity_info = M()->table('tfb_onlinesee_activity_info')->select();
        $this->assign('groups', $groups);
        $this->assign('activity_info', $activity_info);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function index_download() {
        /*
         * 员工编号、员工姓名、所属分组、活动名称、评价项1状态、评价项2状态、评价项3状态、整体服务（均）、评价时间、评价者IP、评价者姓名、联系方式、车牌号（若未勾选为空）
         */
        $map = array(
            'a.group_id' => I('group_id'), 
            'info.id' => I('info_id'), 
            'a.staff_number' => I('staff_number'));
        $list = M()->table('tfb_onlinesee_trace a')
            ->join('tfb_onlinesee_group b on a.group_id = b.id')
            ->join('tfb_onlinesee_member m on m.staff_number = a.staff_number')
            ->join('tfb_onlinesee_activity_info info on info.id = a.info_id')
            ->join('tfb_onlinesee_recommend r on a.recommend = r.value')
            ->where($map)
            ->field(
            'a.*,m.name,info.name as info_name,info.id as info_id,b.id as group_id,r.label as r_label,r.status as r_status')
            ->select();
        
        $count = count($list);
        if ($count <= 0) {
            $this->error('无数据可下载');
        } else {
            $fileName = '员工评价明细.csv';
            $fileName = iconv('utf-8', 'gb2312', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $start_num = 0;
            $page_count = 5000;
            $cj_title = "员工编号,员工姓名,所属分组,活动名称,整体服务,用户推荐值,用户推荐值内容,评价时间,评价者IP,评价者姓名,联系方式,车牌号";
            
            $info_items = M()->table('tfb_onlinesee_activity_info_item a')
                ->where('info_id=' . I('info_id'))
                ->join('tfb_onlinesee_item b on a.item_id = b.id')
                ->field("a.*,b.item_content")
                ->select();
            foreach ($info_items as $item) {
                $cj_title .= "," . $item['item_content'];
            }
            $cj_title .= ",其他意见与建议\r\n";
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            foreach ($list as $v) {
                $checked_items = M()->table('tfb_onlinesee_trace_ex a')
                    ->where('trace_id=' . $v['id'])
                    ->select();
                $checked = array();
                foreach ($checked_items as $item) {
                    $checked[$item['item_id']] = $item['item_content'];
                }
                if ($v['r_status'] == 0) {
                    $v['r_label'] = '';
                    // $v['recommend'] = '';
                }
                $line_data = "{$v['staff_number']}," . "{$v['name']}," .
                     "{$v['group_name']}," . "{$v['info_name']}," .
                     "{$v['see_point']}," . "{$v['recommend']}," .
                     "{$v['r_label']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) . "," .
                     "{$v['from_ip']}," . "{$v['from_name']}," .
                     "{$v['from_mobile']}," . "{$v['from_car_number']}";
                
                foreach ($info_items as $item) {
                    if (isset($checked[$item['item_id']])) {
                        $line_data .= ",是";
                    } else {
                        $line_data .= ",否";
                    }
                }
                $find_str = array(
                    ",", 
                    "\r\n", 
                    "\n", 
                    "\r");
                $replace = '，';
                $v['customer_note'] = str_replace($find_str, $replace, 
                    trim($v['customer_note']));
                $line_data .= "," . $v['customer_note'] . "\r\n";
                
                echo iconv('utf-8', 'gbk', $line_data);
            }
        }
    }

    public function item_download() {
        /* 活动名称、活动状态、评价项内容、评价时间、评价者IP、评价者姓名、联系电话、车牌号 */
        $map = array(
            'b.group_id' => I('group_id'), 
            'info.id' => I('info_id'), 
            'a.item_id' => I('item_id'));
        $list = M()->table('tfb_onlinesee_trace_ex a')
            ->join('tfb_onlinesee_trace b on a.trace_id = b.id')
            ->join('tfb_onlinesee_activity_info info on info.id = b.info_id')
            ->where($map)
            ->field(
            'info.name as info_name,info.status,a.item_content,b.from_ip,b.from_name,b.from_mobile,b.from_car_number,b.add_time')
            ->select();
        $count = count($list);
        /*
         * echo M()->table('tfb_onlinesee_trace_ex a')
         * ->join('tfb_onlinesee_trace b on a.trace_id = b.id')
         * ->join('tfb_onlinesee_activity_info info on info.id = b.info_id')
         * ->where($map) ->field('info.name as
         * info_name,info.status,a.item_content,b.from_ip,b.from_name,b.from_mobile,b.from_car_number,b.add_time')
         * ->buildSql(); die();
         */
        if ($count <= 0) {
            $this->error('无数据可下载');
        } else {
            $fileName = '评价明细.csv';
            $fileName = iconv('utf-8', 'gb2312', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $start_num = 0;
            $page_count = 5000;
            $cj_title = "活动名称,活动状态,评价项内容,评价时间,评价者IP,评价者姓名,联系电话,车牌号\r\n";
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            $status = array(
                '0' => "停用", 
                '1' => "正常");
            foreach ($list as $v) {
                $line_data = "{$v['info_name']}," . "{$status[$v['status']]}," .
                     "{$v['item_content']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) . "," .
                     "{$v['from_ip']}," . "{$v['from_name']}," .
                     "{$v['from_mobile']}," . "{$v['from_car_number']}\r\n";
                
                echo iconv('utf-8', 'gbk', $line_data);
            }
        }
    }

    public function item() {
        $data = I('request.');
        $action = $data['ac'];
        $map = array();
        /*
         * $staff_number = I('staff_number'); if ($staff_number != "") {
         * $map['m.staff_number'] = array('exp', "like '%$staff_number%'"); }
         */
        $item_id = I('item_id');
        if ($item_id != "") {
            $map['a.item_id'] = $item_id;
        }
        $group_id = I('group_id');
        if ($group_id != "") {
            $map['b.group_id'] = $group_id;
        }
        $info_id = I('info_id');
        if ($info_id != "") {
            $map['info.id'] = $info_id;
        }
        $status = I('status');
        if ($status != "") {
            $map['info.status'] = $status;
        }
        $count_sql = M()->table('tfb_onlinesee_trace_ex a')
            ->join('tfb_onlinesee_trace b on a.trace_id = b.id')
            ->join('tfb_onlinesee_activity_info info on info.id = b.info_id')
            ->join('tfb_onlinesee_group g on b.group_id = g.id')
            ->join('tfb_onlinesee_member m on m.staff_number = b.staff_number')
            ->where($map)
            ->field(
            'info.name as info_name,info.status,g.group_name,a.item_content,(select count(*) from tfb_onlinesee_trace where info_id=b.info_id and group_id=b.group_id ) as join_count,(select count(*) from tfb_onlinesee_trace_ex where trace_id=b.id and item_id=a.item_id ) as checked_count')
            ->group('a.item_id,b.group_id')
            ->buildSql();
        $count = M()->table("($count_sql) a")->count();
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        
        if ($action != 'download') {
            $list = M()->table('tfb_onlinesee_trace_ex a')
                ->join('tfb_onlinesee_trace b on a.trace_id = b.id')
                ->join(
                'tfb_onlinesee_activity_info info on info.id = b.info_id')
                ->join('tfb_onlinesee_group g on b.group_id = g.id')
                ->where($map)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->field(
                'info.name as info_name,info.status,g.group_name,a.item_content,(select count(*) from tfb_onlinesee_trace where info_id=b.info_id and group_id=b.group_id ) as join_count,(select count(*) from tfb_onlinesee_trace_ex where trace_id=b.id and item_id=a.item_id ) as checked_count,b.info_id as info_id,b.group_id as group_id,a.item_id as item_id')
                ->group('a.item_id,b.group_id')
                ->select();
        } else {
            $list = M()->table('tfb_onlinesee_trace_ex a')
                ->join('tfb_onlinesee_trace b on a.trace_id = b.id')
                ->join(
                'tfb_onlinesee_activity_info info on info.id = b.info_id')
                ->join('tfb_onlinesee_group g on b.group_id = g.id')
                ->where($map)
                ->field(
                'info.name as info_name,info.status,g.group_name,a.item_content,(select count(*) from tfb_onlinesee_trace where info_id=b.info_id and group_id=b.group_id ) as join_count,(select count(*) from tfb_onlinesee_trace_ex where trace_id=b.id and item_id=a.item_id ) as checked_count,b.info_id as info_id,b.group_id as group_id,b.see_point')
                ->group('a.item_id,b.group_id')
                ->select();
            $count = count($list);
            if ($count <= 0) {
                $this->error('无数据可下载');
            } else {
                $fileName = '评价项情况统计.csv';
                $fileName = iconv('utf-8', 'gb2312', $fileName);
                header("Content-type: text/plain");
                header("Accept-Ranges: bytes");
                header("Content-Disposition: attachment; filename=" . $fileName);
                header(
                    "Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                header("Expires: 0");
                $start_num = 0;
                $page_count = 5000;
                $cj_title = "活动名称,活动状态,整体服务,评价项内容,参与评价人数,被评价人数\r\n";
                echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
                $status = array(
                    '0' => "停用", 
                    '1' => "正常");
                foreach ($list as $v) {
                    $line_data = "{$v['info_name']}," .
                         "{$status[$v['status']]}," . "{$v['see_point']}," .
                         "{$v['item_content']}," . "{$v['join_count']}," .
                         "{$v['checked_count']}\r\n";
                    
                    echo iconv('utf-8', 'gbk', $line_data);
                }
            }
            exit();
        }
        $groups = M()->table('tfb_onlinesee_group')->select();
        $activity_info = M()->table('tfb_onlinesee_activity_info')->select();
        $this->assign('groups', $groups);
        $this->assign('activity_info', $activity_info);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }
}
