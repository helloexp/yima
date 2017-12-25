<?php

/**
 * 数据下载
 */
class DownDataAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }
    // 卡券发放明细下载
    public function index() {
        $requestInfo = I('request.'); // 获取请求数据
        self::getCardSend($requestInfo,I('get.down'));
        $this->display();
    }
    
    // 卡券核销明细
    public function goodsVerifyDown() {
        $requestInfo = I('request.'); // 获取请求数据
        self::getCardVerify($requestInfo,I('get.down'));
        $this->display();
    }
    
    // 营销活动明细
    public function batchDown() {
        $requestInfo = I('request.'); // 获取请求数据
        self::getMarketDown($requestInfo,I('get.down'));
        $this->display();
        exit;
        
    }

    /***************************以下为私有方法*************************/

    private function getCardSend($requestInfo,$down){
        $map = array();
        // 卡券类型及名称
        $goodsTypeArr = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券');
        $cardType = implode(array_keys($goodsTypeArr), ',');
        $goodsType = get_val($requestInfo,'goods_type');
        $goodsName = get_val($requestInfo,'goods_name');
        $map['e.goods_type'] =  (!empty($goodsType)||$goodsType == '0') ? $goodsType : array('in',$cardType);
        !$goodsName or $map['e.goods_name'] = array('like','%'.$goodsName.'%');

        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-90 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['a.trans_time'][] = array('EGT',$beginTime.'000000');
        $map['a.trans_time'][] = array('ELT',$endTime.'235959');
        // 其他条件
        $map['a.node_id']    = $this->nodeId;
        $map['a.trans_type'] = '0001';

        $bTraceObj = M()->table("tbarcode_trace a")
                ->join('tgoods_info e ON a.goods_id=e.goods_id')
                ->where($map);
        $field = "e.goods_name,e.goods_type,a.phone_no,a.trans_time";
        if($down){
            $list = $bTraceObj->field($field)->order("a.id desc")->select();
            if(!empty($list)){
                foreach ($list as $k => $v) {
                    $list[$k]['goods_type'] = $goodsTypeArr[$v['goods_type']];
                    $list[$k]['trans_time'] = date('Y-m-d H:i:s',strtotime($v['trans_time']));
                }
            }
            $cols_arr = array(
                'goods_name' => '卡券', 
                'goods_type' => '类型', 
                'phone_no'   => '手机', 
                'trans_time' => '发送时间'
                );
            parent::csv_lead("卡券发放明细",$cols_arr,$list);
        }
        $bTraceObjClone = clone $bTraceObj;
        $count = $bTraceObj->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $list = $bTraceObjClone->field($field)->order("a.id desc")
            ->limit($Page->firstRow,$Page->listRows)->select();
        $this->assign('goods_type', $goodsType);
        $this->assign('begin_time', $beginTime);
        $this->assign('end_time', $endTime);
        $this->assign('goods_name', $goodsName);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('goodsTypeArr', $goodsTypeArr);
        $this->assign('list', $list);
    }

    private function getCardVerify($requestInfo,$down){
        $map = array();
        // 卡券类型及名称
        $goodsTypeArr = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券');
        $cardType = implode(array_keys($goodsTypeArr), ',');
        $goodsType = get_val($requestInfo,'goods_type');
        $goodsName = get_val($requestInfo,'goods_name');
        $map['e.goods_type'] =  (!empty($goodsType)||$goodsType == '0') ? $goodsType : array('in',$cardType);
        !$goodsName or $map['e.goods_name'] = array('like','%'.$goodsName.'%');
        // 可用门店列表
        $storeArr = M('tstore_info')->field('store_id,store_name')
                ->where(['node_id' => $this->nodeId,'status' => '0'])
                ->select();
        !$store_id or $map['d.store_id'] = $store_id;
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-90 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['a.trans_time'][] = array('EGT',$beginTime.'000000');
        $map['a.trans_time'][] = array('ELT',$endTime.'235959');
        // 其他条件
        $map['a.node_id']    = $this->nodeId;
        $map['a.trans_type'] = '0';
        $map['a.status']     = '0';

        $pTraceObj = M()->table("tpos_trace a")
                ->join('tpos_info c ON a.pos_id=c.pos_id')
                ->join('tstore_info d ON c.store_id = d.store_id')
                ->join('tgoods_info e ON a.goods_id=e.goods_id')
                ->where($map);
        $field = "e.goods_name,d.store_name,e.goods_type,a.pos_id,a.trans_time";
        if($down){
            $list = $pTraceObj->field($field)->order("a.id desc")->select();
            if(!empty($list)){
                foreach ($list as $k => $v) {
                    $list[$k]['goods_type'] = $goodsTypeArr[$v['goods_type']];
                    $list[$k]['trans_time'] = date('Y-m-d H:i:s',strtotime($v['trans_time']));
                }
            }
            $cols_arr = array(
                'goods_name' => '卡券', 
                'goods_type' => '类型', 
                'store_name' => '门店', 
                'pos_id'     => '终端号', 
                'trans_time' => '发送时间'
                );
            parent::csv_lead("卡券核销明细",$cols_arr,$list);
        }
        $pTraceObjClone = clone $pTraceObj;
        $count = $pTraceObj->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $list = $pTraceObjClone->field($field)->order("a.id desc")
            ->limit($Page->firstRow,$Page->listRows)->select();

        $this->assign('goods_type', $goodsType);
        $this->assign('begin_time', $beginTime);
        $this->assign('end_time', $endTime);
        $this->assign('store_id', $storeId);
        $this->assign('goods_name', $goodsName);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('storeArr', $storeArr);
        $this->assign('goodsTypeArr', $goodsTypeArr);
        $this->assign('list', $list);
    }
    public function getMarketDown($requestInfo,$down){
        $map = array();
        // 获取活动类型
        $batchType    = get_val($requestInfo,'batchType');
        $batchName    = get_val($requestInfo,'batch_name');
        $batchTypeArr = self::getBatchType();
        $batchTypeStr = implode(array_keys($batchTypeArr), ',');
        $map['batch_type'] = $batchType ? $batchType : array('in',$batchTypeStr);
        !$batchName or $map['name'] = array('like','%'.$batchName.'%');
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time'); 
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-90 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['add_time'][] = array('EGT',$beginTime.'000000');
        $map['add_time'][] = array('ELT',$endTime.'235959');
        // 其他条件
        $map['node_id']    = $this->nodeId;
        
        $statusArr = array(
            '1' => '正常', 
            '2' => '停用');

        $marketObj = M('tmarketing_info')->where($map);
        $field = "name,batch_type,add_time,start_time,end_time,status,click_count,send_count";
        if($down){
            $list = $marketObj->field($field)->order("add_time desc")->select();
            if(!empty($list)){
                foreach ($list as $k => $v) {
                    $list[$k]['status']     = $statusArr[$v['status']];
                    $list[$k]['batch_type'] = $batchTypeArr[$v['batch_type']];
                    $list[$k]['add_time']   = date('Y-m-d H:i:s',strtotime($v['add_time']));
                    $list[$k]['start_time'] = date('Y-m-d H:i:s',strtotime($v['start_time']));
                    $list[$k]['end_time']   = date('Y-m-d H:i:s',strtotime($v['end_time']));
                }
            }
            $cols_arr = array(
                'name'        => '活动名称', 
                'batch_type'  => '活动类型', 
                'add_time'    => '创建时间', 
                'start_time'  => '开始时间', 
                'end_time'    => '结束时间',
                'status'      => '状态',
                'click_count' => '访问量',
                'send_count'  => '发码量',
                );
            parent::csv_lead("活动效果数据",$cols_arr,$list);
        }
        $marketObjClone = clone $marketObj;
        $count = $marketObj->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $list = $marketObjClone->field($field)->order("add_time desc")
            ->limit($Page->firstRow,$Page->listRows)->select();
        if(!empty($list)){
            foreach ($list as $k => $v) {
                $list[$k]['status']     = $statusArr[$v['status']];
                $list[$k]['batch_type'] = $batchTypeArr[$v['batch_type']];
                $list[$k]['add_time']   = date('Y-m-d H:i:s',strtotime($v['add_time']));
                $list[$k]['start_time'] = date('Y-m-d',strtotime($v['start_time']));
                $list[$k]['end_time']   = date('Y-m-d',strtotime($v['end_time']));
            }
        }
        $this->assign('begin_time', $beginTime);
        $this->assign('end_time', $endTime);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('storeArr', $storeArr);
        $this->assign('batch_name', $batchName);
        $this->assign('batchType', $batchType);
        $this->assign('batchTypeArr', $batchTypeArr);
        $this->assign('list', $list);
    }

    // 获取活动类型
    private function getBatchType(){
        $map = array();
        $map['status'] = '1';
        $info = M('tmarketing_active')->field('batch_type,batch_name')->where($map)->select();
        $batchType = array();
        foreach ($info as $v) {
            $batchType[$v['batch_type']] = $v['batch_name'];
            // 只有翼码市场部的可以看到注册有礼
            if($v['batch_type'] == '32' && $this->node_id != '00014056'){
                unset($batchType[$v['batch_type']]);
            }
        }
        return $batchType;
    }
}
