<?php
//
class DistriAction extends BaseAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }
    // 我分销出去的
    public function index() {
        import("ORG.Util.Page");
        $nodeid = $this->nodeId;
        $goodsname = I('name');
        $begintime = I('begintime');
        $endtime = I('endtime');
        $status = I('status');
        $nodename = I('nodename');
        $wh = array(
            'g.purchase_node_id' => $nodeid, 
            'g.source' => 4, 
            'c.node_id' => $this->node_id);
        if ($goodsname != '') {
            $wh['g.goods_name'] = array(
                'like', 
                "%$goodsname%");
        }
        
        if ($begintime != '' && $endtime == '') {
            $btime = $begintime . '000000';
            $wh['g.add_time'] = array(
                'egt', 
                $btime);
        }
        if ($endtime != '' && $begintime == '') {
            $etime = $endtime . '235959';
            $wh['g.add_time'] = array(
                'elt', 
                $etime);
        }
        if ($begintime != '' && $endtime != '') {
            $btime = $begintime . '000000';
            $etime = $endtime . '235959';
            $wh['g.add_time'] = array(
                array(
                    'egt', 
                    $btime), 
                array(
                    'elt', 
                    $etime));
        }
        
        if ($status != "") {
            $wh['g.status'] = $status;
        }
        
        if ($nodename != '') {
            $wh['n.node_name'] = array(
                'like', 
                "%{$nodename}%");
        }
        // $list=M()->table('tgoods_info')
        // ->field('goods_id')
        // ->where(array('node_id'=>$nodeid))
        // ->select();
        
        // // 匹配存在项
        // foreach($list as $v)
        // {
        // $str1=$v['goods_id'];
        // $str.=","."$str1";
        // }
        // $str=substr($str,1);
        // $wh['source']=4;
        // $wh['purchase_goods_id']=array('in',$str);
        // $count=M('tgoods_info g')
        // ->where($wh)
        // ->field('g.*,n.node_name')
        // ->join('tnode_info n ON g.node_id=n.node_id')
        // ->count();
        // $p=new Page($count,6);
        // foreach($_REQUEST as $key=>$val) {
        // $p->parameter .= "$key=".urlencode($val)."&";
        // }
        // $index=M('tgoods_info g')
        // ->where($wh)
        // ->field('g.*,n.node_name')
        // ->join('tnode_info n ON g.node_id=n.node_id')
        // ->limit($p->firstRow.','.$p->listRows)
        // ->order('add_time desc')
        // ->select();
        
        $count = M()->table('tgoods_info g')
            ->where($wh)
            ->join('tnode_info n ON g.node_id=n.node_id')
            ->join('tsale_relation c on g.node_id=c.relation_node_id')
            ->order('g.add_time desc')
            ->count();
        $p = new Page($count, 6);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $index = M()->table('tgoods_info g')
            ->where($wh)
            ->field('g.*,n.node_name,c.control_type,c.control_flag')
            ->join('tnode_info n ON g.node_id=n.node_id')
            ->join('tsale_relation c on g.node_id=c.relation_node_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('g.add_time desc')
            ->select();
        $contrlClass = array(
            '0' => '否', 
            '1' => '是');
        $batchClass = array(
            '1' => '发码', 
            '2' => '验码');
        $statusClass = array(
            '0' => '正常', 
            '1' => '过期');
        $page = $p->show();
        $this->assign('contrlClass', $contrlClass);
        $this->assign('batchClass', $batchClass);
        $this->assign('statusClass', $statusClass);
        $this->assign('ind', $index);
        $this->assign('post', $_REQUEST);
        $this->assign('page', $page);
        $this->display();
    }
    
    // 分销给我的
    public function giveme() {
        import("ORG.Util.Page");
        $node_id = $this->nodeId;
        $map = array(
            'g.node_id' => $node_id, 
            'g.source' => 4);
        $goods_name = I('goods_name');
        $node_name = I('node_name');
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        if ($goods_name != '') {
            $map['g.goods_name'] = array(
                'like', 
                "%$goods_name%");
        }
        if ($node_name != '') {
            $map['n.node_name'] = array(
                'like', 
                "%$node_name%");
        }
        if ($begin_time != '') {
            $btime = $begin_time . "000000";
            $map['g.add_time'] = array(
                'egt', 
                $btime);
        }
        if ($end_time != '') {
            $etime = $end_time . "235959";
            $map['g.add_time '] = array(
                'elt', 
                $etime);
        }
        $count = M()->table('tgoods_info g')
            ->where($map)
            ->count();
        $p = new Page($count, 5);
        $list = M()->table("tgoods_info g")->field('g.*,n.node_name')
            ->join('tgoods_info d ON g.purchase_goods_id=d.goods_id')
            ->join('tnode_info n ON d.node_id=n.node_id')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('add_time desc')
            ->select();
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign('page', $page);
        if (I('type') == 'numGoods') {
            $this->display('./Home/Tpl/WangcaiPc/NumGoods_giveme.html');
        } else {
            $this->display();
        }
    }
    // 编辑
    public function otheredit() {
        $goodsid = I('goodsid');
        $arr = M()->table('tgoods_info g')
            ->where(array(
            'g.goods_id' => $goodsid))
            ->field('g.*,n.node_name')
            ->join('tnode_info n ON g.node_id=n.node_id')
            ->find();
        $time = M('tgoods_info')->where(
            "goods_id='{$arr['purchase_goods_id']}'")
            ->field('begin_time,end_time,storage_num')
            ->find();
        $this->assign('time', $time);
        $this->assign('goodsData', $arr);
        $this->display();
    }
    
    // 营销品图片移动
    public function moveImage($imageName) {
        $oldImagePath = $this->tempImagePath;
        $newImagePath = $this->numGoodsImagePath;
        // 图片是否存在
        if (! is_file($oldImagePath . '/' . $imageName)) {
            $this->error('营销品图片未找到');
        }
        if (! is_dir($newImagePath)) {
            if (! mkdir($newImagePath, 0777))
                $this->error('目录创建失败');
        }
        Log::write(
            "picpicpic:" . $oldImagePath . '/' . $imageName . ":::" .
                 $newImagePath . '/' . $imageName);
        $flag = copy($oldImagePath . '/' . $imageName, 
            $newImagePath . '/' . $imageName);
        if (! $flag)
            $this->error('图片移动失败');
        return true;
    }
    // 保存修改
    public function saveDis() {
        $goods_id = I('goods_id'); // 已经产生分销的代金券，不是原代金券
        $settle_price = I('goods_amt');
        $storage_num = I('goods_num');
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        $goodsImg = I('ai_img_resp');
        $data = array();
        if ($goodsImg != '') {
            if (is_file($this->tempImagePath . '/' . $goodsImg)) {
                $this->moveImage($goodsImg);
                $goods_images = 'NumGoods/' . $this->nodeId . '/' . $goodsImg;
            }
            $data['goods_image'] = $goods_images;
        }
        
        if ($settle_price != '' && $settle_price > 0) {
            $data['settle_price'] = $settle_price;
        }
        if ($storage_num != '' && $storage_num != '不限' && $storage_num > 0) {
            if (! check_str($storage_num, 
                array(
                    'strtype' => 'number'), $error)) {
                $this->error("分销数量{$error}");
            }
            $purchase_goods_id = M('tgoods_info')->where(
                array(
                    'goods_id' => $goods_id))->getfield('purchase_goods_id');
            $remin = M('tgoods_info')->where(
                array(
                    'goods_id' => $purchase_goods_id))->getField('remain_num');
            // 如果原代金券的库存为限制，则补上原库存
            if ($remin != - 1) {
                if ($storage_num > $remin) {
                    echo "<script>alert('分销数量不能大于代金券的库存！');window.history.go(-1);</script>";
                    die();
                }
                // 将原库存补上
                M()->startTrans();
                $couder = M('tgoods_info')->where(
                    array(
                        'goods_id' => $goods_id))->getField('storage_num');
                if ($couder != - 1) {
                    $tase = array();
                    $tase['remain_num'] += $couder;
                    $cgoods_id = M('tgoods_info')->where(
                        array(
                            'goods_id' => $goods_id))->getField(
                        'purchase_goods_id');
                    $sar = M('tgoods_info')->where(
                        array(
                            'goods_id' => $cgoods_id))->save($tase);
                }
            }
            $data['storage_num'] = $storage_num;
            $data['remain_num'] = $storage_num;
        }
        if ($begin_time != '') {
            $data['begin_time'] = $begin_time . "000000";
        }
        if ($end_time != '') {
            $data['end_time'] = $end_time . "235959";
            if ($end_time > date('Ymd', time())) {
                $data['status'] = '0';
            }
        }
        $stat = M('tgoods_info')->where(
            array(
                'goods_id' => $goods_id))->save($data);
        if ($stat) {
            // 再将原剩余库存减掉当前分销的数量
            $jiankc = array();
            $jiankc['remain_num'] -= $storage_num;
            // 取出分分销前的原代金券的goods_id
            $gid = M('tgoods_info')->where(
                array(
                    'goods_id' => $goods_id))->getField('purchase_goods_id');
            $troyer = M('tgoods_info')->where(
                array(
                    'goods_id' => $gid))->save($jiankc);
            if ($troyer) {
                M()->commit();
                $this->success("修改成功！", U('index'));
            } else {
                M()->rollback();
                $this->error("修改失败！", U('index'));
            }
            // echo
            // "<script>alert('修改成功！');parent.art.dialog.list['comd'].close();</script>";
        } else {
            M()->rollback();
            $this->error("修改失败！", U('index'));
            // echo
            // "<script>alert('修改失败！');parent.art.dialog.list['comd'].close();</script>";
        }
    }
    // 详情
    public function otherdetails() {
        $node_id = $this->node_id;
        $goods_id = I('goodsid');
        // 分销卡券信息
        $goodsData = M('tgoods_info')->where("goods_id='{$goods_id}'")->find();
        // 卡券有效期
        $time = M('tgoods_info')->where(
            "goods_id='{$goodsData['purchase_goods_id']}'")
            ->field('begin_time,end_time,node_id')
            ->find();
        // 供货商信息
        $supplierInfo = M()->table("tsale_relation s")->field('s.*,n.node_name')
            ->join('tnode_info n ON s.node_id=n.node_id')
            ->where(
            "s.node_id={$time['node_id']} and s.relation_node_id='{$this->nodeId}'")
            ->find();
        // 采购方信息
        $partnerList = M()->table("tsale_relation a")->field('a.*,b.node_name')
            ->join('tnode_info b on a.relation_node_id=b.node_id')
            ->where(
            "a.node_id='{$this->node_id}' and a.relation_node_id={$goodsData['node_id']}")
            ->find();
        $goodsType = D('Goods')->getGoodsType();
        $controlType = array(
            '1' => '按采购方使用量', 
            '2' => '按供货方验证量'); // 协议清算方式
        $this->assign('controlType', $controlType);
        $this->assign('time', $time);
        $this->assign('goodsData', $goodsData);
        $this->assign('supplierInfo', $supplierInfo);
        $this->assign('partnerList', $partnerList);
        $this->assign('goodsType', $goodsType);
        if (I('from_to') == 'tome') {
            $this->display('details');
        } elseif (I('from_to') == 'tomeNumgoods') {
            $this->display('./Home/Tpl/WangcaiPc/NumGoods_toDetail.html');
        } else {
            $this->display();
        }
    }
}
