<?php
// 游戏
class GameAction extends BaseAction {

    public $batch_type = '1';

    public $node_id = '';

    public function _initialize() {
        $this->_checkLogin();
        $user = D('UserSess', 'Service');
        $this->node_id = $user->getUserInfo('node_id');
    }
    // 首页
    public function index() {
        $batchModel = M('tgame_batch');
        $batch_id = $batchModel->where(
            array(
                'node_id' => $this->node_id))->getField('id');
        if (! $batch_id) {
            $this->assign('errorMsg', '还未创建活动');
            $this->display();
            exit();
        }
        $model = M('tbatch_channel');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->batch_type);
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = M()->Table('tbatch_channel a')
            ->field(
            'a.batch_type,a.channel_id,a.id,a.click_count,a.send_count,a.add_time,a.defined_id,b.name')
            ->join('tchannel b ON a.channel_id=b.id')
            ->where(
            "a.batch_type= '{$this->batch_type}' and  a.node_id='{$this->node_id}'")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('batch_id', $batch_id);
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    // 奖品设置页
    public function prizeSet() {
        $model = M('tgame_batch');
        $query = $model->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $query)
            $this->error('还未创建活动数据！');
        
        $batch_map = array(
            'node_id' => $this->node_id, 
            'batch_no' => $query['wc_batch_no']);
        $batch_model = M('tbatch_info');
        $batch_name = $batch_model->where($batch_map)->getField(
            'batch_short_name');
        $this->assign('row', $query);
        $this->assign('batch_name', $batch_name);
        $this->display();
    }
    
    // 奖品设置提交页
    public function prizeSetSubmit() {
        $model = M('tgame_batch');
        
        $map = array(
            'node_id' => $this->node_id);
        $data = $_POST;
        /*
         * if($data['agree'] != '1'){ $this->error('请选择玩游戏赢大奖签约！'); }
         */
        if ($data['prize_type'] == '2') {
            if ($data['day_goods_count'] == '') {
                $this->error('每日奖品限量不能为空');
            }
            if ($data['goods_count'] == '') {
                $this->error('奖品总数不能为空');
            }
            if ($data['day_goods_count'] == '') {
                $this->error('中奖概率不能为空');
            }
        }
        $data_arr = array();
        if (! $model->create()) {
            $this->error($model->getError());
        }
        $data_arr['prize_type'] = $data['prize_type'];
        if ($data['prize_type'] == '2') {
            $data_arr['day_goods_count'] = $data['day_goods_count'];
            $data_arr['goods_count'] = $data['goods_count'];
            $data_arr['chance'] = $data['chance'];
            $data_arr['wc_batch_no'] = $data['wc_batch_no'];
        }
        $execute = $model->where($map)->save($data_arr);
        if (! $execute)
            $this->error('系统错误！');
        else
            $this->success('操作成功', U('index'));
    }
    
    // 发布到渠道
    public function channelBind() {
        $model = M('tchannel');
        $map = array(
            'node_id' => $this->node_id);
        
        $channel_arr = C('CHANNEL_TYPE');
        $query_arr = array();
        foreach ($channel_arr as $k => $v) {
            $map['type'] = $k;
            $query_arr[$k] = $model->where($map)->select();
            if (! $query_arr[$k]) {
                unset($query_arr[$k]);
            }
        }
        if (! $query_arr) {
            $model->error('请先添加渠道！');
        }
        $this->assign('channel_arr', $channel_arr);
        $this->assign('query_arr', $query_arr);
        $this->display();
    }
    // 发布到渠道提交
    public function channelBindSubmit() {
        $channel = $_POST['channel'];
        $channel_arr = C('CHANNEL_TYPE');
        if (! $channel) {
            $this->error('请选择渠道');
        }
        
        // 游戏活动
        $model = M('tgame_batch');
        $map = array(
            'node_id' => $this->node_id);
        $batch_id = $model->where($map)->getField('id');
        if (! $batch_id) {
            $this->error('未创建活动！');
        }
        
        $data = array();
        $exec = M('tbatch_channel');
        $exec->startTrans();
        $search_map = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->batch_type);
        // 绑定成功渠道id
        $result_arr = array();
        foreach ($channel as $k => $v) {
            $search_map['channel_id'] = $v;
            $is_exits = $exec->where($search_map)->select();
            if ($is_exits)
                continue;
            $data['batch_type'] = $this->batch_type;
            $data['batch_id'] = $batch_id;
            $data['channel_id'] = $v;
            $data['add_time'] = Date('YmdHis');
            $data['node_id'] = $this->node_id;
            $query = $exec->data($data)->add();
            if (! $query) {
                $exec->rollback();
                $this->error('系统错误！');
            }
            $result_arr[] = $v;
        }
        $query = $exec->commit();
        $carr = array();
        $succ_arr = array();
        if ($result_arr) {
            // 显示页面
            $search_map['channel_id'] = array(
                'in', 
                $result_arr);
            $succ_arr = $exec->where($search_map)
                ->field('id,channel_id')
                ->select();
            
            // 渠道详情
            $m_model = M('tchannel');
            $m_map = array(
                'node_id' => $this->node_id, 
                'id' => array(
                    'in', 
                    $result_arr));
            $m_arr = $m_model->where($m_map)->select();
            foreach ($m_arr as $k => $v) {
                $carr[$v['id']] = $v;
            }
        }
        $this->assign('succ_arr', $succ_arr);
        $this->assign('carr', $carr);
        $this->display('succMsg');
    }
}