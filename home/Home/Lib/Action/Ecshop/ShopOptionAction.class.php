<?php

class ShopOptionAction extends BaseAction {

    public $GOODS_TYPE = "31";
    // 小店商品 tmarket类型
    public $BATCH_TYPE = "29";
    // 旺财小店 tmarket类型
    public $img_path = "";

    public $tmp_path = "";

    public function _initialize() {
        parent::_initialize();
        
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $hasEcshop = $this->_hasEcshop();
        if ($hasEcshop != true)
            $this->error("您未开通多宝电商服务模块。");
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
        $marketInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE))->find();
        
        $logoInfo = M('tecshop_banner')->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 1))->find();
        $banInfo = M()->table("tecshop_banner b")->field('b.*,m.name as link_batch_name')
            ->join('tmarketing_info m on m.id=b.link_batch_no')
            ->where(
            array(
                'b.node_id' => $this->node_id, 
                'b.m_id' => $marketInfo['id'], 
                'b.ban_type' => 0))
            ->order('b.order_by asc')
            ->select();
        
        $proInfo = M()->table("tecshop_goods_ex g")->field(
            'g.*,m.name as m_name,b.batch_img')
            ->join('tmarketing_info m on m.id=g.m_id')
            ->join('tbatch_info b on b.m_id=g.m_id')
            ->where(
            array(
                'g.node_id' => $this->node_id, 
                'g.is_commend' => array(
                    'in', 
                    array(
                        1, 
                        2, 
                        3, 
                        4))))
            ->order('g.is_commend')
            ->select();
        
        $proInfo_arr = array_valtokey($proInfo, 'is_commend');
        for ($i = 1; $i < 5; $i ++) {
            if (! isset($proInfo_arr[$i]))
                $proInfo_arr[$i] = array();
        }
        // 获取营销活动名
        $type_name_arr = C('BATCH_TYPE_NAME');
        
        $this->assign('m_id', $marketInfo['id']);
        $this->assign('m_name', $marketInfo['name']);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('banInfo', $banInfo);
        $this->assign('proInfo', $proInfo_arr);
        $this->assign('type_name_arr', $type_name_arr);
        $this->display();
    }
    
    // logo
    public function logo_add() {
        if ($this->isPost()) {
            $m_id = I('post.m_id', null);
            if (! $m_id)
                $this->error('数据错误');
            $m_name = I('post.m_name', null);
            if (! $m_name)
                $this->error('数据错误');
                // 小店名称
            $ret = M('tmarketing_info')->where(
                array(
                    'id' => $m_id))->save(
                array(
                    'name' => $m_name));
            if ($ret === false)
                $this->error('旺财小店名称保存失败');
                
                // logo
            $logo_id = I('post.logo_id', null);
            
            $img = I('post.e_logo_img', null);
            if ($img) {
                /*
                 * $img_move =
                 * move_batch_image($img,$this->BATCH_TYPE,$this->node_id);
                 * if($img_move !==true) $this->error('LOGO图片上传失败！');
                 */
                $img = str_replace('..', '', $img);
                
                if ($logo_id) // 更新
{
                    $ret = M('tecshop_banner')->where(
                        array(
                            'id' => $logo_id))->save(
                        array(
                            'img_url' => $img));
                } else // 新增
{
                    $ret = M('tecshop_banner')->add(
                        array(
                            'm_id' => $m_id, 
                            'node_id' => $this->node_id, 
                            'ban_type' => 1, 
                            'img_url' => $img, 
                            'add_time' => date('YmdHis')));
                }
                if ($ret === false)
                    $this->error("LOGO保存失败");
            }
        }
        redirect(U('index'));
    }
    
    // ban
    public function ban_add() {
        if ($this->isPost()) {
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => '4', 
                    'sns_type' => 46))->getField('id');
            $m_id = I('post.m_id', null);
            if (! $m_id)
                $this->error('数据错误');
            M()->startTrans();
            // 循环处理
            for ($i = 1; $i < 4; $i ++) {
                $banInfo = M('tecshop_banner')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'm_id' => $m_id, 
                        'ban_type' => 0, 
                        'order_by' => $i))->find();
                $data = array();
                $ad_link = I('post.ad_link' . $i, null);
                if ($ad_link == 1 || $ad_link == 2) {
                    $link_type = 2;
                    $batch_id = I('post.batch_id' . $i, null);
                    $batch_type = I('post.batch_type' . $i, null);
                    $data['link_type'] = $link_type;
                    $data['link_batch_no'] = $batch_id;
                    $data['link_batch_type'] = $batch_type;
                    $label_id = $this->get_batch_channel($batch_id, $channel_id, 
                        $batch_type);
                    $data['link_url'] = U('Label/Label/index', 
                        array(
                            'id' => $label_id), '', '', true);
                } elseif ($ad_link == 3) {
                    $link_type = 1;
                    $link_url = I('post.link_url' . $i, null);
                    $data['link_type'] = $link_type;
                    $data['link_url'] = $link_url;
                    $data['link_batch_no'] = '';
                    $data['link_batch_type'] = '';
                }
                // 处理图片
                $img = I('post.e_ban_img' . $i, null);
                if (! $img && $data['link_url']) {
                    M()->rollback();
                    $this->error('BANNER' . $i . '需上传图片', 
                        array(
                            '返回' => "javascript:history.go(-1)"));
                }
                if ($img != $banInfo['img_url']) {
                    
                    $img = str_replace('..', '', $img);
                    $data['img_url'] = $img;
                }
                
                if ($banInfo) { // 更新
                    $ret = M('tecshop_banner')->where(
                        array(
                            'id' => $banInfo['id']))->save($data);
                } else {
                    // 新增
                    if ($img) {
                        $data['m_id'] = $m_id;
                        $data['ban_type'] = 0;
                        $data['node_id'] = $this->node_id;
                        $data['order_by'] = $i;
                        $data['add_time'] = date('YmdHis');
                        $ret = M('tecshop_banner')->add($data);
                    }
                }
                if ($ret === false) {
                    M()->rollback();
                    $this->error("BANNER保存失败");
                }
            }
            M()->commit();
        }
        redirect(U('index'));
    }
    
    // product
    public function product_add() {
        if ($this->isPost()) {
            $m_id = I('post.m_id', null);
            if (! $m_id)
                $this->error('数据错误');
                
                // product
            for ($i = 1; $i < 5; $i ++) {
                $product_mid = I('post.commend_goods1' . $i, null);
                if ($product_mid) {
                    $result = M('tecshop_goods_ex')->where(
                        array(
                            'node_id' => $this->node_id, 
                            'is_commend' => $i))->save(
                        array(
                            'is_commend' => 9));
                    $result = M('tecshop_goods_ex')->where(
                        array(
                            'node_id' => $this->node_id, 
                            'm_id' => $product_mid))->save(
                        array(
                            'is_commend' => $i));
                    if ($result === false) {
                        $this->error('保存失败');
                    }
                }
            }
        }
        redirect(U('index'));
    }

    public function ban_del() {
        $id = I('get.id', null);
        $m_id = I('get.m_id', null);
        if (! $id || ! $m_id)
            $this->error('数据错误');
        
        $banInfo = M('tecshop_banner')->where(
            array(
                'm_id' => $m_id, 
                'ban_type' => '0', 
                'node_id' => $this->node_id, 
                'id' => $id))->find();
        if (! $banInfo)
            $this->error('数据不存在');
        
        $result = M('tecshop_banner')->where(
            array(
                'm_id' => $m_id, 
                'ban_type' => '0', 
                'node_id' => $this->node_id, 
                'id' => $id))->delete();
        if ($result === false)
            $this->error('BANNER删除失败');
        $ret = M('tecshop_banner')->where(
            array(
                'm_id' => $m_id, 
                'ban_type' => '0', 
                'node_id' => $this->node_id, 
                'order_by' => array(
                    'GT', 
                    $banInfo['order_by'])))->setDec('order_by', '1');
        if ($ret === false)
            $this->error('BANNER重排序失败');
        
        $this->success('BANNER删除成功');
        // redirect(U('index'));
    }

    public function product_del() {
        $id = I('get.id', null);
        if (! $id)
            $this->error('数据错误');
        $proInfo = M('tecshop_goods_ex')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))->find();
        if (! $proInfo)
            $this->error('数据不存在');
        
        $result = M('tecshop_goods_ex')->where(
            array(
                'id' => $proInfo['id'], 
                'node_id' => $this->node_id))->save(
            array(
                'is_commend' => 9));
        if ($result === false)
            $this->error('推荐商品删除失败');
        $this->success('推荐商品删除成功');
    }
    // 获取活动发布渠道的id
    public function get_batch_channel($batch_id, $channel_id, $batch_type) {
        if (! $channel_id)
            $this->error('渠道号获取失败');
        
        $batch_channel_model = M('tbatch_channel');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id, 
            'batch_type' => $batch_type);
        $batch_channel_count = $batch_channel_model->where($map)->count();
        if ($batch_channel_count < 1) {
            $data_arr = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            $query = $batch_channel_model->add($data_arr);
            if ($query) {
                node_log('创建标签成功');
                return $query;
            } else {
                node_log('创建标签失败');
                $this->error('创建标签失败');
                return false;
            }
        } else {
            $id = $batch_channel_model->where($map)
                ->limit('1')
                ->getField('id');
            return $id;
        }
    }
}