<?php

/**
 * 劳动最光荣 @auther zhoukai @time 2015年3月24日15:01:59 @time 2015年3月24日15:01:59
 */
class DuanWuAction extends BaseAction {

    public $_authAccessMap = '*';

    const BATCH_TYPE = 49;
    // 活动类型
    // 活动类型
    public $BATCH_TYPE;

    public $BATCH_NAME;
    // 图片路径
    public $img_path;
    // 旺币有效期，旺币值
    public $wbValidDate = '20150931';

    public $wbNum = 200;

    const END_LABORDAY_TIME = 20150910;
    // 劳动节限制c0,c1用户添加时间
    const APPLYEPOS_LABORDAY_TIME = 20150931;
    // 初始化
    public function _initialize() {
        $this->BATCH_TYPE = self::BATCH_TYPE;
        $this->BATCH_NAME = C('BATCH_TYPE_NAME.' . self::BATCH_TYPE);
        if (ACTION_NAME == 'index_pop') {
            $this->_authAccessMap = '*';
        }
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        // 只允许仟吉创建活动
        if ($this->node_id != C('qianji.node_id')) {
            $this->error("你没有权限开展此类型活动！");
        }
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->assign('batch_name', $this->BATCH_NAME);
        $this->assign('acname', ACTION_NAME);
    }
    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE);
        
        $_GET['node_id'] = I('node_id');
        if ($_GET['node_id'] != '') {
            $map['node_id '] = $_GET['node_id'];
        }
        $_GET['key'] = I('key');
        if ($_GET['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $_GET['key'] . '%');
        }
        
        $_GET['status'] = I('status');
        if ($_GET['status'] != '') {
            $map['status'] = $_GET['status'];
        }
        $_GET['start_time'] = I('start_time');
        $_GET['end_time'] = I('end_time');
        if ($_GET['start_time'] != '' && $_GET['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $_GET['start_time'] . '000000', 
                    $_GET['end_time'] . '235959'));
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['is_mem_batch'] = 'N';
                if ($v['is_cj'] == '1') {
                    $rule_id = M('tcj_rule')->where(
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = M('tcj_batch')->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
            }
        }
        node_log("首页+端午节");
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        // 判断仟吉端午节
        $this->assign('qianji_node_id', $this->node_id);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        $model = M('tmarketing_info');
        $one_map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("只允许创建一个活动");
        }
        // 获取商户名称
        $nodeInfo = M('tnode_info')->where("node_id='{$this->node_id}'")->find();
        $nodeName = $nodeInfo['node_name'];
        $this->assign('node_name', $nodeName);
        $this->assign('row', 
            array(
                'name' => $this->BATCH_NAME));
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        // exit;
        if (empty($data['name']))
            $this->error('请填写活动名称！');
            // if (empty($data['wap_title']))
            // $this->error('请填写wap页面标题！');
        if (empty($data['start_time']))
            $this->error('请填写标签可用开始时间！');
        if (empty($data['end_time']))
            $this->error('请填写标签可用结束时间！');
            // if (empty($data['wap_info']))
            // $this->error('活动页面内容不能为空');
        $one_map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("只允许创建一个活动");
        }
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $data['resp_log_img'], 
            'music' => $data['resp_music'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '0', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'join_mode' => 1, 
            'member_join_flag' => 0, 
            'fans_collect_url' => $data['fans_collect_url']);
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('index')));
        }
        
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        node_log($this->BATCH_NAME . '添加|活动名:' . $data['name']);
        $this->success('添加成功！', 
            array(
                '设置抽奖' => U('LabelAdmin/DuanWuCjSet/index', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布到线上线下渠道' => U('LabelAdmin/BindChannel/index', 
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id)), 
                '返回活动列表页' => U('index')));
    }
    // 编辑页
    public function edit() {
        $model = M('tmarketing_info');
        $id = I('get.id');
        if (empty($id))
            $this->error('错误参错');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $id);
        $query_arr = $model->where($map)->find();
        if (! $query_arr)
            $this->error('未查询到数据！');
        $this->assign('node_name', $query_arr['node_name']);
        $this->assign('row', $query_arr);
        $this->display('add');
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $data['id']);
        $info = $model->where($edit_wh)->find();
        if (! $info)
            $this->error('未查询到记录');
        $node_id = $info['node_id'];
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $node_id, 
            'id' => array(
                'neq', 
                $data['id']));
        $info_ = $model->where($one_map)->find();
        if ($info_) {
            $this->error("活动名称重复");
        }
        // if (empty($data['wap_info'])) {
        // $this->error('活动页面内容不能为空');
        // }
        $map = array(
            'node_id' => $node_id, 
            'id' => $data['id']);
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_show' => '1');
        
        $log_img = $data['resp_log_img'];
        $code_img = $data['resp_code_img'];
        $music = $data['resp_music'];
        
        if ($data['is_log_img'] == '1' && ! empty($log_img)) {
            $data_arr['log_img'] = $log_img;
        } else {
            if ($data['is_log_img'] == '0') {
                $data_arr['log_img'] = '';
            }
        }
        $execute = $model->where($map)->save($data_arr);
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('index')));
        node_log($this->BATCH_NAME . '编辑|活动id:' . $data['id']);
        redirect(U('index'));
        // $this->success('更新成功', array('返回活动列表'=>U('index')));
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in ( {$this->nodeIn()} ) AND id={$batchId}")->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'id' => $batchId, 
                'status' => '1');
        } else {
            $data = array(
                'id' => $batchId, 
                'status' => '2');
        }
        $result = M('tmarketing_info')->save($data);
        if ($result) {
            node_log($this->BATCH_NAME . '状态更改|活动id:' . $batchId);
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 数据导出
    public function export() {
        $status = I('status', null, 'mysql_real_escape_string');
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "'";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', I('post.'));
            if (isset($condition['key']) && $condition['key'] != '') {
                $filter[] = "name LIKE '%{$condition['key']}%'";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "status = '{$condition['status']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $condition['start_time'] = $condition['start_time'] . ' 000000';
                $filter[] = "add_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . ' 235959';
                $filter[] = "add_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $sql = "SELECT name,add_time,start_time,end_time,
    	CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
    	click_count,send_count
    	FROM
    	 tmarketing_info {$where} AND node_id in ({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'send_count' => '发码量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }

    public function winningExport() {
        $batchId = I('batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $nodeInfo = M('tmarketing_info')->where($edit_wh)->find();
        if (! $nodeInfo)
            $this->error('未查询到记录！');
        
        $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-中奖名单.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "参与号,接收手机号,参与时间,中奖状态,奖品等级名称,奖品名称,渠道名称\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $sql = "SELECT a.mobile,case when send_mobile = '13900000000' then '' else send_mobile end send_mobile,a.add_time,a.status,a.prize_level,c.name,d.batch_name,e.name as channel_name
					FROM tcj_trace a left join tcj_batch b on a.rule_id =b.id
					left join tchannel e on a.channel_id= e.id
					left join tcj_cate c  on b.cj_cate_id= c.id
					left join tbatch_info d on b.b_id=d.id
					 WHERE a.batch_id={$batchId} AND
				     a.node_id ='" . $nodeInfo['node_id'] . "'
					 ORDER by a.status DESC LIMIT {$page},{$page_count}";
            
            $query = M()->query($sql);
            if (! $query)
                exit();
            foreach ($query as $v) {
                $cj_status = iconv('utf-8', 'gbk', 
                    $v['status'] == "1" ? '未中奖' : '中奖');
                $cj_cate = iconv('utf-8', 'gbk', $v['name']);
                $jp_name = iconv('utf-8', 'gbk', $v['batch_name']);
                $channel_name = iconv('utf-8', 'gbk', $v['channel_name']);
                echo "{$v['mobile']},{$v['send_mobile']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) .
                     ",{$cj_status},{$cj_cate},{$jp_name},{$channel_name}\r\n";
            }
        }
    }
}