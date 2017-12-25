<?php

/**
 * 玛上买 -MaShangMai
 *
 * @author bao
 */
class MaShangMaiAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '27';
    // 图片路径
    public $img_path;

    public $isInterGral = false;

    //开通自定义短信的标志
    public $startUp = 0;

    // 积分权限
    public function _initialize() {
        parent::_initialize();
        // 验证是否开通服务
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->startUp = $node_info['custom_sms_flag'];
        $hasEcshop = $this->_hasEcshop();
        if ($hasEcshop != true)
            $this->error("您未开通多宝电商服务模块。");
        if (! $node_info['receive_phone']) {
            $this->error("您的接受通知手机号为空，请至多宝电商配置处补齐。", 
                array(
                    '返回' => 'javascript:history.go(-1)', 
                    '去配置' => U('Ecshop/BusiOption/index')));
        }
        $account_info = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->select();
        if (! $account_info)
            $this->error("您的收款账户信息不完整，请至多宝电商配置处添加", 
                array(
                    '返回' => 'javascript:history.go(-1)', 
                    '去配置' => U('Ecshop/BusiOption/index')));
        
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 判断积分权限
        $this->isInterGral = $this->_hasIntegral($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        if ('0' === $intergralType)
            $this->isInterGral = false;
            
            // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('isIntergral', $this->isInterGral); // 订购权限
        $this->assign('tmp_path', $tmp_path);
        $this->assign('node_info', $node_info);
    }

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'm.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
        $data = $_REQUEST;
        
        if ($data['key'] != '') {
            $map['m.name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['m.status'] = $data['status'];
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['m.start_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' m.end_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['m.batch_type'] = $this->BATCH_TYPE;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tmarketing_info m")->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
                               
        // $list = $model->where($map)->order('id
                               // desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = M()->table("tmarketing_info m")->field('m.*,b.storage_num,b.remain_num')
            ->join('tbatch_info b on b.m_id=m.id')
            ->where($map)
            ->order('m.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($list as &$v) {
            // 锁定数量
            $lock_count = M('ttg_order_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_no' => $v['id'], 
                    'order_status' => '0', 
                    'pay_status' => '1'))->sum('buy_num');
            $sale_count = M('ttg_order_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_no' => $v['id'], 
                    'order_status' => '0', 
                    'pay_status' => '2'))->sum('buy_num');
            if (! $lock_count) {
                $v['lock_num'] = 0;
            } else {
                $v['lock_num'] = $lock_count;
            }
            
            if (! $sale_count) {
                $v['sale_num'] = 0;
            } else {
                $v['sale_num'] = $sale_count;
            }
        }
        
        $channelModel = M('tchannel');
        $channelId = $channelModel->where(
            "node_id='{$this->nodeId}' AND type=4 AND sns_type=47 AND status=1")->getField(
            'id');
        if (! $channelId) {
            $data = array(
                'name' => '码上买列表', 
                'type' => '4', 
                'sns_type' => '47', 
                'status' => '1', 
                'node_id' => $this->nodeId, 
                'add_time' => date('YmdHis'));
            $channelId = $channelModel->add($data);
            if (! $channelId)
                $this->error('系统出错创建渠道失败');
        }
        
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function add() {
        // 获取商户名称
        $nodeName = M('tnode_info')->field(' node_name ')->where("node_id='{$this->node_id}'")->find();
        $this->assign('startUp', $this->startUp);
        $this->assign('node_name', $nodeName['node_name']);
        //获取当前机构的资费标准
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $this->assign('sendPrice', $sendPrice);
        $this->display();
    }
    
    // 码上买提交方法
    public function addSubmit() {
        $data = I("post.");
        $model = M('tmarketing_info');
        if (empty($data['name']))
            $this->error('请填写码上买活动名称！');
        if (empty($data['wap_title']))
            $this->error('请填写wap页面标题！');
        if (empty($data['start_time']))
            $this->error('请填写码上买可用开始时间！');
        if (empty($data['end_time']))
            $this->error('请填写码上买可用结束时间！');
        if (empty($data['select_goods_id']))
            $this->error('请选择码上买需要绑定的商品！');
        
        if ($data['end_time'] < date('Ymd'))
            $this->error('活动截止日期不能小于当前日期');
            // if($data['end_time'] > '20140930')
            // $this->error('活动截止日期不能大于9月30日');
        
        $goodsMemo = I('post.goods_memo', null);
        if (! check_str($goodsMemo, 
            array(
                'null' => false, 
                'maxlen_cn' => '200'), $error)) {
            $this->error("商品描述{$error}");
        }
        $goods_id = I('post.select_goods_id', null);
        if (! $goods_id)
            $this->error("请选择卡券");
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))->find(); // tgoodsInfo 数据
                                             
        // sku提交
        $is_sku = I('post.is_sku', null);
        if ('true' === $is_sku) {
            $skuMarkPrice = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            if (! $skuMarkPrice) {
                $this->error("该商品并不是sku商品");
            }
            $countNum = I('post.count_num', null);
            if ('-1' == $countNum) {
                $goodsNum = - 1;
                $goodsNumFlag = 1;
            } else {
                $goodsNum = (int) $countNum;
            }
        } else {
            $groupPrice = I('post.group_price', null);
            if (! check_str($groupPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品销售价{$error}");
            }
            // 商品总数
            $goodsNumFlag = I('post.goods_num_limit', null);
            $goodsNum = I('post.goods_num', null);
            if ($goodsNumFlag == 1) {
                $goodsNum = - 1;
            } else {
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品总数{$error}" . $goodsNumFlag);
                }
            }
        }
        
        if ($goodsInfo['storage_type'] != 0 &&
             ($goodsNum > $goodsInfo['remain_num']))
            $this->error('库存不足');
            // 商品市场价格
        $mPrice = I('post.market_price', null);
        $mPriceFlag = I('post.market_price_flag', null);
        if ($mPriceFlag == 0)
            $mPrice = 0;
        else {
            if (! check_str($mPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0.01'), $error)) {
                $this->error("市场价格{$error}");
            }
        }
        $buyNum = I('post.buy_num', null);
        $buyNumFlag = I('post.buy_num_flag', null);
        if ($buyNumFlag == 0)
            $buyNum = 0;
        else {
            if (! check_str($buyNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("每日限购量{$error}");
            }
        }
        $buyCont = I('post.buy_cont', null);
        $buyNumUser = I('post.buy_num_user', null);
        if ($buyNumUser == 0)
            $buyCont = - 1;
        else {
            if (! check_str($buyCont, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("个人限购量{$error}");
            }
        }
        // 粉丝专享
        $member_join_flag = I('member_join_flag', 0, 'intval');
        $fans_collect_url = I('fans_collect_url', null);
        if ($member_join_flag == 1 && ! $fans_collect_url)
            $this->error('粉丝专享活动请输入关注引导页地址');
        $deliveryFlag = I('post.delivery_flag', null);
        // 非商品类营销品 写死自提方式
        if ($goodsInfo['goods_type'] != '6')
            $deliveryFlag = array(
                '0');
        if (! empty($deliveryFlag)) {
            $deliveryFlag = implode('-', $deliveryFlag);
        } else
            $this->error("未选择是否配送");
        $showFlag = I('post.show_flag', null);
        if ($showFlag == null)
            $showFlag = 0;
        
        $usingRules = I('post.using_rules');
        $mmsTitle = I('post.mms_title','商品');
        $a = strstr($deliveryFlag, '0');
        // 送礼初始值
        $sendGift = 0;
        if ($a !== false) {
            // 是否送礼
            $sendGift = I('post.send_gift');
            // 凭证结束时间
            $verify_time_type = I('post.verify_time_type', null);
            if ($verify_time_type == '0') {
                $verify_end_date = I('post.verify_end_date', null);
                if ($verify_end_date == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_date, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("商品使用截至时间{$error}");
                }
                // 商品使用截至时间必须大于等于营销活动结束时间
                if ($verify_end_date < $endDate) {
                    $this->error('商品使用截至时间必须大于等于营销活动结束时间');
                }
                $verify_end_time = $verify_end_date . '235959';
            } elseif ($verify_time_type == '1') {
                $verify_end_days = I('post.verify_end_days', null);
                if ($verify_end_days == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_days, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品使用截至天数{$error}");
                }
                $verify_end_time = $verify_end_days;
            }
            
            if (! check_str($usingRules, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("商品使用说明{$error}");
            }
            /*
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            */
        }
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("码上买活动名称重复");
        }
        // logo
        if ($data['resp_log_img'] != '') {
            /*
             * $img_move =
             * move_batch_image($data['resp_log_img'],$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true) $this->error('活动logo图片上传失败！');
             */
            
            $log_img = str_replace('..', '', $data['resp_log_img']);
        }
        // 音乐
        if ($data['resp_music'] != '') {
            $music = $data['resp_music'];
        }
        // 背景
        if ($data['resp_bg_img'] != '') {
            /*
             * $img_move =
             * move_batch_image($data['resp_bg_img'],$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true) $this->error('背景图片上传失败！');
             */
            $bg_img = str_replace('..', '', $data['resp_bg_img']);
        }
        
        // 二维码logo
        $isCodeImg = I('post.is_code_img', null);
        $respCodeImg = I('post.resp_code_img', null);
        if ($isCodeImg != '1')
            $respCodeImg = '';
            // 是否可以使用红包
        $bonus_flag = I('bonus_flag', 1, 'intval'); // 默认可以使用
                                                    // 判断积分是否开启
        $integralFlag = I('integral_flag', 0, 'intval'); // 默认不开启
        if (! $this->isInterGral) {
            $integralFlag = 0;
        }
        // 是否支持货到付款
        $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                          // 订购商品不允许货到付款和自提
        if ('2' == $goodsInfo['is_order']) {
            $deli_pay_flag = 0;
            $deliveryFlag = 1;
        }
        M()->startTrans();
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))
            ->lock(true)
            ->find(); // tgoodsInfo 数据
        $data_arr = array(
            'name' => $data['name'],  // 活动名称
            'wap_title' => $data['wap_title'],  // 活动标题
            'wap_info' => $data['wap_info'],  // 活动内容
            'log_img' => $log_img,  // log路径名称
            'start_time' => substr($data['start_time'], 0, 8) . '000000',  // 活动开始时间
            'end_time' => substr($data['end_time'], 0, 8) . '235959',  // 活动结束时间
            'node_id' => $this->node_id,  // 商户id
            'add_time' => date('YmdHis'),  // 增加时间
            'status' => '1',  // 活动状态默认正常
            'size' => $data['size'],  // 生成二维码的大小
            'code_img' => $respCodeImg,  // 二维码路径logo
            'sns_type' => $sns_type,  // sns分享
            'node_name' => $data['node_name'],  // 商户名称
            'page_style' => $data['page_style'],  // 活动页面风格
            'bg_style' => $data['bg_style'],  // 背景图片
            'bg_pic' => $bg_img,  // 背景图片路径
            'batch_type' => $this->BATCH_TYPE,  // 活动类型
                                               // 'tieup'=>$data['select_goods_id'],//商品销售ID
            'music' => $music,  // 音乐
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'group_goods_name' => $goodsInfo['goods_name'], 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            'goods_img' => $goodsInfo['goods_image'], 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'memo' => $goodsMemo, 
            'batch_come_from' => session('batch_from') ? session('batch_from') : '1', 
            'member_join_flag' => $member_join_flag, 
            'fans_collect_url' => $fans_collect_url, 
            'bonus_flag' => $bonus_flag, 
            'integral_flag' => $integralFlag, 
            'config_data' => strstr($deliveryFlag, '0') || $deliveryFlag == '0' ? serialize(
                array(
                    'send_gift' => $sendGift)) : serialize(
                array(
                    'send_gift' => '0')),  // 送礼开关
            'deli_pay_flag' => strstr($deliveryFlag, '1') ? $deli_pay_flag : 0); // 物流支持货到付款
        
        $batchId = $model->add($data_arr); // echo $resp_id;die;
        if (! $batchId) {
            M()->rollback();
            $this->error('活动创建失败！', 
                array(
                    '返回码上买' => U('index')));
        }
        // 创建batch_info数据
        $batch_data = array(
            'batch_no' => $goodsInfo['batch_no'], 
            'batch_short_name' => $goodsInfo['goods_name'], 
            'batch_name' => $goodsInfo['goods_name'], 
            'node_id' => $this->nodeId, 
            'user_id' => $this->userId, 
            'batch_class' => '6',  // 商品销售类型
            'info_title' => $mmsTitle, 
            'use_rule' => $usingRules, 
//            'sms_text' => $usingRules,
            'batch_img' => $goodsInfo['goods_image'], 
            'batch_amt' => $groupPrice, 
            'begin_time' => $start_time . '000000', 
            'end_time' => '20301231235959', 
            // 'send_begin_date' => $startDate.'000000',
            // 'send_end_date' => $endDate.'235959',
            'verify_begin_date' => $start_time . '000000', 
            'verify_end_date' => $verify_end_time, 
            'verify_begin_type' => '0', 
            'verify_end_type' => $verify_time_type, 
            'add_time' => date('YmdHis'), 
            'status' => '0', 
            'goods_id' => $goodsInfo['goods_id'], 
            'storage_num' => $goodsNum, 
            'remain_num' => $goodsNum, 
            'batch_desc' => $goodsInfo['goods_desc'], 
            'm_id' => $batchId, 
            'validate_type' => $goodsInfo['validate_type']);
        //自定义短信内容
        if($deliveryFlag == '0' || $deliveryFlag == '0-1') {        // 门店提领 或 门店提领+物流配送
            if ($this->startUp == 1) {
                $sms_text = I('cusMsg', '');
                if (empty($sms_text)) {
                    M()->rollback();
                    $this->error('短信内容不能空！');
                } else {
                    $batch_data['sms_text'] = $sms_text;
                }
            }
        }
        $bInfoId = M('tbatch_info')->data($batch_data)->add();
        if (! $bInfoId) {
            M()->rollback();
            $this->error('系统出错,添加tbatch_info失败');
        }
        // 更新tgoods_info库存
        $goodsM = D('Goods');
        $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], $goodsNum, 
            $bInfoId, '0', '新增码上买');
        if ($flag === false) {
            M()->rollback();
            $this->error('系统出错,更新tgoods_info库存失败');
        }
        
        // 添加sku上架信息
        if ('true' === $is_sku) {
            // 创建sku链接
            $skuObj = D('Sku', 'Service');
            $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                $batchId, $bInfoId);
            if (false == $info) {
                M()->rollback();
                $this->error($skuObj->getError());
            }
        }
        // 记录活动变更流水
        $marketTraceData = array(
            'batch_id' => $batchId, 
            'batch_type' => $this->BATCH_TYPE, 
            'name' => $data['name'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'wap_title' => $data['wap_title'],  // 活动标题
            'wap_info' => $data['wap_info'], 
            'memo' => $goodsMemo, 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            'verify_begin_date' => $start_time . '000000', 
            'verify_end_date' => $verify_end_time, 
            'verify_begin_type' => '0', 
            'verify_end_type' => $verify_time_type, 
            'modify_time' => date('YmdHis'), 
            'modify_type' => '0', 
            'oper_id' => $this->user_id);
        $result = M('tmarketing_change_trace')->add($marketTraceData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错,记录活动变更流水失败');
        }
        // 发布至默认码上买渠道
        
        $channelId = M('tchannel')->where(
            "node_id='{$this->nodeId}' AND type=4 AND sns_type=47 AND status=1")->getField(
            'id');
        $data = array(
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $batchId, 
            'channel_id' => $channelId, 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id);
        $result = M('tbatch_channel')->add($data);
        if (! $result) {
            M()->rollback();
            $this->error('系统出错,发布默认渠道失败');
        }
        M()->commit();
        
        $this->success('添加成功！', 
            array(
                '发布到线上线下渠道' => U('LabelAdmin/BindChannel/index', 
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $batchId)), 
                '返回活动列表页' => U('O2OHot/index', 
                    array(
                        'batch_type' => 27))));
    }
    
    // 新品推荐提交
    public function addNewSubmit() {
        $data = I("post.");
        $model = M('tmarketing_info');
        if (empty($data['name']))
            $this->error('请填写活动名称！');
        if (empty($data['start_time']))
            $this->error('请填写活动起始时间！');
        if (empty($data['end_time']))
            $this->error('请填写活动结束时间！');
        if ($data['end_time'] < date('Ymd'))
            $this->error('活动截止日期不能小于当前日期');
        if (empty($data['select_goods_id']))
            $this->error('请选择商品或者卡券！');
        
        $goodsMemo = I('post.goods_memo', null);
        if (! check_str($goodsMemo, 
            array(
                'null' => false, 
                'maxlen_cn' => '200'), $error)) {
            $this->error("商品描述{$error}");
        }
        $goods_id = I('post.select_goods_id', null);
        if (! $goods_id)
            $this->error("请选择卡券");
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))->find(); // tgoodsInfo 数据
                                             // sku提交
        $is_sku = I('post.is_sku', null);
        if ('true' === $is_sku) {
            $skuMarkPrice = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            if (! $skuMarkPrice) {
                $this->error("该商品并不是sku商品");
            }
            $countNum = I('post.count_num', null);
            if ('-1' == $countNum) {
                $goodsNum = - 1;
                $goodsNumFlag = 1;
            } else {
                $goodsNum = (int) $countNum;
            }
        } else {
            $groupPrice = I('post.group_price', null);
            if (! check_str($groupPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品销售价{$error}");
            }
            // 商品总数
            $goodsNumFlag = I('post.goods_num_limit', null);
            $goodsNum = I('post.goods_num', null);
            if ($goodsNumFlag == 1) {
                $goodsNum = - 1;
            } else {
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品总数{$error}" . $goodsNumFlag);
                }
            }
            if ($goodsInfo['storage_type'] != 0 &&
                 ($goodsNum > $goodsInfo['remain_num']))
                $this->error('库存不足');
                // 商品市场价格
            $mPrice = I('post.market_price', null);
            $mPriceFlag = I('post.market_price_flag', null);
            if ($mPriceFlag == 0)
                $mPrice = 0;
            else {
                if (! check_str($mPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0.01'), $error)) {
                    $this->error("市场价格{$error}");
                }
            }
        }
        
        $buyNum = I('post.buy_num', null);
        $buyNumFlag = I('post.buy_num_flag', null);
        if ($buyNumFlag == 0)
            $buyNum = 0;
        else {
            if (! check_str($buyNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("每日限购量{$error}");
            }
        }
        $buyCont = I('post.buy_cont', null);
        $buyNumUser = I('post.buy_num_user', null);
        if ($buyNumUser == 0)
            $buyCont = - 1;
        else {
            if (! check_str($buyCont, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("个人限购量{$error}");
            }
        }
        // 粉丝专享
        $member_join_flag = I('member_join_flag', 0, 'intval');
        $fans_collect_url = I('fans_collect_url', null);
        if ($member_join_flag == 1 && ! $fans_collect_url)
            $this->error('粉丝专享活动请输入关注引导页地址');
        $deliveryFlag = I('post.delivery_flag', null);
        // 非商品类营销品 写死自提方式
        if ($goodsInfo['goods_type'] != '6')
            $deliveryFlag = array(
                '0');
        if (! empty($deliveryFlag)) {
            $deliveryFlag = implode('-', $deliveryFlag);
        } else
            $this->error("未选择是否配送");
        $showFlag = I('post.show_flag', null);
        if ($showFlag == null)
            $showFlag = 0;
        
        $usingRules = I('post.using_rules');
        $mmsTitle = I('post.mms_title','商品');
        $a = strstr($deliveryFlag, '0');
        // 送礼初始值
        $sendGift = 0;
        if ($a !== false) {
            // 是否送礼
            $sendGift = I('post.send_gift');
            // 凭证结束时间
            $verify_time_type = I('post.verify_time_type', null);
            if ($verify_time_type == '0') {
                $verify_end_date = I('post.verify_end_date', null);
                if ($verify_end_date == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_date, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("商品使用截至时间{$error}");
                }
                // 商品使用截至时间必须大于等于营销活动结束时间
                if ($verify_end_date < $endDate) {
                    $this->error('商品使用截至时间必须大于等于营销活动结束时间');
                }
                $verify_end_time = $verify_end_date . '235959';
            } elseif ($verify_time_type == '1') {
                $verify_end_days = I('post.verify_end_days', null);
                if ($verify_end_days == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_days, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品使用截至天数{$error}");
                }
                $verify_end_time = $verify_end_days;
            }
            
            if (! check_str($usingRules, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("商品使用说明{$error}");
            }
            /*
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            */
        }
        
        // 是否可以使用红包
        $bonus_flag = I('bonus_flag', 1, 'intval'); // 默认可以使用
                                                    // 判断积分是否开启
        $integralFlag = I('integral_flag', 0, 'intval'); // 默认不开启
        if (! $this->isInterGral) {
            $integralFlag = 0;
        }
        // 是否支持货到付款
        $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
        if ('2' == $goodsInfo['is_order'])
            $deli_pay_flag = 0;
        
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        
        M()->startTrans();
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))
            ->lock(true)
            ->find(); // tgoodsInfo 数据
        $data_arr = array(
            'name' => $data['name'],  // 活动名称
            'start_time' => substr($data['start_time'], 0, 8) . '000000',  // 活动开始时间
            'end_time' => substr($data['end_time'], 0, 8) . '235959',  // 活动结束时间
            'node_id' => $this->node_id,  // 商户id
            'add_time' => date('YmdHis'),  // 增加时间
            'status' => '1',  // 活动状态默认正常
            'node_name' => $this->node_name,  // 商户名称
            'page_style' => $data['page_style'],  // 活动页面风格
            'batch_type' => $this->BATCH_TYPE,  // 活动类型
                                               // 'tieup'=>$data['select_goods_id'],//商品销售ID
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'group_goods_name' => $goodsInfo['goods_name'], 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            'goods_img' => $goodsInfo['goods_image'], 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'memo' => $goodsMemo, 
            'is_new' => '2', 
            'share_pic' => $data['share_pic'], 
            'member_join_flag' => $member_join_flag, 
            'fans_collect_url' => $fans_collect_url, 
            'bonus_flag' => $bonus_flag, 
            'integral_flag' => $integralFlag, 
            'config_data' => strstr($deliveryFlag, '0') || $deliveryFlag == '0' ? serialize(
                array(
                    'send_gift' => $sendGift)) : serialize(
                array(
                    'send_gift' => '0')),  // 送礼开关
            'deli_pay_flag' => strstr($deliveryFlag, '1') ? $deli_pay_flag : 0); // 物流支持货到付款
        
        $batchId = $model->add($data_arr); // echo $resp_id;die;
        if (! $batchId) {
            M()->rollback();
            $this->error('活动创建失败！');
        }
        // 创建batch_info数据
        $batch_data = array(
            'batch_no' => $goodsInfo['batch_no'], 
            'batch_short_name' => $goodsInfo['goods_name'], 
            'batch_name' => $goodsInfo['goods_name'], 
            'node_id' => $this->nodeId, 
            'user_id' => $this->userId, 
            'batch_class' => '6',  // 商品销售类型
            'info_title' => $mmsTitle, 
            'use_rule' => $usingRules, 
//            'sms_text' => $usingRules,
            'batch_img' => $goodsInfo['goods_image'], 
            'batch_amt' => $groupPrice, 
            'begin_time' => $start_time . '000000', 
            'end_time' => '20301231235959', 
            // 'send_begin_date' => $startDate.'000000',
            // 'send_end_date' => $endDate.'235959',
            'verify_begin_date' => $start_time . '000000', 
            'verify_end_date' => $verify_end_time, 
            'verify_begin_type' => '0', 
            'verify_end_type' => $verify_time_type, 
            'add_time' => date('YmdHis'), 
            'status' => '0', 
            'goods_id' => $goodsInfo['goods_id'], 
            'storage_num' => $goodsNum, 
            'remain_num' => $goodsNum, 
            'batch_desc' => $goodsInfo['goods_desc'], 
            'm_id' => $batchId, 
            'validate_type' => $goodsInfo['validate_type']);
        //短信内容
        if($deliveryFlag == '0' || $deliveryFlag == '0-1') {
            if ($this->startUp == 1) {
                $sms_text = I('cusMsg', '');
                if (empty($sms_text)) {
                    M()->rollback();
                    $this->error('短信内容不能空！');
                } else {
                    $batch_data['sms_text'] = $sms_text;
                }
            }
        }

        $bInfoId = M('tbatch_info')->data($batch_data)->add();
        if (! $bInfoId) {
            M()->rollback();
            $this->error('系统出错,添加tbatch_info失败');
        }
        // 更新tgoods_info库存
        $goodsM = D('Goods');
        $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], $goodsNum, 
            $bInfoId, '0', '新增码上买');
        if ($flag === false) {
            M()->rollback();
            $this->error('系统出错,更新tgoods_info库存失败');
        }
        // 记录活动变更流水
        $marketTraceData = array(
            'batch_id' => $batchId, 
            'batch_type' => $this->BATCH_TYPE, 
            'name' => $data['name'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $goodsMemo, 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            'verify_begin_date' => $start_time . '000000', 
            'verify_end_date' => $verify_end_time, 
            'verify_begin_type' => '0', 
            'verify_end_type' => $verify_time_type, 
            'modify_time' => date('YmdHis'), 
            'modify_type' => '0', 
            'oper_id' => $this->user_id);
        $result = M('tmarketing_change_trace')->add($marketTraceData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错,记录活动变更流水失败');
        }
        // 发布至默认码上买渠道
        
        $channelId = M('tchannel')->where(
            "node_id='{$this->nodeId}' AND type=4 AND sns_type=47 AND status=1")->getField(
            'id');
        $data = array(
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $batchId, 
            'channel_id' => $channelId, 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id);
        $result = M('tbatch_channel')->add($data);
        if (! $result) {
            M()->rollback();
            $this->error('系统出错,发布默认渠道失败');
        }
        // 添加sku上架信息
        if ('true' === $is_sku) {
            // 创建sku链接
            $skuObj = D('Sku', 'Service');
            $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                $batchId, $bInfoId);
            if (false == $info) {
                M()->rollback();
                $this->error($skuObj->getError());
            }
        }
        M()->commit();
        // $this->success('码上买活动创建成功！');
        $arr = array(
            'status' => '1', 
            'info' => '码上买活动创建成功！', 
            'm_id' => $batchId);
        echo json_encode($arr);
        exit();
    }
    
    // 编辑提交
    public function editNewSubmit() {
        $id = intval(I("post.id", ''));
        $data = I("post.");
        $model = M('tmarketing_info');
        $where = array(
            'g.node_id' => $this->nodeId, 
            'g.batch_type' => $this->BATCH_TYPE, 
            'g.id' => $id);
        $groupInfo = M()->table("tmarketing_info g")->field(
            'g.*,i.info_title,i.use_rule,i.verify_end_date,i.verify_end_type,i.goods_id,i.storage_num,u.goods_name, u.is_order,u.remain_num')
            ->join("tbatch_info i ON g.id=i.m_id AND g.node_id=i.node_id")
            ->join("tgoods_info u on u.id = i.goods_id")
            ->where($where)
            ->find();
        if (empty($groupInfo))
            $this->error('未找到码上买活动!');
        if (empty($data['name']))
            $this->error('请填写码上买活动名称！');
        if (empty($data['start_time']))
            $this->error('请填写活动起始时间！');
        if (empty($data['end_time']))
            $this->error('请填写活动结束时间！');
        if (empty($data['select_goods_id']))
            $this->error('请选择商品或者卡券！');
        
        if ($data['end_time'] < date('Ymd'))
            $this->error('活动截止日期不能小于当前日期');
            // if($data['end_time'] > '20140930')
            // $this->error('活动截止日期不能大于9月30日');
        $goodsMemo = I('post.goods_memo', null);
        if (! check_str($goodsMemo, 
            array(
                'null' => false, 
                'maxlen_cn' => '200'), $error)) {
            $this->error("商品描述{$error}");
        }
        $goods_id = I('post.select_goods_id', null);
        if (! $goods_id)
            $this->error("请选择卡券");
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))->find(); // tgoodsInfo 数据
                                             
        // sku提交
        $is_sku = I('post.is_sku', null);
        $isCycle = $groupInfo['is_order']; // 是否订购
        if ('1' === $is_sku) {
            $skuMarkPrice = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            if (! $skuMarkPrice) {
                $this->error("该商品并不是sku商品");
            }
            $countNum = I('post.count_num', null);
            if ('-1' == $countNum) {
                $goodsNum = - 1;
                $goodsNumFlag = 1;
            } else {
                $goodsNum = (int) $countNum;
            }
            // 创建sku信息
            $skuObj = D('Sku', 'Service');
            unset($map);
            $skuInfoList = $skuObj->getSkuEcshopList($id, $this->nodeId);
            // 检查商品库存数
            $returnInfo = $skuObj->checkRemainNum($skuMarkPrice, $skuInfoList);
            if (false === $returnInfo['msg'])
                $this->error($returnInfo['info']);
        } else {
            $groupPrice = I('post.group_price', null);
            if (! check_str($groupPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品销售价{$error}");
            }
            // 商品总数
            $goodsNumFlag = I('post.goods_num_limit', null);
            $goodsNum = I('post.goods_num', null);
            if ($goodsNumFlag == 1) {
                $goodsNum = - 1;
            } else {
                // 商品下架后编辑
                if (isset($groupInfo['storage_num'])) {
                    if ('-1' == $groupInfo['storage_num'])
                        $goodsNumFlag = 1;
                    else
                        $goodsNumFlag = 0;
                    // $goodsNum = $groupInfo['storage_num']; ???
                } else {
                    if (! check_str($goodsNum, 
                        array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => '0'), $error)) {
                        $this->error("商品总数{$error}" . $goodsNumFlag);
                    }
                }
            }
            // 商品市场价格
            $mPrice = I('post.market_price', null);
            $mPriceFlag = I('post.market_price_flag', null);
            if ($mPriceFlag == 0)
                $mPrice = 0;
            else {
                if (! check_str($mPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0.01'), $error)) {
                    $this->error("市场价格{$error}");
                }
            }
        }
        
        $buyNum = I('post.buy_num', null);
        $buyNumFlag = I('post.buy_num_flag', null);
        if ($buyNumFlag == 0)
            $buyNum = 0;
        else {
            if (! check_str($buyNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("每日限购量{$error}");
            }
        }
        $buyCont = I('post.buy_cont', null);
        $buyNumUser = I('post.buy_num_user', null);
        if ($buyNumUser == 0)
            $buyCont = - 1;
        else {
            if (! check_str($buyCont, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("个人限购量{$error}");
            }
        }
        // 粉丝专享
        $member_join_flag = I('member_join_flag', 0, 'intval');
        $fans_collect_url = I('fans_collect_url', null);
        if ($member_join_flag == 1 && ! $fans_collect_url)
            $this->error('粉丝专享活动请输入关注引导页地址');
        $deliveryFlag = I('post.delivery_flag', null);
        // 非商品类营销品 写死自提方式
        if ($goodsInfo['goods_type'] != '6')
            $deliveryFlag = array(
                '0');
        if (! empty($deliveryFlag)) {
            $deliveryFlag = implode('-', $deliveryFlag);
        } else
            $this->error("未选择是否配送");
        $showFlag = I('post.show_flag', null);
        if ($showFlag == null)
            $showFlag = 0;
        
        $mmsTitle = I('post.mms_title','商品');
        $usingRules = I('post.using_rules');
        $a = strstr($deliveryFlag, '0');
        // 送礼初始值
        $sendGift = 0;
        if ($a !== false) {
            // 是否送礼
            $sendGift = I('post.send_gift');
            // 凭证结束时间
            $verify_time_type = I('post.verify_time_type', null);
            if ($verify_time_type == '0') {
                $verify_end_date = I('post.verify_end_date', null);
                if ($verify_end_date == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_date, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("商品使用截至时间{$error}");
                }
                // 商品使用截至时间必须大于等于营销活动结束时间
                if ($verify_end_date < $endDate) {
                    $this->error('商品使用截至时间必须大于等于营销活动结束时间');
                }
                $verify_end_time = $verify_end_date . '235959';
            } elseif ($verify_time_type == '1') {
                $verify_end_days = I('post.verify_end_days', null);
                if ($verify_end_days == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_days, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品使用截至天数{$error}");
                }
                $verify_end_time = $verify_end_days;
            }
            if (! check_str($usingRules, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }
            
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
        }
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id, 
            'id' => array(
                'neq', 
                $id));
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("码上买活动名称重复");
        }
        // 是否可以使用红包
        $bonus_flag = I('bonus_flag', 1, 'intval'); // 默认可以使用
                                                    // 判断积分是否开启
        $integralFlag = I('integral_flag', 0, 'intval'); // 默认不开启
        if (! $this->isInterGral) {
            $integralFlag = 0;
        }
        // 是否支持货到付款
        $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                          // 订购商品不允许货到付款和自提
        if ('2' == $isCycle) {
            $deli_pay_flag = 0;
            $deliveryFlag = 1;
        }
        
        // 判断库存
        if ($goodsInfo['storage_type'] != 0) {
            if (($goodsNum - $groupInfo['storage_num']) >
                 $goodsInfo['remain_num'])
                $this->error("库存不足");
        }
        $batchInfo = M('tbatch_info')->where(
            "node_id='{$this->node_id}' AND m_id='{$id}'")->find();
        if ($goodsNumFlag != 1) { // 限库存
            if (($goodsNum <
                 ($batchInfo['storage_num'] - $batchInfo['remain_num'])) &&
                 $batchInfo['storage_num'] != - 1)
                $this->error('商品总数需大于等于已售商品与当前锁定商品总和');
        }
        M()->startTrans();
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))
            ->lock(true)
            ->find(); // tgoodsInfo 数据
        $data_arr = array(
            'name' => $data['name'],  // 活动名称
            'start_time' => $data['start_time'] . '000000',  // 活动开始时间
            'end_time' => $data['end_time'] . '235959',  // 活动结束时间
            'memo' => $goodsMemo, 
            // 'add_time'=>date('YmdHis'),//增加时间
            // 'status'=>'1',//活动状态默认正常
            'node_name' => $data['node_name'],  // 商户名称
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'group_goods_name' => $goodsInfo['goods_name'], 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            'goods_img' => $goodsInfo['goods_image'], 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'share_pic' => $data['share_pic'], 
            'member_join_flag' => $member_join_flag, 
            'fans_collect_url' => $fans_collect_url, 
            'bonus_flag' => $bonus_flag, 
            'integral_flag' => $integralFlag, 
            'config_data' => strstr($deliveryFlag, '0') || $deliveryFlag == '0' ? serialize(
                array(
                    'send_gift' => $sendGift)) : serialize(
                array(
                    'send_gift' => '0')),  // 送礼开关
            'deli_pay_flag' => strstr($deliveryFlag, '1') ? $deli_pay_flag : 0); // 物流支持货到付款
        
        $mape = array(
            "id" => $id, 
            'node_id' => $this->node_id,  // 商户id
            'batch_type' => $this->BATCH_TYPE); // 活动类型
        
        $resp = $model->where($mape)->save($data_arr);
        if ($resp === false) {
            M()->rollback();
            $this->error('码上买活动更新失败！', 
                array(
                    '返回码上买' => U('index')));
        }
        // 更新tbatch_info表
        $remain_num = $batchInfo['remain_num'] +
             ($goodsNum - $groupInfo['storage_num']);
        $batchInfoData = array(
                'verify_end_type' => $verify_time_type,
                'verify_end_date' => $verify_end_time,
                'info_title' => $mmsTitle,
                'use_rule' => $usingRules,
                'storage_num' => $goodsNum,
                'remain_num' => $remain_num,
                'batch_amt' => $groupPrice);

        //自定义短信内容
        if($deliveryFlag == '0' || $deliveryFlag == '0-1') {        // 门店提领 或 门店提领+物流配送
            if ($this->startUp == 1) {
                $sms_text = I('cusMsg', '');
                if (empty($sms_text)) {
                    M()->rollback();
                    $this->error('短信内容不能空！');
                } else {
                    $batchInfoData['sms_text'] = $sms_text;
                }
            }
        }
        $result = M('tbatch_info')->where(
            "node_id='{$this->nodeId}' and m_id='{$id}'")->save($batchInfoData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错,更新tbatch_info失败!');
        }
        // 更新库存
        if ($goodsNum != $groupInfo['storage_num']) {
            // 更新tgoods_info库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                $goodsNum - $groupInfo['storage_num'], $batchInfo['id'], '0', 
                '编辑码上买');
            if ($flag === false) {
                M()->rollback();
                $this->error('系统出错,更新tgoods_info库存失败');
            }
        }
        // 添加sku上架信息
        if ('1' === $is_sku) {
            // 创建sku链接
            $skuObj = D('Sku', 'Service');
            $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                $groupInfo['id'], $batchInfo['id']);
            if (false == $info) {
                M()->rollback();
                $this->error($skuObj->getError());
            }
        }
        // 记录活动变更流水
        $marketTraceData = array(
            'batch_id' => $id, 
            'batch_type' => $this->BATCH_TYPE, 
            'name' => $data['name'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'wap_title' => $data['wap_title'],  // 活动标题
            'wap_info' => $data['wap_info'], 
            'memo' => $goodsMemo, 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            // 'verify_begin_date' => $start_time.'000000',
            'verify_end_date' => $verify_end_time, 
            // 'verify_begin_type' => '0',
            'verify_end_type' => $verify_time_type, 
            'modify_time' => date('YmdHis'), 
            'modify_type' => '1', 
            'oper_id' => $this->user_id);
        $result = M('tmarketing_change_trace')->add($marketTraceData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错,记录活动变更流水失败');
        }
        // 更新成功
        M()->commit();
        // $this->success('更新成功！',array('返回码上买'=>U('Ecshop/O2OHot/index',array('batch_type'=>'27'))));
        $arr = array(
            'status' => '1', 
            'info' => '编辑成功', 
            'm_id' => $data['id'], 
            'new_id' => $data['new_id']);
        echo json_encode($arr);
        exit();
    }
    
    // 编辑码上买
    public function edit() {
        // 将要编辑的活动ID
        $id = I('id', null, 'mysql_real_escape_string'); // echo "$id";die;
        if (empty($id))
            $this->error('错误参数');
        $map = array(
            "g.id" => $id, 
            "g.batch_type" => $this->BATCH_TYPE, 
            "g.node_id" => $this->node_id);
        $row = M()->table("tmarketing_info g")->field(
            'g.*,i.info_title,i.use_rule,i.verify_end_date,i.verify_end_type,i.sms_text,u.id as goods_id ,i.storage_num,u.goods_name, u.is_order,u.remain_num,u.storage_type,u.goods_type,u.goods_id as goods_no')
            ->join("tbatch_info i ON g.id=i.m_id AND g.node_id=i.node_id")
            ->join("tgoods_info u on u.goods_id = i.goods_id")
            ->where($map)
            ->find();
        if (! $row) {
            $this->error('码上买活动未找到');
        }
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        unset($map);
        $skuInfoList = $skuObj->getSkuEcshopList($id, $this->nodeId);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        $isCycle = $row['is_order']; // 是否订购
        
        if (NULL === $skuInfoList) {
            $isSku = false;
        } else {
            $isSku = true;
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList(
                    $goods_sku_list['list']);
                // 取得规格表信息
            if (is_array($goodsSkuDetailList))
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
                
                // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
        }
        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($row);
        $this->assign('sendGift', $sendGift);
        // 商户名称
        $nodeName = M('tnode_info')->field(' node_name ')->where("node_id='{$this->node_id}'")->find();
        $this->assign('isCycle', $isCycle); // 是否订购
        $this->assign("skuDetail", $skuDetail);
        $this->assign("startUp", $this->startUp);        //自定义短信
        $this->assign("skutype",
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('isSku', $isSku);
        $this->assign('row', $row);
        $this->assign('node_name', $nodeName['node_name']);
        $this->display();
    }
    // 编辑提交
    public function editSubmit() {
        $id = intval(I("post.id", ''));
        $data = I("post.");
        $model = M('tmarketing_info');
        $where = array(
            'g.node_id' => $this->nodeId, 
            'g.batch_type' => $this->BATCH_TYPE, 
            'g.id' => $id);
        $groupInfo = M()->table("tmarketing_info g")->field(
            'g.*,i.info_title,i.use_rule,i.verify_end_date,i.verify_end_type,i.goods_id,i.storage_num,u.goods_name, u.is_order,u.remain_num')
            ->join("tbatch_info i ON g.id=i.m_id AND g.node_id=i.node_id")
            ->join("tgoods_info u on u.id = i.goods_id")
            ->where($where)
            ->find();
        if (empty($groupInfo))
            $this->error('未找到码上买活动!');
        if (empty($data['name']))
            $this->error('请填写码上买活动名称！');
        if (empty($data['wap_title']))
            $this->error('请填写wap页面标题！');
        if (empty($data['start_time']))
            $this->error('请填写码上买可用开始时间！');
        if (empty($data['end_time']))
            $this->error('请填写码上买可用结束时间！');
        if (empty($data['select_goods_id']))
            $this->error('请选择码上买需要绑定的商品！');
        
        if ($data['end_time'] < date('Ymd'))
            $this->error('活动截止日期不能小于当前日期');
            // if($data['end_time'] > '20140930')
            // $this->error('活动截止日期不能大于9月30日');
        $goodsMemo = I('post.goods_memo', null);
        if (! check_str($goodsMemo, 
            array(
                'null' => false, 
                'maxlen_cn' => '200'), $error)) {
            $this->error("商品描述{$error}");
        }
        $goods_id = I('post.select_goods_id', null);
        if (! $goods_id)
            $this->error("请选择卡券");
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))->find(); // tgoodsInfo 数据
                                             
        // sku提交
        $is_sku = I('post.is_sku', null);
        $isCycle = $groupInfo['is_order']; // 是否订购
        if ('1' === $is_sku) {
            $skuMarkPrice = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            if (! $skuMarkPrice) {
                $this->error("该商品并不是sku商品");
            }
            $countNum = I('post.count_num', null);
            if ('-1' == $countNum) {
                $goodsNum = - 1;
                $goodsNumFlag = 1;
            } else {
                $goodsNum = (int) $countNum;
            }
            // 创建sku信息
            $skuObj = D('Sku', 'Service');
            unset($map);
            $skuInfoList = $skuObj->getSkuEcshopList($id, $this->nodeId);
            // 检查商品库存数
            $returnInfo = $skuObj->checkRemainNum($skuMarkPrice, $skuInfoList);
            if (false === $returnInfo['msg'])
                $this->error($returnInfo['info']);
        } else {
            $groupPrice = I('post.group_price', null);
            if (! check_str($groupPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品销售价{$error}");
            }
            // 商品总数
            $goodsNumFlag = I('post.goods_num_limit', null);
            $goodsNum = I('post.goods_num', null);
            if ($goodsNumFlag == 1) {
                $goodsNum = - 1;
            } else {
                // 商品下架后编辑
                if (isset($groupInfo['storage_num'])) {
                    if ('-1' == $groupInfo['storage_num'])
                        $goodsNumFlag = 1;
                    else
                        $goodsNumFlag = 0;
                    // $goodsNum = $groupInfo['storage_num'];
                } else {
                    if (! check_str($goodsNum, 
                        array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => '0'), $error)) {
                        $this->error("商品总数{$error}" . $goodsNumFlag);
                    }
                }
            }
        }
        
        // 商品市场价格
        $mPrice = I('post.market_price', null);
        $mPriceFlag = I('post.market_price_flag', null);
        if ($mPriceFlag == 0)
            $mPrice = 0;
        else {
            if (! check_str($mPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0.01'), $error)) {
                $this->error("市场价格{$error}");
            }
        }
        $buyNum = I('post.buy_num', null);
        $buyNumFlag = I('post.buy_num_flag', null);
        if ($buyNumFlag == 0)
            $buyNum = 0;
        else {
            if (! check_str($buyNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("每日限购量{$error}");
            }
        }
        $buyCont = I('post.buy_cont', null);
        $buyNumUser = I('post.buy_num_user', null);
        if ($buyNumUser == 0)
            $buyCont = - 1;
        else {
            if (! check_str($buyCont, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("个人限购量{$error}");
            }
        }
        // 粉丝专享
        $member_join_flag = I('member_join_flag', 0, 'intval');
        $fans_collect_url = I('fans_collect_url', null);
        if ($member_join_flag == 1 && ! $fans_collect_url)
            $this->error('粉丝专享活动请输入关注引导页地址');
        $deliveryFlag = I('post.delivery_flag', null);
        // 非商品类营销品 写死自提方式
        if ($goodsInfo['goods_type'] != '6')
            $deliveryFlag = array(
                '0');
        if (! empty($deliveryFlag)) {
            $deliveryFlag = implode('-', $deliveryFlag);
        } else
            $this->error("未选择是否配送");
        $showFlag = I('post.show_flag', null);
        if ($showFlag == null)
            $showFlag = 0;
        
        $mmsTitle = I('post.mms_title','商品');
        $usingRules = I('post.using_rules');
        $a = strstr($deliveryFlag, '0');
        // 送礼初始值
        $sendGift = 0;
        if ($a !== false) {
            // 是否送礼
            $sendGift = I('post.send_gift');
            // 凭证结束时间
            $verify_time_type = I('post.verify_time_type', null);
            if ($verify_time_type == '0') {
                $verify_end_date = I('post.verify_end_date', null);
                if ($verify_end_date == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_date, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("商品使用截至时间{$error}");
                }
                // 商品使用截至时间必须大于等于营销活动结束时间
                if ($verify_end_date < $endDate) {
                    $this->error('商品使用截至时间必须大于等于营销活动结束时间');
                }
                $verify_end_time = $verify_end_date . '235959';
            } elseif ($verify_time_type == '1') {
                $verify_end_days = I('post.verify_end_days', null);
                if ($verify_end_days == null)
                    $this->error('需设置商品使用截至时间');
                if (! check_str($verify_end_days, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品使用截至天数{$error}");
                }
                $verify_end_time = $verify_end_days;
            }
            if (! check_str($usingRules, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("商品使用说明{$error}");
            }
            /*
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            */
        }
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id, 
            'id' => array(
                'neq', 
                $id));
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("码上买活动名称重复");
        }
        // logo
        if ($data['resp_log_img'] != '' && $data['is_log_img'] == '1') {
            /*
             * $img_move =
             * move_batch_image($data['resp_log_img'],$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true) $this->error('活动logo图片上传失败！');
             */
            $log_img = str_replace('..', '', $data['resp_log_img']);
        } else
            $log_img = '';
            // 背景
        if ($data['resp_bg_img'] != '') {
            /*
             * $img_move =
             * move_batch_image($data['resp_bg_img'],$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true) $this->error('背景图片上传失败！');
             */
            $bg_img = str_replace('..', '', $data['resp_bg_img']);
        }
        // 是否可以使用红包
        $bonus_flag = I('bonus_flag', 1, 'intval'); // 默认可以使用
                                                    // 判断积分是否开启
        $integralFlag = I('integral_flag', 0, 'intval'); // 默认不开启
        if (! $this->isInterGral) {
            $integralFlag = 0;
        }
        // 是否支持货到付款
        $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                          // 订购商品不允许货到付款和自提
        if ('2' == $isCycle) {
            $deli_pay_flag = 0;
            $deliveryFlag = 1;
        }
        // 二维码logo
        $isCodeImg = I('post.is_code_img', null);
        $respCodeImg = I('post.resp_code_img', null);
        if ($isCodeImg != '1')
            $respCodeImg = '';
        if ($data['resp_music'] != '') {
            $music = $data['resp_music'];
        }
        // 判断库存
        if ($goodsInfo['storage_type'] != 0) {
            if (($goodsNum - $groupInfo['storage_num']) >
                 $goodsInfo['remain_num'])
                $this->error("库存不足");
        }
        $batchInfo = M('tbatch_info')->where(
            "node_id='{$this->node_id}' AND m_id='{$id}'")->find();
        if ($goodsNumFlag != 1) { // 限库存
            if (($goodsNum <
                 ($batchInfo['storage_num'] - $batchInfo['remain_num'])) &&
                 $batchInfo['storage_num'] != - 1)
                $this->error('商品总数需大于等于已售商品与当前锁定商品总和');
        }
        
        M()->startTrans();
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $goods_id))
            ->lock(true)
            ->find(); // tgoodsInfo 数据
        $data_arr = array(
            'name' => $data['name'],  // 活动名称
            'wap_title' => $data['wap_title'],  // 活动标题
            'wap_info' => $data['wap_info'],  // 活动内容
            'log_img' => $log_img,  // log路径名称
            'start_time' => $data['start_time'] . '000000',  // 活动开始时间
            'end_time' => $data['end_time'] . '235959',  // 活动结束时间
            'memo' => $goodsMemo, 
            // 'add_time'=>date('YmdHis'),//增加时间
            // 'status'=>'1',//活动状态默认正常
            'size' => $data['size'],  // 生成二维码的大小
            'code_img' => $respCodeImg,  // 二维码路径logo
            'sns_type' => $sns_type,  // sns分享
            'node_name' => $data['node_name'],  // 商户名称
            'page_style' => $data['page_style'],  // 活动页面风格
            'bg_style' => $data['bg_style'],  // 背景图片
            'bg_pic' => $bg_img,  // 背景图片路径
            'music' => $music,  // 音乐
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'group_goods_name' => $goodsInfo['goods_name'], 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            'goods_img' => $goodsInfo['goods_image'], 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'member_join_flag' => $member_join_flag, 
            'fans_collect_url' => $fans_collect_url, 
            'bonus_flag' => $bonus_flag, 
            'integral_flag' => $integralFlag, 
            'config_data' => strstr($deliveryFlag, '0') || $deliveryFlag == '0' ? serialize(
                array(
                    'send_gift' => $sendGift)) : serialize(
                array(
                    'send_gift' => '0')),  // 送礼开关
            'deli_pay_flag' => strstr($deliveryFlag, '1') ? $deli_pay_flag : 0); // 物流支持货到付款
        
        $mape = array(
            "id" => $id, 
            'node_id' => $this->node_id,  // 商户id
            'batch_type' => $this->BATCH_TYPE); // 活动类型
        
        $resp = $model->where($mape)->save($data_arr);
        if ($resp === false) {
            M()->rollback();
            $this->error('码上买活动更新失败！', 
                array(
                    '返回码上买' => U('index')));
        }
        // 更新tbatch_info表
        $remain_num = $batchInfo['remain_num'] +
                ($goodsNum - $groupInfo['storage_num']);

        $batchInfoData = array(
                'verify_end_type' => $verify_time_type,
                'verify_end_date' => $verify_end_time,
//                            'info_title' => $infotitle,
                'use_rule' => $usingRules,
                'storage_num' => $goodsNum,
                'remain_num' => $remain_num,
                'info_title' => $mmsTitle,
                'batch_amt' => $groupPrice);
        //自定义短信内容
        if($deliveryFlag == '0' || $deliveryFlag == '0-1') {        // 门店提领 或 门店提领+物流配送
            if ($this->startUp == 1) {
                $sms_text = I('cusMsg', '');
                if (empty($sms_text)) {
                    M()->rollback();
                    $this->error('短信内容不能空！');
                } else {
                    $batchInfoData['sms_text'] = $sms_text;
                }
            }
        }

        $result = M('tbatch_info')->where(
            "node_id='{$this->nodeId}' and m_id='{$id}'")->save($batchInfoData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错,更新tbatch_info失败!');
        }
        // 更新库存
        if ($goodsNum != $groupInfo['storage_num']) {
            // 更新tgoods_info库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                $goodsNum - $groupInfo['storage_num'], $batchInfo['id'], '0', 
                '编辑码上买');
            if ($flag === false) {
                M()->rollback();
                $this->error('系统出错,更新tgoods_info库存失败');
            }
        }
        // 添加sku上架信息
        if ('1' === $is_sku) {
            // 创建sku链接
            $skuObj = D('Sku', 'Service');
            $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                $groupInfo['id'], $batchInfo['id']);
            if (false == $info) {
                M()->rollback();
                $this->error($skuObj->getError());
            }
        }
        // 记录活动变更流水
        $marketTraceData = array(
            'batch_id' => $id, 
            'batch_type' => $this->BATCH_TYPE, 
            'name' => $data['name'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'wap_title' => $data['wap_title'],  // 活动标题
            'wap_info' => $data['wap_info'], 
            'memo' => $goodsMemo, 
            'defined_one_name' => $deliveryFlag, 
            'defined_two_name' => $showFlag, 
            'defined_three_name' => $buyCont, 
            'market_price' => $mPrice, 
            'group_price' => $groupPrice, 
            'goods_num' => $goodsNum, 
            'buy_num' => $buyNum, 
            // 'verify_begin_date' => $start_time.'000000',
            'verify_end_date' => $verify_end_time, 
            // 'verify_begin_type' => '0',
            'verify_end_type' => $verify_time_type, 
            'modify_time' => date('YmdHis'), 
            'modify_type' => '1', 
            'oper_id' => $this->user_id);
        $result = M('tmarketing_change_trace')->add($marketTraceData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错,记录活动变更流水失败');
        }
        // 更新成功
        M()->commit();
        $this->success('更新成功！', 
            array(
                '返回码上买' => U('Ecshop/O2OHot/index', 
                    array(
                        'batch_type' => '27'))));
    }
    
    // 查看订单信息
    public function orderList() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $goodsName = I('goods_name', null);
        $status = I('status', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $ttglist = M("tmarketing_info")->where(
            array(
                "id" => $batchNo))->find();
        // echo $ttglist['tieup'];die;
        // $where['o.order_type'] = '2';
        $where = array(
            "o.from_batch_no" => $batchNo, 
            "o.batch_no" => $batchNo, 
            "o.node_id" => $this->node_id);
        // 支付状态
        $pay_status = I('pay_status', null);
        if ($pay_status != '') {
            $where['o.pay_status'] = $pay_status;
        }
        // 订单状态
        $order_status = I('order_status', null);
        if ($order_status != '') {
            $where['o.order_status'] = $order_status;
        }
        // 配送状态
        $delivery_status = I('delivery_status', null);
        if ($delivery_status != '') {
            $where['o.delivery_status'] = $delivery_status;
        }
        // 订单类型
        $receiver_type = I('receiver_type', null);
        if ($receiver_type != '') {
            $where['o.receiver_type'] = $receiver_type;
        }
        if ($goodsName)
            $where['g.group_goods_name'] = array(
                'like', 
                "%{$goodsName}%");
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('ttg_order_info o')
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->where($where)
            ->count(); // 查询满足要求的总记录数
                       // print($mapcount );die;
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                         // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $show = $Page->show(); // 分页显示输出
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->where($where)
            ->order('o.add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $payStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $orderStatus = array(
            '0' => '正常', 
            '1' => '取消');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送', 
            '4' => '凭证自提');
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        // dump($orderList);exit;
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('orderList', $orderList);
        $this->assign('orderList', $orderList);
        $this->assign('payStatus', $payStatus);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('post', $_REQUEST);
        $this->assign('batchNo', $batchNo);
        $this->assign('empty', '<tr><td colspan="11">无数据</td></span>');
        $this->display();
    }
    
    // 数据导出
    public function export() {
        
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "' ";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['key']) && $condition['key'] != '') {
                $filter[] = "name LIKE '%{$condition['key']}%'";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "status = '{$condition['status']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $condition['start_time'] = $condition['start_time'] . '000000';
                $filter[] = "start_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . '235959';
                $filter[] = "end_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        
        $filter[] = "batch_type=" . $this->BATCH_TYPE;
        $filter[] = "node_id in({$this->nodeIn()})";
        $count = M('tmarketing_info')->where($filter)->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        
        $sql = "SELECT name,add_time,start_time,end_time,
		CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
		click_count
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editBatchStatus() {
        $batchId = I('post.batch_id', null, 'intval');
        $status = I('post.status', null);
        $isNew = I('post.isNew', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        // 检查是否普通商品变sku商品
        $skuObj = D('Sku', 'Service');
        $skuInfoList = $skuObj->getSkuEcshopList($batchId, $this->node_id);
        $isChange = $skuObj->checkIsProToSku($this->node_id, $batchId, $status);
        $getUrl = 'Ecshop/MaShangMai/add';
        if (2 == $isNew)
            $getUrl = 'Ecshop/O2OHot/newadd';
        if (NULL === $skuInfoList) {
            if (1 === $isChange)
                $this->error("该商品已添加规格信息，请重新上架！", 
                    array(
                        'return' => 'javascript:history.go(-1)', 
                        'reload' => U($getUrl)));
        }
        $result = M('tmarketing_info')->where(
            "node_id in ({$this->nodeIn()}) AND id='{$batchId}'")->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'status' => '1');
        } else {
            $data = array(
                'status' => '2');
        }
        $result = M('tmarketing_info')->where("id='{$batchId}'")->save($data);
        if (false != $result) {
            node_log('投票活动状态更改|活动ID：' . $batchId);
            $this->success('更新成功', 
                array(
                    'id' => $batchId, 
                    'status' => $status));
        } else {
            $this->error('更新失败');
        }
    }
    // 状态修改
    public function editStatus() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('ttg_order_info')->where("order_id='{$orderId}'")->find();
        if (! $result) {
            $this->error('未找到订单信息');
        }
        if ($this->isPost()) {
            $status = I('post.status', null);
            if (is_null($status)) {
                $this->error('缺少配送状态');
            }
            $data = array(
                'order_id' => $orderId, 
                'delivery_status' => $status);
            $result = M('ttg_order_info')->save($data);
            if ($result) {
                $message = array(
                    'respId' => 0, 
                    'respStr' => '更新成功');
                $this->success($message);
            } else {
                $this->error('更新失败');
            }
        }
        
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('order_id', $result['order_id']);
        $this->assign('delovery_status', $result['delivery_status']);
        $this->display();
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT mobile,add_time,
		CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
		FROM
		tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})
		ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有中奖数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败或没有中奖数据');
        }
    }
    
    // 发送团购商品
    public function sendCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['pay_status'] != 2)
            $this->error('该订单还未支付');
        if (! empty($orderInfo['send_seq']))
            $this->error('该订单已经发过码');
            // 获取batchinfoid
        $batchInfoId = M('tbatch_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'm_id' => $orderInfo['batch_no']))->getField('id');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $transId = get_request_id();
        $resp = $req->wc_send($this->nodeId, $this->userId, 
            $orderInfo['group_batch_no'], $orderInfo['receiver_phone'], '8', 
            $transId, '', $batchInfoId);
        if ($resp === true) {
            // 更新request_id
            $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
                array(
                    'send_seq' => $transId));
            $this->success('发送成功');
        } else {
            $this->error('发送失败:' . $resp);
        }
    }
    
    // 撤消团购商品
    public function cancelCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['pay_status'] != 2)
            $this->error('该订单还未支付');
        if (empty($orderInfo['send_seq']))
            $this->error('该订单还未发码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->cancelcode($this->nodeId, $this->userId, 
            $orderInfo['send_seq']);
        if ($resp === true) {
            // 更新request_id
            $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
                array(
                    'send_seq' => ''));
            $this->success('撤消成功');
        } else {
            $this->error('撤消失败:' . $resp);
        }
    }
    
    // 重发团购商品
    public function resendCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['pay_status'] != 2)
            $this->error('该订单还未支付');
        if (empty($orderInfo['send_seq']))
            $this->error('该订单还未发码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->resend_send($this->node_id, $this->user_id, 
            $orderInfo['send_seq']);
        if ($resp === true) {
            $this->success('重发成功');
        } else {
            $this->error('重发失败:' . $resp);
        }
    }
}