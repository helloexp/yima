<?php

/**
 * 全民营销 2014年9月1日13:34:56 @kk
 */
class IndexAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '33';

    public $BATCH_TYPE_1 = '2,3,10,20';

    public $BATCH_TYPE_2 = '26,27';
    
    // 图片路径
    public $img_path;

    public $menu = array();
    
    // 返佣类型
    private $commission_type_arr = array(
        '0' => '卡券', 
        '1' => 'Q币', 
        '2' => '话费', 
        '3' => '现金');

    private $return_status_arr = array(
        '0' => '未发放', 
        '1' => '已发放', 
        '2' => '待确认');

    private $batch_status_arr = array(
        '0' => '启用', 
        '1' => '暂停', 
        '2' => '欠费暂停');

    private $return_channel_arr = array(
        '0' => '新浪微博', 
        '1' => 'QQ空间', 
        '2' => '腾讯微博', 
        '3' => '人人网', 
        '4' => '微信朋友圈', 
        '9' => '其他');

    private $allow_batch_type = array(
        '2', 
        '3', 
        '10', 
        '20', 
        '26', 
        '27');
    
    // 通宝斋标志
    private $tongbaozhai_flag = false;

    public $_authAccessMap = '*';
    
    // 全民营销金额返佣活动，系统参数
    const SYS_PARAM_QMYX_REMAIN = 'QMYX_MIN_REMAIN';

    const SYS_PARAM_QMYX_START_REMAIN = 'QMYX_START_MIN_REMAIN';
    
    // 全民营销charge_code
    const QMYX_CHARGE_CODE = '3060';
    
    // 初始化
    public function _initialize() {
        header("HTTP/1.0 404 Not Found");
        $this->display('../Public/404');
        exit();
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        
        $this->assign('commission_type_arr', $this->commission_type_arr);
        $this->assign('return_status_arr', $this->return_status_arr);
        $this->assign('return_channel_arr', $this->return_channel_arr);
        
        // 通宝斋标志
        $this->tongbaozhai_flag = in_array($this->nodeId, 
            C('fb_tongbaozhai.node_id'), true) ? 1 : 0;
        $this->assign('tongbaozhai_flag', $this->tongbaozhai_flag);
    }
    
    // 导航页
    public function _get_batch_type() {
        static $batch_type = null;
        if ($batch_type != '')
            return $batch_type;
        
        $batch_type = I('batch_type', '', 'intval');
        if ($batch_type == '') {
            foreach ($this->menu as $v) {
                foreach ($v as $k => $vv) {
                    $batch_type = $k;
                    break (2);
                }
            }
        }
        return $batch_type;
    }

    public function intro() {
        if (! $this->_hasPower(true)) {
            $map = array(
                'node_id' => $this->nodeId, 
                'type' => '6');
            $cnt = M('tmessage_apply')->where($map)->count();
            if ($cnt == 0) {
                $concatInfo = M('tnode_info')->where(
                    "node_id = '{$this->nodeId}'")
                    ->field('contact_name, contact_phone, contact_eml')
                    ->find();
                $this->assign('concatInfo', $concatInfo);
            }
            $hook_actions = array(
                'intro');
            $this->assign('hook_actions', $hook_actions);
            $this->assign('apply_flag', $cnt == 0 ? 1 : 0);
            $this->display('noPower');
        } else {
            
            /* 获取全民营销数量 */
            $map = array();
            $map = array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"));
            $mapcount = M()->table('treturn_commission_info')
                ->where($map)
                ->count();
            // 查询满足要求的总记录数
            
            /* 获取活动列表 */
            
            // 非商品销售的多乐互动必须要有奖品才能做全民营销的活动
            $map = array();
            $other_field = '';
            $map['_string'] = '(select count(*) from tcj_batch b where b.batch_id = a.id)>0';
            $map['a.node_id'] = $this->nodeId;
            $map['a.batch_type'] = array(
                'in', 
                '2,3,10,20');
            $map['a.end_time'] = array(
                'gt', 
                date('YmdHis'));
            $map['a.return_commission_flag'] = 0;
            
            $queryData = M()->table('tmarketing_info a')
                ->field(
                "a.id batch_id, a.name, a.start_time, a.end_time" . $other_field)
                ->where($map)
                ->order("a.id desc")
                ->limit(6)
                ->select();
            
            /* 获取活动列表end */
            
            /* 获取营销案例 */
            
            /* 巴拉巴拉巴拉巴拉巴拉巴拉巴拉 */
            
            /* 获取操作指南 帮助 */
            $map = array();
            $map['parent_class_id'] = 44;
            $map['status'] = 1;
            $map['class_id'] = 48;
            $map['parent_class_id'] = 44;
            $query_help_list = M()->table('tym_news')
                ->field("news_id,news_name")
                ->where($map)
                ->order("news_id desc")
                ->select();
            
            /* 获取操作指南 帮助end */
            
            $active_list = $queryData;
            if (empty($active_list)) {
                $has_commission = false;
            } else {
                $has_commission = true;
            }
            
            /* 定义属于本操作的子操作 */
            $hook_actions = array(
                'intro');
            $this->assign('active_list', $active_list);
            $this->assign('has_commission', $has_commission);
            $this->assign('hook_actions', $hook_actions);
            $this->assign('help_list', $query_help_list);
            
            if ($mapcount > 0) {
                
                /* 获取统计数据 */
                $map = array(
                    
                    // 'a.return_commission_id' => $returnInfo['id'],
                    'a.node_id' => array(
                        'exp', 
                        "in (" . $this->nodeIn() . ")"));
                
                // 返佣产生渠道 0-新浪微博 1-QQ空间 2-腾讯微博 3-人人网 4-微信朋友圈 9-其他
                $return_channel_name = "case when return_channel ='0' then '新浪微博' when return_channel ='1' then 'QQ空间' when return_channel ='2' then '腾讯微博' when return_channel ='3' then '人人网' when return_channel ='4' then '微信朋友圈' else '其他' end";
                $field_arr = array(
                    "{$return_channel_name} as return_channel", 
                    'ifnull(sum(return_page_flow_count), 0) as return_page_flow_count', 
                    'ifnull(sum(transmit_count), 0) as transmit_count', 
                    'ifnull(sum(flow_count), 0) as flow_count', 
                    'ifnull(sum(return_times), 0) as return_times', 
                    'ifnull(sum(trans_count), 0) as trans_count', 
                    'ifnull(sum(marketing_join_count), 0) as marketing_join_count');
                // 'count(*) as active_count'
                
                $list = M()->table('treturn_commission_daystat a')
                    ->where($map)
                    ->group($return_channel_name)
                    ->getField(implode(',', $field_arr));
                
                /* 获取昨日数据 */
                $map['_string'] = '';
                $time = time() - 1 * 24 * 3600;
                $stat_date = date('Ymd', $time);
                if ($stat_date != '')
                    $map['_string'] .= "a.stat_date = '{$stat_date}' ";
                $yesterday_list = M()->table('treturn_commission_daystat a')
                    ->where($map)
                    ->group($return_channel_name)
                    ->getField(implode(',', $field_arr));
                
                /* 获取今日数据 */
                $map['_string'] = '';
                $stat_date = date('Ymd');
                if ($stat_date != '')
                    $map['_string'] .= "a.stat_date = '{$stat_date}' ";
                $today_list = M()->table('treturn_commission_daystat a')
                    ->where($map)
                    ->group($return_channel_name)
                    ->getField(implode(',', $field_arr));
                
                /* 获取7日数据 */
                $field_arr = array(
                    "substr(stat_date,'1',8) as day", 
                    'ifnull(sum(return_page_flow_count), 0) as return_page_flow_count', 
                    'ifnull(sum(transmit_count), 0) as transmit_count', 
                    'ifnull(sum(flow_count), 0) as flow_count', 
                    'ifnull(sum(marketing_join_count), 0) as marketing_join_count', 
                    'ifnull(sum(return_times), 0) as return_times');
                $map['_string'] = '';
                $time = time() - 7 * 24 * 3600;
                $char_title = date("Y-m-d", $time) . "至" . date("Y-m-d");
                $begin_time = date('Ymd', $time);
                $end_time = date('Ymd');
                if ($begin_time != '')
                    $map['_string'] .= "a.stat_date >= '{$begin_time}' ";
                if ($end_time != '')
                    $map['_string'] .= " and a.stat_date <= '{$end_time}' ";
                $week_list = M()->table('treturn_commission_daystat a')
                    ->where($map)
                    ->group("substr(a.stat_date,1,8)")
                    ->getField(implode(',', $field_arr));
                
                $series_data = array();
                $yesterday_data = array(
                    'transmit_count' => 0, 
                    'flow_count' => 0, 
                    'marketing_join_count' => 0, 
                    'trans_count' => 0);
                $today_data = array(
                    'transmit_count' => 0, 
                    'flow_count' => 0, 
                    'marketing_join_count' => 0, 
                    'trans_count' => 0);
                $week_data = array(
                    'transmit_count' => 0, 
                    'flow_count' => 0, 
                    'marketing_join_count' => 0, 
                    'trans_count' => 0);
                $summary_data = array(
                    'transmit_count' => 0, 
                    'flow_count' => 0, 
                    'marketing_join_count' => 0, 
                    'trans_count' => 0);
                $active_count = 0;
                if ($list) {
                    foreach ($list as $info) {
                        $series_data[$info['return_channel']] = intval(
                            $info['flow_count']);
                        $summary_data['transmit_count'] += intval(
                            $info['transmit_count']);
                        $summary_data['flow_count'] += intval(
                            $info['flow_count']);
                        $summary_data['marketing_join_count'] += intval(
                            $info['marketing_join_count']);
                        $summary_data['trans_count'] += intval(
                            $info['trans_count']);
                        
                        // $active_count+=intval($info['active_count']);
                    }
                }
                if ($yesterday_list) {
                    foreach ($yesterday_list as $info) {
                        $yesterday_data['transmit_count'] += intval(
                            $info['transmit_count']);
                        $yesterday_data['flow_count'] += intval(
                            $info['flow_count']);
                        $yesterday_data['marketing_join_count'] += intval(
                            $info['marketing_join_count']);
                        $yesterday_data['trans_count'] += intval(
                            $info['trans_count']);
                    }
                }
                if ($today_list) {
                    foreach ($today_list as $info) {
                        $today_data['transmit_count'] += intval(
                            $info['transmit_count']);
                        $today_data['flow_count'] += intval($info['flow_count']);
                        $today_data['marketing_join_count'] += intval(
                            $info['marketing_join_count']);
                        $today_data['trans_count'] += intval(
                            $info['trans_count']);
                    }
                }
                $shop_jsChartDataClick = array();
                // 小店PV访问量
                $shop_jsChartDataOrder = array();
                // 小店订单数
                $shop_jsChartDataAmt = array();
                // 小店销售额
                if ($week_list) {
                    foreach ($week_list as $v) {
                        $shop_jsChartDataClick[$v['day']] = array(
                            $v['day'], 
                            $v['transmit_count'] * 1);
                        $shop_jsChartDataOrder[$v['day']] = array(
                            $v['day'], 
                            $v['flow_count'] * 1);
                        $shop_jsChartDataAmt[$v['day']] = array(
                            $v['day'], 
                            $v['marketing_join_count'] * 1);
                    }
                }
                ksort($shop_jsChartDataOrder);
                ksort($shop_jsChartDataAmt);
                ksort($shop_jsChartDataClick);
                
                /*
                 * echo "<pre>"; var_dump($week_list); echo "</pre>";
                 */
                
                // die();
                // dump($list);
                // dump($series_data);
                // dump(json_encode($series_data['return_times']));
                $map2 = array(
                    'a.return_commission_class' => array(
                        'neq', 
                        '2'), 
                    'a.node_id' => array(
                        'exp', 
                        "in (" . $this->nodeIn() . ")"));
                $active_count = M()->table('treturn_commission_info a')
                    ->where($map2)
                    ->count();
                $this->assign('list', $list);
                $this->assign('series_data', $series_data);
                $this->assign('active_count', $active_count);
                $this->assign('summary_data', $summary_data);
                $this->assign('yesterday_data', $yesterday_data);
                $this->assign('today_data', $today_data);
                $this->assign('week_data', $week_data);
                
                $this->assign('shop_jsChartDataClick', 
                    json_encode(array_values($shop_jsChartDataClick)));
                $this->assign('shop_jsChartDataOrder', 
                    json_encode(array_values($shop_jsChartDataOrder)));
                $this->assign('shop_jsChartDataAmt', 
                    json_encode(array_values($shop_jsChartDataAmt)));
                $this->assign('char_title', $char_title);
                $this->display('overview');
            } else {
                $this->display();
            }
        }
        
        exit();
    }

    public function _get_notify_note($name = null) {
        $notice = M('tsystem_param')->where(
            array(
                'param_name' => 'RETURN_COMMISSION_NOTIFY_NOTE'))->getField(
            'param_value');
        $search = array(
            '#COMMISSION_COUNT#', 
            '#COMMISSION_AMOUNT#');
        $replace = array(
            'N', 
            'X');
        if ($name != null) {
            $search[] = '#MARKETING_INFO_NAME#';
            $replace[] = $name;
        }
        return str_replace($search, $replace, $notice);
    }
    
    // 申请
    public function apply() {
        $name = I('name', '', 'trim');
        $mobile = I('mobile', '', 'trim');
        $email = I('email', '', 'trim');
        $memo = I('content', '', 'trim');
        
        if ($name == '' || $mobile == '')
            $this->error('带*号的必填！');
        
        if (empty($mobile) || ! is_numeric($mobile) || strlen($mobile) != 11)
            $this->error('联系电话格式不正确！');
        
        $query_arr = array(
            'node_id' => $this->nodeId, 
            'type' => '6');
        
        $result = M('tmessage_apply')->where($query_arr)->find();
        if ($result)
            $this->error('您已经提交申请，请勿重复提交！');
        
        $send_email = C('return_commission.apply_receive_email');
        $nodeInfo = M('tnode_info')->where("node_id = '{$this->nodeId}'")->find();
        $arr = array(
            "旺号：{$nodeInfo['client_id']}", 
            "联系人: {$name}", 
            "联系电话：{$mobile}", 
            "联系邮箱：{$email}", 
            "备注：{$memo}");
        
        $data = array(
            'node_id' => $this->nodeId, 
            'type' => '6', 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'comment' => implode(';', $arr));
        $flag = M('tmessage_apply')->add($data);
        if ($flag) {
            $content = implode('<br>', $arr);
            $ps = array(
                "subject" => "全民营销开通申请,旺号：" . $nodeInfo['client_id'], 
                "content" => $content, 
                "email" => $send_email);
            send_mail($ps);
            $this->success('申请成功！');
        } else
            $this->success('申请失败！');
    }
    
    // 全民营销活动列表
    public function index() {
        if (! $this->_hasPower(true))
            $this->intro();
        
        $map = array(
            'a.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'a.id' => array(
                'exp', 
                '=b.m_id'), 
            'a.batch_type' => $this->BATCH_TYPE);
        
        $data = I('request.');
        if (! empty($data['node_id']))
            $map['a.node_id '] = $data['node_id'];
        if ($data['key'] != '') {
            $map['a.name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['b.status'] = $data['status'] == '1' ? '0' : array(
                'neq', 
                '0');
        }
        
        if ($data['start_time'] != '' && $data['end_time'] != '') {
            $map['a.add_time'] = array(
                'BETWEEN', 
                array(
                    $data['start_time'] . '000000', 
                    $data['end_time'] . '235959'));
        }
        import('ORG.Util.Page');
        // 导入分页类
        // $model = M('tmarketing_info');
        $mapcount = M()->table('tmarketing_info a, treturn_commission_info b')
            ->where($map)
            ->count();
        // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10);
        // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $map['b.marketing_info_id'] = array(
            'exp', 
            '=c.id');
        
        $show = $Page->show();
        // 分页显示输出
        $list = M()->table(
            'tmarketing_info a, tmarketing_info c, treturn_commission_info b')
            ->join('tgoods_info g on g.goods_id = b.goods_id')
            ->where($map)
            ->field(
            'a.*, b.status as f_status, g.goods_name, c.name as ac_name, b.commission_type, b.return_money_type, b.return_money, c.batch_type, b.marketing_info_id, c.start_time as ac_start_time, c.end_time as ac_end_time')
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show);
        // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
        // 输出模板
    }
    
    // 多乐互动跳转至全民营销配置界面，用jquery的load方法来加载此脚本
    public function gotoReturnConf() {
        $flag = $this->_hasPower(true);
        do {
            if (! $flag) {
                $ret_code = '1';
                $ret_msg = '您还没有开通全民营销！';
                break;
            }
            
            $m_id = I('m_id', 0, 'intval');
            
            if ($m_id == 0) {
                $ret_code = '1';
                $ret_msg = '参数错误！';
                break;
            }
            
            // 查询多乐互动
            $map = array(
                'node_id' => $this->nodeId, 
                'id' => $m_id);
            $marketingInfo = M('tmarketing_info')->where($map)->find();
            if (! $marketingInfo) {
                $ret_code = '1';
                $ret_msg = '多乐互动参数错误！';
                break;
            }
            
            if (! in_array($marketingInfo['batch_type'], 
                $this->allow_batch_type, true)) {
                $ret_code = '1';
                $ret_msg = '多乐互动【' . $marketingInfo['name'] . '】不能参加全民营销！';
                break;
            }
            
            // 非销售类的多乐互动，需要判断是否有设置奖品
            if (! in_array($marketingInfo['batch_type'], 
                array(
                    '26', 
                    '27'), true)) {
                $count = M('tcj_batch')->where("batch_id = '{$m_id}'")->count();
                if ($count == 0) {
                    $ret_code = '2';
                    $ret_msg = '多乐互动【' . $marketingInfo['name'] .
                         '】没有设置奖品信息，不能参加全民营销！<br>点击确认后跳转到抽奖配置界面';
                    $url = U('LabelAdmin/CjSet/index', 
                        array(
                            'batch_id' => $m_id));
                    break;
                }
            }
            
            // 查询该活动是否已经设置全民营销
            $map = array(
                'node_id' => $this->nodeId, 
                'marketing_info_id' => $m_id);
            $returnInfo = M('treturn_commission_info')->where($map)->find();
            if ($returnInfo) {
                $ret_code = '0';
                $url = U('edit', array(
                    'id' => $m_id));
                break;
            }
            
            if (strtotime($marketingInfo['end_time']) < time()) {
                $ret_code = '1';
                $ret_msg = '多乐互动【' . $marketingInfo['name'] . '】已经过期，不能参加全民营销！';
                break;
            }
            
            $ret_code = '0';
            $url = U('add', array(
                'm_id' => $m_id));
        }
        while (0);
        
        $this->assign('ret_code', $ret_code);
        $this->assign('ret_msg', $ret_msg);
        $this->assign('url', $url);
        $this->display();
    }

    private function urlparamtoarray($string) {
        $result = array();
        $string = urldecode($string);
        if (strlen($string) > 0) {
            $s_array = explode("&amp;", trim($string));
            foreach ($s_array as $item) {
                $t_array = explode("=", trim($item));
                if (urldecode($t_array[0]) == 'tbz_phone[]') {
                    $result['tbz_phone'][] = $t_array[1];
                } elseif (urldecode($t_array[0]) == 'tbz_rate[]') {
                    $result['tbz_rate'][] = $t_array[1];
                } else {
                    $result[urldecode($t_array[0])] = isset($t_array[1]) ? urldecode(
                        $t_array[1]) : '';
                }
            }
        }
        return $result;
    }
    
    // 添加
    public function add() {
        $this->_hasPower();
        if ($this->isPost()) {
            
            /* 某些预先用到的参数先接收过来 */
            $share_pic = I('img_resp');
            $share_note = I('share_note');
            $share_button_position = I('share_button_position');
            $t_arr = array();
            $rule = array(
                'null' => true, 
                'maxlen_cn' => '50', 
                'name' => '分享描述');
            $i = 0;
            foreach ($share_note as & $note) {
                ++ $i;
                if (trim($note) == '') {
                    unset($note);
                    continue;
                }
                $error = '';
                if (! check_str($note, $rule, $error)) {
                    $this->error('分享描述' . ($i) . $error);
                }
                $t_arr[] = $note;
            }
            $share_note_str = implode('--', $t_arr);
            
            /* 把条件规则转换为数组参数 */
            $is_selected = I("is_selected");
            $rule_array = array();
            try {
                M()->startTrans();
                
                /* 2.0版本先执行第三步 */
                
                // 查询多乐互动
                $map = array(
                    'node_id' => $this->nodeId, 
                    'id' => I('m_id'));
                $marketingInfo = M('tmarketing_info')->where($map)->find();
                
                // 全民营销渠道id
                $channel_id = $this->_get_default_channel_id();
                
                // 3. 将多乐互动发布至默认渠道--发布之后才能得到batch_channel_id给后面用
                $batchChannelData = array(
                    'batch_type' => $marketingInfo['batch_type'], 
                    
                    // 'batch_id' => $req['m_id'],
                    'batch_id' => I('m_id'), 
                    'channel_id' => $channel_id, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $this->nodeId);
                $batch_channel_id = M('tbatch_channel')->add($batchChannelData);
                if ($batch_channel_id === false) {
                    M()->rollback();
                    throw new Exception("全民营销活动创建失败[03]！");
                }
                
                /* 把返佣条件预先接收并格式化 方便存入通用信息表 */
                $return_rule_data = array();
                foreach ($is_selected as $selected_id) {
                    
                    /* url格式转数组 */
                    $return_rule_data["rule_data_" . $selected_id] = I(
                        "rule_data_" . $selected_id);
                }
                
                /* 数组序列化 */
                $return_rule_data = serialize($return_rule_data);
                
                foreach ($is_selected as $key => $selected_id) {
                    
                    /* url格式转数组 */
                    $rule_array["rule_data_" . $selected_id] = $this->urlparamtoarray(
                        I("rule_data_" . $selected_id));
                    $rule_array["rule_data_" . $selected_id]['return_condition'] = $selected_id;
                    
                    /* 参数校验 */
                    $req = array();
                    $rules = array(
                        'm_id' => array(
                            'null' => false, 
                            'name' => '多乐互动'), 
                        'commission_type' => array(
                            'null' => false, 
                            'name' => '返佣类型', 
                            'inarr' => array(
                                '0', 
                                '3')), 
                        'user_return_price_flag' => array(
                            'null' => false, 
                            'name' => '单用户最高返佣限制', 
                            'inarr' => array(
                                '0', 
                                '1')), 
                        'send_notice_flag' => array(
                            'null' => false, 
                            'name' => '是否发送通知短信', 
                            'inarr' => array(
                                '0', 
                                '1')), 
                        'commission_rule' => array(
                            'null' => false, 
                            'maxlen_cn' => '300', 
                            'name' => '返佣规则'), 
                        'return_condition' => array(
                            'null' => false, 
                            'name' => '返佣条件', 
                            'inarr' => array(
                                '1', 
                                '2', 
                                '3', 
                                '4')), 
                        'low_times_limit' => array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'name' => '返佣条件次数', 
                            'minval' => 1, 
                            'maxval' => 10));
                    $req = $this->_verifyReqData($rules, FALSE, 'post', 
                        $rule_array["rule_data_" . $selected_id]);
                    
                    $rules = array();
                    if ($req['user_return_price_flag'] == '1') {
                        $rules['user_return_price_time'] = array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => 1, 
                            'name' => '单用户最高返佣次数');
                    }
                    $req = array_merge($req, 
                        $this->_verifyReqData($rules, FALSE, 'post', 
                            $rule_array["rule_data_" . $selected_id]));
                    
                    if (! $marketingInfo) {
                        M()->rollback();
                        $this->error('多乐互动参数错误！');
                    }
                    
                    if (! in_array($marketingInfo['batch_type'], 
                        $this->allow_batch_type, true)) {
                        M()->rollback();
                        $this->error(
                            '多乐互动【' . $marketingInfo['name'] . '】不能参加全民营销！');
                    }
                    
                    // 非销售类的多乐互动，需要判断是否有设置奖品
                    if (! in_array($marketingInfo['batch_type'], 
                        array(
                            '26', 
                            '27'), true)) {
                        $count = M('tcj_batch')->where(
                            "batch_id = '{$req['m_id']}'")->count();
                        if ($count == 0) {
                            M()->rollback();
                            $this->error(
                                '多乐互动【' . $marketingInfo['name'] .
                                     '】没有设置奖品信息，不能参加全民营销！');
                        }
                    }
                    
                    /* 继续校验数据 */
                    
                    // 卡券返佣
                    if ($req['commission_type'] == '0') {
                        $rules = array(
                            'goods_id' => array(
                                'null' => false, 
                                'name' => '卡券'), 
                            'mms_title' => array(
                                'null' => false, 
                                'maxlen_cn' => '10', 
                                'name' => '彩信标题'), 
                            'mms_info' => array(
                                'null' => false, 
                                'maxlen_cn' => '100', 
                                'name' => '彩信内容'), 
                            'verify_time_type' => array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'name' => '验证时间类型', 
                                'inarr' => array(
                                    '0', 
                                    '1')));
                        $req = array_merge($req, 
                            $this->_verifyReqData($rules, FALSE, 'post', 
                                $rule_array["rule_data_" . $selected_id]));
                        
                        if ($req['verify_time_type'] == '0') {
                            $rules = array(
                                'verify_begin_date' => array(
                                    'null' => false, 
                                    'strtype' => 'datetime', 
                                    'format' => 'Ymd', 
                                    'name' => '验证开始时间'), 
                                'verify_end_date' => array(
                                    'null' => false, 
                                    'strtype' => 'datetime', 
                                    'format' => 'Ymd', 
                                    'name' => '验证结束时间'));
                        } else {
                            $rules = array(
                                'verify_begin_days' => array(
                                    'null' => false, 
                                    'strtype' => 'int', 
                                    'minval' => '0', 
                                    'name' => '验证开始天数'), 
                                'verify_end_days' => array(
                                    'null' => false, 
                                    'strtype' => 'int', 
                                    'minval' => '0', 
                                    'name' => '验证结束天数'));
                        }
                        $req = array_merge($req, 
                            $this->_verifyReqData($rules, FALSE, 'post', 
                                $rule_array["rule_data_" . $selected_id]));
                        
                        $req['verify_begin_date'] .= '000000';
                        $req['verify_end_date'] .= '235959';
                        
                        $verify_end_time = '';
                        if ($req['verify_time_type'] == '0') {
                            if ($req['verify_begin_date'] >
                                 $req['verify_end_date']) {
                                M()->rollback();
                                $this->error('验证结束时间不能大于验证开始时间');
                            }
                            $verify_end_time = $req['verify_end_date'];
                        }
                        if ($req['verify_time_type'] == '1') {
                            if ($req['verify_begin_days'] >
                                 $req['verify_end_days']) {
                                M()->rollback();
                                $this->error('验证结束时间不能大于验证开始时间');
                            }
                            
                            $verify_end_time = date('YmdHis', 
                                strtotime("+{$req['verify_end_days']} day"));
                        }
                    }                     

                    // 现金
                    else if ($req['commission_type'] == '3') {
                        $rules = array(
                            'return_money_type' => array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'name' => '返佣金额类型', 
                                'inarr' => array(
                                    '0', 
                                    '1')), 
                            'return_money_limit_flag' => array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'name' => '返佣总金额限制', 
                                'inarr' => array(
                                    '0', 
                                    '1')));
                        $req = array_merge($req, 
                            $this->_verifyReqData($rules, FALSE, 'post', 
                                $rule_array["rule_data_" . $selected_id]));
                        
                        $rules = array();
                        if ($req['return_money_type'] == '0') {
                            $rules['return_money'] = array(
                                'null' => false, 
                                'strtype' => 'number', 
                                'minval' => 0, 
                                'name' => '返佣金额');
                        } else {
                            $rules['return_rate'] = array(
                                'null' => false, 
                                'strtype' => 'number', 
                                'minval' => 0, 
                                'maxval' => 100, 
                                'name' => '返佣商品价格比率');
                        }
                        
                        if ($req['return_money_limit_flag'] == '1') {
                            $rules['return_money_limit'] = array(
                                'null' => false, 
                                'strtype' => 'number', 
                                'minval' => 0.01, 
                                'name' => '返佣总金额');
                        }
                        
                        $req = array_merge($req, 
                            $this->_verifyReqData($rules, FALSE, 'post', 
                                $rule_array["rule_data_" . $selected_id]));
                        
                        // 通宝斋参数校验
                        if ($this->tongbaozhai_flag && ($marketingInfo['batch_type'] ==
                             '26' || $marketingInfo['batch_type'] == '27')) {
                            
                            // $tbz_phone_arr = I('tbz_phone');
                            // $tbz_rate_arr = I('tbz_rate');
                            $tbz_phone_arr = $rule_array["rule_data_" .
                             $selected_id]['tbz_phone'];
                        $tbz_rate_arr = $rule_array["rule_data_" . $selected_id]['tbz_rate'];
                        if (count($tbz_phone_arr) != count($tbz_rate_arr)) {
                            M()->rollback();
                            $this->error('参数错误！');
                        }
                        
                        $cnt = count($tbz_phone_arr);
                        if ($cnt > 10) {
                            M()->rollback();
                            $this->error('返佣对象最多10个！');
                        }
                        
                        $return_rule = $req['return_money_type'] == '0' ? array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0, 
                            'name' => '返佣金额') : array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0, 
                            'maxval' => 100, 
                            'name' => '返佣商品价格比率');
                        $phone_rule = array(
                            'null' => false, 
                            'strtype' => 'mobile', 
                            'name' => '返佣金额');
                        
                        $tbz_phone_data = array();
                        for ($i = 0; $i < $cnt; $i ++) {
                            $error = '';
                            if (! check_str($tbz_phone_arr[$i], $phone_rule, 
                                $error)) {
                                M()->rollback();
                                $this->error(
                                    '手机号' . $tbz_phone_arr[$i] . '不正确！');
                            }
                            if (! check_str($tbz_rate_arr[$i], $return_rule, 
                                $error)) {
                                M()->rollback();
                                $this->error('返佣对象设置有误！');
                            }
                            
                            $tbz_phone_data[] = array(
                                'phone_no' => $tbz_phone_arr[$i], 
                                'return_money' => $tbz_rate_arr[$i]);
                        }
                    }
                }
                
                // 销售类的多乐互动才能设置返佣类型为订单价格比率
                if (! in_array($marketingInfo['batch_type'], 
                    array(
                        '26', 
                        '27'), true) && $req['commission_type'] == '3' &&
                     $req['return_money_type'] == '1') {
                    M()->rollback();
                    $this->error(
                        '多乐互动【' . $marketingInfo['name'] .
                             '】不能设置返佣金额类型为“订单价格比率”！');
                }
                
                // 体验客户不能创建返现金的返佣活动
                if ($this->node_type_name == 'staff' &&
                     $req['commission_type'] != '0') {
                    M()->rollback();
                    $this->error('演示账号无法创建现金返佣!');
                }
                
                // 校验账户余额是否足够开启全民多乐互动
                if ($req['commission_type'] != '0')
                    $this->_check_AccountPrice();
                    
                    // 判断该多乐互动是否已经发布至全民营销渠道
                if ($marketingInfo['return_commission_flag'] == 1) {
                    M()->rollback();
                    $this->error(
                        '多乐互动【' . $marketingInfo['name'] . '】已经参与全民营销！');
                }
                
                // 查询卡券
                if ($req['commission_type'] == '0') {
                    $map = array(
                        'node_id' => $this->nodeId, 
                        'goods_id' => $req['goods_id']);
                    $goodsInfo = M('tgoods_info')->where($map)->find();
                    if (! $goodsInfo) {
                        M()->rollback();
                        $this->error('卡券参数错误！');
                    }
                    if ($goodsInfo['storage_type'] != '0') {
                        M()->rollback();
                        $this->error(
                            '卡券【' . $goodsInfo['goods_name'] . '】有库存限制，不能参加全民营销！');
                    }
                    
                    if ($goodsInfo['end_time'] < $marketingInfo['end_time']) {
                        M()->rollback();
                        $this->error('卡券的有效截止时间不能小于多乐互动的截止时间');
                    }
                }
                
                // 设置全民营销活动名称
                $m_name = '';
                $m_name = $marketingInfo['name'];
                
                /*
                 * switch ($req['commission_type']) { case '0': $m_name =
                 * $marketingInfo['name'] . '-返' . $goodsInfo['goods_name'];
                 * break; case '3': $m_name = $marketingInfo['name'] . '-返现金';
                 * break; default: # code... break; }
                 */
                
                /* 开始写数据 */
                
                $error = '';
                
                // 打标tmarketinfo
                $marketingData = array(
                    'return_commission_flag' => 1);
                $flag = M('tmarketing_info')->where("id = '{$req['m_id']}'")->save(
                    $marketingData);
                if ($flag === false) {
                    M()->rollback();
                    throw new Exception("全民营销活动创建失败[00]！");
                }
                
                // 1. 创建全民营销活动
                $marketingData = array(
                    'node_id' => $this->nodeId, 
                    'batch_type' => $this->BATCH_TYPE, 
                    'name' => $m_name, 
                    'start_time' => $marketingInfo['start_time'], 
                    'end_time' => $marketingInfo['end_time'], 
                    'memo' => $req['commission_rule'], 
                    'add_time' => date('YmdHis'));
                // 'return_commission_flag' => 1
                
                $marketing_id = M('tmarketing_info')->add($marketingData);
                if ($marketing_id === false) {
                    M()->rollback();
                    throw new Exception("全民营销活动创建失败[01]！");
                }
                
                // 2. 创建卡券（tbatch_info）
                if ($req['commission_type'] == '0') {
                    $batchData = array(
                        'm_id' => $marketing_id, 
                        'goods_id' => $goodsInfo['goods_id'], 
                        'batch_no' => $goodsInfo['batch_no'], 
                        'batch_short_name' => $goodsInfo['goods_name'], 
                        'batch_name' => $goodsInfo['goods_name'], 
                        'node_id' => $this->nodeId, 
                        'user_id' => $this->user_id, 
                        'info_title' => $req['mms_title'], 
                        'use_rule' => $req['mms_info'], 
                        'batch_desc' => $m_name, 
                        'storage_num' => - 1, 
                        'validate_times' => $goodsInfo['validate_times'], 
                        'batch_img' => $goodsInfo['goods_image'], 
                        'batch_amt' => $goodsInfo['goods_amt'], 
                        'batch_discount' => $goodsInfo['goods_discount'], 
                        'verify_begin_type' => $req['verify_time_type'], 
                        'verify_end_type' => $req['verify_time_type'], 
                        'verify_begin_date' => $req['verify_time_type'] == '0' ? $req['verify_begin_date'] : $req['verify_begin_days'], 
                        'verify_end_date' => $req['verify_time_type'] == '0' ? $req['verify_end_date'] : $req['verify_end_days'], 
                        'add_time' => date('YmdHis'));
                    
                    $batch_id = M('tbatch_info')->add($batchData);
                    if ($batch_id === false) {
                        M()->rollback();
                        throw new Exception("全民营销活动创建失败[02]！");
                    }
                }
                node_log('创建全民营销活动-建立新的活动和卡券：' . $m_name, print_r($req, true));
                
                /* 成功结束1--2步 */
                
                /* 第三步已经执行 开始执行第四步 */
                
                // 4. 进全民营销活动控制表
                /* 把通用信息写入treturn_commission_info表 只需要写一次就了 */
                if ($key == 0) {
                    $returnData = array(
                        'm_id' => $marketing_id, 
                        'marketing_info_id' => $req['m_id'], 
                        
                        // 'commission_type' => $req['commission_type'],
                        'node_id' => $this->nodeId, 
                        
                        // 'phone_limit_flag' => $req['phone_limit_flag'],
                        // 'phone_limit_num' => $req['phone_limit_flag'] == '1'
                        // ? $req['phone_limit_num'] : '',
                        // 'send_notice_flag' => $req['commission_type'] != '0'
                        // ? $req['send_notice_flag'] : '0',
                        // 'send_notice_content' => $req['send_notice_flag'] ==
                        // '1' ? $req['send_notice_content'] : '',
                        'commission_name' => $m_name, 
                        'commission_rule' => $req['commission_rule'], 
                        'status' => '0',
                             /* 默认开始--业务需求 #9370 */
                            'label_id' => $batch_channel_id, 
                        'add_time' => date('YmdHis'), 
                        
                        // 'return_condition' => $req['return_condition'],
                        // 'return_condition_num' =>
                        // $req['return_condition_num'],
                        'share_note' => $share_note_str, 
                        'share_button_position' => $share_button_position, 
                        'share_pic' => $share_pic, 
                        'share_button_image' => I('share_button_image'), 
                        'custom_image' => I('custom_image'), 
                        'return_rule_data' => $return_rule_data);
                    $return_id = M('treturn_commission_info')->add($returnData);
                    if ($return_id === false) {
                        M()->rollback();
                        throw new Exception("全民营销活动创建失败[04]！");
                    }
                } else {
                    $returnData = array(
                        'm_id' => $marketing_id, 
                        'marketing_info_id' => $req['m_id'], 
                        'node_id' => $this->nodeId, 
                        'commission_name' => $m_name, 
                        'commission_rule' => $req['commission_rule'], 
                        'status' => '0',
                             /* 默认开始--业务需求 #9370 */
                            'label_id' => $batch_channel_id, 
                        'add_time' => date('YmdHis'));
                }
                
                /* 把规则配置信息循环写入treturn_commission_info_ext表 */
                
                /* 把规则基本信息继续补充下 */
                unset($returnData['return_rule_data']);
                /* 扩展表不需要保存这个 */
                
                // unset($returnData['return_rule_data']);
                unset($returnData['share_button_image']);
                unset($returnData['custom_image']);
                
                $return_condition_real = array();
                $return_condition_real[1] = 3;
                // 3-推广转发
                $return_condition_real[2] = 6;
                // 6-宣传引流
                $return_condition_real[3] = 1;
                // 1-购买
                $return_condition_real[4] = 2;
                // 2-参与活动（抽奖）
                $return_condition_real[5] = 4;
                // 4-APP下载
                
                $returnData = array_merge($returnData, 
                    array(
                        'return_commission_id' => $return_id, 
                        'commission_type' => $req['commission_type'], 
                        
                        // 'phone_limit_flag' => $req['phone_limit_flag'],
                        'phone_limit_flag' => $req['user_return_price_flag'], 
                        
                        // 'phone_limit_num' => $req['phone_limit_flag'] == '1'
                        // ? $req['phone_limit_num'] : '',
                        'phone_limit_num' => $req['user_return_price_flag'] ==
                             '1' ? $req['user_return_price_time'] : '', 
                            
                            // 'send_notice_flag' => $req['commission_type'] !=
                            // '0' ? $req['send_notice_flag'] : '0',
                            // 'send_notice_content' => $req['send_notice_flag']
                            // == '1' ? $req['send_notice_content'] : '',
                            // 'return_condition' => $req['return_condition'],
                            'return_condition' => $return_condition_real[$selected_id],
                         /* 返佣条件 1-购买 2-参与活动（抽奖）3-推广转发 4-APP下载 5-推荐注册 6-宣传引流 */
                        'return_condition_num' => $req['low_times_limit']));
                switch ($req['commission_type']) {
                    case '0':
                        $returnData = array_merge($returnData, 
                            array(
                                'goods_id' => $goodsInfo['goods_id'], 
                                'batch_no' => $goodsInfo['batch_no'], 
                                'b_id' => $batch_id, 
                                'return_goods_num' => 1, 
                                'send_notice_content' => $req['mms_info']));
                        break;
                    
                    case '3':
                        unset($returnData['goods_id']);
                        $returnData = array_merge($returnData, 
                            array(
                                'return_money_type' => $req['return_money_type'], 
                                'return_money' => $req['return_money_type'] ==
                                     '0' ? $req['return_money'] : $req['return_rate'], 
                                    'return_money_limit_flag' => $req['return_money_limit_flag'], 
                                    'return_money_limit' => $req['return_money_limit'], 
                                    'send_notice_flag' => $req['commission_type'] !=
                                     '0' ? $req['send_notice_flag'] : '0', 
                                    'send_notice_content' => $req['send_notice_flag'] ==
                                     '1' ? $req['send_notice_content'] : ''));
                        break;
                    
                    default:
                        // code...
                        break;
                }
                $return_ext_id = M('treturn_commission_info_ext')->add(
                    $returnData);
                if ($return_ext_id === false) {
                    M()->rollback();
                    throw new Exception("全民营销活动创建失败[05]！");
                }
                
                if ($this->tongbaozhai_flag && $tbz_phone_data && ($marketingInfo['batch_type'] ==
                     '26' || $marketingInfo['batch_type'] == '27')) {
                    foreach ($tbz_phone_data as $phone_data) {
                        $phone_data['marketing_info_id'] = $req['m_id'];
                        $phone_data['return_commission_id'] = $return_id;
                        $flag = M('tfb_tbz_commission_info')->add($phone_data);
                        if ($flag === false) {
                            M()->rollback();
                            throw new Exception("全民营销活动创建失败[06]！");
                        }
                    }
                }
            }
            /* end foreach */
            M()->commit();
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        node_log('创建全民营销活动：' . $m_name, print_r($req, true));
        $this->success('全民营销活动创建成功！');
    } else {
        
        /* 非提交数据处理 */
        $m_id = I('id', 0, 'intval');
        if (empty($m_id)) {
            $m_id = I('m_id', 0, 'intval');
        }
        if ($m_id > 0) {
            
            // 查询多乐互动
            $map = array(
                'node_id' => $this->nodeId, 
                'id' => $m_id);
            $marketingInfo = M('tmarketing_info')->where($map)->find();
            if (! $marketingInfo)
                $this->error('多乐互动参数错误！', 'javascript:history.go(-1);');
            
            if (! in_array($marketingInfo['batch_type'], 
                $this->allow_batch_type, true))
                $this->error('多乐互动【' . $marketingInfo['name'] . '】不能参加全民营销！', 
                    'javascript:history.go(-1);');
                
                // 非销售类的多乐互动，需要判断是否有设置奖品
            if (! in_array($marketingInfo['batch_type'], 
                array(
                    '26', 
                    '27'), true)) {
                $count = M('tcj_batch')->where("batch_id = '{$m_id}'")->count();
                if ($count == 0) {
                    $this->error(
                        '多乐互动【' . $marketingInfo['name'] . '】没有设置奖品信息，不能参加全民营销！', 
                        'javascript:history.go(-1);');
                }
            }
            
            // 查询该活动是否已经设置全民营销
            $map = array(
                'node_id' => $this->nodeId, 
                'marketing_info_id' => $m_id);
            $returnInfo = M('treturn_commission_info')->where($map)->find();
            if ($returnInfo) {
                header(
                    "Location:" . U('edit', 
                        array(
                            'id' => $returnInfo['m_id'])));
                exit();
            }
            
            if (strtotime($marketingInfo['end_time']) < time()) {
                $this->error(
                    '多乐互动【' . $marketingInfo['name'] . '】已经过期，不能参加全民营销！', 
                    'javascript:history.go(-1);');
            }
            
            $_batch_info = array(
                'batch_id' => $marketingInfo['id'], 
                'name' => $marketingInfo['name'], 
                'start_time' => $marketingInfo['start_time'], 
                'end_time' => $marketingInfo['end_time'], 
                'batch_type' => $marketingInfo['batch_type'], 
                'batch_type_name' => C(
                    'BATCH_TYPE_NAME.' . $marketingInfo['batch_type']));
            $this->assign('_batch_info', json_encode($_batch_info));
        }
        $this->assign('notice_tpl', $this->_get_notify_note());
        $sales = I('sales');
        if (empty($sales) && $marketingInfo['batch_type'] != 26 &&
             $marketingInfo['batch_type'] != 27) {
            $sales = 0;
        } else {
            $sales = 1;
        }
        $this->assign('sales', $sales);
        
        /* 定义属于本操作的子操作 */
        $get_hook_actions = I('hook_actions');
        $hook_actions = ! empty($get_hook_actions) ? array(
            $get_hook_actions) : array(
            'intro');
        $this->assign('hook_actions', $hook_actions);
        $nodename = M('tnode_info')->where("node_id='$this->nodeId'")->getField(
            'node_name');
        $this->assign('nodename', $nodename);
        $this->display();
    }
}

// 编辑
public function edit() {
    $batchId = I('id', '', 'mysql_real_escape_string');
    
    // 多乐互动信息
    $map = array(
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'id' => $batchId);
    // 'batch_type' => $this->BATCH_TYPE
    
    $marketingInfo = M('tmarketing_info')->where($map)->find();
    if (! $marketingInfo)
        $this->error('未找到该活动信息[01]！');
        
        // 返佣配置信息
    $map = array(
        'marketing_info_id' => $batchId, 
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"));
    $returnInfo = M('treturn_commission_info')->where($map)->find();
    if (! $returnInfo)
        $this->error('未找到该活动信息[02]！');
        
        /* 获取统计数据 */
    $daystat_count = 0;
    $is_have_data = false;
    $daystat = M('treturn_commission_daystat')->where(
        array(
            'return_commission_id' => $returnInfo['id']))->find();
    foreach ($daystat as $item) {
        $daystat_count += $item['transmit_count'];
        $daystat_count += $item['trans_count'];
        $daystat_count += $item['marketing_join_count'];
        $daystat_count += $item['flow_count'];
    }
    if ($daystat_count > 0) {
        $is_have_data = TRUE;
    }
    
    // 表单提交
    if ($this->isPost()) {
        
        /* 某些预先用到的参数先接收过来 */
        $share_pic = I('img_resp');
        $share_note = I('share_note');
        $share_button_position = I('share_button_position');
        $t_arr = array();
        $rule = array(
            'null' => true, 
            'maxlen_cn' => '50', 
            'name' => '分享描述');
        $i = 0;
        foreach ($share_note as & $note) {
            ++ $i;
            if (trim($note) == '') {
                unset($note);
                continue;
            }
            $error = '';
            if (! check_str($note, $rule, $error)) {
                $this->error('分享描述' . ($i) . $error);
            }
            $t_arr[] = $note;
        }
        $share_note_str = implode('--', $t_arr);
        
        /* 把条件规则转换为数组参数 */
        $is_selected = I("is_selected");
        $rule_array = array();
        
        // 开启事务
        M()->startTrans();
        $error = '';
        try {
            
            /* 把返佣条件预先接收并格式化 方便存入通用信息表 */
            $return_rule_data = array();
            foreach ($is_selected as $selected_id) {
                
                /* url格式转数组 */
                $return_rule_data["rule_data_" . $selected_id] = I(
                    "rule_data_" . $selected_id);
            }
            
            /* 数组序列化 */
            $return_rule_data = serialize($return_rule_data);
            $returnData = array(
                'commission_rule' => I('commission_rule'), 
                'share_note' => $share_note_str, 
                'share_button_position' => $share_button_position, 
                'share_pic' => $share_pic, 
                'share_button_image' => I('share_button_image'), 
                'custom_image' => I('custom_image'), 
                'return_rule_data' => $return_rule_data);
            $flag = M('treturn_commission_info')->where($map)->save($returnData);
            
            // var_dump($flag);
            if ($flag === false) {
                throw new Exception("全民营销活动更新失败[04]！");
            }
            
            foreach ($is_selected as $key => $selected_id) {
                
                /* url格式转数组 */
                $rule_array["rule_data_" . $selected_id] = $this->urlparamtoarray(
                    I("rule_data_" . $selected_id));
                $rule_array["rule_data_" . $selected_id]['return_condition'] = $selected_id;
                
                /* 参数校验 */
                $req = array();
                $rules = array(
                    'm_id' => array(
                        'null' => false, 
                        'name' => '多乐互动'), 
                    'commission_type' => array(
                        'null' => false, 
                        'name' => '返佣类型', 
                        'inarr' => array(
                            '0', 
                            '3')), 
                    'user_return_price_flag' => array(
                        'null' => false, 
                        'name' => '单用户最高返佣限制', 
                        'inarr' => array(
                            '0', 
                            '1')), 
                    'send_notice_flag' => array(
                        'null' => false, 
                        'name' => '是否发送通知短信', 
                        'inarr' => array(
                            '0', 
                            '1')), 
                    'commission_rule' => array(
                        'null' => false, 
                        'maxlen_cn' => '300', 
                        'name' => '返佣规则'), 
                    'return_condition' => array(
                        'null' => false, 
                        'name' => '返佣条件', 
                        'inarr' => array(
                            '1', 
                            '2', 
                            '3', 
                            '4')), 
                    'low_times_limit' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'name' => '返佣条件次数', 
                        'minval' => 1, 
                        'maxval' => 10));
                $req = $this->_verifyReqData($rules, FALSE, 'post', 
                    $rule_array["rule_data_" . $selected_id]);
                $rules = array();
                if ($req['user_return_price_flag'] == '1') {
                    $rules['user_return_price_time'] = array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => 1, 
                        'name' => '单用户最高返佣次数');
                }
                $req = array_merge($req, 
                    $this->_verifyReqData($rules, FALSE, 'post', 
                        $rule_array["rule_data_" . $selected_id]));
                
                // 非销售类的多乐互动，需要判断是否有设置奖品
                if (! in_array($marketingInfo['batch_type'], 
                    array(
                        '26', 
                        '27'), true)) {
                    $count = M('tcj_batch')->where(
                        "batch_id = '{$req['m_id']}'")->count();
                    if ($count == 0) {
                        M()->rollback();
                        $this->error(
                            '多乐互动【' . $marketingInfo['name'] .
                                 '】没有设置奖品信息，不能参加全民营销！');
                    }
                }
                
                /* 继续校验数据 */
                
                /* 继续校验数据 */
                
                // 卡券返佣
                if ($req['commission_type'] == '0') {
                    $rules = array(
                        'goods_id' => array(
                            'null' => false, 
                            'name' => '卡券'), 
                        'mms_title' => array(
                            'null' => false, 
                            'maxlen_cn' => '10', 
                            'name' => '彩信标题'), 
                        'mms_info' => array(
                            'null' => false, 
                            'maxlen_cn' => '100', 
                            'name' => '彩信内容'), 
                        'verify_time_type' => array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'name' => '验证时间类型', 
                            'inarr' => array(
                                '0', 
                                '1')));
                    $req = array_merge($req, 
                        $this->_verifyReqData($rules, FALSE, 'post', 
                            $rule_array["rule_data_" . $selected_id]));
                    
                    if ($req['verify_time_type'] == '0') {
                        $rules = array(
                            'verify_begin_date' => array(
                                'null' => false, 
                                'strtype' => 'datetime', 
                                'format' => 'Ymd', 
                                'name' => '验证开始时间'), 
                            'verify_end_date' => array(
                                'null' => false, 
                                'strtype' => 'datetime', 
                                'format' => 'Ymd', 
                                'name' => '验证结束时间'));
                    } else {
                        $rules = array(
                            'verify_begin_days' => array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'minval' => '0', 
                                'name' => '验证开始天数'), 
                            'verify_end_days' => array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'minval' => '0', 
                                'name' => '验证结束天数'));
                    }
                    $req = array_merge($req, 
                        $this->_verifyReqData($rules, FALSE, 'post', 
                            $rule_array["rule_data_" . $selected_id]));
                    
                    $req['verify_begin_date'] .= '000000';
                    $req['verify_end_date'] .= '235959';
                    
                    $verify_end_time = '';
                    if ($req['verify_time_type'] == '0') {
                        if ($req['verify_begin_date'] > $req['verify_end_date']) {
                            M()->rollback();
                            $this->error('验证结束时间不能大于验证开始时间');
                        }
                        $verify_end_time = $req['verify_end_date'];
                    }
                    if ($req['verify_time_type'] == '1') {
                        if ($req['verify_begin_days'] > $req['verify_end_days']) {
                            M()->rollback();
                            $this->error('验证结束时间不能大于验证开始时间');
                        }
                        
                        $verify_end_time = date('YmdHis', 
                            strtotime("+{$req['verify_end_days']} day"));
                    }
                }                 

                // 现金
                else if ($req['commission_type'] == '3') {
                    $rules = array(
                        'return_money_type' => array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'name' => '返佣金额类型', 
                            'inarr' => array(
                                '0', 
                                '1')), 
                        'return_money_limit_flag' => array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'name' => '返佣总金额限制', 
                            'inarr' => array(
                                '0', 
                                '1')));
                    $req = array_merge($req, 
                        $this->_verifyReqData($rules, FALSE, 'post', 
                            $rule_array["rule_data_" . $selected_id]));
                    
                    $rules = array();
                    if ($req['return_money_type'] == '0') {
                        $rules['return_money'] = array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0, 
                            'name' => '返佣金额');
                    } else {
                        $rules['return_rate'] = array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0, 
                            'maxval' => 100, 
                            'name' => '返佣商品价格比率');
                    }
                    
                    if ($req['return_money_limit_flag'] == '1') {
                        $rules['return_money_limit'] = array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0.01, 
                            'name' => '返佣总金额');
                    }
                    
                    $req = array_merge($req, 
                        $this->_verifyReqData($rules, FALSE, 'post', 
                            $rule_array["rule_data_" . $selected_id]));
                    
                    // 通宝斋参数校验
                    if ($this->tongbaozhai_flag && ($marketingInfo['batch_type'] ==
                         '26' || $marketingInfo['batch_type'] == '27')) {
                        
                        // $tbz_phone_arr = I('tbz_phone');
                        // $tbz_rate_arr = I('tbz_rate');
                        $tbz_phone_arr = $rule_array["rule_data_" . $selected_id]['tbz_phone'];
                        $tbz_rate_arr = $rule_array["rule_data_" . $selected_id]['tbz_rate'];
                        if (count($tbz_phone_arr) != count($tbz_rate_arr)) {
                            M()->rollback();
                            $this->error('参数错误！');
                        }
                        
                        $cnt = count($tbz_phone_arr);
                        if ($cnt > 10) {
                            M()->rollback();
                            $this->error('返佣对象最多10个！');
                        }
                        
                        $return_rule = $req['return_money_type'] == '0' ? array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0, 
                            'name' => '返佣金额') : array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => 0, 
                            'maxval' => 100, 
                            'name' => '返佣商品价格比率');
                        $phone_rule = array(
                            'null' => false, 
                            'strtype' => 'mobile', 
                            'name' => '返佣金额');
                        
                        $tbz_phone_data = array();
                        for ($i = 0; $i < $cnt; $i ++) {
                            $error = '';
                            if (! check_str($tbz_phone_arr[$i], $phone_rule, 
                                $error)) {
                                M()->rollback();
                                $this->error(
                                    '手机号' . $tbz_phone_arr[$i] . '不正确！');
                            }
                            if (! check_str($tbz_rate_arr[$i], $return_rule, 
                                $error)) {
                                M()->rollback();
                                $this->error('返佣对象设置有误！');
                            }
                            
                            $tbz_phone_data[] = array(
                                'phone_no' => $tbz_phone_arr[$i], 
                                'return_money' => $tbz_rate_arr[$i]);
                        }
                    }
                }
                
                // 销售类的多乐互动才能设置返佣类型为订单价格比率
                if (! in_array($marketingInfo['batch_type'], 
                    array(
                        '26', 
                        '27'), true) && $req['commission_type'] == '3' &&
                     $req['return_money_type'] == '1') {
                    M()->rollback();
                    $this->error(
                        '多乐互动【' . $marketingInfo['name'] .
                             '】不能设置返佣金额类型为“订单价格比率”！');
                }
                
                // 体验客户不能创建返现金的返佣活动
                if ($this->node_type_name == 'staff' &&
                     $req['commission_type'] != '0') {
                    M()->rollback();
                    $this->error('演示账号无法创建现金返佣!');
                }
                
                // 校验账户余额是否足够开启全民多乐互动
                if ($req['commission_type'] != '0')
                    $this->_check_AccountPrice();
                    
                    // 查询卡券
                if ($req['commission_type'] == '0') {
                    $map = array(
                        'node_id' => $this->nodeId, 
                        'goods_id' => $req['goods_id']);
                    $goodsInfo = M('tgoods_info')->where($map)->find();
                    if (! $goodsInfo) {
                        M()->rollback();
                        $this->error('卡券参数错误！');
                    }
                    if ($goodsInfo['storage_type'] != '0') {
                        M()->rollback();
                        $this->error(
                            '卡券【' . $goodsInfo['goods_name'] . '】有库存限制，不能参加全民营销！');
                    }
                    
                    if ($goodsInfo['end_time'] < $marketingInfo['end_time']) {
                        M()->rollback();
                        $this->error('卡券的有效截止时间不能小于多乐互动的截止时间');
                    }
                }
                
                // 设置全民营销活动名称
                $m_name = '';
                $m_name = $returnData['name'];
                
                $returnData = array(
                    'm_id' => $returnInfo['m_id'], 
                    'marketing_info_id' => $returnInfo['marketing_info_id'], 
                    'node_id' => $returnInfo['node_id'], 
                    'commission_name' => $returnInfo['commission_name'], 
                    'commission_rule' => $returnInfo['commission_rule'], 
                    'status' => '0',
                         /* 默认开始--业务需求 #9370 */
                        'label_id' => $returnInfo['label_id']);
                
                /* 把规则配置信息循环写入treturn_commission_info_ext表 */
                
                /* 把规则基本信息继续补充下 */
                
                $return_condition_real = array();
                $return_condition_real[1] = 3;
                // 3-推广转发
                $return_condition_real[2] = 6;
                // 6-宣传引流
                $return_condition_real[3] = 1;
                // 1-购买
                $return_condition_real[4] = 2;
                // 2-参与活动（抽奖）
                $return_condition_real[5] = 4;
                // 4-APP下载
                
                $returnData = array_merge($returnData, 
                    array(
                        'return_commission_id' => $return_id, 
                        'commission_type' => $req['commission_type'], 
                        'phone_limit_flag' => $req['user_return_price_flag'], 
                        'phone_limit_num' => $req['user_return_price_flag'] ==
                             '1' ? $req['user_return_price_time'] : '', 
                            'return_condition' => $return_condition_real[$selected_id],
                         /* 返佣条件 1-购买 2-参与活动（抽奖）3-推广转发 4-APP下载 5-推荐注册 6-宣传引流 */
                        'return_condition_num' => $req['low_times_limit']));
                switch ($req['commission_type']) {
                    case '0':
                        $returnData = array_merge($returnData, 
                            array(
                                'goods_id' => $goodsInfo['goods_id'], 
                                'batch_no' => $goodsInfo['batch_no'], 
                                'b_id' => $batch_id, 
                                'return_goods_num' => 1, 
                                'send_notice_content' => $req['mms_info']));
                        break;
                    
                    case '3':
                        unset($returnData['goods_id']);
                        $returnData = array_merge($returnData, 
                            array(
                                'return_money_type' => $req['return_money_type'], 
                                'return_money' => $req['return_money_type'] ==
                                     '0' ? $req['return_money'] : $req['return_rate'], 
                                    'return_money_limit_flag' => $req['return_money_limit_flag'], 
                                    'return_money_limit' => $req['return_money_limit'], 
                                    'send_notice_flag' => $req['commission_type'] !=
                                     '0' ? $req['send_notice_flag'] : '0', 
                                    'send_notice_content' => $req['send_notice_flag'] ==
                                     '1' ? $req['send_notice_content'] : ''));
                        break;
                    
                    default:
                        // code...
                        break;
                }
                
                /* 判断扩展表是否存在记录 */
                $returnInfo_ext = M('treturn_commission_info_ext')->where(
                    array(
                        'return_commission_id' => $batchId, 
                        'return_condition' => $return_condition_real[$selected_id]))->find();
                if (! $returnInfo_ext) {
                    $return_ext_id = M('treturn_commission_info_ext')->add(
                        $returnData);
                    if ($return_ext_id === false) {
                        M()->rollback();
                        throw new Exception("全民营销活动更新失败[05]！");
                    }
                } else {
                    $flag = M('treturn_commission_info_ext')->where(
                        "id = '{$returnInfo_ext['id']}'")->save($returnData);
                    if ($flag === false) {
                        throw new Exception("全民营销活动更新失败[06]！");
                    }
                }
            }
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        
        M()->commit();
        node_log('更新全民营销活动：' . $m_name, print_r($req, true));
        $this->success('全民营销活动更新成功！');
        exit();
    }
    
    // 返卡券信息
    if ($returnInfo['commission_type'] == '0') {
        $map = array(
            'id' => $returnInfo['b_id']);
        $batchInfo = M('tbatch_info')->where($map)->find();
        if (! $batchInfo)
            $this->error('未找到该活动信息[03]！');
    }
    
    if ($this->tongbaozhai_flag) {
        $tbz_phone_list = M('tfb_tbz_commission_info')->where(
            "return_commission_id = '{$returnInfo['id']}'")->select();
        $this->assign('tbz_phone_list', $tbz_phone_list);
    }
    
    $baseMarketingInfo = M('tmarketing_info')->find(
        $returnInfo['marketing_info_id']);
    
    /*
     * echo "<pre>"; var_dump($marketingInfo); var_dump($baseMarketingInfo);
     * var_dump($returnInfo);
     * var_dump(unserialize($returnInfo['return_rule_data'])); echo "</pre>";
     * die();
     */
    
    $this->assign('notice_tpl', 
        $this->_get_notify_note($returnInfo['commission_name']));
    $this->assign('batchInfo', $batchInfo);
    $this->assign('marketingInfo', $marketingInfo);
    $this->assign('baseMarketingInfo', $baseMarketingInfo);
    $this->assign('returnInfo', $returnInfo);
    $this->assign('return_rule_data', 
        unserialize($returnInfo['return_rule_data']));
    $this->assign('share_note', explode('--', $returnInfo['share_note']));
    $sales = I('sales');
    if (empty($sales) && $marketingInfo['batch_type'] != 26 &&
         $marketingInfo['batch_type'] != 27) {
        $sales = 0;
    } else {
        $sales = 1;
    }
    $this->assign('sales', $sales);
    
    /* 定义属于本操作的子操作 */
    $get_hook_actions = I('hook_actions');
    $hook_actions = ! empty($get_hook_actions) ? array(
        $get_hook_actions) : array(
        'intro');
    $this->assign('hook_actions', $hook_actions);
    $this->assign('is_have_data', $is_have_data);
    $this->display();
}

// 返佣发放
public function record() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $returnId = I('return_id', '', 'mysql_real_escape_string');
    $commission_type = I('commission_type', '', 'mysql_real_escape_string');
    $start_time = I('start_time', '', 'mysql_real_escape_string');
    $end_time = I('end_time', '', 'mysql_real_escape_string');
    $status = I('return_status', '', 'mysql_real_escape_string');
    
    $map = array(
        'node_id' => $this->nodeId);
    $marketingList = M('treturn_commission_info')->where($map)->getField(
        'id, commission_name', true);
    
    import('ORG.Util.Page');
    // 导入分页类
    $map = array(
        'a.node_id' => $this->nodeId, 
        '_string' => ' 1=1 ');
    
    if ($commission_type != '')
        $map['a.commission_type'] = $commission_type;
    
    if ($start_time != '')
        $map['_string'] .= " and a.return_add_time >= '{$start_time}000000'";
    
    if ($end_time != '')
        $map['_string'] .= " and a.return_add_time <= '{$end_time}235959'";
    
    if ($returnId != '' && isset($marketingList[$returnId]))
        $map['a.return_commission_id'] = $returnId;
    
    if ($status != '') {
        $map['a.return_status'] = $status;
    }

    function get_return_status_txt($val) {
        
        // 0-未发放 1-已发放 2+卡券 发放失败 2+现金 扣佣失败
        $r_status = $val['return_status'];
        $r_type = $val['commission_type'];
        if ($r_status == '0')
            return '未发放';
        else if ($r_status == '1')
            return '已发放';
        else {
            if ($r_type == '0')
                return '发放失败';
            if ($r_type == '3')
                return '扣佣失败';
        }
    }
    
    $mapcount = M()->table('treturn_commission_trace a')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    if (I('sub_type') == '2') {
        $map['_string'] .= " and a.return_commission_id = b.id";
        $map['c.node_id'] = array(
            'exp', 
            '=a.node_id');
        $map['c.phone_no'] = array(
            'exp', 
            '=a.phone_no');
        
        if ($mapcount == 0)
            $this->error('没有数据', U('record'));
        
        $fileName = '返佣发放记录.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "手机号码,返佣活动,返佣达成时间,返佣发放时间,返佣类型,返佣内容,返佣账号,状态\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->table(
                'treturn_commission_trace a, treturn_commission_info b, tmember_info c')
                ->where($map)
                ->field(
                'a.*, b.commission_name, c.alipay_acount, c.alipay_name')
                ->order('a.id desc')
                ->limit($page . ',' . $page_count)
                ->select();
            
            if (! $query)
                exit();
            foreach ($query as $v) {
                $line = '"' . $v['phone_no'] . '","' . $v['commission_name'] .
                     '","' . dateformat($v['return_add_time']) . '","' .
                     dateformat($v['return_charge_time']) . '","' .
                     $this->commission_type_arr[$v['commission_type']] . '","' .
                     $v['return_content'] . '","' . ($v['commission_type'] == '0' ? $v['return_acount'] : ($v['alipay_acount'] .
                     ',' . $v['alipay_name'])) . '","' .
                     get_return_status_txt($v) . "\"\r\n";
                $line = iconv('utf-8', 'gbk', $line);
                echo $line;
            }
        }
        exit();
    }
    
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    $map['_string'] .= " and a.return_commission_id = b.id";
    $list = M()->table('treturn_commission_trace a, treturn_commission_info b')
        ->where($map)
        ->field('a.*, b.commission_name')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    foreach ($list as & $info) {
        $info['return_status'] = get_return_status_txt($info);
    }
    $this->assign('list', $list);
    $this->assign('page', $Page->show());
    $this->assign('batch_list', $marketingList);
    $nodename = M('tnode_info')->where("node_id='$this->nodeId'")->getField(
        'node_name');
    $this->assign('nodename', $nodename);
    $this->display();
}

// 渠道分析
public function channel_chart() {
    $batchId = I('id', '', 'mysql_real_escape_string');
    
    // 多乐互动信息
    $map = array(
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'id' => $batchId);
    // 'batch_type' => $this->BATCH_TYPE
    
    $marketingInfo = M('tmarketing_info')->where($map)->find();
    if (! $marketingInfo)
        $this->error('未找到该活动信息[01]！');
        
        // 返佣配置信息
    $map = array(
        'marketing_info_id' => $batchId, 
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"));
    $returnInfo = M('treturn_commission_info')->where($map)->find();
    
    if (! $returnInfo)
        $this->error('未找到该活动信息[02]！');
    
    $map = array(
        'a.return_commission_id' => $returnInfo['id'], 
        'a.node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"));
    
    // 返佣产生渠道 0-新浪微博 1-QQ空间 2-腾讯微博 3-人人网 4-微信朋友圈 9-其他
    $return_channel_name = "case when return_channel ='0' then '新浪微博' when return_channel ='1' then 'QQ空间' when return_channel ='2' then '腾讯微博' when return_channel ='3' then '人人网' when return_channel ='4' then '微信朋友圈' else '其他' end";
    $field_arr = array(
        "{$return_channel_name} as return_channel", 
        'ifnull(sum(return_page_flow_count), 0) as return_page_flow_count', 
        'ifnull(sum(transmit_count), 0) as transmit_count', 
        'ifnull(sum(flow_count), 0) as flow_count', 
        'ifnull(sum(return_times), 0) as return_times');
    $list = M()->table('treturn_commission_daystat a')
        ->where($map)
        ->group($return_channel_name)
        ->getField(implode(',', $field_arr));
    
    $series_data = array();
    if ($list) {
        foreach ($list as $info) {
            $series_data['page_visit'][] = array(
                $info['return_channel'], 
                intval($info['return_page_flow_count']));
            $series_data['transmit_count'][] = array(
                $info['return_channel'], 
                intval($info['transmit_count']));
            $series_data['flow_count'][] = array(
                $info['return_channel'], 
                intval($info['flow_count']));
            $series_data['return_times'][] = array(
                $info['return_channel'], 
                intval($info['return_times']));
        }
    }
    
    // dump($list);
    // dump($series_data);
    // dump(json_encode($series_data['return_times']));
    $this->assign('list', $list);
    $this->assign('series_data', $series_data);
    $this->assign('active_data', $marketingInfo);
    
    /* 定义属于本操作的子操作 */
    $hook_acction = I('hook_actions');
    $hook_actions = array();
    $hook_actions[] = $hook_acction;
    $this->assign('hook_actions', $hook_actions);
    $this->display();
}

// 数据统计
public function statistics() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $begin_time = I('begin_time', '', 'mysql_real_escape_string');
    $end_time = I('end_time', '', 'mysql_real_escape_string');
    $day_type = I('day_type', '1', 'mysql_real_escape_string');
    $commission_type = I('commission_type', '', 'mysql_real_escape_string');
    if (empty($end_time)) {
        $end_time = date("Ymd");
    }
    if (empty($begin_time)) {
        $time = time() - 7 * 24 * 3600;
        $begin_time = date('Ymd', $time);
    }
    
    $map = array(
        'a.node_id' => $this->nodeId, 
        '_string' => " a.return_channel is not null and a.return_channel != ''", 
        'a.return_channel' => array(
            'in', 
            array_keys($this->return_channel_arr)));
    if ($begin_time != '')
        $map['_string'] .= " and a.stat_date >= '{$begin_time}' ";
    if ($end_time != '')
        $map['_string'] .= " and a.stat_date <= '{$end_time}' ";
    if ($commission_type != '')
        $map['a.commission_type'] = $commission_type;
    
    if ($day_type == '1') {
        $day_group = "%Y-%m-%d";
    } elseif ($day_type == '2') {
        $day_group = '%Y-%v';
    } elseif ($day_type == '3') {
        $day_group = "%Y-%m";
    }
    
    $list = M()->table("treturn_commission_daystat a")->where($map)
        ->field(
        "DATE_FORMAT(a.stat_date, '{$day_group}') as t1, a.return_channel, sum(a.transmit_count) as transmit_count, sum(a.flow_count) as flow_count, sum(a.trans_count) as trans_count")
        ->group("DATE_FORMAT(a.stat_date, '{$day_group}'), a.return_channel")
        ->order("DATE_FORMAT(a.stat_date, '{$day_group}') asc")
        ->select();
    
    // echo M()->_sql();
    // $categories = array();
    $series = array();
    foreach ($this->return_channel_arr as $k => $v) {
        $series[$k . '_transmit_count'] = array(
            'name' => $v . '转发数', 
            'data' => array());
        $series[$k . '_flow_count'] = array(
            'name' => $v . '流量数', 
            'data' => array());
        $series[$k . '_trans_count'] = array(
            'name' => $v . '成交数', 
            'data' => array());
    }
    if ($list) {
        foreach ($list as $info) {
            $time = strtotime(dateformat($info['t1'], 'Ymd235959')) * 1000;
            $series[$info['return_channel'] . '_transmit_count']['data'][] = array(
                $time, 
                intval($info['transmit_count']));
            $series[$info['return_channel'] . '_flow_count']['data'][] = array(
                $time, 
                intval($info['flow_count']));
            $series[$info['return_channel'] . '_trans_count']['data'][] = array(
                $time, 
                intval($info['trans_count']));
        }
    }
    $series = array_values($series);
    foreach ($series as $k => $info) {
        if (! $info['data'])
            unset($series[$k]);
        continue;
    }
    $series = array_values($series);
    $this->assign('series', json_encode($series));
    $this->assign('list', $list);
    $this->assign('day_type', $day_type);
    $this->assign('begin_time', $begin_time);
    $this->assign('end_time', $end_time);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'statistics');
    $this->assign('hook_actions', $hook_actions);
    $this->display();
}

// 数据统计-成本统计
public function statistics_cost() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $begin_time = I('begin_time', '', 'mysql_real_escape_string');
    $end_time = I('end_time', '', 'mysql_real_escape_string');
    
    if (empty($end_time)) {
        $end_time = date("Ymd");
    }
    if (empty($begin_time)) {
        $time = time() - 7 * 24 * 3600;
        $begin_time = date('Ymd', $time);
    }
    
    $_string = "t.node_id = '{$this->nodeId}'";
    if ($begin_time != '')
        $_string .= " and t.stat_date >= '{$begin_time}' ";
    if ($end_time != '')
        $_string .= " and t.stat_date <= '{$end_time}' ";
    
    $sql = "SELECT 
            g.goods_name item, 
            SUM(t.return_times) return_times, 
            SUM(t.return_amount) return_num 
        FROM treturn_commission_daystat t 
            LEFT JOIN tgoods_info g ON g.goods_id = t.goods_id
        WHERE {$_string} and t.commission_type = '0' GROUP BY t.goods_id
        UNION ALL
        SELECT 
            CASE 
                WHEN t.commission_type = '1' 
                    THEN 'Q币' 
                WHEN t.commission_type = '2' 
                    THEN '话费' 
                ELSE '现金' END AS item, 
            SUM(t.return_times) return_times, 
            SUM(t.return_amount) return_num 
        FROM treturn_commission_daystat t 
        WHERE {$_string} and t.commission_type != '0' GROUP BY t.commission_type";
    
    $list = M()->table("($sql) a")->select();
    $series_data = array();
    foreach ($list as $info) {
        $series_data[] = array(
            $info['item'], 
            intval($info['return_num']));
    }
    $this->assign('list', $list);
    $this->assign('series_data', $series_data);
    $this->assign('begin_time', $begin_time);
    $this->assign('end_time', $end_time);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'statistics');
    $this->assign('hook_actions', $hook_actions);
    $this->display();
}

// 数据统计-活动效果对比
public function statistics_batch() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $map = array(
        'a.node_id' => $this->nodeId);
    import('ORG.Util.Page');
    // 导入分页类
    $mapcount = M()->table('treturn_commission_info a')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    $show = $Page->show();
    // 分页显示输出
    
    $list = M()->table('treturn_commission_info a')
        ->join('tmarketing_info b on b.id = a.marketing_info_id ')
        ->join(
        '(SELECT 
                        return_commission_id
                        , SUM(transmit_count) AS transmit_count
                        , SUM(flow_count) AS flow_count
                        , SUM(trans_count) AS trans_count 
                    FROM treturn_commission_daystat 
                    GROUP BY return_commission_id) c 
                    ON c.return_commission_id = a.id ')
        ->where($map)
        ->field(
        'a.commission_name , b.batch_type , IFNULL(c.transmit_count, 0) as transmit_count  , IFNULL(c.flow_count, 0) as flow_count  , IFNULL(c.trans_count, 0) as trans_count  ')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    $this->assign('page', $show);
    // 赋值分页输出
    $this->assign('list', $list);
    // 赋值分页输出
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'statistics');
    $this->assign('hook_actions', $hook_actions);
    $this->display();
}

// 点击数报表
public function statistics_click() {
    $id = I('id');
    if (empty($id))
        $this->error("参数错误！");
    
    $_get = I('get.');
    
    // 查询
    $_get['begin_date'] = $begin_date = I('begin_date', 
        dateformat("-30 days", 'Ymd'));
    $_get['end_date'] = $end_date = I('end_date', dateformat("0 days", 'Ymd'));
    
    // 多乐互动信息
    $map = array(
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'id' => $id, 
        'batch_type' => $this->BATCH_TYPE);
    $marketingInfo = M('tmarketing_info')->where($map)->find();
    if (! $marketingInfo)
        $this->error('未找到该活动信息[01]！');
        
        // 返佣配置信息
    $map = array(
        'm_id' => $id, 
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"));
    $returnInfo = M('treturn_commission_info')->where($map)->find();
    if (! $returnInfo)
        $this->error('未找到该活动信息[02]！');
    
    $map = array(
        'a.return_commission_id' => $returnInfo['id'], 
        'a.node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"));
    $map['stat_date'] = array();
    if ($begin_date != '') {
        $map['stat_date'][] = array(
            'EGT', 
            $begin_date);
    }
    if ($end_date != '') {
        $map['stat_date'][] = array(
            'ELT', 
            $end_date);
    }
    
    $field_arr = array(
        'stat_date', 
        'ifnull(sum(return_page_flow_count), 0) as return_page_flow_count', 
        'ifnull(sum(transmit_count), 0) as transmit_count');
    $list = M()->table('treturn_commission_daystat a')
        ->where($map)
        ->group('stat_date')
        ->getField(implode(',', $field_arr));
    
    // 计算出JS值
    $jsChartDataClick = array();
    $jsChartDataSend = array();
    foreach ($list as $v) {
        $jsChartDataClick[] = array(
            $v['stat_date'], 
            $v['return_page_flow_count'] * 1);
        $jsChartDataSend[] = array(
            $v['stat_date'], 
            $v['transmit_count'] * 1);
    }
    
    $this->assign('_get', $_get);
    $this->assign('jsChartDataClick', json_encode($jsChartDataClick));
    $this->assign('jsChartDataSend', json_encode($jsChartDataSend));
    $this->assign('batch_type', $batch_type);
    $this->assign('list', $list);
    $this->assign('batch_name', $batch_name);
    $this->assign('returnInfo', $returnInfo);
    
    $this->display();
}

// 状态修改
public function editStatus() {
    $batchId = I('post.batch_id', null, 'intval');
    $status = I('post.status', null, 'intval');
    if (is_null($batchId) || is_null($status)) {
        $this->error('缺少必要参数');
    }
    $result = M('tmarketing_info')->where("id='{$batchId}'")->find();
    if (! $result) {
        $this->error('未找到该活动0');
    }
    
    $returnInfo = M('treturn_commission_info')->where(
        "marketing_info_id = '{$batchId}'")->find();
    if (! $returnInfo)
        $this->error('未找到该活动1');
    
    if ($returnInfo['status'] == '3')
        $this->error('活动已过期！');
    
    if ($returnInfo['status'] == '0' && $status == '0')
        $this->success('更新成功');
    
    if (($returnInfo['status'] == '1' || $returnInfo['status'] == '2') &&
         $status == '1')
        $this->success('更新成功');
    
    if ($returnInfo['commission_type'] != '0' && $status == '0')
        $this->_check_AccountPrice(2);
    
    if ($status == '1') {
        $data = array(
            'status' => '1');
        
        $data2 = array(
            'status' => '2');
    } else {
        $data = array(
            'status' => '0');
        
        $data2 = array(
            'status' => '1');
    }
    M()->startTrans();
    $result = M('treturn_commission_info')->where(
        "marketing_info_id = '{$batchId}'")->save($data);
    if ($result === false) {
        M()->rollback();
        $this->error('更新失败');
    }
    $result = M('tbatch_info')->where("id = '{$batchId}'")->save($data2);
    
    if ($result !== false) {
        M()->commit();
        node_log('全民营销活动状态更改|活动ID：' . $batchId);
        $this->success('更新成功');
    } else {
        M()->rollback();
        $this->error('更新失败');
    }
}

// 全民营销活动列表下载
// 格式：活动名称,添加时间,活动开始时间,活动结束时间,状态,推广页面访问量,推广转发人数
public function list_export() {
    $map = array(
        'node_id' => $this->nodeId);
    $nodeInfo = M('tnode_info')->where($map)->find();
    if (! $nodeInfo)
        $this->error('未查询到记录！', U('index'));
    
    $fileName = $nodeInfo['node_short_name'] . '-' . date('Y-m-d') .
         '-全民营销活动列表.csv';
    $fileName = iconv('utf-8', 'gb2312', $fileName);
    
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $start_num = 0;
    $page_count = 5000;
    $cj_title = "多乐互动,返佣类型,返佣内容,添加时间,活动开始时间,活动结束时间,状态,推广页面访问量,推广转发人数\r\n";
    $cj_title = iconv('utf-8', 'gbk', $cj_title);
    echo $cj_title;
    for ($j = 1; $j < 100; $j ++) {
        $page = ($j - 1) * $page_count;
        $sql = "SELECT b.name, a.commission_type, a.return_money_type, a.return_money, c.goods_name, 
            a.add_time, b.start_time, b.end_time, a.status, d.click_count, d.fb_dzn
            FROM tmarketing_info b, tmarketing_info d, treturn_commission_info a
                LEFT JOIN tgoods_info c ON c.goods_id = a.goods_id
            WHERE a.marketing_info_id = b.id AND d.id = a.m_id and a.node_id = '{$this->nodeId}'
            ORDER BY a.id desc
            LIMIT {$page},{$page_count}";
        
        $query = M()->query($sql);
        if (! $query)
            exit();
        
        foreach ($query as $v) {
            $return_content = '';
            if ($v['commission_type'] == '0') {
                $return_content = $v['goods_name'];
            } else {
                if ($v['return_money_type'] == '0') {
                    $return_content = '现金' . $v['return_money'];
                } else {
                    $return_content = '订单交易金额的' . $v['return_money'] . '%';
                }
            }
            $line = $v['name'] . ',' .
                 $this->commission_type_arr[$v['commission_type']] . ',' .
                 $return_content . ',' . dateformat($v['add_time']) . ',' .
                 dateformat($v['start_time']) . ',' . dateformat($v['end_time']) .
                 ',' . ($v['status'] == '0' ? '正常' : '停用') . ',' .
                 $v['click_count'] . ',' . $v['fb_dzn'] . "\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
    }
}

// 推广数据
public function export_generalize() {
    $batchId = I('batch_id', null);
    if (is_null($batchId))
        $this->error('缺少参数', U('index'));
    
    $map = array(
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'id' => $batchId);
    $nodeInfo = M('tmarketing_info')->where($map)->find();
    if (! $nodeInfo)
        $this->error('未查询到记录[1]！', U('index'));
    
    $map = array(
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'marketing_info_id' => $batchId);
    $returnInfo = M('treturn_commission_info')->where($map)->find();
    if (! $returnInfo)
        $this->error('未查询到记录[2]！', U('index'));
    
    $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-推广数据.csv';
    $fileName = iconv('utf-8', 'gb2312', $fileName);
    
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $start_num = 0;
    $page_count = 5000;
    if ((int) $returnInfo['return_commission_class'] == 2) {
        $cj_title = "分享人手机号,转发次数,引流次数,购买次数\r\n";
    } else {
        $cj_title = "分享人手机号,转发次数,引流次数,参与次数\r\n";
    }
    
    $cj_title = iconv('utf-8', 'gbk', $cj_title);
    echo $cj_title;
    for ($j = 1; $j < 100; $j ++) {
        $page = ($j - 1) * $page_count;
        $sql = "SELECT 
                      t.phone_no
                      , SUM(t.transmit_count) t_count
                      , SUM(t.flow_count) f_count
                      , SUM(t.marketing_join_count) j_count
                      , SUM(t.trans_count) b_count
                    FROM
                      treturn_commission_daystat t 
                    WHERE t.return_commission_id = '{$returnInfo['id']}'
                    GROUP BY t.phone_no 
                    ORDER by t.id DESC LIMIT {$page},{$page_count}";
        $query = M()->query($sql);
        if (! $query)
            exit();
        foreach ($query as $v) {
            if ((int) $returnInfo['return_commission_class'] == 2) {
                $line = $v['phone_no'] . ',' . $v['t_count'] . ',' .
                     $v['f_count'] . ',' . $v['b_count'] . "\r\n";
            } else {
                $line = $v['phone_no'] . ',' . $v['t_count'] . ',' .
                     $v['f_count'] . ',' . $v['j_count'] . "\r\n";
            }
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
    }
}

// 中奖名单下载
public function export() {
    $batchId = I('batch_id', null);
    if (is_null($batchId))
        $this->error('缺少参数', U('index'));
    
    $edit_wh = array(
        'node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'id' => $batchId);
    $nodeInfo = M('tmarketing_info')->where($edit_wh)->find();
    if (! $nodeInfo)
        $this->error('未查询到记录！', U('index'));
    
    $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-分拥记录.csv';
    $fileName = iconv('utf-8', 'gb2312', $fileName);
    
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $start_num = 0;
    $page_count = 5000;
    $cj_title = "分享人手机号,返佣活动名称,被分享者手机号,时间,返佣类型,返佣内容,发放状态\r\n";
    $cj_title = iconv('utf-8', 'gbk', $cj_title);
    echo $cj_title;
    for ($j = 1; $j < 100; $j ++) {
        $page = ($j - 1) * $page_count;
        $query = M()->query(
            "SELECT a.from_phone_no , b.commission_name , a.phone_no , a.return_add_time ,
                                  a.commission_type
                                  , a.return_content
                                  , a.return_status
                                FROM
                                  treturn_commission_trace a
                                  , treturn_commission_info b 
                                WHERE a.return_commission_id = b.id and b.marketing_info_id = '{$batchId}'
                                    ORDER by a.id DESC LIMIT {$page},{$page_count}");
        if (! $query)
            exit();
        foreach ($query as $v) {
            $line = $v['from_phone_no'] . ',' . $v['commission_name'] . ',' .
                 $v['phone_no'] . ',' . dateformat($v['return_add_time']) . ',' .
                 $this->commission_type_arr[$v['commission_type']] . ',' .
                 $v['return_content'] . ',' .
                 $this->return_status_arr[$v['return_status']] . "\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
    }
}

// 校验多乐互动是否能发布到全民营销（前端ajax调用）
public function verify_marketinginfo() {
    $id = I('id', 0, 'intval');
    if ($id <= 0)
        $this->error('参数错误！请重新选择！');
        
        // 查询多乐互动
    $map = array(
        'node_id' => $this->nodeId, 
        'id' => $id);
    $marketingInfo = M('tmarketing_info')->where($map)->find();
    if (! $marketingInfo)
        $this->error('多乐互动参数错误！');
    
    if (! in_array($marketingInfo['batch_type'], $this->allow_batch_type, true))
        $this->error('多乐互动【' . $marketingInfo['name'] . '】不能参加全民营销！');
        
        // 非销售类的多乐互动，需要判断是否有设置奖品
    if (! in_array($marketingInfo['batch_type'], 
        array(
            '26', 
            '27'), true)) {
        $count = M('tcj_batch')->where("batch_id = '{$id}'")->count();
        if ($count == 0) {
            $this->error(
                '多乐互动【' . $marketingInfo['name'] . '】没有设置奖品信息，不能参加全民营销！');
        }
    }
    
    // 判断该多乐互动是否已经发布至全民营销渠道
    if ($marketingInfo['return_commission_flag'] == 1)
        $this->error('多乐互动【' . $marketingInfo['name'] . '】已经参与全民营销！');
    
    $this->success();
}

// 是否有全民营销的权限
public function _hasPower($return = false) {
    $flag = false;
    if ($return)
        return $flag;
    if (! $flag)
        $this->error('您还没有开通全民营销！', U('index'));
}

// 校验商户账户余额是否足够开设现金返佣活动
public function _check_AccountPrice($type = '1') {
    $this->_hasPower();
    
    // 后付费用户无需校验余额
    /*
     * if($this->payType == '0') return true;
     */
    $param_name = $type == '1' ? self::SYS_PARAM_QMYX_REMAIN : self::SYS_PARAM_QMYX_START_REMAIN;
    
    // 获取现金返佣活动的最低余额限制
    $qmyx_min_remain = M('tsystem_param')->where("param_name = '{$param_name}'")->getField(
        'param_value');
    if ($qmyx_min_remain == 0)
        return true;
    
    $accountInfo = $this->getAccountInfo();
    if (! $accountInfo)
        $this->error('获取商户余额失败！请重试');
    
    if ($accountInfo['AccountPrice'] < $qmyx_min_remain)
        $this->error('您的账户余额不足' . $qmyx_min_remain . '元，无法开展现金返佣活动！');
}

// 获取全民营销渠道id
public function _get_default_channel_id() {
    static $channel_id = null;
    if ($channel_id === null) {
        $map = array(
            'node_id' => $this->nodeId, 
            'type' => '5', 
            'sns_type' => '53');
        $channelInfo = M('tchannel')->where($map)->find();
        
        // 若没有全民营销渠道id，则创建
        if (! $channelInfo) {
            $data = array(
                'node_id' => $this->nodeId, 
                'name' => '全民营销渠道', 
                'type' => '5', 
                'sns_type' => '53', 
                'status' => 1, 
                'add_time' => date('YmdHis'), 
                'send_count' => 0);
            
            $channel_id = M('tchannel')->add($data);
            if ($channel_id === false)
                $this->error('全民营销渠道创建失败！');
        } else
            $channel_id = $channelInfo['id'];
    }
    return $channel_id;
}

public function _handleCheckAuth($msg) {
    if (! $this->isAjax()) {
        $this->error($msg);
    }
    $url = '';
    $this->assign('ret_code', 1);
    $this->assign('ret_msg', $msg);
    $this->assign('url', $url);
    $this->show(
        '<script>
            art.dialog("' . $msg . '");
            </script>');
    exit();
}

/* add by john 2015-03-10 */
public function marketing_activities() {
    if (! $this->_hasPower(true))
        $this->intro();
        
        /* 获取活动列表 */
        
    // 非商品销售的多乐互动必须要有奖品才能做全民营销的活动
    $other_field = '';
    $map['_string'] = '(select count(*) from tcj_batch b where b.batch_id = a.id)>0';
    $map['a.node_id'] = $this->nodeId;
    $map['a.batch_type'] = array(
        'in', 
        '2,3,10,20');
    $map['a.end_time'] = array(
        'gt', 
        date('YmdHis'));
    $map['a.return_commission_flag'] = 0;
    $data = I('request.');
    if ($data['key'] != '') {
        $map['a.name'] = array(
            'like', 
            '%' . $data['key'] . '%');
    }
    if ($data['batch_type'] != '') {
        $map['a.batch_type'] = $data['batch_type'];
    }
    
    import('ORG.Util.Page');
    // 导入分页类
    foreach ($data as $key => $val) {
        $Page->parameter .= "&$key=" . urlencode($val) . '&';
    }
    
    // $model = M('tmarketing_info');
    $mapcount = M()->table('tmarketing_info a')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    $show = $Page->show();
    // 分页显示输出
    
    $queryData = M()->table('tmarketing_info a')
        ->field(
        "a.id batch_id, a.name, a.start_time, a.end_time" . $other_field)
        ->where($map)
        ->order("a.id desc")
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    
    /* 获取活动列表end */
    $active_list = $queryData;
    if (empty($active_list)) {
        $has_commission = false;
    } else {
        $has_commission = true;
    }
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_activities');
    $this->assign('hook_actions', $hook_actions);
    $this->assign('query_list', $active_list);
    $this->assign('has_commission', $has_commission);
    $this->assign('page', $show);
    // 赋值分页输出
    $this->assign('nodeList', $this->getNodeTree());
    
    $this->display();
    // 输出模板
}

public function marketing_activities_running() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $map = array(
        'a.node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'a.id' => array(
            'exp', 
            '=b.marketing_info_id'), 
        'a.batch_type' => array(
            'in', 
            $this->BATCH_TYPE_1));
    
    $data = I('request.');
    if (! empty($data['node_id']))
        $map['a.node_id '] = $data['node_id'];
    if ($data['key'] != '') {
        $map['a.name'] = array(
            'like', 
            '%' . $data['key'] . '%');
    }
    if ($data['status'] != '') {
        $map['b.status'] = $data['status'] == '1' ? '0' : array(
            'neq', 
            '0');
    }
    
    // $map['c.end_time'] = array('egt', date(YmdHis));
    
    import('ORG.Util.Page');
    // 导入分页类
    // $model = M('tmarketing_info');
    $mapcount = M()->table('tmarketing_info a, treturn_commission_info b')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    foreach ($data as $key => $val) {
        $Page->parameter .= "&$key=" . urlencode($val) . '&';
    }
    
    $map['b.marketing_info_id'] = array(
        'exp', 
        '=c.id');
    
    $show = $Page->show();
    // 分页显示输出
    $query_transmit_count = "(SELECT ifnull(sum(transmit_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as transmit_count";
    $query_return_page_flow_count = "(SELECT ifnull(sum(return_page_flow_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as return_page_flow_count";
    
    $list = M()->table(
        'tmarketing_info a, tmarketing_info c, treturn_commission_info b')
        ->join('tgoods_info g on g.goods_id = b.goods_id')
        ->where($map)
        ->field(
        'a.*, b.id as b_id,' . $query_transmit_count . ',' .
             $query_return_page_flow_count .
             ',b.status as f_status, g.goods_name, c.name as ac_name, b.commission_type, b.return_money_type, b.return_money,b.return_rule_data as return_rule_data, c.batch_type, b.marketing_info_id, c.start_time as ac_start_time, c.end_time as ac_end_time')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    if (empty($list)) {
        $has_commission = false;
    } else {
        $has_commission = true;
    }
    
    $arr_ = C('CHANNEL_TYPE');
    $this->assign('arr_', $arr_);
    $this->assign('query_list', $list);
    $this->assign('page', $show);
    // 赋值分页输出
    $this->assign('nodeList', $this->getNodeTree());
    $this->assign('has_commission', $has_commission);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_activities');
    $this->assign('hook_actions', $hook_actions);
    
    $this->display();
}

public function export_marketing_activities_running() {
    $map = array(
        'a.node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'a.id' => array(
            'exp', 
            '=b.marketing_info_id'), 
        'a.batch_type' => array(
            'in', 
            $this->BATCH_TYPE_1));
    $data = I('request.');
    if (! empty($data['node_id']))
        $map['a.node_id '] = $data['node_id'];
    if ($data['key'] != '') {
        $map['a.name'] = array(
            'like', 
            '%' . $data['key'] . '%');
    }
    if ($data['status'] != '') {
        $map['b.status'] = $data['status'] == '1' ? '0' : array(
            'neq', 
            '0');
    }
    if ($data['start_time'] != '' && $data['end_time'] != '') {
        $map['a.add_time'] = array(
            'BETWEEN', 
            array(
                $data['start_time'] . '000000', 
                $data['end_time'] . '235959'));
    }
    $map['b.marketing_info_id'] = array(
        'exp', 
        '=c.id');
    $query_transmit_count = "(SELECT ifnull(sum(transmit_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as transmit_count";
    $query_return_page_flow_count = "(SELECT ifnull(sum(return_page_flow_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as return_page_flow_count";
    
    $list = M()->table(
        'tmarketing_info a, tmarketing_info c, treturn_commission_info b')
        ->join('tgoods_info g on g.goods_id = b.goods_id')
        ->where($map)
        ->field(
        'a.*, b.status as f_status,' . $query_transmit_count . ',' .
             $query_return_page_flow_count .
             ',g.goods_name, c.name as ac_name, b.commission_type, b.return_money_type, b.return_money,b.return_rule_data as return_rule_data, c.batch_type, b.marketing_info_id, c.start_time as ac_start_time, c.end_time as ac_end_time')
        ->order('a.id desc')
        ->select();
    
    $fileName = date('Y-m-d') . '-进行中的多乐互动.csv';
    $fileName = iconv('utf-8', 'gb2312', $fileName);
    
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $start_num = 0;
    $page_count = 5000;
    $cj_title = "活动名称,活动时间,转发奖励,引流奖励,参与奖励,分享页访问量,转发人数\r\n";
    $cj_title = iconv('utf-8', 'gbk', $cj_title);
    echo $cj_title;
    foreach ($list as $v) {
        $line = $v['ac_name'] . ',' .
             date('Y-m-d', strtotime($v['ac_start_time'])) . '至' .
             date('Y-m-d', strtotime($v['ac_end_time'])) . ',' .
             $this->get_return_content($v['return_rule_data'], 1) . ',' .
             $this->get_return_content($v['return_rule_data'], 2) . ',' .
             $this->get_return_content($v['return_rule_data'], 4) . ',' .
             $v['return_page_flow_count'] . ',' . $v['transmit_count'] . "\r\n";
        $line = iconv('utf-8', 'gbk', $line);
        echo $line;
    }
}

public function export_marketing_ecommerce_running() {
    $map = array(
        'a.node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'a.id' => array(
            'exp', 
            '=b.marketing_info_id'), 
        'a.batch_type' => array(
            'in', 
            $this->BATCH_TYPE_2));
    $data = I('request.');
    if (! empty($data['node_id']))
        $map['a.node_id '] = $data['node_id'];
    if ($data['key'] != '') {
        $map['a.name'] = array(
            'like', 
            '%' . $data['key'] . '%');
    }
    if ($data['status'] != '') {
        $map['b.status'] = $data['status'] == '1' ? '0' : array(
            'neq', 
            '0');
    }
    if ($data['start_time'] != '' && $data['end_time'] != '') {
        $map['a.add_time'] = array(
            'BETWEEN', 
            array(
                $data['start_time'] . '000000', 
                $data['end_time'] . '235959'));
    }
    $map['b.marketing_info_id'] = array(
        'exp', 
        '=c.id');
    $query_transmit_count = "(SELECT ifnull(sum(transmit_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as transmit_count";
    $query_return_page_flow_count = "(SELECT ifnull(sum(return_page_flow_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as return_page_flow_count";
    
    $list = M()->table(
        'tmarketing_info a, tmarketing_info c, treturn_commission_info b')
        ->join('tgoods_info g on g.goods_id = b.goods_id')
        ->where($map)
        ->field(
        'a.*, b.status as f_status,' . $query_transmit_count . ',' .
             $query_return_page_flow_count .
             ',g.goods_name, c.name as ac_name, b.commission_type, b.return_money_type, b.return_money,b.return_rule_data as return_rule_data, c.batch_type, b.marketing_info_id, c.start_time as ac_start_time, c.end_time as ac_end_time')
        ->order('a.id desc')
        ->select();
    
    $fileName = date('Y-m-d') . '-进行中的爆款销售.csv';
    $fileName = iconv('utf-8', 'gb2312', $fileName);
    
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $start_num = 0;
    $page_count = 5000;
    $cj_title = "活动名称,活动时间,转发奖励,引流奖励,参与奖励,分享页访问量,转发人数\r\n";
    $cj_title = iconv('utf-8', 'gbk', $cj_title);
    echo $cj_title;
    foreach ($list as $v) {
        $line = $v['ac_name'] . ',' .
             date('Y-m-d', strtotime($v['ac_start_time'])) . '至' .
             date('Y-m-d', strtotime($v['ac_end_time'])) . ',' .
             $this->get_return_content($v['return_rule_data'], 1) . ',' .
             $this->get_return_content($v['return_rule_data'], 2) . ',' .
             $this->get_return_content($v['return_rule_data'], 4) . ',' .
             $v['return_page_flow_count'] . ',' . $v['transmit_count'] . "\r\n";
        $line = iconv('utf-8', 'gbk', $line);
        echo $line;
    }
}

private function get_return_content($v, $id) {
    $return_rule_data = unserialize($v);
    if (isset($return_rule_data['rule_data_' . $id])) {
        $result = array();
        $string = $return_rule_data['rule_data_' . $id];
        if (strlen($string) > 0) {
            $s_array = explode("&amp;", trim($string));
            foreach ($s_array as $item) {
                $t_array = explode("=", trim($item));
                $result[$t_array[0]] = isset($t_array[1]) ? urldecode(
                    $t_array[1]) : '';
            }
        }
        $return_content = '';
        if ($result['commission_type'] == '0') {
            $return_content = $result['goods_name'];
        } else {
            if ($result['return_money_type'] == '0') {
                $return_content = '现金' . $result['return_money'] . '元';
            } elseif ($result['return_money_type'] == '1') {
                $return_content = '订单交易金额的' . $result['return_money'] . '%';
            } else {
                $return_content = "无";
            }
        }
        
        // echo $return_content;
    } else {
        $return_content = "无";
    }
    return $return_content;
}

public function marketing_ecommerce() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $map = array(
        'a.node_id' => $this->nodeId, 
        'b.marketing_info_id' => array(
            'exp', 
            '=a.id'), 
        
        // 'b.status'=>'0',
        'b.return_commission_class' => '2');
    
    // $map['b.marketing_info_id'] = array('exp', '=c.id');
    
    import('ORG.Util.Page');
    // 导入分页类
    // $model = M('tmarketing_info');
    $mapcount = M()->table('tmarketing_info a, treturn_commission_info b')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    $show = $Page->show();
    // 分页显示输出
    $query_trans_count = "(SELECT ifnull(sum(trans_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as trans_count";
    $query_transmit_count = "(SELECT ifnull(sum(transmit_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as transmit_count";
    
    $list = M()->table('tmarketing_info a, treturn_commission_info b')
        ->where($map)
        ->field(
        'a.*,' . $query_trans_count . ',' . $query_transmit_count .
             ', b.status as f_status, b.commission_name as ac_name, b.id as b_id, b.return_commission_start_time as ac_start_time, b.return_commission_end_time as ac_end_time')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    if (empty($list)) {
        $has_commission = false;
    } else {
        $has_commission = true;
    }
    
    $arr_ = C('CHANNEL_TYPE');
    $this->assign('arr_', $arr_);
    $this->assign('query_list', $list);
    $this->assign('page', $show);
    // 赋值分页输出
    $this->assign('nodeList', $this->getNodeTree());
    $this->assign('has_commission', $has_commission);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_ecommerce');
    $this->assign('hook_actions', $hook_actions);
    
    $this->display();
}

public function view_marketing_ecommerce() {
    $batchId = I('id', '', 'mysql_real_escape_string');
    
    // 返佣配置信息
    $map = array(
        'id' => $batchId);
    $returnInfo = M('treturn_commission_info')->where($map)->find();
    if (! $returnInfo)
        $this->error('未找到该活动信息[01]！');
    
    $this->assign('marketing_ecommerce', $returnInfo);
    $returnInfo1 = M('tsystem_param')->where(
        "param_name = 'RETURN_COMMISSION_NOTIFY_NOTE'")->find();
    $this->assign('notice_tpl', $returnInfo1['param_value']);
    $this->display();
}

public function add_marketing_plan() {
    if (! $this->_hasPower(true))
        $this->intro();
    if ($this->isPost()) {
        
        /* 参数校验 */
        if (I('return_commission_start_time') > I('return_commission_end_time')) {
            $this->error("开始时间必须小于结束时间！");
        }
        $map = array(
            'node_id' => $this->nodeId, 
            'commission_type' => '3', 
            'return_commission_class' => '2', 
            'return_commission_end_time' => array(
                'egt', 
                I('return_commission_start_time')));
        $mapcount = M()->table('treturn_commission_info')
            ->where($map)
            ->count();
        // 查询满足要求的总记录数
        if ($mapcount > 0) {
            $this->error("开始时间和已有推广计划重合！");
        }
        $req = array();
        $rules = array(
            'user_return_price_flag' => array(
                'null' => false, 
                'name' => '单用户最高返佣限制', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'return_money_limit_flag' => array(
                'null' => false, 
                'name' => '返佣总金额限制', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'send_notice_flag' => array(
                'null' => false, 
                'name' => '是否发送通知短信', 
                'inarr' => array(
                    '0', 
                    '1', 
                    '2')), 
            'commission_rule' => array(
                'null' => false, 
                'maxlen_cn' => '300', 
                'name' => '返佣规则'), 
            'return_commission_start_time' => array(
                'null' => false, 
                'maxlen_cn' => '8', 
                'name' => '开始时间'), 
            'return_commission_end_time' => array(
                'null' => false, 
                'maxlen_cn' => '8', 
                'name' => '结束时间'));
        // 'return_money' => array('null' => false, 'strtype' => 'int', 'name'
        // => '金额设置', 'minval' => 1),
        
        $req = $this->_verifyReqData($rules);
        
        $rules = array();
        if ($req['user_return_price_flag'] == '1') {
            $rules['user_return_price_time'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => 1, 
                'name' => '单用户最高返佣次数');
        }
        
        if ($req['return_money_limit_flag'] == '1') {
            $rules['return_money_limit'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => 1, 
                'name' => '返佣总金额');
        }
        
        $req = array_merge($req, $this->_verifyReqData($rules));
        
        // 全民营销渠道id
        $channel_id = $this->_get_default_channel_id();
        $m_name = '小店-全民营销' . I('return_commission_start_time');
        
        // 查询多乐互动
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_type' => 29);
        $marketingInfo = M('tmarketing_info')->where($map)->find();
        $marketing_id = $marketingInfo['id'];
        try {
            M()->startTrans();
            $channel_id = $this->_get_default_channel_id();
            $map = array(
                'batch_id' => $marketing_id, 
                'channel_id' => $channel_id, 
                'node_id' => $this->nodeId);
            $tbatch_channel_info = M('tbatch_channel')->where($map)->find();
            if (! $tbatch_channel_info) {
                
                // 3. 将多乐互动发布至默认渠道--发布之后才能得到batch_channel_id给后面用
                $batchChannelData = array(
                    'batch_type' => $marketingInfo['batch_type'], 
                    
                    // 'batch_id' => $req['m_id'],
                    'batch_id' => $marketing_id, 
                    'channel_id' => $channel_id, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $this->nodeId);
                $batch_channel_id = M('tbatch_channel')->add($batchChannelData);
                if ($batch_channel_id === false) {
                    M()->rollback();
                    throw new Exception("全民营销活动创建失败[03]！");
                }
            } else {
                $batch_channel_id = $tbatch_channel_info['id'];
            }
            
            $returnData = array(
                
                // 'm_id' => $marketing_id,/*----*/
                'marketing_info_id' => $marketing_id,
                     /* ---- */
                    'node_id' => $this->nodeId, 
                'return_commission_class' => '2', 
                'version_flag' => '2', 
                'commission_type' => '3', 
                'return_condition' => '1', 
                'status' => '1', 
                'commission_name' => $m_name, 
                'phone_limit_flag' => I('user_return_price_flag'), 
                'phone_limit_num' => (int) I('user_return_price_flag') == 1 ? I(
                    'user_return_price_time') : '', 
                'return_commission_start_time' => I(
                    'return_commission_start_time') . '000000', 
                'return_commission_end_time' => I('return_commission_end_time') .
                     '235959', 
                    'label_id' => $batch_channel_id, 
                    'add_time' => date('YmdHis'), 
                    'return_money_type' => I('return_money_type'), 
                    'return_money' => (int) I('return_money_type') == 0 ? I(
                        'return_money') : I('return_rate'), 
                    'return_money_limit_flag' => I('return_money_limit_flag'), 
                    'return_money_limit' => (int) I('return_money_limit_flag') ==
                     1 ? I('return_money_limit') : '', 
                    'send_notice_flag' => I('send_notice_flag') ,
                    
                    /* 'send_notice_content' => I('send_notice_flag') == '0' ? I('send_notice_content') : '0', */
                    'return_condition_num' => I('low_times_limit'), 
                    'commission_rule' => I('commission_rule'));
            $return_id = M('treturn_commission_info')->add($returnData);
            $returnData['return_commission_id'] = $return_id;
            unset($returnData['return_rule_data']);
            $return_ext_id = M('treturn_commission_info_ext')->add($returnData);
            
            // echo $return_id;
            M()->commit();
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        node_log('创建推广计划：' . $m_name, print_r($req, true));
        $this->success('推广计划创建成功！');
    }
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_ecommerce');
    $this->assign('hook_actions', $hook_actions);
    $returnInfo = M('tsystem_param')->where(
        "param_name = 'RETURN_COMMISSION_NOTIFY_NOTE'")->find();
    $this->assign('notice_tpl', $returnInfo['param_value']);
    
    $nodename = M('tnode_info')->where("node_id='$this->nodeId'")->getField(
        'node_name');
    $this->assign('nodename', $nodename);
    
    $this->display();
}

public function edit_marketing_plan() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $batchId = I('id', '', 'mysql_real_escape_string');
    
    // 返佣配置信息
    $map = array(
        'id' => $batchId);
    $returnInfo = M('treturn_commission_info')->where($map)->find();
    if (! $returnInfo)
        $this->error('未找到该活动信息[01]！');
    $is_start = 0;
    if (strlen($returnInfo['return_commission_start_time']) < 14) {
        $returnInfo['return_commission_start_time'] .= "000000";
    }
    if ($returnInfo['return_commission_start_time'] < date('YmdHis') ||
         (int) $returnInfo['status'] == 0) {
        $is_start = 1;
    }
    
    if ($this->isPost()) {
        try {
            M()->startTrans();
            $returnData = array(
                'commission_rule' => I('commission_rule'));
            if (! $is_start) {
                $returnData['return_money_type'] = I('return_money_type');
                $returnData['return_money'] = (int) I('return_money_type') == 0 ? I(
                    'return_money') : I('return_rate');
            }
            $flag = M('treturn_commission_info')->where(
                "id = {$returnInfo['id']}")->save($returnData);
            if ($flag === false)
                throw new Exception("推广计划更新失败[02]！");
            $flag_ext = M('treturn_commission_info_ext')->where(
                "return_commission_id = {$returnInfo['id']}")->save($returnData);
            if ($flag_ext === false)
                throw new Exception("推广计划更新失败[03]！");
            M()->commit();
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        $this->success('推广计划编辑成功！');
    }
    
    $this->assign('is_start', $is_start);
    $this->assign('marketing_ecommerce', $returnInfo);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_ecommerce');
    $this->assign('hook_actions', $hook_actions);
    $returnInfo = M('tsystem_param')->where(
        "param_name = 'RETURN_COMMISSION_NOTIFY_NOTE'")->find();
    $this->assign('notice_tpl', $returnInfo['param_value']);
    
    $nodename = M('tnode_info')->where("node_id='$this->nodeId'")->getField(
        'node_name');
    $this->assign('nodename', $nodename);
    
    $this->display();
}

public function statusChangeforME() {
    $batchId = I('post.batch_id', null, 'intval');
    $status = I('post.status', null, 'intval');
    if (is_null($batchId) || is_null($status)) {
        $this->error('缺少必要参数');
    }
    $returnInfo = M('treturn_commission_info')->where("id = {$batchId}")->find();
    if (! $returnInfo)
        $this->error('未找到该活动');
        
        /*
     * if($status == '0' &&
     * $returnInfo['return_commission_start_time']>date('YmdHis') &&
     * $returnInfo['return_commission_class']=='2') { $this->error('还没到开始时间！');
     * }
     */
    if ($status == '0' &&
         $returnInfo['return_commission_end_time'] < date('YmdHis') &&
         $returnInfo['return_commission_class'] == '2') {
        $this->error('该推广计划已过期，不可启用！');
    }
    
    if ($returnInfo['status'] == '3')
        $this->error('活动已过期！');
    
    if ($returnInfo['status'] == '0' && $status == '0')
        $this->success('更新成功');
    
    if (($returnInfo['status'] == '1' || $returnInfo['status'] == '2') &&
         $status == '1')
        $this->success('更新成功');
    
    $returnInfo_ext_count = M('treturn_commission_info_ext')->where(
        "return_commission_id = {$returnInfo['id']} AND commission_type!='0'")->count();
    
    /*
     * if ($returnInfo['commission_type'] != '0' && $status == '0')
     * $this->_check_AccountPrice(2);
     */
    if ($returnInfo_ext_count > 0 && $status == '0') {
        $this->_check_AccountPrice(2);
    }
    
    if ($status == '1') {
        $data = array(
            'status' => '1');
    } else {
        $data = array(
            'status' => '0');
    }
    M()->startTrans();
    $result = M('treturn_commission_info')->where("id = {$batchId}")->save(
        $data);
    
    if ($result !== false) {
        M()->commit();
        node_log('店铺推广活动状态更改|活动ID：' . $batchId);
        $this->success('更新成功');
    } else {
        M()->rollback();
        $this->error('更新失败');
    }
}

public function marketing_ecommerce_explosion_sales() {
    if (! $this->_hasPower(true))
        $this->intro();
        
        /* 获取活动列表 */
        
    // 非商品销售的多乐互动必须要有奖品才能做全民营销的活动
    $other_field = '';
    
    // $map['_string']='(select count(*) from tcj_batch b where b.batch_id =
    // a.id)>0';
    $map['a.node_id'] = $this->nodeId;
    $map['a.batch_type'] = array(
        'in', 
        $this->BATCH_TYPE_2);
    $map['a.end_time'] = array(
        'gt', 
        date('YmdHis'));
    $map['a.return_commission_flag'] = 0;
    $data = I('request.');
    if ($data['key'] != '') {
        $map['a.name'] = array(
            'like', 
            '%' . $data['key'] . '%');
    }
    if ($data['batch_type'] != '') {
        $map['a.batch_type'] = $data['batch_type'];
    }
    
    import('ORG.Util.Page');
    // 导入分页类
    foreach ($data as $key => $val) {
        $Page->parameter .= "&$key=" . urlencode($val) . '&';
    }
    
    // $model = M('tmarketing_info');
    $mapcount = M()->table('tmarketing_info a')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    $show = $Page->show();
    // 分页显示输出
    
    $queryData = M()->table('tmarketing_info a')
        ->field(
        "a.id batch_id, a.name, a.start_time, a.end_time" . $other_field)
        ->where($map)
        ->order("a.id desc")
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    
    /* 获取活动列表end */
    $active_list = $queryData;
    if (empty($active_list)) {
        $has_commission = false;
    } else {
        $has_commission = true;
    }
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_ecommerce');
    $this->assign('hook_actions', $hook_actions);
    $this->assign('query_list', $active_list);
    $this->assign('has_commission', $has_commission);
    $this->assign('page', $show);
    // 赋值分页输出
    $this->assign('nodeList', $this->getNodeTree());
    
    $this->display();
    // 输出模板
}

public function marketing_ecommerce_running() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $map = array(
        'a.node_id' => array(
            'exp', 
            "in (" . $this->nodeIn() . ")"), 
        'a.id' => array(
            'exp', 
            '=b.marketing_info_id'), 
        
        // 'a.id' => array('exp', '=b.m_id'),
        'a.batch_type' => array(
            'in', 
            $this->BATCH_TYPE_2));
    
    $data = I('request.');
    if (! empty($data['node_id']))
        $map['a.node_id '] = $data['node_id'];
    if ($data['key'] != '') {
        $map['a.name'] = array(
            'like', 
            '%' . $data['key'] . '%');
    }
    if ($data['status'] != '') {
        $map['b.status'] = $data['status'] == '1' ? '0' : array(
            'neq', 
            '0');
    }
    
    import('ORG.Util.Page');
    // 导入分页类
    // $model = M('tmarketing_info');
    $mapcount = M()->table('tmarketing_info a, treturn_commission_info b')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    /*
     * echo "<pre>"; var_dump($map);echo "</pre>"; die($mapcount);
     */
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    foreach ($data as $key => $val) {
        $Page->parameter .= "&$key=" . urlencode($val) . '&';
    }
    
    $map['b.marketing_info_id'] = array(
        'exp', 
        '=c.id');
    
    $show = $Page->show();
    // 分页显示输出
    $query_transmit_count = "(SELECT ifnull(sum(transmit_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as transmit_count";
    $query_return_page_flow_count = "(SELECT ifnull(sum(return_page_flow_count),0) FROM treturn_commission_daystat where return_commission_id=b.id) as return_page_flow_count";
    $list = M()->table(
        'tmarketing_info a, tmarketing_info c, treturn_commission_info b')
        ->join('tgoods_info g on g.goods_id = b.goods_id')
        ->where($map)
        ->field(
        'a.*, b.id as b_id,' . $query_transmit_count . ',' .
             $query_return_page_flow_count .
             ',b.status as f_status, g.goods_name, c.name as ac_name, b.commission_type, b.return_money_type, b.return_money,b.return_rule_data as return_rule_data, c.batch_type, b.marketing_info_id, c.start_time as ac_start_time, c.end_time as ac_end_time')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    if (empty($list)) {
        $has_commission = false;
    } else {
        $has_commission = true;
    }
    
    $arr_ = C('CHANNEL_TYPE');
    $this->assign('arr_', $arr_);
    $this->assign('query_list', $list);
    $this->assign('page', $show);
    // 赋值分页输出
    $this->assign('nodeList', $this->getNodeTree());
    $this->assign('has_commission', $has_commission);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'marketing_ecommerce');
    $this->assign('hook_actions', $hook_actions);
    
    $this->display();
}

public function commission_issued_cash() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $returnId = I('return_id', '', 'mysql_real_escape_string');
    $commission_type = I('commission_type', '', 'mysql_real_escape_string');
    $start_time = I('start_time', '', 'mysql_real_escape_string');
    $end_time = I('end_time', '', 'mysql_real_escape_string');
    $status = I('return_status', '', 'mysql_real_escape_string');
    
    $map = array(
        'node_id' => $this->nodeId);
    $marketingList = M('treturn_commission_info')->where($map)->getField(
        'id, commission_name', true);
    
    import('ORG.Util.Page');
    // 导入分页类
    $map = array(
        'a.node_id' => $this->nodeId, 
        '_string' => ' 1=1 ');
    $map['a.commission_type'] = '3';
    
    if ($commission_type != '')
        $map['a.commission_type'] = $commission_type;
    
    if ($start_time != '')
        $map['_string'] .= " and a.return_add_time >= '{$start_time}000000'";
    
    if ($end_time != '')
        $map['_string'] .= " and a.return_add_time <= '{$end_time}235959'";
    
    if ($returnId != '' && isset($marketingList[$returnId]))
        $map['a.return_commission_id'] = $returnId;
    
    if ($status != '') {
        $map['a.return_status'] = $status;
    }

    function get_return_status_txt($val) {
        
        // 0-未发放 1-已发放 2+卡券 发放失败 2+现金 扣佣失败
        $r_status = $val['return_status'];
        $r_type = $val['commission_type'];
        $user_get_flag = $val['user_get_flag'];
        if ($user_get_flag == '0' && $r_status == '0')
            return '未发放';
        if ($user_get_flag == '1' && $r_status == '0')
            return '已提领';
        if ($user_get_flag == '1' && $r_status == '1')
            return '已发放 ';
        
        /*
         * if ($user_get_flag == '1' &&$r_status=='2') return '待确认'; if
         * ($user_get_flag == '1' &&$r_status=='3') return '过期未领取';
         */
        
        /*
         * if ($r_status == '0') return '未发放'; else if ($r_status == '1') return
         * '已发放'; else { if ($r_type == '0') return '发放失败'; if ($r_type == '3')
         * return '扣佣失败'; }
         */
    }
    
    $mapcount = M()->table('treturn_commission_trace a')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    if (I('sub_type') == '2') {
        $map['_string'] .= " and a.return_commission_id = b.id";
        $map['c.node_id'] = array(
            'exp', 
            '=a.node_id');
        $map['c.phone_no'] = array(
            'exp', 
            '=a.phone_no');
        
        if ($mapcount == 0)
            $this->error('没有数据', U('record'));
        
        $fileName = '返佣发放记录.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "手机号码,返佣活动,返佣达成时间,返佣发放时间,返佣类型,返佣内容,返佣账号,状态\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->table(
                'treturn_commission_trace a, treturn_commission_info b, tmember_info c')
                ->where($map)
                ->field(
                'a.*, b.commission_name, c.alipay_acount, c.alipay_name')
                ->order('a.id desc')
                ->limit($page . ',' . $page_count)
                ->select();
            
            if (! $query)
                exit();
            foreach ($query as $v) {
                $line = '"' . $v['phone_no'] . '","' . $v['commission_name'] .
                     '","' . dateformat($v['return_add_time']) . '","' .
                     dateformat($v['return_charge_time']) . '","' .
                     $this->commission_type_arr[$v['commission_type']] . '","' .
                     $v['return_content'] . '","' . ($v['commission_type'] == '0' ? $v['return_acount'] : ($v['alipay_acount'] .
                     ',' . $v['alipay_name'])) . '","' .
                     get_return_status_txt($v) . "\"\r\n";
                $line = iconv('utf-8', 'gbk', $line);
                echo $line;
            }
        }
        exit();
    }
    
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    $map['_string'] .= " and a.return_commission_id = b.id";
    $list = M()->table('treturn_commission_info b,treturn_commission_trace a')
        ->join("treturn_get_trace c ON a.user_get_trace_id = c.id")
        ->where($map)
        ->field(
        'a.*, b.commission_name,c.alipay_acount,c.add_time as c_add_time')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    foreach ($list as & $info) {
        $info['return_status'] = get_return_status_txt($info);
    }
    $this->assign('list', $list);
    $this->assign('page', $Page->show());
    $this->assign('batch_list', $marketingList);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'commission_issued');
    $this->assign('hook_actions', $hook_actions);
    
    $this->display();
}

public function commission_issued() {
    if (! $this->_hasPower(true))
        $this->intro();
    
    $returnId = I('return_id', '', 'mysql_real_escape_string');
    $commission_type = I('commission_type', '', 'mysql_real_escape_string');
    $start_time = I('start_time', '', 'mysql_real_escape_string');
    $end_time = I('end_time', '', 'mysql_real_escape_string');
    $status = I('return_status', '', 'mysql_real_escape_string');
    
    $map = array(
        'node_id' => $this->nodeId);
    $marketingList = M('treturn_commission_info')->where($map)->getField(
        'id, commission_name', true);
    
    import('ORG.Util.Page');
    // 导入分页类
    $map = array(
        'a.node_id' => $this->nodeId, 
        '_string' => ' 1=1 ');
    $map['a.commission_type'] = '0';
    
    if ($commission_type != '')
        $map['a.commission_type'] = $commission_type;
    
    if ($start_time != '')
        $map['_string'] .= " and a.return_add_time >= '{$start_time}000000'";
    
    if ($end_time != '')
        $map['_string'] .= " and a.return_add_time <= '{$end_time}235959'";
    
    if ($returnId != '' && isset($marketingList[$returnId]))
        $map['a.return_commission_id'] = $returnId;
    
    if ($status != '') {
        $map['a.return_status'] = $status;
    }

    function get_return_status_txt($val) {
        
        // 0-未发放 1-已发放 2+卡券 发放失败 2+现金 扣佣失败
        $r_status = $val['return_status'];
        $r_type = $val['commission_type'];
        if ($r_status == '0')
            return '未发放';
        else if ($r_status == '1')
            return '已发放';
        else {
            if ($r_type == '0')
                return '发放失败';
            if ($r_type == '3')
                return '扣佣失败';
        }
    }
    
    $mapcount = M()->table('treturn_commission_trace a')
        ->where($map)
        ->count();
    // 查询满足要求的总记录数
    if (I('sub_type') == '2') {
        $map['_string'] .= " and a.return_commission_id = b.id";
        $map['c.node_id'] = array(
            'exp', 
            '=a.node_id');
        $map['c.phone_no'] = array(
            'exp', 
            '=a.phone_no');
        
        if ($mapcount == 0)
            $this->error('没有数据', U('record'));
        
        $fileName = '返佣发放记录.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "手机号码,返佣活动,返佣达成时间,返佣发放时间,返佣类型,返佣内容,返佣账号,状态\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->table(
                'treturn_commission_trace a, treturn_commission_info b, tmember_info c')
                ->where($map)
                ->field(
                'a.*, b.commission_name, c.alipay_acount, c.alipay_name')
                ->order('a.id desc')
                ->limit($page . ',' . $page_count)
                ->select();
            
            if (! $query)
                exit();
            foreach ($query as $v) {
                $line = '"' . $v['phone_no'] . '","' . $v['commission_name'] .
                     '","' . dateformat($v['return_add_time']) . '","' .
                     dateformat($v['return_charge_time']) . '","' .
                     $this->commission_type_arr[$v['commission_type']] . '","' .
                     $v['return_content'] . '","' . ($v['commission_type'] == '0' ? $v['return_acount'] : ($v['alipay_acount'] .
                     ',' . $v['alipay_name'])) . '","' .
                     get_return_status_txt($v) . "\"\r\n";
                $line = iconv('utf-8', 'gbk', $line);
                echo $line;
            }
        }
        exit();
    }
    
    $Page = new Page($mapcount, 10);
    // 实例化分页类 传入总记录数和每页显示的记录数
    
    $map['_string'] .= " and a.return_commission_id = b.id";
    $list = M()->table('treturn_commission_trace a, treturn_commission_info b')
        ->where($map)
        ->field('a.*, b.commission_name')
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    foreach ($list as & $info) {
        $info['return_status'] = get_return_status_txt($info);
    }
    $this->assign('list', $list);
    $this->assign('page', $Page->show());
    $this->assign('batch_list', $marketingList);
    
    /* 定义属于本操作的子操作 */
    $hook_actions = array(
        'commission_issued');
    $this->assign('hook_actions', $hook_actions);
    
    $this->display();
}

public function forwarding_rule() {
    $sales = I('sales');
    $this->assign('notice_tpl', $this->_get_notify_note());
    $this->assign('sales', $sales);
    $this->assign('tongbaozhai_flag', $this->tongbaozhai_flag);
    
    // $this->assign('tongbaozhai_flag', "1");
    $this->display();
}

public function joinactivities_rule() {
    $sales = I('sales');
    $this->assign('notice_tpl', $this->_get_notify_note());
    $this->assign('sales', $sales);
    $this->assign('tongbaozhai_flag', $this->tongbaozhai_flag);
    
    // $this->assign('tongbaozhai_flag', "1");
    $this->display();
}

public function getseo_rule() {
    $sales = I('sales');
    $this->assign('notice_tpl', $this->_get_notify_note());
    $this->assign('sales', $sales);
    $this->assign('tongbaozhai_flag', $this->tongbaozhai_flag);
    
    // $this->assign('tongbaozhai_flag', "1");
    $this->display();
}

public function buyproduct_rule() {
    $sales = I('sales');
    $this->assign('notice_tpl', $this->_get_notify_note());
    $this->assign('sales', $sales);
    $this->assign('tongbaozhai_flag', $this->tongbaozhai_flag);
    
    // $this->assign('tongbaozhai_flag', "1");
    $this->display();
}
}
