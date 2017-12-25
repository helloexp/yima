<?php
// 活动列表
class ListAction extends BaseAction {
    // 定义活动类型
    public $BATCH_TYPE = '8';
    // 图片路径
    public $img_path;

    public $CHANNEL_TYPE;

    public $CHANNEL_SNS_TYPE;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->CHANNEL_TYPE = CommonConst::CHANNEL_TYPE_HIGH_LEVEL;
        $this->CHANNEL_SNS_TYPE = CommonConst::SNS_TYPE_LIST;
    }
    
    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->BATCH_TYPE);
        
        // 商户简称
        $node_short_name = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('node_short_name');
        
        $data = I('request.');
        
        if ($data['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['start_time'] != '' && $data['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $data['start_time'] . '000000', 
                    $data['end_time'] . '235959'));
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('node_short_name', $node_short_name);
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $name = I('post.name', '', 'trim');
        $webtitle = I('post.webtitle', '', 'trim');
        if (! $this->isPost())
            $this->error('非法提交！');
        if (empty($name))
            $this->error('请填写活动名称！');
        if (empty($webtitle))
            $webtitle = M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->getField('node_short_name');
        
        $one_map = array(
            'name' => $name, 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        $data_arr = array(
            'name' => $name, 
            'status' => '1', 
            'start_time' => date('YmdHis'), 
            'end_time' => date('YmdHis', strtotime("+10 year")), 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id, 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'node_name' => $webtitle, 
            'batch_come_from' => session('batch_from') ? session('batch_from') : '1');
        $query = $model->add($data_arr);
        if ($query) {
            node_log('列表活动添加|活动名:' . $name);
            $ser = D('TmarketingInfo');
            $arr = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $query);
            $ser->init($arr);
            $ser->sendBatch();
            // 顺便发布到多乐互动专用渠道上
            $bchId = D('MarketCommon')->chPublish($this->nodeId,$query);
            if($bchId === false){
                $this->error('发布到渠道失败');
            }
            $this->success($query, 
                array(
                    '返回列表模板活动' => U('index'), 
                    '发布渠道' => U('LabelAdmin/BindChannel/index', 
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $query))));
        } else {
            $this->error('error');
        }
    }
    
    // 编辑页
    public function edit() {
        $model = M('tmarketing_info');
        $id = I('get.id', '', 'trim');
        if (empty($id))
            $this->error('错误参错');
        
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $id);
        $query_arr = $model->where($map)->find();
        if (! $query_arr)
            $this->error('未查询到数据！');
            
            // 列表
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M('tlist_batch_list')->where(
            array(
                'node_id' => $this->node_id, 
                'list_id' => $id, 
                'status' => '1'))->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $result = M('tlist_batch_list')->where(
            array(
                'node_id' => $this->node_id, 
                'list_id' => $id, 
                'status' => '1'))
            ->order('list_sort_id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $this->assign('page', $show);
        $this->assign('row', $query_arr);
        $this->assign('result', $result);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        if (! $this->isPost())
            $this->error('非法提交！');
        $model = M('tmarketing_info');
        $id = I('post.id');
        $name = I('post.name');
        $webtitle = I('post.webtitle');
        
        if (empty($id) || empty($name))
            $this->error('参数错误！');
        
        $edit_wh = array(
            'id' => $id, 
            'node_id' => $this->node_id);
        $node_id = $model->where($edit_wh)->getField('node_id');
        if (! $node_id)
            $this->error('未查询到记录');
        
        $map = array(
            'name' => $name, 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $node_id, 
            'id' => array(
                'neq', 
                $id));
        
        $query = $model->where($map)->find();
        if ($query)
            $this->error('活动名重复！');
        
        if (empty($webtitle))
            $webtitle = M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->getField('node_short_name');
        
        $up_arr = array(
            'name' => $name, 
            'node_name' => $webtitle);
        $map = array(
            'node_id' => $node_id, 
            'id' => $id);
        $query = $model->where($map)->save($up_arr);
        if ($query === false)
            $this->error('修改失败！');
        else
            $this->success('修改成功！');
        node_log('列表活动名编辑|活动id:' . $id);
    }
    
    // 查询活动
    public function select() {
        $id = I('id', '', 'trim');
        if (empty($id))
            $this->error('参数错误！');
        $list_id = I('list_id', '', 'trim');
        if (! empty($list_id)) {
            $result = M('tlist_batch_list')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $list_id))->find();
            $this->assign('result', $result);
        }
        
        $this->assign('list_id', $list_id);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 列表记录添加
    public function addList() {
        if (! $this->isPost())
            $this->error('错误提交！');
        
        $id = I('post.id', '', 'trim');
        $list_id = I('post.list_id', '', 'trim');
        $name = I('post.name', '', 'trim');
        $batch_type = I('post.batch_type', '', 'trim');
        $batch_id = I('post.batch_id', '', 'trim');
        $short_note = I('post.short_note', '', 'trim');
        $up_img_t = I('post.up_img');
        $url = I('post.url', '', 'trim');
        $url_type = I('post.url_type', '', 'trim');
        $addtime_show_flag = I('post.addtime_show_flag');
        if (empty($id))
            $this->error('错误参数');
        
        if ($up_img_t) {
            $up_img = str_replace('..', '', $up_img_t);
        }
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $model = M('tlist_batch_list');
        $add_time = date('YmdHis');
        // 选择互动模块
        if ($url_type == '1') {
            if (empty($batch_id) || empty($batch_type)) {
                $this->error('请选择互动模块！');
            } else {
                
                $islistchannel = M('tchannel')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'type' => '4', 
                        'sns_type' => '42'))->find();
                if (! $islistchannel) {
                    $c_arr = array(
                        'name' => '列表渠道', 
                        'type' => '4', 
                        'sns_type' => '42', 
                        'status' => '1', 
                        'node_id' => $this->node_id, 
                        'add_time' => $add_time);
                    $channel_id = M('tchannel')->add($c_arr);
                    if (! $channel_id) {
                        $tranDb->rollback();
                        $this->error('渠道创建失败！');
                    }
                } else {
                    $channel_id = $islistchannel['id'];
                }
                $isbatchsend = M('tbatch_channel')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'channel_id' => $channel_id, 
                        'batch_id' => $batch_id))->find();
                
                if (! $isbatchsend) {
                    // 发布活动
                    $bc_arr = array(
                        'batch_type' => $batch_type, 
                        'batch_id' => $batch_id, 
                        'channel_id' => $channel_id, 
                        'add_time' => $add_time, 
                        'node_id' => $this->node_id);
                    $label_id = M('tbatch_channel')->add($bc_arr);
                    if (! $label_id) {
                        $tranDb->rollback();
                        $this->error('活动发布失败！');
                    }
                } else {
                    $label_id = $isbatchsend['id'];
                }
                
                $url = U('Label/Label/index', 
                    array(
                        'id' => $label_id), '', '', true);
            }
        } else {
            if (empty($url))
                $this->error('请输入有效的url地址');
        }
        $maxid = M('tlist_batch_list')->where(
            array(
                'node_id' => $this->node_id, 
                'list_id' => $id))->max('list_sort_id');
        
        $data = array(
            'name' => $name, 
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'short_note' => $short_note, 
            'url' => $url, 
            'name' => $name, 
            'pic' => $up_img, 
            'list_id' => $id, 
            'status' => '1', 
            'node_id' => $this->node_id, 
            'add_time' => $add_time, 
            'list_sort_id' => $maxid + 1, 
            'addtime_show_flag' => $addtime_show_flag);
        $query = $model->add($data);
        if ($query) {
            $tranDb->commit();
            $this->success('保存成功！ ');
        } else {
            $tranDb->rollback();
            $this->error('保存失败！');
        }
    }
    
    // 列表记录编辑
    public function editList() {
        if (! $this->isPost())
            $this->error('错误提交！');
        
        $id = I('post.id', '', 'trim');
        $list_id = I('post.list_id', '', 'trim');
        $name = I('post.name', '', 'trim');
        $batch_type = I('post.batch_type', '', 'trim');
        $batch_id = I('post.batch_id', '', 'trim');
        $short_note = I('post.short_note', '', 'trim');
        $up_img_t = I('post.up_img');
        $url = I('post.url', '', 'trim');
        $url_type = I('post.url_type', '', 'trim');
        $addtime_show_flag = I('post.addtime_show_flag');
        if (empty($id) || empty($list_id))
            $this->error('错误参数');
        
        if ($up_img_t) {
            if (I('post.reset_img') == '1') {
                
                /*
                 * 用新的图片上传 by tr $img_move = move_batch_image($up_img_t,
                 * $this->BATCH_TYPE, $this->node_id); if ($img_move !== true)
                 * $this->error('图片上传失败！' . $up_img_t);
                 */
                
                $up_img = $up_img_t;
            } else {
                $up_img = $up_img_t;
            }
        } else {
            $up_img = $up_img_t;
        }
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $model = M('tlist_batch_list');
        if ($url_type == '1') {
            // 生成url地址
            $islistchannel = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => '4', 
                    'sns_type' => '42'))->find();
            if (! $islistchannel) {
                $send_time = date('YmdHis');
                $c_arr = array(
                    'name' => '列表渠道', 
                    'type' => '4', 
                    'sns_type' => '42', 
                    'status' => '1', 
                    'node_id' => $this->node_id, 
                    'add_time' => $send_time);
                $channel_id = M('tchannel')->add($c_arr);
                if (! $channel_id) {
                    $tranDb->rollback();
                    $this->error('渠道创建失败！');
                }
            } else {
                $channel_id = $islistchannel['id'];
            }
            $isbatchsend = M('tbatch_channel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'channel_id' => $channel_id, 
                    'batch_id' => $batch_id))->find();
            if (! $isbatchsend) {
                // 发布活动
                $bc_arr = array(
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id, 
                    'channel_id' => $channel_id, 
                    'add_time' => $send_time, 
                    'node_id' => $this->node_id);
                $label_id = M('tbatch_channel')->add($bc_arr);
                if (! $label_id) {
                    $tranDb->rollback();
                    $this->error('活动发布失败！');
                }
            } else {
                $label_id = $isbatchsend['id'];
            }
            $url = U('Label/Label/index', 
                array(
                    'id' => $label_id), '', '', true);
        } else {
            if (empty($url))
                $this->error('请输入有效的url地址');
        }
        
        // 编辑
        if (! empty($list_id)) {
            $data = array(
                'name' => $name, 
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'short_note' => $short_note, 
                'url' => $url, 
                'name' => $name, 
                'pic' => $up_img, 
                'add_time' => Date('YmdHis'), 
                'addtime_show_flag' => $addtime_show_flag);
            $map = array(
                'node_id' => $this->node_id, 
                'id' => $list_id);
            $query = $model->where($map)->save($data);
            if ($query !== false) {
                $tranDb->commit();
                $this->success('编辑成功！');
            } else {
                $tranDb->rollback();
                $this->error('编辑失败！');
            }
        }
    }
    
    // 删除记录
    function del() {
        $id = I('id');
        $model = M('tlist_batch_list');
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $id);
        $query = $model->where($map)->find();
        if (! $query)
            $this->error('错误参数！');
        
        $data = array(
            'status' => '2');
        $result = $model->where($map)->save($data);
        if ($result)
            $this->success('删除成功');
        else
            $this->error('编辑失败！');
    }
    
    // 置顶
    public function Top() {
        $dao = M('tlist_batch_list'); // 实例化对象
        $Sid = I('id', 0, 'trim,intval'); // 获取当前ID
        $Lid = I('list_id', 0, 'trim,intval'); // 获取当前活动ID
        if ($Sid == 0) {
            // 判断当前ID是否存在
            $this->error('参数错误！');
        }
        if ($Lid == 0) {
            // 判断当前活动是否存在
            $this->error('参数错误！');
        }
        $map = array(
            // 实例化当前与活动ID
            'id' => $Sid, 
            'list_id' => $Lid, 
            'node_id' => $this->nodeId);
        // 获取当前ID
        $row1 = $dao->where($map)->find(); // 获取传来的ID
        if (! $row1) {
            $this->error('找不到当前信息!');
        }
        $i = $dao->where("id='$Sid'")->find();
        
        $maxid = $dao->where(
            array(
                'list_id' => $Lid, 
                'status' => '1'))->max('list_sort_id');
        
        $dao->where("id='$Sid'")->save(
            array(
                'list_sort_id' => $maxid + 1));
        $this->redirect(U('edit', array(
            'id' => $Lid)));
    }
    
    // 置底
    public function Bot() {
        $dao = M('tlist_batch_list'); // 实例化对象
        $Sid = I('id', 0, 'trim,intval'); // 获取当前ID
        $Lid = I('list_id', 0, 'trim,intval'); // 获取当前活动ID
        if ($Sid == 0) {
            // 判断当前ID是否存在
            $this->error('参数错误！');
        }
        if ($Lid == 0) {
            // 判断当前活动是否存在
            $this->error('参数错误！');
        }
        $map = array(
            // 实例化当前与活动ID
            'id' => $Sid, 
            'list_id' => $Lid, 
            'node_id' => $this->nodeId);
        // 获取当前ID
        $row1 = $dao->where($map)->find(); // 获取传来的ID
        if (! $row1) {
            $this->error('找不到当前信息!');
        }
        $i = $dao->where("id='$Sid'")->find();
        
        $minid = $dao->where(
            array(
                'list_id' => $Lid, 
                'status' => '1'))->min('list_sort_id');
        
        $dao->where("id='$Sid'")->save(
            array(
                'list_sort_id' => $minid - 1));
        $this->redirect(U('edit', array(
            'id' => $Lid)));
    }
    
    // 上移一层
    public function Up() {
        $dao = M('tlist_batch_list');
        $Sid = I('id', 0, 'trim,intval');
        $Lid = I('list_id', 0, 'trim,intval');
        if ($Sid == 0) {
            $this->error('参数错误！');
        }
        if ($Lid == 0) {
            $this->error('参数错误！');
        }
        $map = array(
            'id' => $Sid, 
            'list_id' => $Lid, 
            'node_id' => $this->nodeId, 
            'status' => '1');
        $row1 = $dao->where($map)->find(); // 获取传来的ID
        if (! $row1) {
            $this->error('找不到当前信息!');
        }
        $Ma = array(
            'list_sort_id' => array(
                'gt', 
                $row1['list_sort_id']), 
            'list_id' => $Lid, 
            'node_id' => $this->nodeId, 
            'status' => '1');
        $row2 = $dao->where($Ma)
            ->order('list_sort_id asc')
            ->find(); // 获取交换的ID
        if ($row2 == null) {
            $this->redirect(
                U('edit', array(
                    'id' => $Lid)));
        }
        $data1['list_sort_id'] = $row2['list_sort_id'];
        
        $dao->where("id={$row1['id']}")->save($data1); // 更新交换数据
        
        $data2['list_sort_id'] = $row1['list_sort_id'];
        $dao->where("id={$row2['id']}")->save($data2); // 更新交换数据2
        $this->redirect(U('edit', array(
            'id' => $Lid)));
    }
    
    // 下移动一层
    public function Dw() {
        $dao = M('tlist_batch_list');
        $Sid = I('id', 0, 'trim,intval');
        $Lid = I('list_id', 0, 'trim,intval');
        if ($Sid == 0) {
            $this->error('参数错误！');
        }
        if ($Lid == 0) {
            $this->error('参数错误！');
        }
        $map = array(
            'id' => $Sid, 
            'list_id' => $Lid, 
            'node_id' => $this->nodeId, 
            'status' => '1');
        $row1 = $dao->where($map)->find(); // 获取传来的ID
        if (! $row1) {
            $this->error('找不到当前信息!');
        }
        $Ma = array(
            'list_sort_id' => array(
                'lt', 
                $row1['list_sort_id']), 
            'list_id' => $Lid, 
            'node_id' => $this->nodeId, 
            'status' => '1');
        $row2 = $dao->where($Ma)
            ->order('list_sort_id desc')
            ->find(); // 获取交换的ID
        if ($row2 == null) {
            $this->redirect(
                U('edit', array(
                    'id' => $Lid)));
        }
        
        $data1['list_sort_id'] = $row2['list_sort_id'];
        
        $dao->where("id={$row1['id']}")->save($data1); // 更新交换数据
        
        $data2['list_sort_id'] = $row1['list_sort_id'];
        $dao->where("id={$row2['id']}")->save($data2); // 更新交换数据2
        $this->redirect(U('edit', array(
            'id' => $Lid)));
    }

    /**
     * 编辑基础信息
     */
    public function setActBasicInfo() {
        if (IS_POST) {
            $m_id = I('m_id', '');
            $data = I('post.');
            $listModel = D('List');
            try {
                $m_id = $listModel->editBasicInfo($this->node_id, $m_id, $data);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            // 顺便发布到多乐互动专用渠道上
            $bchId = D('MarketCommon')->chPublish($this->nodeId,$m_id);
            if($bchId === false){
                $this->error('发布到渠道失败');
            }
            $this->success(array(
                'm_id' => $m_id));
        }
        $m_id = I('get.m_id');
        try {
            $assignData = D('List')->getListData($this->node_id, $m_id);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->assign('data', $assignData['data']);
        $this->assign('listData', json_encode($assignData['listData']));
        $this->assign('m_id', $m_id);
        // 步骤进度条
        $stepBar = D('CjSet')->getActStepsBar('setActBasicInfo', $m_id, '', 0, 
            array(
                'setActBasicInfo' => '页面设置', 
                'publish' => '活动发布'));
        $this->assign('stepBar', $stepBar);
        $this->display();
    }

    /**
     * 把新增的列表添加到草稿表
     */
    public function addListNew() {
        $data = I('post.');
        $title = I('title');
        $data['title'] = stripslashes($title);
        $text = I('text');
        $text = stripslashes($text);
        $data['text'] = str_replace(
            array(
                "\r\n", 
                "\r", 
                "\n",
                "\t"
            ), '', $text);
        $mId = I('m_id');
        $listModel = D('List');
        try {
            $listModel->addDraft($this->node_id, $mId, $data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success();
    }

    /**
     * ajax返还生成的labelId
     */
    public function ajaxGetLabelId() {
        $batchType = I('batchType');
        $batchId = I('batchId');
        try {
            D('List')->getChannelId($this->node_id, $this->CHANNEL_TYPE, 
                $this->CHANNEL_SNS_TYPE, '列表渠道');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $labelId = $this->getLabelIdAndTryAddLabelInfo($batchId, $batchType);
        $this->success($labelId, '', true);
    }

    /**
     * 清除草稿的某个列表数据
     */
    public function delListDraft() {
        $mId = I('m_id');
        $tempId = I('temp_id');
        $listId = I('list_id');
        try {
            D('List')->delListDraft($this->node_id, $mId, $tempId, $listId);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success();
    }

    /**
     * 清除草稿并跳转
     */
    public function delDraft() {
        $mId = I('m_id');
        D('List')->delDraft($this->node_id, $mId);
        $this->redirect('MarketActive/Activity/MarketList');
    }

    /**
     * 发布
     */
    public function publish() {
        // 查询活动
        $marketingModel = M('tmarketing_info');
        $m_id = I('m_id');
        $marketInfo = $marketingModel->where(
            array(
                'id' => $m_id, 
                'node_id' => $this->node_id, 
                'batch_type' => CommonConst::BATCH_TYPE_LIST))->find();
        if (! $marketInfo) {
            $this->error('参数错误');
        }
        $this->redirect('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_LIST, 
                'selfDefineActionArr' => array(
                    'setActBasicInfo' => '页面设置', 
                    'publish' => '活动发布')));
    }
}
