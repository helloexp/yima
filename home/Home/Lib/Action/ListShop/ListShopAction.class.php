<?php

/**
 * 门店导航
 *
 * @author 陈赛锋
 */
class ListShopAction extends BaseAction {

    const BATCH_TYPE_SHOPGPS = 17;

    public function _initialize() {
        parent::_initialize();
        $this->assign('BATCH_TYPE_SHOPGPS', self::BATCH_TYPE_SHOPGPS);
    }
    
    // 单独定位
    function plocation() {
        $this->assign('slng', $_REQUEST['lng']);
        $this->assign('slat', $_REQUEST['lat']);
        $this->assign('lng', $_REQUEST['endLng']);
        $this->assign('lat', $_REQUEST['endLat']);
        $this->assign('cityName', $_REQUEST['cityName']);
        $this->assign('des_city', $_REQUEST['des_city']);
        $this->display();
    }

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_SHOPGPS);
        
        $list = $model->where($map)
            ->limit('1')
            ->select();
        $this->assign('list', $list);
        $this->display(); // 输出模板
    }
    
    // 新增门店导航
    public function add() {
        $data = I('request.');
        // 配置默认营销活动
        $batch_arr = $this->addbatch(); // 门店导航默认营销活动
                                        
        // 获取门店数据
        $model = M('tstore_info');
        
        // 按机构树数据隔离
        $where = "a.node_id in (" . $this->nodeIn() . ")";
        $node_id = I('node_id');
        // 按机构号查询
        if ($node_id && $node_id != $this->nodeId) {
            $where .= " and a.node_id in (" . $this->nodeIn($node_id) . ")";
        }
        // 门店名查询
        if (I('store_name') != '') {
            $where .= " and a.store_name like '%" . I('store_name') . "%'";
        }
        if (I('province') != '') {
            $where .= " and a.province_code = '" . I('province') . "'";
        }
        
        if (I('city') != '') {
            $where .= " and a.city_code = '" . I('city') . "'";
        }
        
        if (I('town') != '') {
            $where .= " and a.town_code = '" . I('town') . "'";
        }
        import('ORG.Util.Page'); // 导入分页类
        $count = $model->table('tstore_info a')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $queryList = $model->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('a.*,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 获取营销活动号
        if ($data['batch_id']) {
            $map['batch_id'] = $data['batch_id'];
        } else {
            $map['batch_id'] = $batch_arr['batch_id'];
        }
        
        // 获取当前机构的所有下级机构
        $nodeList = M('tnode_info')->field('node_id,node_name,parent_id')
            ->where("node_id IN({$this->nodeIn()})")
            ->select();
        
        $this->assign('node_list', $nodeList);
        $this->assign('queryList', $queryList);
        $this->assign('batch_id', $map['batch_id']);
        $this->assign('batch_name', $batch_arr['batch_name']);
        $this->assign('node_id', $this->node_id);
        $this->assign('pageShow', $pageShow);
        $this->display(); // 输出模板
    }

    private function addbatch() {
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_SHOPGPS);
        $batch_data = $batch_model->where($onemap)->find();
        if (! $batch_data) {
            // 营销活动不存在则新增
            $batch_arr = array(
                'batch_type' => self::BATCH_TYPE_SHOPGPS, 
                'name' => '门店导航默认营销活动', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+10 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $query = $batch_model->add($batch_arr);
            if (! $query) {
                $this->error('添加门店导航默认营销活动失败');
            }
            return array(
                'batch_id' => $query, 
                'batch_name' => '门店导航默认营销活动', 
                'click_count' => 0);
        } else {
            return array(
                'batch_id' => $batch_data['id'], 
                'batch_name' => $batch_data['name'], 
                'click_count' => $batch_data['click_count']);
        }
    }
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in ( {$this->nodeIn()} ) AND id='{$batchId}'")->find();
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
            node_log('门店导航活动状态更改|活动id:' . $batchId);
            $this->success('更新成功', 
                array(
                    '返回' => U('ListShop/index')));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 更新门店gps_flag
    public function UpdateGps() {
        $data = I('request.');
        if (! $data['store_id_list']) {
            $this->error('请选择需要导航的门店');
        }
        // 开启事务
        M()->startTrans();
        $tstore_model = M('tstore_info');
        $onemap = array(
            'node_id' => $this->node_id);
        $data_arr = array(
            gps_flag => '1');
        // 全门店导航
        if ($data['store_id'] == 'all') {
            $flag = $tstore_model->where($onemap)->save($data_arr);
            if ($flag === false) {
                M()->rollback();
                $this->error('更新导航门店失败1');
            }
        } else {
            $onemap['id'] = array(
                'in', 
                $data['store_id']);
            // 更新当前页面所有门店为不导航
            $flag = $tstore_model->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => array(
                        'in', 
                        $data['store_id_list'])))->save(
                array(
                    'gps_flag' => '0'));
            if ($flag === false) {
                M()->rollback();
                $this->error('更新导航门店失败2');
            }
            // 更新选择门店为导航
            $flag = $tstore_model->where($onemap)->save($data_arr);
            if ($flag === false) {
                M()->rollback();
                $this->error('更新导航门店失败3');
            }
        }
        
        M()->commit();
        $message = array(
            'respId' => 0, 
            'respStr' => '更新成功');
        $this->success($message);
    }

    public function edit() {
        if ($this->batch_type != '8')
            $this->error('错误活动类型！');
        
        $id = $this->id;
        $batch_id = $this->batch_id;
        
        // 访问量
        import('@.Vendor.ClickStat');
        $opt = new ClickStat();
        $opt->updateStat($id);
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(array(
            'id' => $batch_id))->find();
        if (! $row)
            $this->error('未查询到活动！');
        
        $map = array(
            'list_id' => $row['id']);
        
        $map['status'] = array(
            'eq', 
            1);
        
        $model = M('tlist_batch_list');
        
        // 当前页
        $page = I('page');
        $page = empty($page) ? 1 : (int) $page;
        // 下一页
        $next_page = $page + 1;
        // 上一页
        $pre_page = $page - 1 == 0 ? 1 : $page - 1;
        // 每页条数
        $page_count = 10;
        // 初始行
        $firstRow = $page_count * ($page - 1);
        // 总记录数
        $mapcount = $model->where($map)->count();
        // 总页数
        $pageall_count = ceil($mapcount / $page_count);
        
        $next_url = U('ListBatch/index', 
            array(
                'id' => $id, 
                'page' => $page + 1));
        $pre_url = U('ListBatch/index', 
            array(
                'id' => $id, 
                'page' => $page - 1));
        $list = $model->where($map)
            ->order('id DESC')
            ->limit($firstRow . ',' . $page_count)
            ->select();
        $this->assign('id', $id);
        $this->assign('next_url', $next_url);
        $this->assign('pre_url', $pre_url);
        $this->assign('page', $page);
        $this->assign('mapcount', $mapcount);
        $this->assign('pageall_count', $pageall_count);
        $this->assign('list', $list);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        $this->display(); // 输出模板
    }
}