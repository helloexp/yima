<?php
/**
 * 采购需求模块
 * @author bao
 *
 */
class ProcurementAction extends HallBaseAction
{
    public function _initialize()
    {
        parent::_initialize ();
        $actionName = array (
            'index' 
        ); // 不需要登陆的模块
        if (! in_array ( ACTION_NAME, $actionName )) {
            $userService = D ( 'UserSess', 'Service' );
            if (! $userService->isLogin ()) { // 登陆
                redirect ( U ( 'Home/Login/showLogin', array (
                    'fromurl' => urlencode ( U ( 'Hall/' . MODULE_NAME . '/' . ACTION_NAME, '', true, false, true ) ) 
                ), true, false, true ) );
            }
        }
    }
    /**
     * 采购需求首页
     */
    public function index()
    {
        $map = array (
            'd.status' => '0' 
        );
        import ( "ORG.Util.Page" );
        $count = M ( 'tnode_demand d' )->where ( $map )->count ();
        $p = new Page ( $count, 10 );
        $list = M ( 'tnode_demand d' )->field ( 'd.*,n.node_name' )->join ( 'tnode_info n ON d.node_id=n.node_id' )->where ( $map )->order ( 'd.add_time DESC' )->limit ( $p->firstRow . ',' . $p->listRows )->select ();
        // dump($list);exit;
        $page = $p->show ();
        $this->assign ( 'list', $list );
        $this->assign ( 'page', $page );
        $this->display ();
    }
    /**
     * 发布采购需求
     */
    public function releasePurchasingDemand()
    {
        if ($this->ispost ()) {
            $error = '';
            /* 数据验证 */
            $title = I ( 'title' );
            if (! check_str ( $title, array (
                'null' => false,
                'maxlen_cn' => '24' 
            ), $error )) {
                $this->error ( "标题{$error}" );
            }
            // 电子券处理
            $name = I ( 'name' );
            if (empty ( $name )) {
                $this->error ( '请填写采购信息' );
            }
            $num = I ( 'num' );
            $description = I ( 'post.description' );
            $goodsArr = array ();
            for($i = 0; $i < 10; $i ++) {
                if (is_null ( $name [$i] ))
                    break;
                $goodsArr [$i] ['name'] = $name [$i];
                $goodsArr [$i] ['num'] = $num [$i];
                $goodsArr [$i] ['descrition'] = $description [$i];
            }
            $endDate = I ( 'end_time' );
            if (! check_str ( $endDate, array (
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd' 
            ), $error )) {
                $this->error ( "截止时间{$error}" );
            }
            $demandDesc = I ( 'demand_desc' );
            if (! check_str ( $demandDesc, array (
                'null' => false,
                'maxlen_cn' => '200' 
            ), $error )) {
                $this->error ( "采购目的{$error}" );
            }
            $linkman = I ( 'linkman' );
            if (! check_str ( $linkman, array (
                'null' => false,
                'maxlen_cn' => '24' 
            ), $error )) {
                $this->error ( "联系人{$error}" );
            }
            $tel = I ( 'tel' );
            if (! check_str ( $tel, array (
                'null' => false,
                'strtype' => 'number' 
            ), $error )) {
                $this->error ( "联系电话{$error}" );
            }
            $email = I ( 'email' );
            if (! check_str ( $email, array (
                'null' => false,
                'strtype' => 'email' 
            ), $error )) {
                $this->error ( "联系邮箱{$error}" );
            }
            $sDate = array (
                'title' => $title,
                'node_id' => $this->nodeId,
                'linkman' => $linkman,
                'tel' => $tel,
                'email' => $email,
                'demand_desc' => $description,
                'end_time' => $endDate . '235959',
                'demand_memo' => serialize ( $goodsArr ),
                'add_user' => $this->userId,
                'add_time' => date ( 'YmdHis' ) 
            );
            $result = M ( 'tnode_demand' )->add ( $sDate );
            if (! $result)
                $this->error ( '系统出错,创建失败' );
            $this->success ( '创建成功' );
        }
        $this->display ();
    }
    
    /**
     * 供货
     */
    public function supply()
    {
        $id = I ( 'id' );
        $demandInfo = M ( 'tnode_demand d' )->where ( "d.status='0' and d.id='{$id}'" )->find ();
        if (! $demandInfo)
            $this->error ( '未找到有效的需求信息' );
        if ($this->ispost ()) {
            $goodsId = I ( 'goods_id', null, 'mysql_real_escape_string' );
            
            $cityStr = I ( 'city_str' );
            $price = I ( 'price' );
            $remark = I ( 'remark' );
            $dataArr = array ();
            for($i = 0; $i < 5; $i ++) { // 最多添加5条
            }
            exit ();
        }
        $demandInfo ['demand_memo'] = unserialize ( $demandInfo ['demand_memo'] );
        // dump($demandInfo);
        $this->assign ( 'demandInfo', $demandInfo );
        $this->display ();
    }
    
    /**
     * 供货详情
     */
    public function demandDetail()
    {
        $id = I ( 'id' );
        $demandInfo = M ( 'tnode_demand d' )->field ( 'd.*,n.node_name,n.node_citycode' )->join ( "tnode_info n ON d.node_id=n.node_id" )->where ( "d.status='0' and d.id='{$id}'" )->find ();
        if (! $demandInfo)
            $this->error ( '未找到有效的需求信息' );
        $demandInfo ['demand_memo'] = unserialize ( $demandInfo ['demand_memo'] );
        $cityModel = D ( 'CityCode' );
        $demandInfo ['city'] = $cityModel->getAreaText ( $demandInfo ['node_citycode'] );
        // 已收到的供货申请
        
        $this->assign ( 'demandInfo', $demandInfo );
        $this->display ();
    }
    
    /**
     * 供货选择电子券
     */
    public function selectGoods()
    {
        R ( 'Common/SelectJp/index' );
    }
}