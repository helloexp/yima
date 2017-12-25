<?php

class BusiOptionAction extends BaseAction {

    public $GOODS_TYPE = "31";
    // 小店商品 tmarket类型
    public $BATCH_TYPE = "29";
    // 旺财小店 tmarket类型
    public $img_path = "";

    public $tmp_path = "";

    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function beforeCheckAuth() {
        $this->_authAccessMap = '*';
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
                'ban_type' => 2))->find();
        $nodeAccount = M('tnode_account')->where(array('node_id' => $this->node_id))->select();
        $nodeAccountInfo = array_valtokey($nodeAccount, 'account_type');
        
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        
        $biaoti = $logoInfo['biaoti'];
        if ($biaoti == null) {
            $biaoti = $nodeInfo['node_short_name'];
        }
        // 判断是否为电商正是用户
        $nodeAccountInfo['wctype'] = 2;
        // 提现帐号信息
        $cashInfo = M('tnode_cash')->where(
            array(
                'node_id' => $this->node_id))->find();
        
        $accountTypeArr = array(
            '1' => '支付宝', 
            '2' => '联动优势', 
            '3' => '微信');
        // 弹窗标识
        $popupMark = I('get.popupMark', 'false');
        $this->assign('popupMark', $popupMark);
        $this->assign('cashInfo', $cashInfo);
        $this->assign('accountTypeArr', $accountTypeArr);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('nodeAccountInfo', $nodeAccountInfo);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('biaoti', $biaoti);
        $this->assign('receive_phone', $nodeInfo['receive_phone']);
        $this->assign('account_pwd', $nodeInfo['account_pwd']);
        $this->assign('node_id', $this->node_id);
        // 查询积分商城
        $rate = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->getField('rate');
        $this->assign('rate', $rate);
        $this->display();
    }
    
    // logo
    public function logo_add() {
        if ($this->isPost()) {
            $m_id = I('post.m_id', null);
            $node_name = I('post.node_name', null);
            // logo
            $logo_id = I('post.logo_id', null);
            if (! $m_id || ! $node_name)
                $this->error('数据错误');
            $data = array(
                'biaoti' => $node_name, 
                'link_url' => C('CURRENT_HOST') .
                     'index.php?g=Label&m=MyOrder&a=index&node_id=' .
                     $this->node_id);
            
            $img = I('post.e_logo_img', null);
            if ($img) {
                // 采用新的图片上传类 by tr
                /*
                 * $img_move =
                 * move_batch_image($img,$this->BATCH_TYPE,$this->node_id);
                 * if($img_move !==true) $this->error('LOGO图片上传失败！'); $img =
                 * $this->img_path .$img;
                 */
                $img = str_replace('..', '', $img);
                $data['img_url'] = $img;
            }
            
            if ($logo_id) // 更新
{
                $ret = M('tecshop_banner')->where(
                    array(
                        'id' => $logo_id))->save($data);
            } else // 新增
{
                
                $ret = M('tecshop_banner')->add(
                    array(
                        'm_id' => $m_id, 
                        'node_id' => $this->node_id, 
                        'ban_type' => 2, 
                        'biaoti' => $node_name, 
                        'img_url' => $img, 
                        'link_url' => C('CURRENT_HOST') .
                             'index.php?g=Label&m=MyOrder&a=index&node_id=' .
                             $this->node_id, 
                            'add_time' => date('YmdHis')));
            }
            if ($ret === false)
                $this->error("LOGO保存失败");
        }
        redirect(U('index'));
    }

    /*
     * 申请提现
     */
    public function get_cash() {
        $cash_money = I('act_cash', 0);
        if ($cash_money <= 0)
            $this->error('提现金额必须大于0');
        log_write('get_cash data:' . $cash_money . '===' . $this->node_id);
        if (bcsub($cash_money, 3.00, 2) < 0) // 提交提现金额为已扣除2元手续费的金额
            $this->error('由于转账费用，金额低于5元无法提现！');
        $cashInfo = M('tnode_cash')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $cashInfo || ! $cashInfo['account_no'])
            $this->error('您未配置提现帐号，无法进行提现申请！');
            
            /* START 吴刚砍树活动提现 */
            // 判断是否为电商正是用户
        if (! $this->hasPayModule('m2')) {
            $batchInfo = M('tmarketing_info')->field('end_time')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => '55'))
                ->find();
            $nowTime = time();
            if (isset($batchInfo['end_time'])) {
                $getCashTime = strtotime($batchInfo['end_time']) + 3600 * 24 * 14;
                if ($nowTime < $getCashTime) {
                    $this->error(
                        '您当前的提现申请将在' . date('Y-m-d H:i:s', $getCashTime) .
                             '开始，请稍后再尝试');
                }
            }
        }
        /* END 吴刚砍树活动提现 */
        $nctModel = M('tnode_cash_trace');
        $cashId = M('tnode_cash_trace')->where(
            array(
                'trans_type' => '2', 
                'node_id' => $this->node_id))
            ->order('id desc')
            ->limit(1)
            ->getField('id');
        if ($cashId) {
            $cashTrace = M('tnode_cash_trace')->field(
                'trans_type,sum(cash_money) as cash_money')
                ->where(
                array(
                    'id' => array(
                        'gt', 
                        $cashId), 
                    'node_id' => $this->node_id))
                ->group('trans_type')
                ->select();
        } else {
            $cashTrace = M('tnode_cash_trace')->field(
                'trans_type,sum(cash_money) as cash_money')
                ->where(
                array(
                    'node_id' => $this->node_id))
                ->group('trans_type')
                ->select();
        }
        $cashTrace = array_valtokey($cashTrace, 'trans_type');
        // 每笔交易减2块手续费
        $allow_money = $cashTrace['1']['cash_money'];
        // if(($allow_money-$cash_money) < 0){
        if (bcsub($allow_money, $cash_money, 2) < 0) {
            log_write(
                'get_cash 提现金额超出最大可提现金额 :allow_money' . $allow_money .
                     '===cash_money' . $cash_money);
            $this->error('提现金额超出最大可提现金额');
        }
        $allow_money = $allow_money - 2;
        if ($allow_money < 1) {
            log_write('get_cash 提现金额低于手续费 :allow_money' . $allow_money);
            $this->error('提现金额不足以扣手续费，无法提现');
        }
        
        M()->startTrans();
        // 取得电商手续费
        $o2oFee = D('O2O', 'Service')->getO2OFEE();
        // 插入提现记录
        $data = array(
            'node_id' => $this->node_id, 
            'cash_money' => $allow_money, 
            'add_time' => date('YmdHis'), 
            'trans_type' => '2', 
            'trans_status' => '0', 
            'memo' => '提现申请', 
            'user_id' => $this->user_id, 
            'fee' => $o2oFee);
        
        $ret = $nctModel->add($data);
        if ($ret === false) {
            M()->rollback();
            $this->error('提现申请失败');
        }
        // 更新提现id
        $result = $nctModel->where(
            array(
                'add_time' => array(
                    'lt', 
                    date('YmdHis')), 
                'get_id' => 0, 
                'node_id' => $this->node_id, 
                'trans_type' => '1'))->save(
            array(
                'get_id' => $ret));
        if ($result === false) {
            M()->rollback();
            $this->error('提现申请更新记录失败');
        }
        
        // 更新订单提现状态为1
        $result = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2', 
                'extract_status' => '9'))->save(
            array(
                'extract_status' => '1'));
        if ($result === false) {
            M()->rollback();
            $this->error('订单提现状态更新记录失败');
        }
        M()->commit();
        $this->success('提现申请成功');
    }

    /*
     * 提现记录
     */
    public function cash_trace() {
        $map = array(
            'n.node_id' => $this->node_id, 
            'n.trans_type' => '2');
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tnode_cash_trace n')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        $show = $Page->show(); // 分页显示输出
                               // 判断是否为c1和c2用户
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        
        // 判断是否为电商正是用户
        $wctype = 1;
        if (! $this->hasPayModule('m2')) {
            $wctype = 2;
        }
        if (2 == $wctype) {
            $traceInfo = M()->table('tnode_cash_trace n')
                ->field(
                'n.*,ta.account_no,u.user_name,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=2 and a.trans_type=1),0) yl_money,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=3 and a.trans_type=1),0) wx_money,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=1 and a.trans_type=1),0) aliypay_money')
                ->join('tuser_info u on u.user_id=n.user_id')
                ->join('tnode_cash ta ON ta.node_id = n.node_id')
                ->where($map)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('n.id desc')
                ->select();
        } else {
            $traceInfo = M()->table('tnode_cash_trace n')
                ->field(
                'n.*,ta.account_no,u.user_name,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=2 and a.trans_type=1),0) yl_money,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=3 and a.trans_type=1),0) wx_money')
                ->join('tuser_info u on u.user_id=n.user_id')
                ->join('tnode_cash ta ON ta.node_id = n.node_id')
                ->where($map)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('n.id desc')
                ->select();
        }
        $tranType = array(
            '1' => '订单金额充值', 
            '2' => '提现');
        $tranStatus = array( // 提现时有用 0-提交成功 1-处理中 2-已取消 3-已处理 9-导出中
            '0' => '提交成功', 
            '1' => '提交成功', 
            '2' => '已取消', 
            '3' => '已完成', 
            '4' => '处理中', 
            '9' => '导出中');
        
        $this->assign('traceInfo', $traceInfo);
        $this->assign('tranType', $tranType);
        $this->assign('wctype', $wctype);
        $this->assign('tranStatus', $tranStatus);
        $this->assign('page', $show);
        $this->display();
    }

    /**
     * 确认到账
     */
    public function verify() {
        $id = I('id', '');
        $info = M('tnode_cash_trace')->where(
            array(
                'id' => $id))->save(array(
            'trans_status' => 3));
        
        if ($info) {
            $this->ajaxReturn(1, '更新成功', 1);
        } else {
            $this->ajaxReturn(0, '更新失败', 0);
        }
    }

    /*
     * 注意缓存文件的使用，数据存储格式并不方便于数据的处理 地区显示页面
     */
    function shippingCity() {
        $result = array();
        $isCheckArray = array();
        $key = I('get.key');
        // 查询已配置运费
        $cityExpressShippingModel = D('CityExpressShipping');
        $cityExpressInfoArray = $cityExpressShippingModel->getCityExpressConfig();
        $shippingService = D('Shipping', 'Service');
        $cityCodeModel = D('CityCode');
        if ($key != '') {
            $existCityConfig = $cityExpressInfoArray[$key];
            $condition = array(
                'path' => array(
                    'in', 
                    $existCityConfig['cityCode']), 
                'city' => array(
                    'neq', 
                    'null'));
            $field = 'id, province_code, city_code, city, province';
            $existCityArray = $cityCodeModel->getCityCodeAndProvince($condition, 
                $field);
            $formatedExistShippingConfig = $shippingService->formatExistExpressConfig(
                $existCityArray);
            $tmpArray = $formatedExistShippingConfig['tmp'];
            $result = $formatedExistShippingConfig['result'];
        }
        
        $condition = array(
            'city' => array(
                'neq', 
                'null'));
        $field = 'id, province_code, city_code, city, province';
        $cityArray = $cityCodeModel->getCityCodeAndProvince($condition, $field);
        $result = $shippingService->unformatExpressConfig($cityExpressInfoArray, 
            $cityArray, $tmpArray, $result, 
            $formatedExistShippingConfig['isCheckArray']);
        
        $this->assign('key', $key);
        $this->assign('city', $result['result']);
        $this->assign('isCheckArray', $result['isCheckArray']);
        $this->display();
    }

    /**
     * 保存设置入库
     */
    function saveShippingConfig() {
        $saveData = array();
        $saveData['node_id'] = $_SESSION['userSessInfo']['node_id'];
        
        $saveData['freight'] = (int)I('post.rest', '0', 'string');
        if($saveData['freight'] < 0){
            $this->error('运费信息不正确，请重新配置');
        }
        unset($_POST['rest']);
        
        if ($_POST['freight_free_flag'] == '1') {
            $saveData['freight_free_flag'] = '1';
            $saveData['freight_free_limit'] = I('post.freight_free_limit');
        } else {
            $saveData['freight_free_flag'] = '0';
        }
        unset($_POST['freight_free_flag']);
        unset($_POST['freight_free_limit']);
        
        if (count($_POST) > 0) {
            $jsonArray = array();
            $keyArray = array_keys($_POST);
            foreach ($keyArray as $key => $val) {
                $jsonArray[$key]['cityCode'] = $val;
                if (strpos($_POST[$val], '.')) {
                    $jsonArray[$key]['price'] = $_POST[$val];
                } else {
                    $jsonArray[$key]['price'] = $_POST[$val] . '.00';
                }
            }
            $saveData['express_rule'] = json_encode($jsonArray);
        } else {
            $saveData['express_rule'] = '';
        }
        $cityExpressShippingModel = D('CityExpressShipping');
        $cityExpressShippingModel->saveOrAddData($saveData);
    }

    /**
     * 删除缓存文件中的数据
     */
    function delShippingConfig() {
        $cityExpressShippingModel = D('CityExpressShipping');
        $expressRuleArray = $cityExpressShippingModel->getCityExpressConfig();
        unset($expressRuleArray[$_POST['cityCode']]);
        // 删除配送城市
        if (empty($expressRuleArray)) {
            S($_SESSION['userSessInfo']['node_id'] . 'cityExpressPrice', NULL, 
                1800);
        } else {
            S($_SESSION['userSessInfo']['node_id'] . 'cityExpressPrice', 
                $expressRuleArray, 1800); // 维护缓存文件
        }
    }

    /**
     * 维护缓存文件
     */
    function saveForCashe() {
        $cityExpressShippingModel = D('CityExpressShipping');
        $expressRuleArray = $cityExpressShippingModel->getCityExpressConfig();
        $expressRuleArray[$_POST['key']] = array(
            'cityCode' => $_POST['val']);
        S($_SESSION['userSessInfo']['node_id'] . 'cityExpressPrice', 
            $expressRuleArray, 1800); // 维护缓存文件
    }

    /**
     * 保存短信通知
     */
    function saveNodeConfig() {
        $cityExpressShippingModel = D('CityExpressShipping');
        $result = $cityExpressShippingModel->setSmsNotice(1, $this->node_id);
        if (null === $result)
            $this->error('请先进行运费信息配置');
        if (false === $result)
            $this->error('短信信息配置失败，请重新配置');
        echo $result;
    }

    /**
     * 取消短信通知
     */
    function delNodeConfig() {
        $cityExpressShippingModel = D('CityExpressShipping');
        $result = $cityExpressShippingModel->setSmsNotice(0, $this->node_id);
        if (null === $result)
            $this->error('请先进行运费信息配置');
        if (false === $result)
            $this->error('短信信息配置失败，请重新配置');
        echo $result;
    }
}