<?php

class NumGoodsAction extends BaseAction {

    public $numGoodsImagePath;
    // 卡券图片存放路径
    public $tempImagePath;
    // 临时图片存放路径
    
    /**
     *
     * @var VisitLogModel
     */
    private $VisitLogModel;

    public function _initialize() {
        parent::_initialize();
        $this->numGoodsImagePath = APP_PATH . 'Upload/NumGoods/' . $this->nodeId;
        $this->tempImagePath = APP_PATH . 'Upload/img_tmp/' . $this->nodeId;
        
        $this->VisitLogModel = D('VisitLog');
        switch (ACTION_NAME) {
            case 'numGoodsList':
                $title = '我的卡券';
                $logInfo = '我的卡券';
                break;
            default:
                $title = '我的卡券';
                $logInfo = ACTION_NAME;
        }
        $visitPage = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http' .
             '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $this->VisitLogModel->logByAction($visitPage, $title, $logInfo);
    }
    
    // 电子卷首页
    public function index() {
        $goodsModel = D('Goods');
        // 各类卡券数量
        $goodsTypeNum = $goodsModel->getGoodsNum(
            array(
                'exp', 
                "in ({$this->nodeIn()})"), '0,1');
        // 卡券大厅热销卡券
        $hallModel = D('Hall');
        $hotGoodsList = $hallModel->getHotGoods();
        
        /*
         * //卡券类型 $goodsType = $goodsModel->getGoodsType('0,1,2,3');
         * //一周内各类型卡券发码,验码,验证率 $weekago =
         * mktime(0,0,0,date("m"),date("d")-6,date("Y")); $bTime = date("Y-m-d",
         * $weekago);//一周前日期 $eTime = date("Y-m-d"); //排名前五的 $topVerifyGoodsInfo
         * = $goodsModel->getTopVerifyGoods(array('exp',"in
         * ({$this->nodeIn()})"), $bTime, $eTime);
         * $this->assign('topVerifyGoodsInfo', $topVerifyGoodsInfo);
         * $this->assign('bTime',$bTime); $this->assign('eTime',$eTime);
         */
        // 浏览过的商品
        $browsedIdArr = cookie('hall_browsed_goods');
        if (! empty($browsedIdArr)) {
            $browsedList = M('thall_goods')->field(
                'id,batch_img,batch_name,batch_amt,sell_num_note')
                ->where("id in(" . implode(',', $browsedIdArr) . ") and check_status='1'")
                ->select();
        }
        //微信卡券普通券数量
        $map = array(
            'node_id' => $this->nodeId,
            'card_class' => '1'
        );
        $wxCardNum = M('twx_card_type')->where($map)->count();
        //微信卡券朋友的券数量
        $map = array(
            'node_id' => $this->nodeId,
            'card_class' => '2'
        );
        $wxCardFriendNum = M('twx_card_type')->where($map)->count();
        
        
        $this->assign('hotGoodsList', $hotGoodsList);
        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign('browsdList', get_val($browsedList));
        $this->assign('wxCardNum',$wxCardNum);
        $this->assign('wxCardFriendNum',$wxCardFriendNum);
        $this->assign('hallModel',$hallModel);
        $this->display();
    }
    
    // 卡券添加
    public function addNumGoods() {
        $param = I('param.isWcadd');

        if($param == 1){
            $map = array(
                    'node_id' => $this->nodeId,
                    'account_type' => array('in', ['2','4']),
                    'status' => '0'
            );
            $winxinInfo = M('tweixin_info')->where($map)->find();
            // 微信已认证服务号并且状态正常的
            if (!$winxinInfo) {
                $this->error("请先配置微信公众账号。", array('立即绑定' => U('Weixin/Weixin/index')));
            }
        }

        node_log("首页+创建卡券");
        // 商户信息
        if(IS_POST){
            $res = D('Store','Service')->addNumGoods($this->nodeId,$this->nodeIn(null, true),$this->nodeIn(),$this->userId);

            if($res['status'] == 'success'){
                $this->success('创建成功', '', $res['ajaxData']);
            }else{
                $this->error("'".var_export($res,1)."'");
            }
        }

        // 可验证门店数量
        $storeNum = M('tstore_info')->where("node_id IN({$this->nodeIn()}) AND pos_count>0 AND status=0")->count();
        // 各类卡券数量

        $goodsTypeNum = D('Goods')->getGoodsNum(array('exp',"in ({$this->nodeIn()})"), '0');
        // 卡券颜色
        $color = D('WeiXinCard', 'Service')->getcolors();

        if ($this->nodeId == C('withDraw.createNodeId')) {
            $this->assign('type', 'yimaSellRice');
        }

        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign('storeNum', $storeNum);
        $this->assign('color', $color['colors']);
        $this->assign('nodeTyoe', $this->node_type_name);
        $this->display();
    }

    /**
     * 卡券发布到大厅
     */
    public function numGoodsPublish() {
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $goodsData = M()->table("tgoods_info g")->field(
            'g.*,n.busi_contact_name,n.busi_contact_tel,n.busi_contact_eml')
            ->where(
            "g.node_id='{$this->nodeId}' AND g.goods_id='{$goodsId}' AND g.status=0")
            ->join("tnode_info n ON g.node_id=n.node_id")
            ->find();
        if (! $goodsData)
            $this->error('未找到该商品或该商品已停用或过期');
        $hall_is_exist = M('thall_goods')->where(
            array(
                'node_id' => $this->node_id, 
                'goods_id' => $goodsId))->select();
        if ($hall_is_exist) {
            $this->error('您已发布到卡券交易大厅');
        }
        if ($this->isPost()) {
            $error = '';
            /*
             * //截止时间 $endDate = I('post.show_end_date');
             * if(!check_str($endDate,array('null'=>false,'strtype'=>'datetime','format'=>'Ymd'),$error)){
             * $this->error("展示截止日期{$error}"); } if(strtotime($endDate) >
             * strtotime($goodsData['end_time']) || strtotime($endDate) <
             * strtotime($goodsData['begin_time'])){
             * $this->error('展示日期要在卡券有效日期之内'); }
             */
            $goodsCate = I('post.cate2');
            if (! check_str($goodsCate, 
                array(
                    'null' => false), $error)) {
                $this->error("类目{$error}");
            }
            // 采购价
            $batchAmt = I('post.show_price');
            if (! check_str($batchAmt, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0.01'), $error)) {
                $this->error("卡券市场采购价{$error}");
            }
            
            $cgName = I('post.cg_name');
            if (! check_str($cgName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '24'), $error)) {
                $this->error("采购联系人{$error}");
            }
            $cgMail = I('post.cg_mail');
            if (! check_str($cgMail, 
                array(
                    'null' => false), $error)) {
                $this->error("采购联系人邮箱{$error}");
            }
            $cgPhone = I('post.cg_phone');
            if (! check_str($cgPhone, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '24'), $error)) {
                $this->error("采购联系人电话{$error}");
            }
            $cgMark = I('post.cg_mark');
            if (! check_str($cgMark, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("采购条件{$error}");
            }
            // 图片
            $goodsImage = I('post.batch_img');
	        if(empty($goodsImage) && !is_array($goodsImage)){
	    		$this->error("请上传卡券图片");
	    	}
            // 描述
            $batchDesc = I('post.show_batch_desc');
            if (! check_str($batchDesc, 
                array(
                    'null' => false), $error)) {
                $this->error("卡券描述{$error}");
            }
            //发票
            $invoiceType = I('invoice_type');
            if (! check_str($invoiceType,array('null' => false,'strtype' => 'int','minval' => '0','maxval' => '2'), $error)) {
            	$this->error("发票类型{$error}");
            }
            
            // thall_goods数据添加
            $data = array(
                'batch_short_name' => $goodsData['goods_name'], 
                'batch_name' => $goodsData['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'use_rule' => $batchDesc, 
                'batch_img' => json_encode($goodsImage), 
                'batch_amt' => $batchAmt, 
                // 'begin_time' => $goodsData['begin_time'],
                // 'end_time' => $endDate.'235959',
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsData['pos_group'], 
                'node_pos_type' => $goodsData['pos_group_type'], 
                'batch_desc' => $batchDesc, 
                'goods_id' => $goodsId, 
                'cg_name' => $cgName, 
                'cg_mail' => $cgMail, 
                'cg_phone' => $cgPhone, 
                'cg_mark' => $cgMark, 
                'goods_cat' => $goodsCate,
            	'invoice_type' => $invoiceType
            );
            
            $batchId = M('thall_goods')->add($data);
            if (! $batchId) {
                $this->error('数据库错误,发布失败');
            }
            // 邮件通知
            $sendMailContent = "卡券名称:{$goodsData['goods_name']}<br/>商户名:" .
                 get_node_info($this->nodeId, 'node_name') .
                 "<br/>旺号:{$this->clientId}";
            $mailData = array(
                'subject' => "卡券:{$goodsData['goods_name']}-大厅发布", 
                'content' => $sendMailContent, 
                'email' => C('PUBLISH_SEND_MAIL'));
            send_mail($mailData);
            node_log("卡券发布，发布类型：异业联盟中心，名称：" . $goodsData['goods_name']);
            $this->success('发布成功');
            exit();
        }
        $publishNum = M('thall_goods')->where("node_id='{$this->nodeId}' and check_status='1'")->count();
        $goodsCate = M('tgoods_category')->where("level=1")->select();
        $this->assign('goodsCate', $goodsCate);
        $this->assign('goodsData', $goodsData);
        $this->assign('publishNum', $publishNum);
        $this->display();
    }

    /**
     * 卡券发布到个人
     */
    public function numGoodsPrivatePublish() {
        $goodsId = I('goods_id',null, 'mysql_real_escape_string');
        $goodsData = M()->table("tgoods_info g")->field(
            'g.*,n.busi_contact_name,n.busi_contact_tel,n.busi_contact_eml,n.sale_flag,n.custom_sms_flag')
            ->where(
            "g.node_id='{$this->nodeId}' AND g.goods_id='{$goodsId}' AND g.status=0")
            ->join("tnode_info n ON g.node_id=n.node_id")
            ->find();
        if (! $goodsData)
            $this->error('未找到该商品或该商品已停用或过期');
            // 查看是否存在tbatch_info信息
        $cur_batch_info = M()->table("tbatch_info b")->field('b.*')
            ->where(
            array(
                'b.node_id' => $this->node_id, 
                'b.goods_id' => $goodsId, 
                'm.batch_type' => '1007'))
            ->join('tmarketing_info m ON m.id=b.m_id')
            ->select();
        if (in_array($goodsData['goods_type'], 
            array(
                '7', 
                '8'))) { // 话费Q币设置短彩信内容
            $smsInfo = D('Goods')->getPqText($goodsId);
        } else {
            $smsInfo['title'] = $cur_batch_info[0]['info_title'];
            $smsInfo['content'] = $cur_batch_info[0]['use_rule'];
        }
        //自定义短信内容
        $startUp = $goodsData['custom_sms_flag'];

        if ($this->isPost()) {
            $goods_type = I('goods_type', "", 'trim,htmlspecialchars');
            $phone = I('phone_no', "", 'trim,htmlspecialchars');
            $time_type = I('time_type', "", 'trim,htmlspecialchars');
            $use_start_time = I('use_start_time', "", 'trim,htmlspecialchars');
            $use_end_time = I('use_end_time', "", 'trim,htmlspecialchars');
            $later_start_time = I('later_start_time', "", 
                'trim,htmlspecialchars');
            $later_end_time = I('later_end_time', "", 'trim,htmlspecialchars');

            $caixin_content = I('caixin_content', "", 'trim,htmlspecialchars');
            $batch_desc = I('batch_desc', "", 'trim,htmlspecialchars');

            $file_md5 = "";
            if(!preg_match("/^1[34578]\d{9}$/", $phone)){
                $this->error('请输入正确的手机号码');
            }

            // 开启事务
            $model = M();
            $model->startTrans();
            $goodsData = $model->table('tgoods_info')
                ->lock(true)
                ->where(
                "node_id='{$this->nodeId}' AND goods_id='{$goodsId}' AND status=0")
                ->find();
            // 对数据进行验证
            (empty($goods_type) && $goods_type != '0') && $this->error('非法提交');
            (empty($time_type) && $time_type != '0') && $this->error('非法提交');

            if ($goodsData['storage_num'] != '-1') {
                ($goodsData['remain_num'] < 1) && $this->error('卡券库存不足');
            }
            if (mb_strlen($batch_desc, 'utf8') > 50) {
                $this->error('备注不得大于50个字');
            }

            if ($time_type == '1') {
                //按天数
                empty($later_start_time) && $later_start_time != 0 && $this->error('时间不得为空');
                empty($later_end_time) && $this->error('时间不得为空');
                if ($later_start_time > $later_end_time) {
                    $this->error('时间填写有误');
                }
                if ($later_start_time > 1000 || $later_end_time > 1000) {
                    $this->error('时间不得大于1000天');
                }
            } else {
                //按日期
                empty($use_start_time) && $this->error('时间不得为空');
                empty($use_end_time) && $this->error('时间不得为空');
                if ($use_start_time > $use_end_time) {
                    $this->error('时间填写有误');
                }
            }

            $caixin_title = '电子券';

            empty($caixin_content) && $this->error('使用说明不得为空');
            // 判断是否已经创建了tmarketing_info和tbatch_info
            $isCreate = M()->table("tbatch_info b")->field(
                'b.id AS b_id,b.m_id AS m_id,b.storage_num')
                ->where(
                array(
                    'b.node_id' => $this->node_id, 
                    'b.goods_id' => $goodsId, 
                    'm.batch_type' => '1007'))
                ->join('tmarketing_info m ON m.id=b.m_id')
                ->select();
            // tmarketing_info插入数据 1007是发送到个人
            $market_data['name'] = $goodsData['goods_name'];
            $market_data['batch_type'] = '1007';
            $market_data['node_id'] = $this->node_id;
            $market_data['start_time'] = date('YmdHis');
            $market_data['end_time'] = date('YmdHis', strtotime('+10 year'));
            $market_data['add_time'] = date('YmdHis');
            if (empty($isCreate)) {
                $m_id = $model->table('tmarketing_info')->add($market_data);
                if (! $m_id) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            } else {
                $m_id = $isCreate[0]['m_id'];
                $m_id_ex = $model->table('tmarketing_info')
                    ->where(array(
                    'id' => $m_id))
                    ->save($market_data);
                if (false === $m_id_ex) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            }
            
            // tbatch_info 插入数据
            $batch_info_data['batch_short_name'] = $goodsData['goods_name'];
            $batch_info_data['batch_no'] = $goodsData['batch_no'];
            $batch_info_data['node_id'] = $this->node_id;
            $batch_info_data['user_id'] = $this->userInfo['user_id'];
            $batch_info_data['batch_class'] = $goods_type;
            $batch_info_data['use_rule'] = $caixin_content;
            $batch_info_data['batch_amt'] = $goodsData['goods_amt'];
            $batch_info_data['info_title'] = $caixin_title;
            $batch_info_data['print_text'] = $goodsData['print_text'];
            $batch_info_data['batch_desc'] = $batch_desc;
            $batch_info_data['begin_time'] = date('YmdHis');
            $batch_info_data['add_time'] = date('YmdHis');
            if ($time_type == '1') {
                $batch_info_data['verify_begin_date'] = $later_start_time;
                $batch_info_data['verify_end_date'] = $later_end_time;
                $batch_info_data['verify_begin_type'] = '1';
                $batch_info_data['verify_end_type'] = '1';
            } else {
                $batch_info_data['verify_begin_date'] = $use_start_time .
                     '000000';
                $batch_info_data['verify_end_date'] = $use_end_time . '235959';
                $batch_info_data['verify_begin_type'] = '0';
                $batch_info_data['verify_end_type'] = '0';
            }
            $batch_info_data['end_time'] = date('YmdHis', strtotime('+10 year'));
            $batch_info_data['goods_id'] = $goodsId;
            $batch_info_data['m_id'] = $m_id;

            //自定义短信内容
            if($startUp == 1){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    $this->error('短信内容不能空');
                }else{
                    $batch_info_data['sms_text'] = $sms_text;
                }
            }
            // 非批量发送
            if ($phone){

                // 添加数据到数组
                $allPhones = array($phone);

                $batch_info_data['storage_num'] = $isCreate[0]['storage_num'] + 1;
                $batch_info_data['remain_num'] = 0;
                if (empty($isCreate)) {
                    $b_id = $model->table('tbatch_info')->add($batch_info_data);
                    if (! $b_id) {
                        $model->rollback();
                        $this->error('提交失败');
                    }
                } else {
                    $b_id = $isCreate[0]['b_id'];
                    $b_id_ex = $model->table('tbatch_info')
                        ->where(
                        array(
                            'id' => $b_id))
                        ->save($batch_info_data);
                    if (false === $b_id_ex) {
                        $model->rollback();
                        $this->error('提交失败');
                    }
                }
                
                // 判断库存是否足够,tgoods_info减去库存
                $num_limit =1;
                if ($goodsData['storage_num'] != '-1') {
                    if ($goodsData['remain_num'] < $num_limit) {
                        $model->rollback();
                        $this->error('卡券库存不足');
                    }
                    if (false === $model->table('tgoods_info')
                        ->where(
                        array(
                            'goods_id' => $goodsId))
                        ->save(
                        array(
                            'remain_num' => ($goodsData['remain_num'] - $num_limit)
                            )
                        )
                    ) {
                        $model->rollback();
                        $this->error('提交失败');
                    }


                    // 库存流水变动
                    self::modifyStorageTrace(
                            $goodsData['id'],
                            $num_limit,
                            $goodsData['remain_num'],
                            $model,
                            $_POST,
                            16,
                        '非批量发送到个人，库存变动');
                } else {
                    // 库存流水变动
                    self::modifyStorageTraceEx($goodsData['id'], $num_limit, 
                        $model, $_POST, 16, '非批量发送到个人，库存变动');
                }

                $model->commit();

                // 调用接口发送短信,接口中会自动插入流水
                import("@.Vendor.SendCode");
                $req = new SendCode();

                $failPhones = array();
                foreach ($allPhones as $key => $value) {
                    $resp = $req->wc_send(
                            $this->nodeId,
                            $this->userId,
                            $goodsData['batch_no'],
                            $value,
                            0,
                            null,
                            null,
                            $b_id,
                            null,
                            array('batch_desc' => $batch_desc)
                    );
                    if ($resp != true) {
                        $failPhones[] = $value . '：' . $resp;
                    }
                }
                if (!empty($failPhones)) {
                    $this->ajaxReturn(array('status' => 0, 'info' => '发送失败！错误发送：' . implode(',', $failPhones)), 'JSON');
                }

                $model->commit();
                $ajaxData['goods_id'] = $goodsId;
                $ajaxData['phone_no'] = $phone;
                $this->ajaxReturn(array('status' => 1), 'JSON');
            }

        }
        
        $publishNum = M('thall_goods')->where(
            "node_id='{$this->nodeId}' and check_status='1'")->count();
        /*
        //提示是否为自建卡券及情况说明
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $tipStr = '每张卡券将收取'.$sendPrice['buy'].'元异业卡券下发费';
        if($goodsData['source'] == 0){//自建
            $tipStr = '每张卡券将收取'.$sendPrice['self'].'元卡券下发费用';
        }
        */
        $tipStr = '';
        /******************整理示例图中的短信文字************/
        //企业简称
        $storeShortName = session('userSessInfo.node_short_name');
        //企业简称的字数差值
        $storeDifference = 6-mb_strlen($storeShortName,'utf8');
        if($storeDifference < 0){          //企业简称字数超出时
            $storeShortName = mb_substr($storeShortName,0,6,'utf8');
            //当卡券名称大于11个字的时候。。。。以下相同
            if(mb_strlen($goodsData['goods_name'],'utf8') > 11){
                $cardName = mb_substr($goodsData['goods_name'],0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsData['goods_name'],0,11,'utf8');
            }
        }elseif($storeDifference > 0){     //企业简称字数有结余时
            if(mb_strlen($goodsData['goods_name'],'utf8') > (11+abs($storeDifference))){
                $cardName = mb_substr($goodsData['goods_name'],0,(10+abs($storeDifference)),'utf8').'...';
            }else{
                $cardName = mb_substr($goodsData['goods_name'],0,(11+abs($storeDifference)),'utf8');
            }
        }else{
            if(mb_strlen($goodsData['goods_name'],'utf8') > 11){
                $cardName = mb_substr($goodsData['goods_name'],0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsData['goods_name'],0,11,'utf8');
            }
        }
        $smsContent = '【'.$storeShortName.'】的'.$cardName;
        $this->assign('smsContent', $smsContent);
        $this->assign('storeDifference', $storeDifference);

        $this->assign('startUp',$goodsData['custom_sms_flag']);
        $this->assign('tipStr', $tipStr);
        $this->assign('goodsData', $goodsData);
        $this->assign('smsInfo', $smsInfo);
        $this->assign('batch_info', $cur_batch_info[0]);
        $this->display();

    }


    /*****************流量包发送（start）********************/
    /**
     * 流量包发送页面
     */
    public function flowPacketPublish()
    {
        $goodsId = I('get.goods_id',null,'trim,mysql_real_escape_string');
        $goodsData = self::getGoodsData($goodsId);
        if (empty($goodsData))
            $this->error('未找到该商品或该商品已停用或过期');
        if(!in_array($goodsData['goods_type'],['7','15']))
            $this->error('卡券错误，请选择话费/流量包');
        $titleArr = [
            '7'  => '话费',
            '15' => '流量包'
        ];
        $this->assign('goodsData', $goodsData);
        $this->assign('titleArr',$titleArr);
        $this->display();
    }

    /**
     * 流量包发送提交处理
     */
    public function flowPacketSubmit()
    {
        // 0是10人以内发送，1是10人以上发送
        $upload_type = I('post.uploadType','');
        if(!in_array($upload_type,['0','1']))
            $this->error('参数错误');
        $goodsId = I('post.goods_id',null,'trim,mysql_real_escape_string');
        $goodsData = self::getGoodsData($goodsId);
        if (empty($goodsData))
            $this->error('未找到该商品或该商品已停用或过期');
        if(!in_array($goodsData['goods_type'],['7','15']))
            $this->error('卡券错误，请选择话费/流量包');
        if($upload_type == '0')
        {
            $phone_no = I('post.phone_no','','trim,mysql_real_escape_string');
            $phone_no = str_replace('，',',',$phone_no);
            $phone_no = str_replace('\n','',$phone_no);
            $phone_no_arr = explode(',',$phone_no);
            $phone_no_arr = array_filter($phone_no_arr);//去掉空
            if(count($phone_no_arr) > 10)
                $this->error('手机号码数量不得大于10个');
            $phone_no_arr = array_unique($phone_no_arr);//去掉重复
            if(empty($phone_no_arr))
                $this->error('手机号码不得为空');
            array_walk_recursive($phone_no_arr,function(&$item){
                $item = trim($item);
                if(preg_match("/^1[34578]\d{9}$/", $item) == 0)
                    $this->error('手机号码输入有误，错误号码：'.$item);
            });
            
            // 更新卡券库存信息
            self::updateCardInfo($phone_no_arr,$goodsData);
            // 调用接口给这些手机号码发送流量包
            self::sendFlowPacket($phone_no_arr,$goodsData);
            $this->success('发送成功');
        }else{
            $file_info = get_val($_FILES,'phone_no_path');
            if(empty($file_info))
                $this->error('文件不存在');
            if(strcasecmp(substr($file_info['name'],-3), 'csv') != 0)
                $this->error('文件格式错误，请上传csv格式文件');
            $phone_no_arr = self::getPhoneFromFile($file_info['tmp_name']);
            // 更新卡券库存信息
            self::updateCardInfo($phone_no_arr,$goodsData,true);
            $this->success('发送成功');
        }
    }
    /**
     * @param $goodsId
     *  获取卡券数据
     * @return mixed
     */
    private function getGoodsData($goodsId){
        if(!$goodsId)
            $this->error('卡券不存在');
        $map = array(
            'node_id'  => $this->nodeId,
            'goods_id' => $goodsId,
            'status'   => '0'
        );
        $goodsData = M()->table("tgoods_info")->where($map)->find();
        return $goodsData;
    }
    /**
     * @param $file
     *  从csv文件获取手机号码
     * @return array
     */
    private function getPhoneFromFile($file){
        $fp = fopen($file, 'a+');
        $allPhones = []; // 可用号码
        $errPhone  = []; // 错误号码
        while (!feof($fp)) {
            $phoneArr = fgetcsv($fp); // 循环读取
            if (preg_match("/^1[34578]\d{9}$/", $phoneArr[0]) == 1) {
                $allPhones[] = $phoneArr[0];
            } else {
                $errPhone[]  = $phoneArr[0];
            }
        }
        //去除由于打开文件带来的首尾额外未匹配上的字符
        array_shift($errPhone);
        array_pop($errPhone);

        //检测最大上传的手机号数量
        if (count($allPhones) > 1000) {
            $this->error('手机号码不得大于1000条');
        }
        //存储重复的手机号
        $repeatPhone = array();
        $eltPhones = $allPhones;
        foreach ($eltPhones as $key => $value) {
            if (in_array($value, $eltPhones)) {
                unset($eltPhones[$key]);
                if(in_array($value, $eltPhones)){
                    $repeatPhone[] = $value;
                }
            }
        }
        // 返回重复的号码
        if(!empty($repeatPhone))
            $this->error('csv文件中的重复号码为：<br/>'.implode("<br/>",$repeatPhone),'',['type'=>'1']);
        // 返回错误的号码
        if(!empty($errPhone))
            $this->error('csv文件中的错误号码为：<br/>'.implode("<br/>",$errPhone),'',['type'=>'1']);
        return $allPhones;
    }

    /**
     * @param $phone_no_arr
     * @param $goodsData
     * @param $flag [是否为文件上传]
     */
    private function updateCardInfo($phone_no_arr,$goodsData,$flag = false)
    {
        if($goodsData['storage_num'] != '-1' && $goodsData['remain_num'] < count($phone_no_arr))
            $this->error('提交失败，库存不足，错误码：0x000');
        // 开启事务
        $model = M();
        $model->startTrans();
        // 判断是否已经创建了tmarketing_info
        $m_info = $model->table('tmarketing_info')
            ->where(
                array(
                    'node_id'    => $this->nodeId,
                    'batch_type' => '1007'
                )
            )->find();
        // tmarketing_info插入数据 1007是发送到个人
        $market_data['name']       = $goodsData['goods_name'];
        $market_data['batch_type'] = '1007';
        $market_data['node_id']    = $this->nodeId;
        $market_data['start_time'] = date('YmdHis');
        $market_data['end_time']   = date('YmdHis', strtotime('+10 year'));
        $market_data['add_time']   = date('YmdHis');
        if (empty($m_info)) {
            $m_id = $model->table('tmarketing_info')->add($market_data);
            if (!$m_id) {
                $model->rollback();
                $this->error('提交失败，错误码：0x001');
            }
        } else {
            $m_id    = $m_info['id'];
            $m_id_ex = $model->table('tmarketing_info')->where(['id' => $m_id])->save($market_data);
            if (false === $m_id_ex) {
                $model->rollback();
                $this->error('提交失败，错误码：0x002');
            }
        }
        // 判断是否创建了tbatch_info
        $b_info = $model->table('tbatch_info')
            ->where(
                array(
                    'node_id'    => $this->nodeId,
                    'goods_id'   => $goodsData['goods_id']
                )
            )->find();

        $batch_info_data['batch_short_name'] = $goodsData['goods_name'];
        $batch_info_data['batch_no']         = $goodsData['batch_no'];
        $batch_info_data['node_id']          = $this->nodeId;
        $batch_info_data['user_id']          = $this->userInfo['user_id'];
        $batch_info_data['batch_class']      = $goodsData['goods_type'];
        $batch_info_data['batch_amt']        = $goodsData['goods_amt'];
        $batch_info_data['batch_desc']       = I('post.batch_desc',"",'mysql_real_escape_string');//备注
        $batch_info_data['begin_time']       = date('YmdHis');
        $batch_info_data['add_time']         = date('YmdHis');
        $batch_info_data['verify_begin_date'] = date('YmdHis');
        $batch_info_data['verify_end_date']   = date('YmdHis',strtotime('+10 year'));
        $batch_info_data['verify_begin_type'] = '0';
        $batch_info_data['verify_end_type']   = '0';
        $batch_info_data['end_time'] = date('YmdHis', strtotime('+10 year'));
        $batch_info_data['goods_id'] = $goodsData['goods_id'];
        $batch_info_data['m_id']     = $m_id;

        if (empty($b_info))
        {
            $batch_info_data['storage_num'] = count($phone_no_arr);
            $batch_info_data['remain_num']  = 0;
            $b_id = $model->table('tbatch_info')->add($batch_info_data);
            if (!$b_id) {
                $model->rollback();
                $this->error('提交失败，错误码：0x003');
            }
        } else {
            $batch_info_data['storage_num'] = $b_info['storage_num'] + count($phone_no_arr);
            $batch_info_data['remain_num']  = $b_info['remain_num'];
            $b_id    = $b_info['id'];
            $b_id_ex = $model->table('tbatch_info')->where(['id' => $b_id])->save($batch_info_data);
            if (false === $b_id_ex) {
                $model->rollback();
                $this->error('提交失败，错误码：0x004');
            }
        }
        if($flag)
        {
            $importData['batch_no']        = $goodsData['batch_no'];
            $importData['user_id']         = $this->userInfo['user_id'];
            $importData['node_id']         = $this->node_id;
            $importData['total_count']     = count($phone_no_arr);
            $importData['status']          = 0;
            $importData['send_level']      = count($phone_no_arr) > 100 ? 3 : 2;
            $importData['add_time']        = date('YmdHis');
            $importData['send_begin_time'] = date('YmdHis');
            $importData['verify_begin_time'] = date('YmdHis');
            $importData['verify_end_time'] = date('YmdHis',strtotime('+10 year'));
            $importData['validate_times'] = '1';
            $importData['validate_amt']   = $goodsData['goods_amt'];
            $importData['data_from']      = 8;
            $importData['batch_desc']     = I('post.batch_desc',"",'mysql_real_escape_string');//备注
            $importData['b_id']           = $b_id;
                $import_id = $model->table('tbatch_import')->add($importData);
            if (!$import_id) {
                $model->rollback();
                $this->error('提交失败，错误码：0x006');
            }
            // 插入tbatch_importdetail
            foreach ($phone_no_arr as $kp => $vp) {
                $impDetData['batch_no']   = $goodsData['batch_no'];
                $impDetData['batch_id']   = $import_id;
                $impDetData['node_id']    = $this->nodeId;
                $impDetData['request_id'] = time() . sprintf('%04s', mt_rand(0, 1000)) . sprintf('%04s', ($kp + 1));
                $impDetData['orirequest_id'] = "";
                $impDetData['phone_no']   = $vp;
                $impDetData['add_time']   = date('YmdHis');
                $impDet_result            = $model->table('tbatch_importdetail')->add(
                    $impDetData
                );
                if (!$impDet_result) {
                    $model->rollback();
                    $this->error('提交失败，错误码：0x007');
                }
            }
        }

        // tgoods_info减去库存
        $bk_type = [
            '7'  => '话费',
            '15' => '流量包'
        ];
        if ($goodsData['storage_num'] != '-1') {
            $save_data = ['remain_num'=>$goodsData['remain_num']-count($phone_no_arr)];
            $map = ['id'=>$goodsData['id']];
            $ret = $model->table('tgoods_info')->where($map)->save($save_data);
            if(false === $ret)
            {
                $model->rollback();
                $this->error('提交失败，错误码：0x005');
            }
            // 库存流水变动
            self::modifyStorageTrace(
                $goodsData['id'],
                count($phone_no_arr),
                $goodsData['remain_num'],
                $model,
                $_POST,
                16,
                '发布'.$bk_type[$goodsData['goods_type']].'到个人，库存变动'
            );
        } else {
            // 库存流水变动
            self::modifyStorageTraceEx(
                $goodsData['id'],
                count($phone_no_arr),
                $model,
                $_POST,
                16,
                '发布'.$bk_type[$goodsData['goods_type']].'到个人，库存变动'
            );
        }
        $model->commit();
    }

    /**
     * 为每一个号码发送流量包
     * @param $phone_no_arr
     * @param $goodsData
     */
    private function sendFlowPacket($phone_no_arr,$goodsData)
    {
        $b_info = M()->table('tbatch_info')
            ->where(
                array(
                    'node_id'    => $this->nodeId,
                    'goods_id'   => $goodsData['goods_id']
                )
            )->find();
        // 调用接口发送短信,接口中会自动插入流水
        import("@.Vendor.SendCode");
        $req = new SendCode();
        //存储发短信失败的手机号及原因
        $failPhones = array();
        foreach ($phone_no_arr as $value) {
            $resp = $req->wc_send(
                $this->nodeId,
                $this->userId,
                $goodsData['batch_no'],
                $value,
                0,
                null,
                null,
                $b_info['id'],
                null,
                array('batch_desc' => $b_info['batch_desc'],'m_id'=>$b_info['m_id'])
            );
            file_debug($resp, '$resp', 'log.log');
            if ($resp !== true) {
                $failPhones[] = $value . '：' . $resp;
            }
        }
        log_write('$failPhones:'.var_export($failPhones,1));
        if(!empty($failPhones))
        {
            M()->startTrans();
            $save_data = ['storage_num'=>$b_info['storage_num']-count($failPhones)];
            $ret = M()->table('tbatch_info')->where(['id'=>$b_info['id']])->save($save_data);
            if(false === $ret)
            {
                M()->rollback();
                log_write($this->nodeId.'手机号码发送异常'.M()->_sql());
                $this->error('警告：您提交的手机号码未能全部发送成功，且数据异常，请与客户联系解决！','',['type'=>1]);
            }
            if($goodsData['storage_num'] != -1)
            {
                $remain_num = M()->table('tgoods_info')->where(['id'=>$goodsData['id']])->getField('remain_num');
                $ret = M()->table('tgoods_info')->where(['id'=>$goodsData['id']])->setInc('remain_num',count($failPhones));
                if(false === $ret)
                {
                    M()->rollback();
                    log_write($this->nodeId.'手机号码发送异常'.M()->_sql());
                    $this->error('警告：您提交的手机号码未能全部发送成功，且数据异常，请与客户联系解决！','',['type'=>1]);
                }
                // 库存流水变动
                self::modifyStorageTrace(
                    $goodsData['id'],
                    -count($failPhones),
                    $remain_num,
                    M(),
                    $_POST,
                    16,
                    '发布失败，库存回退'
                );

            }else {
                // 库存流水变动
                self::modifyStorageTraceEx(
                    $goodsData['id'],
                    -count($failPhones),
                    M(),
                    $_POST,
                    16,
                    '发布失败，库存回退'
                );
            }
            M()->commit();
            $this->error('警告：您提交的以下手机号码没有发送成功：<br/>'.implode('<br/>',$failPhones),'',['type'=>1]);
        }
    }
    /*****************流量包发送（end）********************/

    /*******************************************
    * 流量包、话费……手动退回到总库存
    */
    public function backToStore()
    {
        $goodsId = I('request.goods_id');
        if(!$goodsId)
            $this->error('参数错误，错误码：0x001');
        $goodsInfo = M('tgoods_info')->where(['node_id'=>$this->nodeId,'goods_id'=>$goodsId])->find();
        if(empty($goodsInfo))
            $this->error('参数错误，错误码：0x002');
        $mobileTraceName = '';
        if($goodsInfo['goods_type'] == '7')
            $mobileTraceName = 'tmobile_bill_send_trace';
        else
            $mobileTraceName = 'tmobile_date_send_trace';
        $map = [
            'goods_id' => $goodsId,
            'node_id'  => ['exp', "in (".$this->nodeIn().")"],
            'status'   => '3'
        ];
        $failureInfo = M($mobileTraceName)->where($map)->select();
        if(empty($failureInfo))
            $this->error('没有可回退的库存');
        $failure_num = count($failureInfo);
        M()->startTrans();
        $ret = M($mobileTraceName)->where($map)->save(['status'=>'5']);
        if(false === $ret)
        {
            M()->rollback();
            $this->error('数据存储错误，错误码：0x005');
        }
        if($goodsInfo['storage_num'] != -1)
        {
            $remain_num = M()->table('tgoods_info')->where(['goods_id'=>$goodsId])->getField('remain_num');
            $ret = M()->table('tgoods_info')->where(['goods_id'=>$goodsId])->setInc('remain_num',$failure_num);
            if(false === $ret)
            {
                M()->rollback();
                $this->error('数据存储错误，错误码：0x006');
            }
            // 库存流水变动
            self::modifyStorageTrace($goodsId, -$failure_num, $remain_num, M(), $_POST, 15, '话费/Q币/流量包库存回退');
        }else {
            // 库存流水变动
            self::modifyStorageTraceEx($goodsId, -$failure_num, M(), $_POST, 15, '话费/Q币/流量包库存回退');
        }

        M()->commit();
        $this->success('库存回退成功');
    }
    
    /**
     * @return ImportModel
     */
    public function getImportModel()
    {
        $data = D('Import');
        return $data;
    }

    /**
     * 卡券数据->发给个人
     */
    public function toPersonal()
    {
        $startTime = I('startTime', '');
        $endTime = I('endTime', '');
        $importModel = $this->getImportModel();

        if (!empty($startTime) && !empty($endTime)) {
            if (intval(str_replace('-', '', $startTime)) > intval(str_replace('-', '', $endTime))) {
                $this->error('时间错误！');
            }
            $data = $importModel->getSendPersonalList(
                    $this->node_id,
                    str_replace('-', '', $startTime) . '000000',
                    str_replace('-', '', $endTime) . '595959'
            );
        } else {
            $data = $importModel->getSendPersonalList($this->node_id);
        }

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('list', $data['list']);
        $this->assign('page', $data['page']);
        $this->display();
    }

    /**
     * 发送卡券失败的数据列表
     */
    public function sendFailList()
    {
        $batchId = I('get.batchId', '');

        $importModel = $this->getImportModel();
        $list        = $importModel->getFailList($this->node_id, $batchId);
        if ($list) {
            $this->ajaxReturn(array('status' => 1, 'info' => $list), 'JSON');
        } else {
            $this->ajaxReturn(array('status' => 0, 'info' => '暂无失败的手机号'), 'JSON');
        }
    }
    /**
     * 卡券重发
     */
    public function reSendToCard()
    {
        $batchId = I('get.batchId', '');
        $importModel = $this->getImportModel();
        $result = $importModel->reBatchSend($this->node_id,$batchId);

        if ($result) {
            $this->ajaxReturn(array('status' => 1, 'info' => '重发成功'), 'JSON');
        } else {
            $this->ajaxReturn(array('status' => 0, 'info' => '重发失败'), 'JSON');
        }
    }
    /**
     * 批量发送卡券详情
     */
    public function toPersonalDetails()
    {
        //验证手机号
        if(IS_AJAX){
            $phone = I('post.phone','');
            $isOk = preg_match("/^1[34578]\d{9}$/", $phone);
            if(empty($phone) || $isOk == 1){
                $this->ajaxReturn(array('status'=>1,'info'=>'success'),'JSON');
            }else{
                $this->ajaxReturn(array('status'=>0,'info'=>'手机号错误'),'JSON');
            }
        }
        //获取页面数据
        $batchId = I('batchId','');
        $importModel = $this->getImportModel();
        $configData = $importModel->batchSendCardConfig($this->node_id, $batchId);         //批量发送的卡券的基本信息
        if(IS_POST){
            $phone = I('post.phone','');
            $queryType = I('post.status',0);
            $list = $importModel->batchSendCardData($this->node_id, $batchId, $phone, $queryType);                 //批量发送卡券的列表信息
            $this->assign('phone',$phone);
            $this->assign('queryType',$queryType);
        }else{
            $list = $importModel->batchSendCardData($this->node_id, $batchId);                 //批量发送卡券的列表信息
        }

        //发送状态
        $status = array(
            '0'     => '正常',
            '1'     => '已发送',
            '2'     => '发送失败',
            '3'     => '发送超时',
            '9'     => '异常'
        );
        $this->assign('configData',$configData);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('status',$status);
        $this->display();
    }

    /**
     * 下载批量发送卡券的详情列表
     */
    public function downloadToSendCard(){
        $batchId = I('get.batchId','');
        if(empty($batchId)){
            $this->error('下载错误');
        }
        $importModel = $this->getImportModel();
        $queryList = $importModel->batchSendCardData($this->node_id,$batchId);
        if(empty($queryList['list'])){
            $this->error('暂无数据可下载');
        }
        $cardName = $importModel->batchSendCardConfig($this->node_id, $batchId);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=sendCard.csv");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo iconv('utf-8', 'gbk', '卡券名称') . ',';
        echo iconv('utf-8', 'gbk', '手机号') . ',';
        echo iconv('utf-8', 'gbk', '状态') . ',';
        echo iconv('utf-8', 'gbk', '说明') . ',';
        echo "\r\n";
        //发送状态
        $status = array(
                '0'     => '正常',
                '1'     => '已发送',
                '2'     => '发送失败',
                '3'     => '发送超时',
                '9'     => '异常'
        );
        foreach ($queryList['list'] as $key => $value) {
            echo iconv('utf-8', 'gbk', $cardName['goods_name']) . ',';
            echo iconv('utf-8', 'gbk', $value['phone_no']) . ',';
            echo iconv('utf-8', 'gbk', $status[$value['status']]) . ',';
            echo iconv('utf-8', 'gbk', $value['ret_desc']) . ',';
            echo "\r\n";
        }
        exit;
    }
    /**
     * 批量发送卡券的页面
     */
    public function numGoodsBatchPublish()
    {
        $sourceData = array(
                'phone_no'      => '',
                'phone_no_path' => '',
                'batch_desc'    => ''
        );
        if (IS_POST) {
            //从发送成功的提示页面过来的时候，需要把数据再给出去
            $sourceData = I('post.');
        }
        $this->assign('data', $sourceData);
        $this->display();

    }

    /**
     * 发送卡券成功后的提示页面
     */
    public function sendCardResultCore()
    {
        $uploadType = I('get.uploadType', '0');           //用来区分页面中是提交的输入框中的手机号还是文件方式提交的手机号
        $this->assign('uploadType', $uploadType);
        $this->display();
    }

    /**
     * 执行批量发送卡券相关操作
     */
    public function performSendCard()
    {
        $goodsId          = I('goods_id', 'mysql_real_escape_string');
        $goods_type       = I('goods_type', "", 'trim,htmlspecialchars');

        $time_type        = I('time_type', "", 'trim,htmlspecialchars');
        $use_start_time   = I('use_start_time', "", 'trim,htmlspecialchars');
        $use_end_time     = I('use_end_time', "", 'trim,htmlspecialchars');
        $later_start_time = I('later_start_time', "", 'trim,htmlspecialchars');
        $later_end_time   = I('later_end_time', "", 'trim,htmlspecialchars');

        $uploadType     = I('uploadType', "", 'trim,htmlspecialchars');
        $phone          = I('phone_no', "", 'trim,htmlspecialchars');           //输入框中的手机号
        $caixin_content = I('caixin_content', "", 'trim,htmlspecialchars');     //使用说明（取卡券信息中的）
        $batch_desc     = I('batch_desc', "", 'trim,htmlspecialchars');         //备注
        $goRepeatPhone  = I('post.goRepeatPhone');         //标记当有重复手机号时是否继续发送  1：是  0否
        $goRepeatFile   = I('post.goRepeatFile');          //标记当有重复文件上传时提示是否继续发送  1：是  0否
        /*******************************卡券的检测处理********************/
        $goodsData = M('tgoods_info')->lock(true)->where(
                        "node_id='{$this->nodeId}' AND goods_id='{$goodsId}' AND status=0"
                )->find();
        if (!$goodsData) {
            $this->error('未找到该卡券或该卡券已停用或过期');
        }
        if (empty($goods_type) && $goods_type != '0') {
            $this->error('卡券类型错误');
        }

        //选择流量包的时候不需要如下验证
        if($goods_type == '15') {
            //卡券时间验证
            if (empty($time_type) && $time_type != '0') {
                $this->error('卡券使用时间类型错误');
            }
            if ($time_type == '1') {
                empty($later_start_time) && $later_start_time != 0 && $this->error('时间填写有误');
                empty($later_end_time) && $this->error('时间填写有误');
                if ($later_start_time > $later_end_time) {
                    $this->error('时间填写有误');
                }
                if ($later_start_time > 1000 || $later_end_time > 1000) {
                    $this->error('时间不得大于1000天');
                }
            } else {
                empty($use_start_time) && $this->error('时间不得为空');
                empty($use_end_time) && $this->error('时间不得为空');
                if ($use_start_time > $use_end_time) {
                    $this->error('时间填写有误');
                }
            }
            empty($caixin_content) && $this->error('使用说明不得为空');
        }
        /*******************************手机号的检测处理********************/
        $allPhones = array();           //存储要发送的手机号
        $errPhone  = array();            //存储错误的手机号
        if (!empty($phone)) {
            $phoneStr  = str_replace("，", ",", $phone);
            $allPhones = explode(',', $phoneStr);
            //存储错误的手机号
            foreach ($allPhones as $key => $value) {
                if (!preg_match("/^1[34578]\d{9}$/", $value)) {
                    $errPhone[] = $value;
                }
            }
        } else {
            //上传的文件
            $file_md5 = '';
            if (!empty($_FILES['phone_no_path']['tmp_name'])) {
                // 判断是否上传了同一个文件
                $file_md5 = md5_file($_FILES['phone_no_path']['tmp_name']);
                if (M('tbatch_import')->getFieldByFile_md5($file_md5, 'file_md5')) {
                    // $goRepeatFile 标记着当遇到重复手机号时是否继续发送      1：是   0：否
                    if ($goRepeatFile != 1) {
                        $this->ajaxReturn(array('status' => 3, 'info' => '您重复提交了文件，请确认是否继续提交'), 'JSON');
                    }
                }
                //从上传的文件中获取手机号（存成数组）
                $data = fopen($_FILES['phone_no_path']['tmp_name'], 'a+');
                while (!feof($data)) {
                    $phoneArr = fgetcsv($data, 1000); // 循环读取
                    if (preg_match("/^1[34578]\d{9}$/", $phoneArr[0]) == 1) {
                        $allPhones[] = $phoneArr[0];
                    } else {
                        $errPhone[] = $phoneArr[0];
                    }
                }
                //去除由于打开文件带来的首尾额外未匹配上的字符
                array_shift($errPhone);
                array_pop($errPhone);

                //检测最大上传的手机号数量
                if (count($allPhones) > 1000) {
                    $this->error('手机号码不得大于1000条');
                }
                //存储重复的手机号
                $repeatPhone = array();
                $eltPhones = $allPhones;
                foreach ($eltPhones as $key => $value) {
                    if (in_array($value, $eltPhones)) {
                        unset($eltPhones[$key]);
                        if(in_array($value, $eltPhones)){
                            $repeatPhone[] = $value;
                        }
                    }
                }
                // $goRepeatPhone 标记着当遇到重复手机号时是否继续发送      1：是   0：否
                if ($goRepeatPhone != 1 && !empty($repeatPhone)) {
                    $this->ajaxReturn(array('status' => 2, 'info' => '重复号码：' . implode(',', $repeatPhone)), 'JSON');
                }
            } else {
                $this->ajaxReturn(array('status' => 0, 'info' => '请输入或上传要发送的手机号'), 'JSON');
            }
        }
        //手机号数量
        $num_limit = count($allPhones);
        if (!empty($errPhone)) {
            //提示错误的手机号（字符串）
            $this->ajaxReturn(array('status' => 0, 'info' => '错误手机号：' . implode(',', $errPhone)), 'JSON');
        }

        //检测卡券库存
        if ($goodsData['storage_num'] != '-1' && $goodsData['remain_num'] < count($allPhones)) {
            $this->ajaxReturn(array('status' => 0, 'info' => '库存不足！'), 'JSON');
        }
        if (mb_strlen($batch_desc, 'utf8') > 50) {
            $this->error('备注不得大于50个字');
        }
        //上传手机号文件存储
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload           = new UploadFile();
        $upload->maxSize  = 3145728;
        $upload->savePath = APP_PATH . 'Upload/publish_model/';
        $upload->uploadOne($_FILES['phone_no_path']);
        $caixin_title = '电子券';
        /*******************************卡券信息和手机号相结合的数据操作********************/
        // 开启事务
        $model = M();
        $model->startTrans();
        // 判断是否已经创建了tmarketing_info和tbatch_info
        $isCreate = M()->table("tbatch_info b")->field(
                'b.id AS b_id,b.m_id AS m_id,b.storage_num'
        )->where(
                        array(
                                'b.node_id'    => $this->node_id,
                                'b.goods_id'   => $goodsId,
                                'm.batch_type' => '1007'
                        )
                )->join('tmarketing_info m ON m.id=b.m_id')->find();
        // tmarketing_info插入数据 1007是发送到个人
        $market_data['name']       = $goodsData['goods_name'];
        $market_data['batch_type'] = '1007';
        $market_data['node_id']    = $this->node_id;
        $market_data['start_time'] = date('YmdHis');
        $market_data['end_time']   = date('YmdHis', strtotime('+10 year'));
        $market_data['add_time']   = date('YmdHis');
        if (empty($isCreate)) {
            $m_id = $model->table('tmarketing_info')->add($market_data);
            if (!$m_id) {
                $model->rollback();
                $this->error('提交失败');
            }
        } else {
            $m_id    = $isCreate['m_id'];
            $m_id_ex = $model->table('tmarketing_info')->where(
                            array(
                                    'id' => $m_id
                            )
                    )->save($market_data);
            if (false === $m_id_ex) {
                $model->rollback();
                $this->error('提交失败');
            }
        }
        // tbatch_info 插入数据
        $batch_info_data['batch_short_name'] = $goodsData['goods_name'];
        $batch_info_data['batch_no']         = $goodsData['batch_no'];
        $batch_info_data['node_id']          = $this->node_id;
        $batch_info_data['user_id']          = $this->userInfo['user_id'];
        $batch_info_data['batch_class']      = $goods_type;
        $batch_info_data['use_rule']         = $caixin_content;
        $batch_info_data['batch_amt']        = $goodsData['goods_amt'];
        $batch_info_data['info_title']       = $caixin_title;
        $batch_info_data['print_text']       = $goodsData['print_text'];
        $batch_info_data['batch_desc']       = $batch_desc;
        $batch_info_data['begin_time']       = date('YmdHis');
        $batch_info_data['add_time']         = date('YmdHis');
        if ($time_type == '1') {
            $batch_info_data['verify_begin_date'] = $later_start_time;
            $batch_info_data['verify_end_date']   = $later_end_time;
            $batch_info_data['verify_begin_type'] = '1';
            $batch_info_data['verify_end_type']   = '1';
        } else {
            $batch_info_data['verify_begin_date'] = $use_start_time . '000000';
            $batch_info_data['verify_end_date']   = $use_end_time . '235959';
            $batch_info_data['verify_begin_type'] = '0';
            $batch_info_data['verify_end_type']   = '0';
        }
        $batch_info_data['end_time'] = date('YmdHis', strtotime('+10 year'));
        $batch_info_data['goods_id'] = $goodsId;
        $batch_info_data['m_id']     = $m_id;
        //自定义短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        if($startUp == 1 && $goods_type != '15'){
            $sms_text = I('cusMsg','');
            if(empty($sms_text)){
                $model->rollback();
                $this->error('短信内容不能空！');
            }else{
                $batch_info_data['sms_text'] = $sms_text;
            }
        }

        if (empty($_FILES['phone_no_path']['tmp_name'])) {
            //输入框中的手机号
            $batch_info_data['storage_num'] = $isCreate['storage_num'] + $num_limit;
            $batch_info_data['remain_num']  = 0;
            if (empty($isCreate)) {
                $b_id = $model->table('tbatch_info')->add($batch_info_data);
                if (!$b_id) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            } else {
                $b_id    = $isCreate['b_id'];
                $b_id_ex = $model->table('tbatch_info')->where(
                                array(
                                        'id' => $b_id
                                )
                        )->save($batch_info_data);
                if (false === $b_id_ex) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            }
            // tgoods_info减去库存
            if ($goodsData['storage_num'] != '-1') {


                // 库存流水变动
                self::modifyStorageTrace(
                        $goodsData['id'],
                        $num_limit,
                        $goodsData['remain_num'],
                        $model,
                        $_POST,
                        16,
                        '批量发布到个人，库存变动'
                );
            } else {
                // 库存流水变动
                self::modifyStorageTraceEx(
                        $goodsData['id'],
                        $num_limit,
                        $model,
                        $_POST,
                        16,
                        '批量发布到个人，库存变动'
                );
            }
            $model->commit();
            // 调用接口发送短信,接口中会自动插入流水
            import("@.Vendor.SendCode");
            $req = new SendCode();
            //存储发短信失败的手机号及原因
            $failPhones = array();
            foreach ($allPhones as $key => $value) {
                $resp = $req->wc_send(
                        $this->nodeId,
                        $this->userId,
                        $goodsData['batch_no'],
                        $value,
                        0,
                        null,
                        null,
                        $b_id,
                        null,
                        array('batch_desc' => $batch_desc)
                );
                if ($resp != true) {
                    $failPhones[] = $value . '：' . $resp;
                }
            }
            //后减库存
            if($goodsData['storage_num'] != '-1'){
                $isSentSucces = $num_limit - count($failPhones);
                if (false === $model->table('tgoods_info')->where(
                                array(
                                        'goods_id' => $goodsId
                                )
                        )->save(
                                array(
                                        'remain_num' => ($goodsData['remain_num'] - $isSentSucces)
                                )
                        )
                ) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            }
            if (!empty($failPhones)) {
                $this->ajaxReturn(array('status' => 0, 'info' => '发送失败！错误发送：' . implode(',', $failPhones)), 'JSON');

            }
            /*
            如果发送失败的时候就停留在当前页面，只有当成功的时候才会页面跳走
            else{
                $this->ajaxReturn(array('status'=>1,'info'=>'发送成功，共发送'.$num_limit.'条短信，'.'已成功发送'.$num_limit.'条'),'JSON');
            }
            */
        } else {
            // 如果有上传文件，添加数据
            $batch_info_data['storage_num'] = $isCreate['storage_num'] + $num_limit;
            $batch_info_data['remain_num']  = 0;
            if (empty($isCreate)) {
                $b_id = $model->table('tbatch_info')->add($batch_info_data);
                if (!$b_id) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            } else {
                $b_id    = $isCreate['b_id'];
                $b_id_ex = $model->table('tbatch_info')->where(
                                array(
                                        'id' => $b_id
                                )
                        )->save($batch_info_data);
                if (false === $b_id_ex) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            }

            $importData['batch_no']        = $goodsData['batch_no'];
            $importData['user_id']         = $this->userInfo['user_id'];
            $importData['node_id']         = $this->node_id;
            $importData['total_count']     = $num_limit;
            $importData['status']          = 0;
            $importData['send_level']      = $num_limit > 100 ? 3 : 2;
            $importData['add_time']        = date('YmdHis');
            $importData['send_begin_time'] = date('YmdHis');
            if ($time_type == '1') {
                $importData['verify_begin_time'] = date(
                                'Ymd',
                                strtotime(
                                        date('YmdHis') . ' +' . $later_start_time . ' day'
                                )
                        ) . '000000';
                $importData['verify_end_time']   = date(
                                'Ymd',
                                strtotime(
                                        date('YmdHis') . ' +' . $later_end_time . ' day'
                                )
                        ) . '235959';
            } else {
                $importData['verify_begin_time'] = $use_start_time . '000000';
                $importData['verify_end_time']   = $use_end_time . '235959';
            }
            $importData['validate_times'] = '1';
            $importData['validate_amt']   = $goodsData['goods_amt'];
            $importData['file_md5']       = $file_md5;
            $importData['file_name']      = $_FILES['phone_no_path']['name'];
            $importData['data_from']      = 8;
            $importData['batch_desc']     = $batch_desc;
            $importData['info_title']     = $caixin_title;
            $importData['mms_notes']      = $caixin_content;
            $importData['notes']          = $caixin_content;
            $importData['b_id']           = $b_id;

            $import_id = $model->table('tbatch_import')->add($importData);
            if (!$import_id) {
                $model->rollback();
                $this->error('提交失败');
            }
            // 判断库存是否足够,tgoods_info减去库存
            if ($goodsData['storage_num'] != '-1') {
                if ($goodsData['remain_num'] < $num_limit) {
                    $model->rollback();
                    $this->error('卡券库存不足');
                }
                //减去库存
                if (false === $model->table('tgoods_info')->where(
                                        array(
                                                'goods_id' => $goodsId
                                        )
                                )->save(
                                        array(
                                                'remain_num' => ($goodsData['remain_num'] - $num_limit)
                                        )
                                )
                ) {
                    $model->rollback();
                    $this->error('提交失败');
                }
                // 库存流水变动
                self::modifyStorageTrace(
                        $goodsData['id'],
                        $num_limit,
                        $goodsData['remain_num'],
                        $model,
                        $_POST,
                        16,
                        '批量发送到个人，库存变动'
                );
            } else {
                // 库存流水变动
                self::modifyStorageTraceEx(
                        $goodsData['id'],
                        $num_limit,
                        $model,
                        $_POST,
                        16,
                        '批量发送到个人，库存变动'
                );
            }

            // 插入tbatch_importdetail
            foreach ($allPhones as $kp => $vp) {
                $impDetData['batch_no']   = $goodsData['batch_no'];
                $impDetData['batch_id']   = $import_id;
                $impDetData['node_id']    = $this->node_id;
                $impDetData['request_id'] = time() . sprintf('%04s', mt_rand(0, 1000)) . sprintf('%04s', ($kp + 1));
                $impDetData['phone_no']   = $vp;
                $impDetData['add_time']   = date('YmdHis');
                $impDet_result            = $model->table('tbatch_importdetail')->add(
                        $impDetData
                );
                if (!$impDet_result) {
                    $model->rollback();
                    $this->error('提交失败');
                }
            }
        }
        $model->commit();
        if (!empty($_FILES['phone_no_path']['tmp_name'])) {
            $phone = $_FILES['phone_no_path']['tmp_name'];
        }
        $this->assign('phone_no', $phone);           //表单中的手机号
        $this->assign('batch_desc', $batch_desc);    //备注
        $this->success('发送成功');
//        $this->display();
    }

    /**
     * 选择卡券的弹窗
     */
    public function choiceCard()
    {
        $m_id        = I('m_id', '');
        $prizeCateId = I('prizeCateId', '');
        $b_id        = I('b_id', '');

        if (!$b_id) { // 如果没有b_id表示添加奖品
            $this->redirect(
                    'Common/SelectJp/indexNew',
                    array(
                            'next_step'    => urlencode(
                                    U(
                                            'WangcaiPc/NumGoods/cardConfig',
                                            array(
                                                    'm_id'              => $m_id,
                                                    'prizeCateId'       => $prizeCateId,
                                                    'availableSendType' => '0'
                                            )
                                    )
                            ),
                            'availableTab' => '1',
                            'availableGoodsType'  => '0,1,2,3,7,8,15',
                            'availableSourceType'   =>'0,1'
                    )
            ); // 给个参数让按钮显示成下一步
        }
    }

    /**
     * 编辑卡券中弹窗中的内容
     */
    public function editCardConfig()
    {
        //短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        $this->assign('startUp', $startUp);
        $this->assign('isEdit', true);
        $this->display('cardConfig');

    }

    /**
     * 选择卡券弹窗配置
     */
    public function cardConfig()
    {
        $prizeId = I('prizeId', '', 'trim'); // 奖品的goods_id
        // 查询准备添加的奖品是否是这个机构下的
        $goodsInfo = M('tgoods_info')->where(array('node_id' => $this->node_id, 'goods_id' => $prizeId))->find();
        if (!$goodsInfo) {
            $this->error('错误卡券', '', true);
        }
        //使用说明
        $txtContent = '';
        $batchInfo  = M('tbatch_info')->field(' a.* ')->join(' a INNER JOIN tmarketing_info b ON a.m_id = b.id')->where(
                        array('a.goods_id' => $prizeId, 'b.batch_type' => '1007')
                )->find();
        if ($batchInfo) {
            $txtContent = $batchInfo['use_rule'];
        }
        $this->assign('goods_remain_num', $goodsInfo['remain_num']);
        $goodsType = $goodsInfo['goods_type'];
        $goodsAmt  = $goodsInfo['goods_amt'];

        // 话费和Q币的使用说明
        if ($goodsType == CommonConst::GOODS_TYPE_HF || $goodsType == CommonConst::GOODS_TYPE_QB) {
            $goodsAmt = intval($goodsAmt);
            if ($goodsType == CommonConst::GOODS_TYPE_HF) {
                $txtContent = '您已获得' . $goodsAmt . '元手机话费，点击[#GET_URL]，提交待充值手机号，即可领取！领取截止时间：[#END_DATE]。';
            }
            if ($goodsType == CommonConst::GOODS_TYPE_QB) {
                $txtContent = '您已获得' . $goodsAmt . '元Q币，点击[#GET_URL],即可领取！领取截止时间：[#END_DATE]。';
            }
        }
        //提领券的使用说明
        if ($goodsType == CommonConst::GOODS_TYPE_TLQ && $goodsInfo['online_verify_flag'] == '1') {
            $txtContent = '本券支持线上提领';
        }
        //获取图片地址
        $goodsInfo['goods_image'] = get_upload_url($goodsInfo['goods_image']);

        //自定义短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');

        /*
        //提示是否为自建卡券及情况说明
        $tipStr = '每张卡券将收取1元异业卡券下发费';
        //            if($goodsData['sale_flag'] == 0){
        //未开通多米收单
        if($goodsInfo['source'] == 0){
            //自建
            $tipStr = '每张卡券将收取0.5元卡券下发费用';
        }
        //            }
        */
        $tipStr = '';
        $this->assign('tipStr', $tipStr);
        /******************整理示例图中的短信文字************/
        //企业简称
        $storeShortName = session('userSessInfo.node_short_name');
        //企业简称的字数差值
        $storeDifference = 6-mb_strlen($storeShortName,'utf8');
        if($storeDifference < 0){          //企业简称字数超出时
            $storeShortName = mb_substr($storeShortName,0,6,'utf8');
            //当卡券名称大于11个字的时候。。。。以下相同
            if(mb_strlen($goodsInfo['goods_name'],'utf8') > 11){
                $cardName = mb_substr($goodsInfo['goods_name'],0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsInfo['goods_name'],0,11,'utf8');
            }
        }elseif($storeDifference > 0){     //企业简称字数有结余时
            if(mb_strlen($goodsInfo['goods_name'],'utf8') > (11+abs($storeDifference))){
                $cardName = mb_substr($goodsInfo['goods_name'],0,(10+abs($storeDifference)),'utf8').'...';
            }else{
                $cardName = mb_substr($goodsInfo['goods_name'],0,(11+abs($storeDifference)),'utf8');
            }
        }else{
            if(mb_strlen($goodsInfo['goods_name'],'utf8') > 11){
                $cardName = mb_substr($goodsInfo['goods_name'],0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsInfo['goods_name'],0,11,'utf8');
            }
        }
        $smsContent = '【'.$storeShortName.'】的'.$cardName;
        $this->assign('smsContent', $smsContent);
        $this->assign('storeDifference', $storeDifference);

        $this->assign('startUp',$startUp);
        $this->assign('batchInfo', $batchInfo);
        $this->assign('txtContent', $txtContent);
        $this->assign('goodInfo', $goodsInfo);
        $this->assign('prizeId', $prizeId);
        $this->display();

    }

    public function publishSuccess() {
        $type = I('type', '');
        if (! in_array($type, 
            array(
                '1', 
                '2'))) {
            $this->error('非法操作');
        }
        $goods_id = I('get.goods_id', '');
        $phone_no = I('get.phone_no', '');
        $this->assign('goods_id', $goods_id);
        $this->assign('phone_no', $phone_no);
        $this->assign('type', $type);
        $this->display();
    }

    /**
     * [getPhones 获取手机号码，并上传文件]
     *
     * @param [file] $file [文件]
     * @param [model] $model [事务处理]
     * @return [array] [1维数组，手机号码]
     */
    private function getPhones($file, $model) {
        C('PUBLISH_MODEL', APP_PATH . 'Upload/publish_model/');
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->maxSize = 3145728;
        $upload->savePath = C('PUBLISH_MODEL');
        $info = $upload->uploadOne($file);
        $flieWay = $upload->savePath . $info['savepath'] . $info[0]['savename'];
        $row = 0;
        $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
        if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
            @unlink($flieWay);
            $model->rollback();
            $this->error('文件类型不符合');
        }
        $insertArr = array();
        if (($handle = fopen($flieWay, "rw")) !== FALSE) {
            while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                ++ $row;
                $arr = utf8Array($arr);
                if ($row == 1) {
                    $fileField = array(
                        '手机号码');
                    $arrDiff = array_diff_assoc($arr, $fileField);
                    if (count($arr) != count($fileField) || ! empty($arrDiff)) {
                        fclose($handle);
                        @unlink($flieWay);
                        $model->rollback();
                        $this->error(
                            '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                    }
                    continue;
                }
                $phone_no = trim(htmlspecialchars($arr[0]));
                if (empty($phone_no)) {
                    continue;
                }
                if (! is_numeric($phone_no) || strlen($phone_no) != 11) {
                    $model->rollback();
                    $this->error('文件第' . $row . '行的手机号码不正确');
                }
                $insertArr[] = $phone_no;
            }
            // 判断要插入的数据
/*            if (! empty($insertArr)) {
                if (count($insertArr) != count(array_unique($insertArr))) {
                    @unlink($flieWay);
                    $model->rollback();
                    $this->error("文件中的手机号码不得重复");
                }
            } else {
                @unlink($flieWay);
                $model->rollback();
                $this->error('您尚未填写任何手机号码!');
            }*/

            if(empty($insertArr)){
                @unlink($flieWay);
                $model->rollback();
                $this->error('您尚未填写任何手机号码!');
            }
            @fclose($handle);
            @unlink($flieWay);
            return $insertArr;
        }
    }


    /**
     * [modifyStorageTrace 库存有限制的记录]
     */
    private function modifyStorageTrace($id, $modNum, $preNum, $model, $post, 
        $opt_type = '3', $desc = '') {
        // 记录变更流水
        $data = array(
            'node_id' => $this->node_id, 
            'goods_id' => $id, 
            'change_num' => abs($modNum), 
            'pre_num' => $preNum, 
            'current_num' => ($preNum - $modNum), 
            'opt_type' => $opt_type, 
            'relation_id' => $this->user_id, 
            'opt_desc' => $desc, 
            'add_time' => date('YmdHis'));
        $flag = $model->table('tgoods_storage_trace')->add($data);
        if ($flag === false) {
            $model->rollback();
            log_write("库存增加失败，原因：" . $model->error());
            $this->error('库存增加失败，请重试！');
        }
        node_log(
            "卡券库存减少，原库存【{$preNum}】，减少数【{$modNum}】，新库存【" . ($preNum - $modNum) .
                 "】", print_r($post, true));
    }

    /**
     * [modifyStorageTraceEx 库存无限制的记录]
     */
    private function modifyStorageTraceEx($id, $modNum, $model, $post, 
        $opt_type = '3', $desc = '') {
        // 记录变更流水
        $data = array(
            'node_id' => $this->node_id, 
            'goods_id' => $id, 
            'change_num' => $modNum, 
            'pre_num' => "", 
            'current_num' => "", 
            'opt_type' => $opt_type, 
            'relation_id' => $this->user_id, 
            'opt_desc' => $desc, 
            'add_time' => date('YmdHis'));
        $flag = $model->table('tgoods_storage_trace')->add($data);
        if ($flag === false) {
            $model->rollback();
            log_write("库存增加失败，原因：" . $model->error());
            $this->error('库存增加失败，请重试！');
        }
        node_log("卡券库存减少，减少库存【{$modNum}】", print_r($post, true));
    }

    /**
     * 卡券发布到旺财APP
     */
    public function numGoodsAppPublish() {
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $goodsData = M()->table("tgoods_info g")->field(
            'g.*,n.busi_contact_name,n.busi_contact_tel,n.busi_contact_eml,n.sale_flag,n.custom_sms_flag')
            ->where(
            "g.node_id='{$this->nodeId}' AND g.goods_id='{$goodsId}' AND g.status=0")
            ->join("tnode_info n ON g.node_id=n.node_id")
            ->find();
        if (! $goodsData)
            $this->error('未找到该商品或该商品已停用或过期');
            // 如果已经发布过门店，禁止显示该页面
        $is_exist = M('tbatch_info_tostore_exp')->getFieldByGoods_id($goodsId, 
            'id');
        if ($is_exist) {
            $this->error('非法操作');
        }
        if (in_array($goodsData['goods_type'], 
            array(
                '7', 
                '8'))) { // 话费Q币设置短彩信内容
            $smsInfo = D('Goods')->getPqText($goodsId);
        }
        //商户是否开通自定义短信内容的标志
        $startUp = $goodsData['custom_sms_flag'];
        //取短信内容
        $smsText = M()->table("tbatch_info b")->field('b.sms_text')->where(
                        array(
                                'b.node_id'    => $this->node_id,
                                'b.goods_id'   => $goodsId,
                                'm.batch_type' => '1007'
                        )
                )->join('tmarketing_info m ON m.id=b.m_id')->find();


        if ($this->isPost()) {
            $goods_type = I('goods_type', "", 'trim,htmlspecialchars');
            $material_code = I('elecNo', "", 'trim,htmlspecialchars');
            $shop_type = I('shop', "", 'trim,htmlspecialchars');
            $shop_idstr = I('openStores', "", 'trim,htmlspecialchars');
            $num_limit = I('num_limit', "", 'trim,htmlspecialchars');
            $goods_begin_time = I('goods_begin_time', "", 
                'trim,htmlspecialchars');
            $goods_end_time = I('goods_end_time', "", 'trim,htmlspecialchars');
            $use_start_time = I('use_start_time', "", 'trim,htmlspecialchars');
            $use_end_time = I('use_end_time', "", 'trim,htmlspecialchars');
            $caixin_title = I('caixin_title', "", 'trim,htmlspecialchars');
            $caixin_content = I('caixin_content', "", 'trim,htmlspecialchars');
            if (! empty($smsInfo))
                $caixin_content = $smsInfo['content'];
                // 对数据进行验证
            (empty($goods_type) && $goods_type != '0') && $this->error('非法提交');
            (! is_numeric($material_code) || strlen($material_code) != '4') &&
                 $this->error('卡券编号填写错误');
            empty($shop_type) && $this->error('非法提交');
            if ($shop_type == '2') {
                empty($shop_idstr) && $this->error('请选择验证门店');
            }
            (! is_numeric($num_limit) || $num_limit == 0 || $num_limit == "") &&
                 $this->error("请填写正确的数量");
            if ($goodsData['storage_num'] != '-1') {
                ($goodsData['remain_num'] < $num_limit) &&
                     $this->error('数量不得大于库存');
            }
            empty($goods_begin_time) && $this->error('有效期开始时间不得为空');
            empty($goods_end_time) && $this->error('有效期结束时间不得为空');
            if ($goods_begin_time > $goods_end_time) {
                $this->error('有效期开始时间不得大于结束时间');
            }
            empty($use_start_time) && $this->error('使用开始时间不得为空');
            empty($use_end_time) && $this->error('使用结束时间不得为空');
            if ($use_start_time > $use_end_time) {
                $this->error('使用开始时间不得大于结束时间');
            }
//            empty($caixin_title) && $this->error('彩信标题不得为空');
            $caixin_title = '电子券';
            
            empty($caixin_content) && $this->error('使用说明不能为空');
            // 开启事务
            $model = M();
            $model->startTrans();
            // tmarketing_info插入数据,1006是发送到APP
            $market_data['name'] = $goodsData['goods_name'];
            $market_data['batch_type'] = '1006';
            $market_data['node_id'] = $this->node_id;
            $market_data['start_time'] = date('Ymd000000');
            $market_data['end_time'] = $use_end_time . '235959';
            $market_data['add_time'] = date('YmdHis');
            $m_id = $model->table('tmarketing_info')->add($market_data);
            if (! $m_id) {
                $model->rollback();
                $this->error('提交失败');
            }
            // tbatch_info 插入数据
            $batch_info_data['batch_short_name'] = $goodsData['goods_name'];
            $batch_info_data['batch_no'] = $goodsData['batch_no'];
            $batch_info_data['node_id'] = $this->node_id;
            $batch_info_data['user_id'] = $this->userInfo['user_id'];
            $batch_info_data['material_code'] = $material_code;
            $batch_info_data['batch_class'] = $goods_type;
            $batch_info_data['batch_amt'] = $goodsData['goods_amt'];
            $batch_info_data['use_rule'] = $caixin_content;
            $batch_info_data['info_title'] = $caixin_title;
            $batch_info_data['begin_time'] = $goods_begin_time . '000000';
            $batch_info_data['add_time'] = date('YmdHis');
            $batch_info_data['verify_begin_date'] = $use_start_time . '000000';
            $batch_info_data['verify_end_date'] = $use_end_time . '235959';
            $batch_info_data['verify_begin_type'] = '0';
            $batch_info_data['verify_end_type'] = '0';
            $batch_info_data['end_time'] = $goods_end_time . '235959';
            $batch_info_data['storage_num'] = $num_limit;
            $batch_info_data['remain_num'] = $num_limit;
            $batch_info_data['goods_id'] = $goodsId;
            $batch_info_data['m_id'] = $m_id;

            //自定义短信内容
            if($startUp == 1){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    $this->error('短信内容不能空');
                }else{
                    $batch_info_data['sms_text'] = $sms_text;
                }
            }

            $b_id = $model->table('tbatch_info')->add($batch_info_data);
            if (! $b_id) {
                $model->rollback();
                $this->error('提交失败');
            }
            // tgoods_info减去库存
            if ($goodsData['storage_num'] != '-1') {
                if (false === M('tgoods_info')->where(
                    array(
                        'goods_id' => $goodsId))->save(
                    array(
                        'remain_num' => ($goodsData['remain_num'] - $num_limit)))) {
                    $model->rollback();
                    $this->error('提交失败');
                }
                // 库存流水变动
                self::modifyStorageTrace($goodsData['id'], $num_limit, 
                    $goodsData['remain_num'], $model, $_POST, 17, 
                    '发布到旺财APP，库存变动');
            } else {
                // 库存流水变动
                self::modifyStorageTraceEx($goodsData['id'], $num_limit, $model, 
                    $_POST, 17, '发布到旺财APP，库存变动');
            }
            // tbatch_info_tostore_exp插入数据
            $store_data['node_id'] = $this->node_id;
            $store_data['b_id'] = $b_id;
            $store_data['type'] = $shop_type;
            $store_data['goods_id'] = $goodsId;
            $store_data['store_id_list'] = $shop_idstr;
            $store_data['add_time'] = date('YmdHis');

            if($shop_type == '2'){
                $store_data['store_id_list'] = $shop_idstr;
            }else{
                $store_data['store_id_list'] = '';
            }
            $store_result = $model->table('tbatch_info_tostore_exp')->add(
                $store_data);
            if ($store_result) {
                $model->commit();
                $ajaxData['goods_id'] = $goodsId;
                $this->success('提交成功', '', $ajaxData);
            } else {
                $model->rollback();
                $this->error('提交失败');
            }
        }
        $publishNum = M('thall_goods')->where(
            "node_id='{$this->nodeId}' and check_status='1'")->count();
        /*
        //提示是否为自建卡券及情况说明
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $tipStr = '每张卡券将收取'.$sendPrice['buy'].'元异业卡券下发费';
        if($goodsData['source'] == 0){//自建
            $tipStr = '每张卡券将收取'.$sendPrice['self'].'元卡券下发费用';
        }
        */
        $tipStr = '';
        /******************整理示例图中的短信文字************/
        //企业简称
        $storeShortName = session('userSessInfo.node_short_name');
        //企业简称的字数差值
        $storeDifference = 6-mb_strlen($storeShortName,'utf8');
        if($storeDifference < 0){          //企业简称字数超出时
            $storeShortName = mb_substr($storeShortName,0,6,'utf8');
            //当卡券名称大于11个字的时候。。。。以下相同
            if(mb_strlen($goodsData['goods_name'],'utf8') > 11){
                $cardName = mb_substr($goodsData['goods_name'],0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsData['goods_name'],0,11,'utf8');
            }
        }elseif($storeDifference > 0){     //企业简称字数有结余时
            if(mb_strlen($goodsData['goods_name'],'utf8') > (11+abs($storeDifference))){
                $cardName = mb_substr($goodsData['goods_name'],0,(10+abs($storeDifference)),'utf8').'...';
            }else{
                $cardName = mb_substr($goodsData['goods_name'],0,(11+abs($storeDifference)),'utf8');
            }
        }else{
            if(mb_strlen($goodsData['goods_name'],'utf8') > 11){
                $cardName = mb_substr($goodsData['goods_name'],0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsData['goods_name'],0,11,'utf8');
            }
        }
        $smsContent = '【'.$storeShortName.'】的'.$cardName;
        $this->assign('smsContent', $smsContent);
        $this->assign('storeDifference', $storeDifference);

        $this->assign('startUp', $startUp);
        $this->assign('smsText', $smsText['sms_text']);
        $this->assign('tipStr', $tipStr);
        $this->assign('goodsData', $goodsData);
        $this->assign('publishNum', $publishNum);
        $this->display();
        $this->assign('smsInfo', $smsInfo);
    }

    public function loadModel() {
        C('PUBLISH_MODEL', APP_PATH . 'Upload/publish_model/');
        // 新建一个文件夹，下面的函数上传需要用到
        if (! file_exists(APP_PATH . 'Upload/publish_model/')) {
            mkdir(APP_PATH . 'Upload/publish_model/');
        }
        header("Content-type:text/csv");
        header("Content-Disposition: attachment;filename=publish_tpl.csv ");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $rs = array(
            array(
                '手机号码'),
            array(
                $this->nodeInfo['contact_phone']));
        $str = '';
        foreach ($rs as $row) {
            $str_arr = array();
            foreach ($row as $column) {
                $str_arr[] = '"' .
                     str_replace('"', '""', iconv('utf-8', 'gb2312', $column)) .
                     '"';
            }
            $str .= implode(',', $str_arr) . PHP_EOL;
        }
        echo $str;
    }

    /**
     * 验码时间校验(已取消)
     */
    public function checkVerifyDate() {
        $error = '';
        $sendBeginTime = I('post.send_begin_date');
        if (! check_str($sendBeginTime, 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Ymd'), $error)) {
            $this->error("卡券发送开始时间{$error}");
        }
        $sendBeginTime .= '000000';
        $sendEndTime = I('post.send_end_date');
        if (! check_str($sendEndTime, 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Ymd'), $error)) {
            $this->error("卡券发送结束时间{$error}");
        }
        if ($sendEndTime < date('Ymd')) {
            $this->error('卡券发送结束时间不能小于当前时间');
        }
        $sendEndTime .= '235959';
        if (strtotime($sendEndTime) < strtotime($sendBeginTime)) {
            $this->error('卡券发送开始时间不能小于卡券发送结束时间');
        }
        
        // 验码开始时间转换成日期格式
        $verifyBeginType = I('post.verify_begin_type');
        switch ($verifyBeginType) {
            case '0':
                $verifyBeginDate = I('post.verify_begin_date');
                if (! check_str($verifyBeginDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("卡券使用开始时间{$error}");
                }
                $verifyBeginDate .= '000000';
                $beginverifyDate = $verifyBeginDate;
                break;
            case '1':
                $verifyBeginDate = I('post.verify_begin_days');
                if (! check_str($verifyBeginDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("卡券使用起始天数{$error}");
                }
                // 验码开始日期 = 发码结束日期 + 验码开始天数
                $beginverifyDate = date('Ymd000000', 
                    strtotime($sendEndTime) + $verifyBeginDate * 24 * 3600);
                break;
            default:
                $this->error('请填卡券使用时间信息');
        }
        // 验码结束时间转换成日期格式
        $verifyEndType = I('post.verify_end_type');
        switch ($verifyEndType) {
            case '0':
                $verifyEndDate = I('post.verify_end_date');
                if (! check_str($verifyEndDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("卡券使用结束时间{$error}");
                }
                $verifyEndDate .= '235959';
                $endverifyDate = $verifyEndDate;
                break;
            case '1':
                $verifyEndDate = I('post.verify_end_days');
                if (! check_str($verifyEndDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("卡券使用结束天数{$error}");
                }
                // 验码结束日期 = 发码结束日期 + 验码结束天数
                $endverifyDate = date('Ymd235959', 
                    strtotime($sendEndTime) + $verifyEndDate * 24 * 3600);
                break;
            default:
                $this->error('请填卡券使用时间信息');
        }
        // 验码结束时间要大于等于发码结束时间
        if ($sendEndTime > $endverifyDate)
            $this->error('卡券使用结束时间要大于卡券发送结束时间');
            // 验码结束时间要大于等于发码结束时间
        if ($beginverifyDate > $endverifyDate)
            $this->error('卡券使用开始时间不能大于卡券使用结束时间');
            // 计算活动开始时间和结束时间
        $beginTime = $sendBeginTime;
        // 类型为日期：活动结束时间=发码结束时间和验码结束时间两者之间的最大值
        strtotime($sendEndTime) > strtotime($endverifyDate) ? $endTime = $sendEndTime : $endTime = $endverifyDate;
        $verifyData = array(
            'begin_time' => $beginTime,  // 活动开始时间
            'end_time' => $endTime,  // 活动结束时间
            'send_begin_date' => $sendBeginTime,  // 发码开始时间
            'send_end_date' => $sendEndTime,  // 发码结束时间
            'verify_begin_date' => $verifyBeginDate,  // 验码开始时间或天数
            'verify_end_date' => $verifyEndDate,  // 验码结束时间或天数
            'verify_begin_type' => $verifyBeginType,  // 验码开始时间类型
            'verify_end_type' => $verifyEndType); // 验码结束时间类型
        
        return $verifyData;
    }
    
    // 卡券编辑
    public function numGoodsEdit() {
        if ($this->nodeId == C('withDraw.createNodeId')) {
            $this->assign('type', 'yimaSellRice');
        }
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $goodsData = M('tgoods_info')->where("node_id='{$this->nodeId}' AND goods_id='{$goodsId}'")->find();
        if (! $goodsData) $this->error('未找到该商品');
            // 获取门店信息
        if ($goodsData['pos_group_type'] == '2') {
            $onlineShopStoreId = M('tstore_info')->where(array('node_id' => $this->nodeId,'type' => '3','status' => '0'))->getfield('store_id');
            if ($onlineShopStoreId != '') {
                $storeData = M('tgroup_pos_relation')->field('store_id')->where("group_id={$goodsData['pos_group']} AND store_id <> {$onlineShopStoreId}")->select();
            } else {
                $storeData = M('tgroup_pos_relation')->field('store_id')->where("group_id={$goodsData['pos_group']}")->select();
            }
            $oldStoreArr = array();
            foreach ($storeData as $v) {
                $oldStoreArr[] = $v['store_id'];
            }
            $oldStoreArr = array_unique($oldStoreArr);
        }
        // 是否已发布到微信卡券
        $wxCardModel = D('WeixinCard');
        $isSyncWxCard = $wxCardModel->checkSyncFornumGoods($goodsId);
        if ($this->isPost()) {
            // 编辑的记录日志
            $log_arr = array();
            $onlineVerify = '0';
            $log_arr['pre_goods_end_date'] = I('post.pre_goods_end_date');
            // 打印小票
            $printText = I('post.print_text');
            if (! check_str($printText,array('null' => false,'maxlen_cn' => '100'), $error)) {
                $this->error("打印小票内容{$error}");
            }
            $log_arr['print_text'] = I('post.print_text');
            $log_arr['pre_print_text'] = I('post.pre_print_text');
            // 线上提领
            if ($goodsData['goods_type'] == '2') {
                $onlineVerify = I('post.online_verify_flag');
                $onlineStoreInfo = M('tstore_info')->where(array('node_id' => $this->nodeId,'status' => 0,'type' => 3))->find();
                if ($onlineVerify == 1 && empty($onlineStoreInfo)) { // 是否支持线上门店
                	$this->error("您尚未开通线上门店");
                }
            }
            
            // 使用须知
            // $goodsDesc = I('post.goods_desc');
            // if(!check_str($goodsDesc,array('null'=>true,'maxlen_cn'=>'200'),$error)){
            // $this->error("使用须知{$error}");
            // }
            $log_arr['goods_desc'] = I('post.goods_desc');
            $log_arr['pre_goods_desc'] = I('post.pre_goods_desc');
            $goodImage = I('post.img_resp');
            if (! check_str($goodImage,array('null' => false,'maxlen_cn' => '100'), $error)) {
                $this->error("请上传卡券图片");
            }
            $log_arr['img_resp'] = I('post.img_resp');
            $log_arr['pre_img_resp'] = I('post.pre_img_resp');
            // 同步微信卡券数据校验
            $isCreatWx = I('is_createWx');
            if ($isCreatWx && ! $isSyncWxCard) {
                $isBindWx = D('WeelCjSet')->isBindCertWxAccount($this->nodeId);
                if (! $isBindWx)
                    $this->error('微信公众号还未授权绑定至旺财,无法创建微信卡券');
                    // 使用方式 1-投放 2-预存
                $wxStoreMode = I('useType', null, 'mysql_real_escape_string');
                if (! check_str($wxStoreMode,array('null' => false), $error)) {
                    $this->error("请选择微信卡券使用方式");
                }
                // 商户名称
                $wxNodeName = I('node_name', null, 'mysql_real_escape_string');
                if (! check_str($wxNodeName,array('null' => false,'maxlen_cn' => '12'), $error)) {
                    $this->error("微信卡券商家名称{$error}");
                }
                // 卡卷标题
                $wxTitle = I('title', null, 'mysql_real_escape_string');
                if (! check_str($wxTitle,array('null' => false,'maxlen_cn' => '9'), $error)) {
                    $this->error("微信卡券标题{$error}");
                }
                // 副标题
                $wxSubTitle = I('sub_title', null, 'mysql_real_escape_string');
                if (! check_str($wxSubTitle,array('null' => ture,'maxlen_cn' => '18'), $error)) {
                    $this->error("微信卡券副标题{$error}");
                }
                $wxDefaultDetail = '';
                $wxLeastCost = '0';
                $wxReduceCost = '0';
                $wxGift = '';
                $wxDiscount = '0';
                switch ($goodsData['goods_type']) {
                    case '0': // 优惠券
                        $wxDefaultDetail = I('default_detail');
                        if (! check_str($wxDefaultDetail,array('null' => false,'maxlen_cn' => '500'), $error)) {
                            $this->error("微信卡券优惠详情{$error}");
                        }
                        $wxGoodsType = '3';
                        break;
                    case '1': // 代金卷
                        $wxLeastCost = I('least_cost');
                        if (! check_str($wxLeastCost,array('null' => true,'strtype' => 'number','minval' => '0'), $error)) {
                            $this->error("微信卡券抵扣条件{$error}");
                        }
                        // 减免金额不能为0
                        $wxReduceCost = I('reduce_cost');
                        if (! check_str($wxReduceCost,array('null' => true,'strtype' => 'number','minval' => '1'), $error)) {
                            $this->error("微信卡券减免金额要大于0");
                        }
                        $wxGoodsType = '1';
                        break;
                    case '2': // 提领券
                        $wxGift = I('gift');
                        if (! check_str($wxGift,array('null' => false,'maxlen_cn' => '100'), $error)) {
                            $this->error("微信卡券礼品内容{$error}");
                        }
                        $wxGoodsType = '2';
                        $wxSendDetail = I('post.send_withdrow_detail');
                        if ($wxSendDetail == 1) {
                            $isSendWithDrowDetail = '1';
                        }
                        break;
                    case '3': // 折扣券
                        $wxDiscount = I('c_discount', null,'mysql_real_escape_string');
                        if (! check_str($wxDiscount,array('null' => true,'strtype' => 'number','minval' => '0'), $error)) {
                            $this->error("微信卡券折扣额度{$error}");
                        }
                        $wxGoodsType = '0';
                        break;
                    default:
                        $this->error('未知的微信卡券类型');
                }
                // 卡券颜色
                $wxCardColor = I('card_color', null, 'mysql_real_escape_string');
                if (! check_str($wxCardColor,array('null' => false), $error)) {
                    $this->error("请选择微信卡券颜色");
                }
                // 领券限制
                $wxGetLimit = I('get_limit', null, 'mysql_real_escape_string');
                if (! check_str($wxGetLimit,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
                    $this->error("微信卡券领券限制{$error}");
                }
                // 用户分享
                $wxCanGiveFriend = I('can_give_friend', null,'mysql_real_escape_string');
                if (! check_str($wxCanGiveFriend,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '2'), $error)) {
                    $this->error("微信卡券用户分享信息有误");
                }
                // 销券设置
                $wxCodeType = I('code_type', null, 'mysql_real_escape_string');
                if (! check_str($wxCodeType,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '3'), $error)) {
                    $this->error("微信卡券销券设置信息有误");
                }
                // 使用须知
                $wxDescription = I('description');
                if (! check_str($wxDescription,array('null' => false,'maxlen_cn' => '500'), $error)) {
                    $this->error("微信卡券使用须知{$error}");
                }
                // 客服电话
                $wxServicePhone = I('service_phone', null,'mysql_real_escape_string');
                // 图片处理
                $wxNodeImg = I('node_img', null);
                
                // 卡券日期处理
                $wxBeginDate = I('post.start_time');
                if (! check_str($wxBeginDate,array('null' => false,'strtype' => 'datetime','format' => 'Ymd'), $error)) {
                    $this->error("微信卡券使用开始时间日期{$error}");
                }
                $wxEndDate = I('post.end_time');
                if (! check_str($wxEndDate,array('null' => false,'strtype' => 'datetime','format' => 'Ymd'), $error)) {
                    $this->error("微信卡券使用结束时间日期{$error}");
                }
                if ($wxEndDate < $wxBeginDate) {
                    $this->error('微信卡券有效期开始日期不能大于结束时间');
                }
                $dateBeginTimestamp = strtotime($wxBeginDate . '000000');
                $dateEndTimestamp = strtotime($wxEndDate . '235959');
                
                $quantity = $goodsData['remain_num'];
            }
            // 图片处理
            $smilId = null;
            if ($goodImage != $goodsData['goods_image']) {
                // 卡券图片移动
                $smilId = D('Goods')->getSmil($goodImage, $goodsData['goods_name'], $this->nodeId);
                if (! $smilId) $this->error('创建失败:smilid获取失败');
            }
            
            $goodsModel = D('Goods');
            $data = array( // 更新数据
                'goods_image' => $goodImage, 
                'print_text' => $printText, 
                // 'begin_time' => $goodsData['begin_time'],
                // 'end_time' => $goodsEndDate.'235959',
                'status' => '0', 
                // 'goods_desc' => $goodsDesc,
                'online_verify_flag' => $onlineVerify == '1' ? 1 : 0
            );
            $goodsModel->startTrans();
            
            // 门店修改
            $shop = I('post.shop');
            $log_arr['shop'] = I('post.shop');
            $log_arr['pre_shop'] = I('post.pre_shop');
            $log_arr['shop_idstr'] = I('post.openStores', '');
            $log_arr['pre_shop_idstr'] = I('post.pre_shop_idstr', '');
            if ($goodsData['goods_type'] == '2') {
                $log_arr['onlineVerify'] = I('post.online_verify_flag');
                $log_arr['preOnlineVerify'] = I('post.preOnlineVerify');
            }
            switch ($shop) {
                case '1':
                    if ($goodsData['pos_group_type'] == '2') { // 子门店变为全门店
                        $groupId = $goodsModel->zcModifyStore($this->nodeId, 
                            $goodsData['p_goods_id'], '4');
                        if (! $groupId) {
                            $goodsModel->rollback();
                            $this->error($goodsModel->getError());
                        }
                        // 新建合约
                        $nodeList = M()->query($this->nodeIn(null, true));
                        $groupData = array( // tpos_group
                            'node_id' => $this->nodeId, 
                            'group_id' => $groupId, 
                            'group_name' => $this->nodeId . '商户型-终端组', 
                            'group_type' => '0', 
                            'status' => '0'
                        );
                        $result = M('tpos_group')->add($groupData);
                        if (! $result) {
                            $goodsModel->rollback();
                            $this->error('终端数据创建失败01');
                        }
                        foreach ($nodeList as $v) {
                            $data_1 = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id']
                            );
                            $result = M('tgroup_pos_relation')->add($data_1);
                            if (! $result) {
                                $goodsModel->rollback();
                                $this->error('终端数据创建失败02');
                            }
                        }
                        $data['pos_group'] = $groupId;
                        $data['pos_group_type'] = $shop;
                    }
                    break;
                case '2':
                    $storeList = I('post.openStores', '');
                    empty($storeList) ? $shopList = array() : $shopList = explode(',', $storeList);
                    if ($onlineVerify == '1') { // 线上门店处理
                        $shopList[] = $onlineStoreInfo['store_id'];
                    }
                    if (! is_array($shopList) || empty($shopList)){
                        $goodsModel->rollback();
                        $this->error('请选择核验门店');
                    }
                    // 获取所选门店的所有终端
                    $where = array(
                        's.store_id' => array('in',array_unique($shopList)), 
                        's.node_id' => array('exp',"in ({$this->nodeIn()})"), 
                        's.pos_range' => array('gt','0'));
                    $posData = M()->table("tstore_info s")->field('p.pos_id,p.store_id,p.node_id')
                        ->join('tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                        ->where($where)
                        ->select();
                    if(empty($posData)){
                        $goodsModel->rollback();
                        $this->error('未找到门店信息');
                    }
                    // 获取有效的门店和过滤非法$shopList
                    $newStoreArr = array();
                    foreach ($posData as $v) {
                        if(empty($v['pos_id'])){
                            $goodsModel->rollback();
                            $this->error("未找到门店[{$v['store_id']}]的终端信息");
                        }
                        $newStoreArr[] = $v['store_id'];
                    }
                    $newStoreArr = array_unique($newStoreArr);
                    $arrayDiff = array_diff($newStoreArr, $oldStoreArr);
                    if ($goodsData['pos_group_type'] == '1' || count($newStoreArr) != count($oldStoreArr) || !empty($arrayDiff)) { // 全门店变成子门店或门店增加减少
                        $groupId = $goodsModel->zcModifyStore($this->nodeId,$goodsData['p_goods_id'],'2',implode(',', $newStoreArr));
                        if (! $groupId) {
                            $goodsModel->rollback();
                            $this->error($goodsModel->getError());
                        }
                        $num = M('tpos_group')->where("group_id='{$groupId}' AND node_id='{$this->nodeId}'")->count();
                        if ($num != '0') { // 删除旧合约
                            $result = M('tpos_group')->where("group_id='{$groupId}' AND node_id={$this->nodeId}")->delete();
                            if ($result === false) {
                                $goodsModel->rollback();
                                $this->error('数据出错,旧合约删除失败01');
                            }
                            $result = M('tgroup_pos_relation')->where("group_id='{$groupId}' AND node_id={$this->nodeId}")->delete();
                            if ($result === false) {
                                $goodsModel->rollback();
                                $this->error('数据出错,旧合约删除失败02');
                            }
                        }
                        // 创建新合约
                        $groupData = array( // tpos_group
                            'node_id' => $this->nodeId, 
                            'group_id' => $groupId, 
                            'group_name' => $this->nodeId . '终端型-终端组', 
                            'group_type' => '1', 
                            'status' => '0'
                        );
                        $result = M('tpos_group')->add($groupData);
                        if (! $result) {
                            $goodsModel->rollback();
                            $this->error('终端数据创建失败03');
                        }
                        foreach ($posData as $v) {
                            $data_2 = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id'], 
                                'store_id' => $v['store_id'], 
                                'pos_id' => $v['pos_id']
                            );
                            $result = M('tgroup_pos_relation')->add($data_2);
                            if (! $result) {
                                $goodsModel->rollback();
                                $this->error('终端数据创建失败04');
                            }
                        }
                        $data['pos_group'] = $groupId;
                        $data['pos_group_type'] = $shop;
                    }
                    break;
                default:
                    $this->error('请选择核验方式');
            }
            $specialTag = $onlineVerify == '1' ? '01' : '0';
            $result = $goodsModel->zcModifyBatch($this->nodeId,$goodsData['batch_no'],$goodsData['goods_name'],$goodsData['goods_name'],$goodsData['add_time'],$printText,$smilId,$specialTag);
            if (! $result) {
                $goodsModel->rollback();
                $this->error($goodsModel->getError());
            }
            $result = M('tgoods_info')->where("goods_id='{$goodsId}'")->save($data);
            
            $wxCardStatus = '0'; // 卡券创建状态 0-失败 1-成功
            $wxMsg = ''; // 卡券创建成功或错误信息
            if ($result === false) {
                $goodsModel->rollback();
                $this->error('数据出错,更新失败');
            } else {
                $goodsModel->commit();
                if ($isCreatWx && ! $isSyncWxCard) { // 微信卡券添加
                    $wxData = array(
                        'node_id' => $this->nodeId, 
                        'user_id' => $this->userId, 
                        'goods_id' => $goodsId, 
                        'card_type' => $wxGoodsType, 
                        'logo_url' => get_upload_url($wxNodeImg), 
                        'code_type' => $wxCodeType, 
                        'brand_name' => $wxNodeName, 
                        'title' => $wxTitle, 
                        'sub_title' => $wxSubTitle, 
                        'color' => $wxCardColor, 
                        'notice' => '使用时向营业员出示二维码或辅助码', 
                        'service_phone' => $wxServicePhone, 
                        'description' => $wxDescription, 
                        'get_limit' => $wxGetLimit, 
                        'can_give_friend' => $wxCanGiveFriend, 
                        'date_type' => '1', 
                        'date_begin_timestamp' => $dateBeginTimestamp, 
                        'date_end_timestamp' => $dateEndTimestamp, 
                        'quantity' => $quantity, 
                        'gift' => $wxGift, 
                        'default_detail' => $wxDefaultDetail, 
                        'least_cost' => $wxLeastCost, 
                        'reduce_cost' => $wxReduceCost, 
                        'discount' => $wxDiscount, 
                        'store_type' => '酒店', 
                        'add_time' => date("YmdHis"), 
                        'store_mode' => $wxStoreMode, 
                        'send_withdrow_detail' => $isSendWithDrowDetail);
                    if ($wxStoreMode == '2') {
                        $wxData['store_modify_num'] = $quantity;
                    }
                    // 数据插入
                    $wxCardModel = D('WeixinCard');
                    M()->startTrans();
                    $resutl = $wxCardModel->addWxCard($wxData);
                    if ($resutl) {
                        M()->commit();
                        $wxCardStatus = '1';
                        $wxMsg = '微信卡券创建成功';
                    } else {
                        M()->rollback();
                        $wxMsg = $wxCardModel->getError();
                    }
                }
            }
            $log_detail = json_encode($log_arr);
            node_log("营销编辑，名称：" . $goodsData['goods_name'], $log_detail,array('log_type' => 'edit' . $goodsId));
            $ajaxData['isCreatWx'] = $isCreatWx;
            $ajaxData['wxCardStatus'] = $wxCardStatus;
            $ajaxData['wxMsg'] = $wxMsg;
            $this->success('创建成功', '', $ajaxData);
            exit();
        }
        // 卡券颜色
        $color = D('WeiXinCard', 'Service')->getcolors();
        
        $this->assign('color', $color['colors']);
        $this->assign('isSyncWxCard', $isSyncWxCard);
        $this->assign('storeArr', $oldStoreArr);
        $this->assign('goodsData', $goodsData);
        $this->display();
    }
    
    // 卡券统计数据
    public function codeCount() {
        $sort_field = I('sort_field', '');
        if ($sort_field != '') {
            do {
                $arr = explode('|', $sort_field);
                if (count($arr) != 2)
                    break;
                $sort_sql = $arr[0] . ($arr[1] == 'asc' ? ' asc' : ' desc');
            }
            while (0);
        }
        
        $map = array(
            'g.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'g.source' => array(
                'in', 
                '0,1,4'));
        if ('' == $sort_field) {
            $sort_sql = 'codeEfficiency DESC,send_num DESC';
        }
        
        $goodsModel = D('Goods');
        // $goodsModel->getTopVerifyGoods(array('exp',"in
        // ({$this->nodeIn()})"),$sTime=null,$eTime=null, $source=null);
        
        $seachStatus = 0; // 更多筛选状态
                          // 处理特殊查询字段
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }

        $badd_time = I('badd_time', null, 'mysql_real_escape_string');
        $eadd_time = I('eadd_time', null, 'mysql_real_escape_string');
        if ($badd_time == null || $eadd_time == null) {
            $badd_time = date('Ym01', strtotime(date("Y-m-d")));
            $eadd_time = date('Ymd', strtotime("$badd_time +1 month -1 day"));
        }
        $re = strtotime($eadd_time) - strtotime($badd_time);
        if ( $re > 2592000 ) {
            $this->error('开始时间结束时间区间不能大于30天');
        }
        $map['g.add_time'] = array(
            'egt', 
            $badd_time . '000000');
        $map['g.add_time '] = array(
            'elt', 
            $eadd_time . '235959');
        
        if (! empty($baddTime)) {
            $map['g.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        if (! empty($eaddTime)) {
            $map['g.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['g.node_id '] = $nodeId;
            $seachStatus = 1;
        }
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if (isset($goodsType) && $goodsType != '') {
            $map['g.goods_type '] = $goodsType;
        }
        import("ORG.Util.Page");
        $count = M()->table("tgoods_info g")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        if ('' == $sort_field) {
            $sort_sql = 'g.add_time DESC';
        }
        $list = M()->table("tgoods_info g")->field(
            'g.add_time,g.goods_name,n.node_name,g.goods_type,ifnull(SUM(p.send_num),0) as send_num,ifnull(SUM(p.verify_num),0) as verify_num,ifnull(SUM(p.cancel_num),0) as cancel_num,ifnull(truncate((SUM(p.verify_num)/SUM(p.send_num))*100,2),0) as codeEfficiency')
            ->join("tpos_day_count p ON g.goods_id = p.goods_id")
            ->join('tnode_info n ON g.node_id=n.node_id')
            ->where($map)
            ->group('g.goods_id')
            ->order($sort_sql)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $goodsType = $goodsModel->getGoodsType('0,1,2,3,11');
        $this->getNodeTree();

        $this->assign('badd_time',$badd_time);
        $this->assign('eadd_time',$eadd_time);
        $this->assign('goodsType', $goodsType);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign("page", $page);
        $this->assign('seachStatus', $seachStatus);
        $this->display();
    }
    
    // 卡券统计数据下载
    public function goodsCodeDown() {
        $map = array(
            'g.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'g.source' => array(
                'in', 
                '0,1,2,4'));
        
        // 处理特殊查询字段
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['g.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['g.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['g.node_id '] = $nodeId;
            $seachStatus = 1;
        }
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if (isset($goodsType) && $goodsType != '') {
            $map['g.goods_type '] = $goodsType;
        }
        
        $mapcount = M()->table("tgoods_info g")->where($map)->count();
        if ($mapcount == 0)
            $this->error('未查询到记录！');
        $goodsModel = D('Goods');
        $goodsTypeStr = $goodsModel->getGoodsType('0,1,2,3');
        $fileName = '卡券数据统计.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $title = "创建时间,券名,所属商户,类型,发送量,验证量,验证率,撤销量\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table("tgoods_info g")->field(
                'g.add_time,g.goods_name,n.node_name,g.goods_type,ifnull(SUM(p.send_num),0) as send_num,ifnull(SUM(p.verify_num),0) as verify_num,ifnull(SUM(p.cancel_num),0) as cancel_num,ifnull(truncate((SUM(p.verify_num)/SUM(p.send_num))*100,2),0) as codeEfficiency')
                ->join("tpos_day_count p ON g.goods_id = p.goods_id")
                ->join('tnode_info n ON g.node_id=n.node_id')
                ->where($map)
                ->group('g.goods_id')
                ->order('g.add_time DESC')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $addTime = dateformat($v['add_time'], 'Y-m-d');
                $goodsName = iconv('utf-8', 'gbk', $v['goods_name']);
                $nodeName = iconv('utf-8', 'gbk', $v['node_name']);
                $goodsType = iconv('utf-8', 'gbk', 
                    $goodsTypeStr[$v['goods_type']]);
                
                echo "{$addTime},{$goodsName},{$nodeName},{$goodsType},{$v['send_num']},{$v['verify_num']},{$v['codeEfficiency']}%,{$v['cancel_num']}\r\n";
            }
        }
    }
    
    // 单个活动凭证统计
    public function oneCodeCount() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $beginDate = I('begin_date', null);
        $endDate = I('end_date', null);
        $type = I('type', null);
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$goodsId}' AND node_id IN({$this->nodeIn()})")->find();
        if (! $goodsInfo)
            $this->error('未找到该卡券信息');
            // 判断开始结束日期和统计类型
        if (empty($beginDate)) {
            $beginDate = $goodsInfo['add_time'];
        }
        if (empty($endDate)) {
            $endDate = date('YmdHis');
        }
        if (strtotime($beginDate) > strtotime($endDate)) {
            $this->error('开始日期不能大于结束日期');
        }
        is_null($type) ? $type = 1 : $type;
        // echo $beginDate.'::'.$endDate.'::'.$type;
        // 计算节点日期
        $nodeDate = $this->formatDateNode($beginDate, $endDate, 15);
        // dump($nodeDate);
        $codeCountArr = array();
        switch ($type) {
            case '1': // 发码
                $fieldType = 'send_num';
                $type = '1';
                break;
            case '2': // 验码
                $fieldType = 'verify_num';
                $type = '2';
                break;
            case '3': // 撤销
                $fieldType = 'cancel_num';
                $type = '3';
                break;
            default:
                $this->error('未知的统计类型');
        }
        // 计算各时间节点，不同类型活动的发码，验码，撤销数量
        foreach ($nodeDate as $k => $v) {
            // 第一天的发码量从零开始
            if ($k == 0) {
                $codeCountArr[] = '0';
                continue;
            }
            $where = array(
                'i.node_id' => $this->nodeId, 
                'i.goods_id' => $goodsInfo['goods_id'], 
                'c.trans_date ' => array(
                    'egt', 
                    $nodeDate[$k - 1]),  // 第一天到每个节点的数量
                'c.trans_date' => array(
                    'lt', 
                    $v));
            // 优惠券数量
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where($where)
                ->sum("c.{$fieldType}");
            $codeCountArr[] = is_null($count) ? '0' : $count;
        }
        // dump($codeCountArr);
        $typeInfo = array(
            '1' => '发码量', 
            '2' => '验码量', 
            '3' => '撤销量');
        $nodeDateStr = "'" . implode("','", $nodeDate) . "'";
        $codeCountArr = implode(',', $codeCountArr);
        $this->assign('nodeDate', $nodeDateStr);
        $this->assign('codeCountArr', $codeCountArr);
        $this->assign('type', $type);
        $this->assign('typeInfo', $typeInfo);
        
        $this->assign('beginDate', $nodeDate[0]);
        $this->assign('endDate', $nodeDate[count($nodeDate) - 1]);
        $this->assign('goodsId', $goodsId);
        $this->assign('bt_name', $goodsInfo['goods_name']);
        $this->display();
    }

    /**
     * 卡券列表
     */
    public function numGoodsList() {
        $map = array(
            'b.source' => '0',
            'b.goods_type' => array('in','0,1,2,3'),
            'b.status' => '0',
        );
        $goodsTypeMap = array( // 获取各类卡券数量查询条件
            'status' => '0',
        );
        $seachStatus = 0; // 更多筛选状态
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if (isset($goodsType) && $goodsType != '') { // 如果有卡券种类，覆盖掉上面的in 0，1，2，3
            $map['b.goods_type'] = $goodsType;
        }
//         $remainType = I('remain_type', null, 'mysql_real_escape_string');
//         if ($remainType == '1') { // 无库存
//             $map['b.remain_num'] = '0';
//             $goodsTypeMap = array(
//                 'status' => '0',
//                 'remain_num' => '0');
//         }
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.goods_name'] = array(
                'like',
                "%{$name}%");
        }
        $goodsSource = I('goods_source', null, 'mysql_real_escape_string');
        if ($goodsSource != '') {
            $map['b.source'] = $goodsSource;
        }
        $map['b.node_id'] = array(
            'exp',
            "in ({$this->nodeIn()})");
        import("ORG.Util.Page");
        $count = M()->table("tgoods_info b")->where($map)->count();
        $p = new Page($count, 10);

        $list = M()->table("tgoods_info b")->field('b.*,n.node_name')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->where($map)
            ->order('b.status ASC,b.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $goodsModel = D('Goods');
        // 各类卡券数量
        $goodsTypeNum = $goodsModel->getGoodsNum(array('exp',"in ({$this->nodeIn()})"), '0', $goodsTypeMap);
        $goodsType = $goodsModel->getGoodsType();
        $goodsSourceArr = $goodsModel->getGoodsSource('0,1,4');
        node_log("首页+卡券库+自建卡券");
        $this->getNodeTree();
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsSourceArr', $goodsSourceArr);
        $this->assign('list', $list);
        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign("page", $page);
        $this->assign('seachStatus', $seachStatus);
        $this->assign('goodsModel', $goodsModel);
        $this->display();
    }

    /**
     * 采购卡券列表
     */
    public function numGoodsBuy() {
        
        $map = array(
            'b.source' => '1', 
            'b.goods_type' => array('in','0,1,2,3,7,8,15'), 
            'b.status' => '0', 
        );
        $goodsTypeMap = array( // 获取各类卡券数量查询条件
            'status' => '0', 
        );
        $seachStatus = 0; // 更多筛选状态
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if (isset($goodsType) && $goodsType != '') { // 如果有卡券种类，覆盖掉上面的in 0，1，2，3
            $map['b.goods_type'] = $goodsType;
        }

        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $goodsSource = I('goods_source', null, 'mysql_real_escape_string');
        if ($goodsSource != '') {
            $map['b.source'] = $goodsSource;
        }
        $map['b.node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        import("ORG.Util.Page");
        $count = M()->table("tgoods_info b")->where($map)->count();
        $p = new Page($count, 10);
        
        $list = M()->table("tgoods_info b")->field('b.*,n.node_name')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->where($map)
            ->order('b.status ASC,b.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $goodsModel = D('Goods');
        // 各类卡券数量
        $goodsTypeNum = $goodsModel->getGoodsNum(array('exp',"in ({$this->nodeIn()})"), '1', $goodsTypeMap);
        $goodsType = $goodsModel->getGoodsType();
        $goodsSourceArr = $goodsModel->getGoodsSource('1');
        node_log("首页+卡券库+自建卡券");
        $this->getNodeTree();
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsSourceArr', $goodsSourceArr);
        $this->assign('list', $list);
        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign("page", $page);
        $this->assign('seachStatus', $seachStatus);
        $this->assign('goodsModel', $goodsModel);
        $this->display();
    }

    /**
     * 再次采购操作
     */
    public function againPurchase() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $bId = M('thall_goods')->where("goods_id='{$goodsId}'")->getField('id');
        if (empty($bId))
            $this->error('未找到该卡券信息');
        redirect(
            U('Hall/Index/goods', 
                array(
                    'goods_id' => $bId)));
        exit();
    }

    public function agentNumgoods() {
        $map = array(
            'b.source' => '5', 
            'b.goods_type' => array(
                'in', 
                '0,1,2,3'));
        $seachStatus = 0; // 更多筛选状态
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if (isset($goodsType) && $goodsType != '') { // 如果有卡券种类，覆盖掉上面的in 0，1，2，3
            $map['b.goods_type'] = $goodsType;
        }
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $bgoodsAmt = I('bgoods_amt', null, 'mysql_real_escape_string');
        if (! empty($bgoodsAmt)) {
            $map['b.goods_amt'] = array(
                'egt', 
                $bgoodsAmt);
            $seachStatus = 1;
        }
        $egoodsAmt = I('egoods_amt', null, 'mysql_real_escape_string');
        if (! empty($egoodsAmt)) {
            $map['b.goods_amt '] = array(
                'egt', 
                $egoodsAmt);
            $seachStatus = 1;
        }
        $validateType = I('validate_type', null, 'mysql_real_escape_string');
        if (isset($validateType) && $validateType != '') {
            $map['b.validate_type'] = $validateType;
            $seachStatus = 1;
        }
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['b.status'] = $status;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['b.node_id '] = $nodeId;
            $seachStatus = 1;
        }
        
        $map['b.node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        import("ORG.Util.Page");
        $count = M()->table('tgoods_info b')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tgoods_info b')
            ->field('b.*,n.node_name')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->where($map)
            ->order('b.status ASC,b.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $goodsModel = D('Goods');
        // 判断是否发送过交易大厅，个人，APP，微信和微博
        if (! empty($list)) {
            foreach ($list as $ks => $vs) {
                $hall_is_exist = M('thall_goods')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'goods_id' => $vs['goods_id']))->select();
                $pri_is_exist = M()->table("tbatch_info b")->join(
                    'tmarketing_info m ON m.id=b.m_id')
                    ->where(
                    array(
                        'm.batch_type' => '1007', 
                        'b.node_id' => $this->node_id, 
                        'b.goods_id' => $vs['goods_id']))
                    ->select();
                $app_is_exist = M('tbatch_info_tostore_exp')->getFieldByGoods_id(
                    $vs['goods_id'], 'id');
                $weixin_is_exist = M('twx_card_type')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'goods_id' => $vs['goods_id']))->select();
                $weibo_is_exist = M('tweibo_card_type')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'goods_id' => $vs['goods_id']))->select();
                $list[$ks]['hall_is_exist'] = empty($hall_is_exist) ? 0 : 1;
                $list[$ks]['pri_is_exist'] = empty($pri_is_exist) ? 0 : 1;
                $list[$ks]['app_is_exist'] = empty($app_is_exist) ? 0 : 1;
                $list[$ks]['weixin_is_exist'] = empty($weixin_is_exist) ? 0 : 1;
                $list[$ks]['weibo_is_exist'] = empty($weibo_is_exist) ? 0 : 1;
            }
        }
        // 各类卡券数量
        $goodsTypeNum = $goodsModel->getGoodsNum(
            array(
                'exp', 
                "in ({$this->nodeIn()})"), '5');
        $goodsType = $goodsModel->getGoodsType();
        $status = array(
            '0' => '正常', 
            '2' => '过期');
        $statusArr = array(
            '0' => '正常', 
            '1' => '停用', 
            '2' => '过期');
        $goods_trans_type = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券');
        node_log("首页+卡券库+发布卡券");
        $this->getNodeTree();
        $this->assign('goodsType', $goodsType);
        $this->assign('status', $status);
        $this->assign('statusArr', $statusArr);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign("goods_trans_type", $goods_trans_type);
        $this->assign('seachStatus', $seachStatus);
        $this->display();
    }
    
    // 发布的卡券列表
    public function pulishList() {
        $seachStatus = 0; // 更多筛选状态
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if (isset($goodsType) && $goodsType != '') {
            $map['g.goods_type'] = $goodsType;
        }
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.batch_short_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['b.status'] = $status;
            $seachStatus = 1;
        }
        $checkStatus = I('check_status', null, 'mysql_real_escape_string');
        if (isset($checkStatus) && $checkStatus != '') {
            $map['b.check_status'] = $checkStatus;
        }
        
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['b.node_id '] = $nodeId;
            $seachStatus = 1;
        }
        
        $map['b.node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        import("ORG.Util.Page");
        $count = M()->table("thall_goods b")->join(
            "tgoods_info g ON b.goods_id=g.goods_id")
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('thall_goods b')
            ->field('b.*,g.goods_type,n.node_name')
            ->join('tgoods_info g ON b.goods_id=g.goods_id')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->where($map)
            ->order('b.add_time DESC,b.check_status')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        // 获取各类型卡券数量
        $TypeNum = M()->table("thall_goods h")->field("g.goods_type,count(*) as num")
            ->join('tgoods_info g ON h.goods_id=g.goods_id')
            ->where("h.node_id IN({$this->nodeIn()})")
            ->group('g.goods_type')
            ->select();
        $goodsTypeNum = array();
        foreach ($TypeNum as $v) {
            $goodsTypeNum[$v['goods_type']] = $v['num'];
        }
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '审核通过', 
            '2' => '审核拒绝', 
            '3' => '已下架');
        $goodsModel = D('Goods');
        $goodsType = $goodsModel->getGoodsType();
        $this->getNodeTree();
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('checkStatus', $checkStatus);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('goodsType', $goodsType);
        $this->assign('post', $_REQUEST);
        $this->assign('seachStatus', $seachStatus);
        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign("hallModel", D('Hall'));
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 旺财小店上架商品
     */
    public function pulishshopList() {
        $where = array(
            'a.node_id' => $this->nodeId, 
            't.is_delete' => 0,  // 显示未删除的信息
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,7,8'), 
            'g.source' => array(
                'in', 
                '0,1,4'));
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $where['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $goodsSource = I('goods_source', null, 'mysql_real_escape_string');
        echo $goodsSource;
        if ($goodsSource != '') {
            $where['g.source'] = $goodsSource;
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tecshop_goods_ex a')
            ->join('tbatch_info t ON a.node_id=T.node_id and t.id = a.b_id')
            ->join('tgoods_info g on t.goods_id =  g.goods_id')
            ->where($where)
            ->count();
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $subQuery = M()->table('tecshop_goods_ex a')
            ->field(
            "a.id,g.goods_id,g.goods_name,g.source,g.goods_type,g.goods_image,m.start_time,m.end_time,x.sale_num,t.status,t.batch_amt")
            ->join(
            "(SELECT o.node_id, oe.b_id,SUM(CASE WHEN o.pay_status IN('2','3') THEN oe.goods_num END) sale_num FROM ttg_order_info o, ttg_order_info_ex oe WHERE o.order_id = oe.order_id AND o.order_type = '2' AND o.order_status = '0' GROUP BY o.node_id, oe.b_id) X ON  x.node_id = a.node_id AND x.b_id = a.b_id")
            ->join('tbatch_info t ON a.node_id=t.node_id and t.id = a.b_id')
            ->join('tgoods_info g on t.goods_id =  g.goods_id')
            ->join('tmarketing_info m on m.id =  a.m_id')
            ->where($where)
            ->buildSql();
        
        $list = M()->table($subQuery . ' a')
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // dump($list);exit;
        // 获取各类型卡券数量
        $TypeNum = M()->table("tecshop_goods_ex a")->field(
            "g.goods_type,count(*) as num")
            ->join('tbatch_info t ON a.node_id=t.node_id and t.id = a.b_id')
            ->join('tgoods_info g on t.goods_id =  g.goods_id')
            ->where($where)
            ->group('g.goods_type')
            ->select();
        $goodsTypeNum = array();
        foreach ($TypeNum as $v) {
            $goodsTypeNum[$v['goods_type']] = $v['num'];
        }
        $goodsModel = D('Goods');
        $goodsType = $goodsModel->getGoodsType();
        $goodsStatusArr = array(
            '0' => '已上架', 
            '1' => '已下架', 
            '2' => '已过期');
        $goodsSourceArr = $goodsModel->getGoodsSource('0,1,4');
        $show = $Page->show(); // 分页显示输出
        $this->assign('page', $show);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsStatusArr', $goodsStatusArr);
        $this->assign('goodsTypeNum', $goodsTypeNum);
        $this->assign('goodsSourceArr', $goodsSourceArr);
        $this->assign('list', $list); // 赋值分页输出
        $this->display();
    }
    
    // 发布卡券详情
    public function pulishDetail() {
        $id = I('id', null, 'mysql_real_escape_string');
        $hallInfo = M()->table("thall_goods h")->field('h.*,g.goods_type,n.node_name')
            ->join('tgoods_info g ON h.goods_id=g.goods_id')
            ->join('tnode_info n ON h.node_id=n.node_id')
            ->where("h.id='{$id}' AND h.node_id IN({$this->nodeIn()})")
            ->find();
        if (! $hallInfo)
            $this->error('未找到该卡券信息');
        
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '审核通过', 
            '2' => '审核拒绝', 
            '3' => '已下架');
        $goodsModel = D('Goods');
        $goodsType = $goodsModel->getGoodsType();
        $this->assign('hallInfo', $hallInfo);
        $this->assign('checkStatus', $checkStatus);
        $this->assign('goodsType', $goodsType);
        $this->display();
    }
    // 商品详情
    public function numGoodsDetail() {
        $goodsId = I('get.goods_id', null);
        if (is_null($goodsId)) {
            $this->error('参数错误');
        }
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$goodsId}' AND node_id IN({$this->nodeIn()})")->find();
        if (! $goodsInfo)
            $this->error('未找到该卡券信息');
            // $codeInfo = $this->getBatchCodeNum($batchInfo['batch_no']);
            // $batchInfo = array_merge($batchInfo,$codeInfo);
            // dump($batchInfo);exit;
            // 获取发布信息
        $publishData = M('thall_goods')->where("goods_id='{$goodsId}'")->select();
        $status = array(
            '0' => '正常',
            '1' => '停用',
            '2' => '过期');
        $checkStatus = array(
            '0' => '正在审核',
            '1' => '审核通过',
            '2' => '审核拒绝');
        $goodsModel = D('Goods');
        $goodsType = $goodsModel->getGoodsType();
        // 门店列表
        $shopList = $goodsModel->getGoodsShop($goodsInfo['goods_id'], false,
            $this->nodeIn());
        $user_name = M('tuser_info')->getFieldByUser_id($goodsInfo['user_id'],
            'user_name');
        // 是否同步微信卡券
        $wxCardInfo = D('WeixinCard')->checkSyncFornumGoods($goodsId);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('publishData', $publishData);
        $this->assign('status', $status);
        $this->assign('checkStatus', $checkStatus);
        $this->assign('goodsType', $goodsType);
        $this->assign('user_name', $user_name);
        $this->assign('shopList', $shopList);
        $this->assign('goods_id', $goodsId);
        $this->assign('top_type', 1);
        $this->assign('wxCardInfo', $wxCardInfo);
        $this->display();
    }
    
    // 发布的卡券编辑
    public function publishEdit() {
        $id = I('id', null, 'mysql_real_escape_string');
        // 获取活动信息
        $activityInfo = M()->table('thall_goods h')
            ->field('h.*,g.begin_time as gb_time,g.end_time as ge_time')
            ->join('tgoods_info g ON g.goods_id=h.goods_id')
            ->where("h.id='{$id}' AND h.node_id='{$this->nodeId}'")
            ->find();
        if (! $activityInfo) {
            $this->error('未找到该卡券信息');
        }
        if ($activityInfo['check_status'] == '0')
            $this->error('该卡券不能编辑');
        if ($this->isPost()) {
            $error = '';
            /*
             * //截止时间 $endDate = I('post.show_end_date');
             * if(!check_str($endDate,array('null'=>false,'strtype'=>'datetime','format'=>'Ymd'),$error)){
             * $this->error("展示截止日期{$error}"); } if(strtotime($endDate) >
             * strtotime($activityInfo['ge_time']) || strtotime($endDate) <
             * strtotime($activityInfo['gb_time'])){
             * $this->error('展示日期要在卡券有效日期之内'); }
             */
            $goodsCate = I('post.cate2');
            if (! check_str($goodsCate, 
                array(
                    'null' => false), $error)) {
                $this->error("类目{$error}");
            }
            // 采购价
            $batchAmt = I('post.show_price');
            if (! check_str($batchAmt, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0.01'), $error)) {
                $this->error("卡券市场采购价{$error}");
            }
            
            // 图片
            $goodsImage = I('post.batch_img');
            if(empty($goodsImage) && !is_array($goodsImage)){
            	$this->error("请上传卡券图片");
            }
            // 描述
            $batchDesc = I('post.show_batch_desc');
            if (! check_str($batchDesc, 
                array(
                    'null' => false), $error)) {
                $this->error("卡券描述{$error}");
            }
            //发票
            $invoiceType = I('invoice_type');
            if (! check_str($invoiceType,array('null' => false,'strtype' => 'int','minval' => '0','maxval' => '2'), $error)) {
            	$this->error("发票类型{$error}");
            }
            // thall_goods数据添加
            $data = array(
                'batch_img' => json_encode($goodsImage), 
                'batch_amt' => $batchAmt, 
                // 'end_time' => $endDate.'235959',
                'batch_desc' => $batchDesc, 
                'check_status' => '0', 
                'goods_cat' => $goodsCate,
            	'invoice_type' => $invoiceType	
            );
            M()->startTrans();
            $result = M('thall_goods')->where("id='{$id}'")->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('数据库错误,添加失败');
            }
            M()->commit();
            node_log("卡券发布，发布类型：异业联盟中心，名称：异业联盟中心" . $activityInfo['batch_name']);
            $this->success('修改成功');
            exit();
        }
        $goodsCate = M('tgoods_category')->where("level=1")->select(); // 一级类目
        $this->assign('goodsCate', $goodsCate);
        $this->assign('activityInfo', $activityInfo);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }
    // 卡券大厅下架
    public function stopNumGoods() {
        $id = I('post.id', null, 'mysql_real_escape_string');
        // 获取活动信息
        $activityInfo = M('thall_goods')->where(
            "id='{$id}' AND node_id='{$this->nodeId}'")->find();
        if (! $activityInfo) {
            $this->error('未找到该卡券信息');
        }
        if ($activityInfo['status'] != '0')
            $this->error('该卡券已经下架或已经过期');
        if ($activityInfo['check_status'] != '1')
            $this->error('该卡券审核还没有通过');
            // 更新本地状态
        $data = array(
            'check_status' => '3');
        $result = M('thall_goods')->where("id='{$id}'")->save($data);
        if ($result === false)
            $this->error("系统错误，下架失败");
        node_log("卡券下架。卡券名称：" . $activityInfo['batch_name']);
        $this->success('下架成功');
    }
    
    // 获取单个活动的验码,发码,撤销量(只有粉丝卡再用这个,卡券要用的话参数要改造成goods_id)
    public function getBatchCodeNum($batch_no) {
        $infoArr = array();
        // 发码量
        $where = array(
            'batch_no' => $batch_no, 
            'trans_type' => '0001', 
            'status' => '0');
        $sendNum = M('tbarcode_trace')->where($where)->count();
        $infoArr['send_num'] = $sendNum;
        // 验码量
        $where = array(
            'batch_no' => $batch_no, 
            'trans_type' => '0', 
            // 'is_canceled' => '0',
            'status' => '0');
        $verifyNum = M('tpos_trace')->where($where)->count();
        $infoArr['verifyNum'] = $verifyNum;
        // 撤销量
        $where = array(
            'batch_no' => $batch_no, 
            'trans_type' => '1', 
            // 'is_canceled' => '0',
            'status' => '0');
        $recNum = M('tpos_trace')->where($where)->count();
        $infoArr['recNum'] = $recNum;
        return $infoArr;
    }

    /**
     * 将2个日期间天数平均分成若干日期节点
     *
     * @param $startDate 开始日期
     * @param $endDate 结束日期
     * @param int $nodeCount 日期节点个数
     * @param string 返回数据的时间格式
     * @return array
     */
    public function formatDateNode($startDate, $endDate, $nodeCount = 5, 
        $format = 'Y-m-d') {
        $begin = strtotime($startDate);
        $end = strtotime($endDate);
        $days = floor(($end - $begin) / (24 * 3600));
        $node = $nodeCount - 1; // 日期节点数
        $dateArr = array(
            date($format, $begin));
        if ($days <= $node) {
            // 一天一个节点
            for ($i = 0; $i < $days; $i ++) {
                $begin += 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        } else {
            $nodeDays = floor($days / $node); // 每个节点之间的天数
            $remainder = $days % $node; // 余数
            for ($i = 0; $i < $node; $i ++) {
                if ($i == $node - 1) {
                    $nodeDays += $remainder;
                }
                $begin += $nodeDays * 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        }
        return $dateArr;
    }
    
    // 数据导出
    public function export() {
        // 查询条件组合
        $where = "WHERE 1";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['batch_class']) &&
                 $condition['batch_class'] != '') {
                $filter[] = "i.goods_type = '{$condition['batch_class']}'";
            } else {
                $filter[] = "i.goods_type IN(0,1,2,3)";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "i.status = '{$condition['status']}'";
            }
            if (isset($condition['badd_time']) && $condition['badd_time'] != '') {
                $condition['badd_time'] = $condition['badd_time'] . ' 000000';
                $filter[] = "i.add_time >= '{$condition['badd_time']}'";
            }
            if (isset($condition['eadd_time']) && $condition['eadd_time'] != '') {
                $condition['eadd_time'] = $condition['eadd_time'] . ' 235959';
                $filter[] = "i.add_time <= '{$condition['eadd_time']}'";
            }
            if (! empty($condition['node_id'])) {
                $filter['i.node_id '] = $condition['node_id'];
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $sql = "SELECT
				i.goods_name,i.add_time,i.begin_time,i.end_time,
				CASE i.goods_type WHEN '0' THEN '优惠券' WHEN '1' THEN '代金券' ELSE '提领券' END goods_type,
				CASE i.status WHEN '0' THEN '正常' WHEN '1' THEN '停用' ELSE '过期' END status
			FROM
				tgoods_info i {$where} AND i.source=0 AND i.node_id in (" .
             $this->nodeIn() . ")";
        $cols_arr = array(
            'goods_name' => '卡券名称', 
            'add_time' => '添加时间', 
            'begin_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'goods_type' => '类型', 
            'status' => '状态');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // iframe中添加
    public function iframeAdd() {
        // 可验证门店数量
        $storeNum = M('tstore_info')->where(
            "node_id='{$this->nodeId}' AND status=0")->count();
        // 用户类型
        $nodeType = M('tnode_info')->where("node_id='{$this->nodeId}'")->getField(
            'node_type');
        $type = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券');
        $this->assign('type', $type);
        $this->assign('storeNum', $storeNum);
        $this->display();
    }

    /**
     *
     * @return StoresModel
     */
    private function getStoresModel() {
        if (empty($this->storesModel)) {
            $this->storesModel = D('Stores');
        }
        return $this->storesModel;
    }

    /**
     *
     * @return StoresGroupModel
     */
    private function getStoresGroupModel() {
        if (empty($this->storesGroupModel)) {
            $this->storesGroupModel = D('StoresGroup');
        }
        return $this->storesGroupModel;
    }
    /**
     * 获取子商户的所有nodeId
     * @param   string  $str  多数是个SQL语句
     * @return  mixed
     */
    public function getNodeIn($str){
        if(stripos($str,'from')){
            $result = M()->query($str);
            $nodeArr = array();
            foreach($result as $key => $value){
                $nodeArr[] = $value['node_id'];
            }
            $str = implode(',',$nodeArr);
        }
        return $str;
    }
    
    // 门店列表
    public function shopList() {
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        $storesModel = $this->getStoresModel();
        $where = array(
            'a.status' => '0', 
            'userTable.pos_status' => '0');
        
        $type = I('type');
        $type == 'member' ? $where['a.pos_range'] = array(
            'gt', 
            '1') : $where['a.pos_range'] = array(
            'gt', 
            '0');
        
        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, "查询成功", 0);
            exit();
        }
        // 获取分组
        $storeGroup = $this->getStoreGroup($type);
        $this->assign('storeGroup', $storeGroup);
        
        // 获取门店
        $getAllStores = $storesModel->getAllStore($nodeIn, $where, 'tpos_info');
        
        $this->assign('allStores', $getAllStores);
        
        $this->display('./Home/Tpl/Home/Store_storePopup.html');
    }

    /**
     * 获取分组
     *
     * @param $type string 会员的筛选条件
     * @return mixed
     */
    public function getStoreGroup($type) {
        $nodeId = $this->node_id;
        $storesModel = $this->getStoresModel();
        $storesGroupModel = $this->getStoresGroupModel();
        
        if ($type == 'member') {
            $getGroupWhere = ' c.status = 0 and c.pos_range >1 and userTable.pos_status=0 ';
            $getUnGroupWhere = ' a.status = 0 and a.pos_range >1 and userTable.pos_status=0 ';
        } else {
            $getGroupWhere = ' c.status = 0 and c.pos_range >0 and userTable.pos_status=0 ';
            $getUnGroupWhere = ' a.status = 0 and a.pos_range >0 and userTable.pos_status=0 ';
        }
        // 获取所有分组
        $allGroup = $storesGroupModel->getPopGroupStoreId($nodeId, 
            $getGroupWhere, 'tpos_info');
        
        // 未分组的门店
        $noGroup = $storesModel->getUnGroupedAllStore($nodeId, $getUnGroupWhere, 
            'tpos_info');
        $noGroupArr = array();
        foreach ($noGroup as $key => $value) {
            $noGroupArr[] = $value['store_id'];
        }
        // 追加未分组项
        array_unshift($allGroup, 
            array(
                'id' => '-1', 
                'group_name' => '未被分组', 
                'num' => count($noGroupArr), 
                'storeId' => implode(',', $noGroupArr)));
        
        return array_filter($allGroup);
    }
    
    // 获取商品剩余库存
    public function getGoodsStock($goodsId) {
        // 获取商品的总库存
        $totalStock = M('tgoods_info')->where("goods_id='{$goodsId}'")->getField(
            'storage_num');
        if ($totalStock == '-1')
            return '-1';
    }
    
    // ajax获取卡券类目
    public function ajaxGoodsCate() {
        $id = I('id', null, 'mysql_real_escape_string');
        $cateData = M('tgoods_category')->where("parent_code='{$id}'")->select();
        if ($cateData) {
            $this->ajaxReturn($cateData, '', 1);
        }
        return null;
    }
    
    // 修改电子合约
    public function setGoodsInfoReq($pGoodsId, $printInfo, $beginDate) {
        // 支撑请求修改打印文本
        $req_array = array(
            'SetGoodsInfoReq' => array(
                'InterfaceAccount' => 'g', 
                'InterfacePassword' => 'g', 
                'PGoodsID' => $pGoodsId, 
                'PrintControl' => '1', 
                'StartTime' => $beginDate, 
                'EndTime' => '20301231235959', 
                'PrintText' => $printInfo));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->SetGoodsInfoReq($req_array);
        $ret_msg = $resp_array['SetGoodsInfoReq']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            return false;
        }
        return true;
    }
    
    // 商品库存调整
    public function addStorageNum() {
        $id = I('id', 0, 'intval');
        $addNum = I('addNum', 0, 'intval');
        if ($addNum == 0)
            $this->error('增加库存数不能为0');
        M()->startTrans();
        $goods_info = M('tgoods_info')->lock(true)
            ->where(
            array(
                'node_id' => $this->nodeId,
                'id' => $id))
            ->find();
        if (! $goods_info) {
            M()->rollback();
            $this->error('参数错误！错误的商品号！');
        }

        if ($goods_info['storage_type'] == 0 || $goods_info['storage_num'] == - 1) {
            M()->rollback();
            $this->error('参数错误！库存不限的商品不能增加库存');
        }
        // 处理库存-开始事务
        $data = array(
            'storage_num' => $goods_info['storage_num'] + $addNum,
            'remain_num' => $goods_info['remain_num'] + $addNum);
        $flag = M('tgoods_info')->where(array(
            'id' => $id))->save($data);
        if ($flag === false) {
            M()->rollback();
            log_write("库存增加失败，原因：" . M()->error());
            $this->error('库存增加失败，请重试！');
        }

        // 记录变更流水
        $data = array(
            'node_id' => $this->node_id,
            'goods_id' => $id,
            'change_num' => $addNum,
            'pre_num' => $goods_info['remain_num'],
            'current_num' => $goods_info['remain_num'] + $addNum,
            'opt_type' => '3',
            'relation_id' => $this->user_id,
            'add_time' => date('YmdHis'));
        $flag = M('tgoods_storage_trace')->add($data);
        if ($flag === false) {
            M()->rollback();
            log_write("库存增加失败，原因：" . M()->error());
            $this->error('库存增加失败，请重试！');
        }
        // 同步增加微信卡券库存
        $wxCardModel = D('WeixinCard');
        $wxCardInfo = $wxCardModel->checkSyncFornumGoods(
            $goods_info['goods_id']);
        if ($wxCardInfo) {
            $flag = $wxCardModel->addStorageNum($wxCardInfo['id'], $addNum);
            if (! $flag) {
                M()->rollback();
                $this->error($wxCardModel->getError());
            }
        }
        M()->commit();
        node_log(
            "卡券库存增加，原库存【{$goods_info['storage_num']}】，增加数【{$addNum}】，新库存【" .
                 ($goods_info['storage_num'] + $addNum) . "】",
                print_r($_POST, true));
        $this->success('库存增加成功！');
    }
    // APP商品库存调整
    public function addAppStorageNum() {
        $id = I('id', 0, 'intval');
        $addNum = I('addNum', 0, 'intval');
        if ($addNum == 0)
            $this->error('增加库存数不能为0');
        
        M()->startTrans();
        $goods_info = M('tgoods_info')->lock(true)
            ->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $id))
            ->find();
        if (! $goods_info) {
            M()->rollback();
            $this->error('参数错误！错误的商品号！');
        }
        
        if ($goods_info['storage_type'] > 0 &&
             $goods_info['remain_num'] < $addNum) {
            M()->rollback();
            $this->error('库存不足，无法添加APP的数量');
        }
        
        // 处理库存-开始事务
        
        do {
            // 变更商品表库存
            $data = array(
                'remain_num' => $goods_info['remain_num'] - $addNum);
            $flag = M('tgoods_info')->where(
                array(
                    'id' => $id))->save($data);
            if ($flag === false) {
                M()->rollback();
                log_write("添加App数量失败，原因：" . M()->error());
                $this->error('添加App数量失败，请重试！');
            }
            $batch_info = M()->table("tbatch_info b")->field('b.*')
                ->join('tmarketing_info m ON m.id=b.m_id')
                ->where(
                array(
                    'm.node_id' => $this->node_id, 
                    'm.batch_type' => '1006', 
                    'b.goods_id' => $goods_info['goods_id']))
                ->select();
            if (empty($batch_info)) {
                M()->rollback();
                log_write("添加App数量失败，原因：" . M()->error());
                $this->error('添加App数量失败，请重试！');
            }
            if (false ===
                 M('tbatch_info')->where(
                    array(
                        'id' => $batch_info[0]['id']))->save(
                    array(
                        'remain_num' => ($batch_info[0]['remain_num'] + $addNum), 
                        'storage_num' => ($batch_info[0]['storage_num'] + $addNum)))) {
                M()->rollback();
                log_write("添加App数量失败，原因：" . M()->error());
                $this->error('添加App数量失败，请重试！');
            }
            // 记录变更流水
            $data = array(
                'node_id' => $this->node_id, 
                'goods_id' => $id, 
                'change_num' => $addNum, 
                'pre_num' => ($goods_info['storage_num'] > 0 ? $goods_info['remain_num'] : '不限'), 
                'current_num' => ($goods_info['storage_num'] > 0 ? ($goods_info['remain_num'] -
                     $addNum) : '不限'), 
                    'opt_type' => '17', 
                    'relation_id' => $this->user_id, 
                    // 'opt_desc' => printf(""),
                    'add_time' => date('YmdHis'));
            $flag = M('tgoods_storage_trace')->add($data);
            if ($flag === false) {
                M()->rollback();
                log_write("添加App数量失败，原因：" . M()->error());
                $this->error('添加App数量失败，请重试！');
            }
        }
        while (0);
        M()->commit();
        
        node_log("添加App数量", print_r($_POST, true));
        
        $this->success('添加App数量成功！');
    }

    /**
     * 库存变动流水
     */
    public function storageTrace() {
        $goods_id = I('goods_id', '', 'trim,htmlspecialchars');
        $goodsInfo = M('tgoods_info')->where(
            array(
                'goods_id' => $goods_id, 
                'node_id' => $this->nodeId))->find();
        $map = array(
            'goods_id' => $goodsInfo['id'], 
            'node_id' => $this->nodeId);
        // 查询处理
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $changeFlag = I('change_flag', null, 'mysql_real_escape_string');
        if ($changeFlag == '1') {
            $map['_string'] = 'current_num-pre_num > 0';
        } elseif ($changeFlag == '2') {
            $map['_string'] = 'current_num-pre_num < 0';
        }
        $count = M('tgoods_storage_trace')->where($map)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $dataList = (array) M('tgoods_storage_trace')->field(
            "*,(current_num-pre_num) as change_flag")
            ->where($map)
            ->order('add_time desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        // echo M()->getLastSql();
        if (! empty($dataList)) {
            $batchName = C('BATCH_TYPE_NAME');
            foreach ($dataList as $k => $v) {
                $batchInfo = array();
                if (in_array($v['opt_type'],array('0','6','7','20'))) { // 营销活动信息
                    $batchInfo = M()->table("tcj_batch b")->field("m.name,m.batch_type")
                        ->join("tmarketing_info m ON b.batch_id=m.id")
                        ->where("b.id='{$v['relation_id']}'")
                        ->find();
                }
                $dataList[$k]['batch_name'] = empty($batchInfo['name']) ? '--' : $batchInfo['name'];
                $dataList[$k]['batch_type'] = empty($batchInfo['batch_type']) ? '--' : $batchName[$batchInfo['batch_type']];
            }
        }
        $goodsModel = D('Goods');
        $show = $Page->show();
        $this->assign('dataList', $dataList);
        $this->assign('page', $show);
        $this->assign('top_type', 2);
        $this->assign('goods_id', $goods_id);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('goods_type', $goodsModel->getGoodsType());
        $this->assign('storageTraceOptType', 
            $goodsModel->getStorageTraceOptType());
        $this->display('numGoodsDetail');
    }

    /**
     * 卡券详情使用记录
     */
    public function goodsUseList() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $goodsInfo = M('tgoods_info')->field("goods_name,goods_type,goods_id")
            ->where("goods_id='{$goodsId}' and node_id='{$this->nodeId}'")
            ->find();
        if (!$goodsInfo) $this->error('无效的卡券');

        // 累计发送和验证数量
        $svNumInfo = M('tpos_day_count')->field(
            "sum(send_num) as send_num,sum(verify_num) as verify_num")
            ->where("node_id='{$this->nodeId}' and goods_id='{$goodsId}'")
            ->find();
        // 都需要传的变量
        $this->assign('svNumInfo', $svNumInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('goods_id', $goodsId);
        $this->assign('top_type', 3);
        $this->assign('goodsTypeArr', D('Goods')->getGoodsType());
        $this->assign('batchTypeArr', C('BATCH_TYPE_NAME'));

        if($goodsInfo['goods_type'] == '15') // 流量来源不同
            self::getGoodsUseListFP($goodsInfo);
        elseif($goodsInfo['goods_type'] == '7')
            self::getGoodsUseListHF($goodsInfo);
        $map = array(
            't.node_id' => $this->nodeId, 
            't.goods_id' => $goodsId,
            't.trans_type' => '0001');
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['t.trans_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['t.trans_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $phoneNo = I('phone_no', null, 'mysql_real_escape_string');
        if (! empty($phoneNo)) {
            $map['t.phone_no '] = $phoneNo;
        }
        $batchName = I('batch_name', null, 'mysql_real_escape_string');
        if (! empty($batchName)) {
            $map['m.name'] = array(
                'like', 
                "%{$batchName}%");
        }
        $sendStatus = I('send_status', null, 'mysql_real_escape_string');
        if ($sendStatus != '') {
            $map['t.send_status '] = $sendStatus;
        }
        $useStatus = I('use_status', null, 'mysql_real_escape_string');
        if ($useStatus != '') {
            $map['t.use_status '] = $useStatus;
        }

        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table("tbarcode_trace t")->where($map)->count();
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $dataList = M()->table("tbarcode_trace t")->field("t.req_seq,t.trans_time,t.phone_no,t.send_status,t.use_status,m.name,m.batch_type,m.id")
            ->join("tbatch_info b ON t.b_id=b.id")
            ->join("tmarketing_info m ON b.m_id=m.id")
            ->where($map)
            ->limit($Page->firstRow, $Page->listRows)
            ->order("t.trans_time desc")
            ->select();
        
        $barcodeModel = D('TbarcodeTrace');
        $userStatusArr = array(
            '未使用', 
            '使用中', 
            '已使用'); // 码使用状态
        $show = $Page->show();
        $this->assign('dataList', $dataList);
        $this->assign('page', $show);
        $this->assign('sendStatusArr', $barcodeModel->getSendStatus());
        $this->assign('userStatusArr', $userStatusArr);
        $this->display('numGoodsDetail');
    }
    private function getGoodsUseListFP($goodsInfo)
    {
        $czStatusArr = array(
                '2'=>'充值成功',
                '3'=>'充值失败'
        );
        $map = array(
            't.node_id' => $this->nodeId,
            't.goods_id' => $goodsInfo['goods_id']);
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['t.add_time'] = array(
                'egt',
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['t.add_time '] = array(
                'elt',
                $eaddTime . '235959');
        }
        $phoneNo = I('phone_no', null, 'mysql_real_escape_string');
        if (! empty($phoneNo)) {
            $map['t.phone_no '] = array(array('eq',$phoneNo),array('neq','13900000000'),'and');
        }else{
            $map['t.phone_no'] = array('neq','13900000000');//默认号码不显示
        }
        $batchName = I('batch_name', null, 'mysql_real_escape_string');
        if (! empty($batchName)) {
            $map['m.name'] = array(
                'like',
                "%{$batchName}%");
        }
        $czStatus = I('cz_status', null, 'mysql_real_escape_string');
        if ($czStatus != '') {
            $map['t.status '] = $czStatus;
        }else{
            $map['t.status '] = array('in',array(2,3));
        }
        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table("tmobile_date_send_trace t")->join("tmarketing_info m ON t.m_id=m.id")->where($map)->count();
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $dataList = M()->table("tmobile_date_send_trace t")->field("t.add_time AS trans_time,t.phone_no,t.status AS send_status,m.name,m.batch_type,m.id")
            ->join("tmarketing_info m ON t.m_id=m.id")
            ->where($map)
            ->limit($Page->firstRow, $Page->listRows)
            ->order("t.add_time desc")
            ->select();
        $show = $Page->show();
        $this->assign('dataList', $dataList);
        $this->assign('page', $show);
        $this->assign('czStatusArr', $czStatusArr);
        $this->display('numGoodsDetail');
        exit;
    }
    private function getGoodsUseListHF($goodsInfo)
    {
        $czStatusArr = array(
                '2'=>'充值成功',
                '3'=>'充值失败'
        );
        $map = array(
                't.node_id' => $this->nodeId,
                't.goods_id' => $goodsInfo['goods_id']);
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['t.add_time'] = array(
                    'egt',
                    $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['t.add_time '] = array(
                    'elt',
                    $eaddTime . '235959');
        }
        $phoneNo = I('phone_no', null, 'mysql_real_escape_string');
        if (! empty($phoneNo)) {
            $map['t.phone_no '] = array(array('eq',$phoneNo),array('neq','13900000000'),'and');
        }else{
            $map['t.phone_no'] = array('neq','13900000000');//默认号码不显示
        }
        $batchName = I('batch_name', null, 'mysql_real_escape_string');
        if (! empty($batchName)) {
            $map['m.name'] = array(
                    'like',
                    "%{$batchName}%");
        }
        $czStatus = I('cz_status', null, 'mysql_real_escape_string');
        if ($czStatus != '') {
            $map['t.status '] = $czStatus;
        }else{
            $map['t.status '] = array('in',array(2,3));
        }
        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table("tmobile_bill_send_trace t")->join("tmarketing_info m ON t.m_id=m.id")->where($map)->count();
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $dataList = M()->table("tmobile_bill_send_trace t")->field("t.add_time AS trans_time,t.phone_no,t.status AS send_status,m.name,m.batch_type,m.id")
                ->join("tmarketing_info m ON t.m_id=m.id")
                ->where($map)
                ->limit($Page->firstRow, $Page->listRows)
                ->order("t.add_time desc")
                ->select();
        $show = $Page->show();
        $this->assign('dataList', $dataList);
        $this->assign('page', $show);
        $this->assign('czStatusArr', $czStatusArr);
        $this->display('numGoodsDetail');
        exit;
    }
    /**
     * 卡券详情发布到个人记录
     */
    public function priPublishLog() {
        $goods_id = I('request.goods_id', "", "htmlspecialchars,trim");
        empty($goods_id) && $this->error('非法操作');
        $goodsInfo = M('tgoods_info')->where(
            array(
                'goods_id' => $goods_id))->select();
        $result = M()->table("tbatch_info b")->field('b.id AS b_id')
            ->where(
            array(
                'b.node_id' => $this->node_id, 
                'b.goods_id' => $goods_id, 
                'm.batch_type' => '1007'))
            ->join('tmarketing_info m ON m.id=b.m_id')
            ->select();
        $b_id = $result[0]['b_id'];
        $start_time = I('request.start_time','','htmlspecialchars,trim');
        $end_time   = I('request.end_time','','htmlspecialchars,trim');
        if($start_time)
            $startDay = date('Ymd000000',strtotime($start_time));
        else
            $startDay = date('Ymd000000',strtotime(date('Ymd').'-1 month +1 day'));
        if($end_time)
            $endDay = date('Ymd235959',strtotime($end_time));
        else
            $endDay = date('Ymd235959',strtotime(date('Ymd')));
        import("ORG.Util.Page");
        $map = array(
                'node_id'    => $this->node_id, 
                'goods_id'   => $goods_id, 
                'b_id'       => $b_id, 
                'trans_type' => '0001');
        $map['trans_time'][] = array('EGT',$startDay);
        $map['trans_time'][] = array('ELT',$endDay);
        if(I('get.down') == '1')
        {
            $list = M('tbarcode_trace')
            ->field('phone_no,trans_time,status,prize_key')
            ->where($map)
            ->order('trans_time desc')
            ->select();
            foreach ($list as $k => $v) {
                $list[$k]['trans_time'] = date('Y-m-d H:i:s',strtotime($v['trans_time']));
                switch ($v['status']) {
                    case '0':
                        $list[$k]['status'] = '成功';
                        break;
                    case '1':
                        $list[$k]['status'] = '已撤销';
                        break;
                    default:
                        $list[$k]['status'] = '失败';
                        break;
                }
            }
            $cols_arr = array(
                'phone_no'   => '接收手机号', 
                'trans_time' => '发送时间', 
                'status'     => '发送状态', 
                'prize_key'  => '备注'
                );
            parent::csv_lead("发布到个人记录下载",$cols_arr,$list);
            exit;
        }
        $count = M('tbarcode_trace')->where($map)->count();
        $p = new Page($count, 10);
        $list = M('tbarcode_trace')->where($map)
            ->order('trans_time desc')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        $this->assign('top_type', 4);
        $this->assign('goods_id', $goods_id);
        $this->assign('goodsInfo', $goodsInfo[0]);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('start_time', date('Y-m-d',strtotime($startDay)));
        $this->assign('end_time', date('Y-m-d',strtotime($endDay)));
        $this->assign('goodsTypeArr', D('Goods')->getGoodsType());
        $this->display('numGoodsDetail');
        exit;
    }

    /**
     * 卡券详情编辑记录
     */
    public function editLog() {
        import("ORG.Util.Page");
        $count = M()->table("tweb_log_info w")->join(
            'tuser_info u ON w.user_id=u.user_id')
            ->where(
            array(
                'w.log_type' => 'edit' . I('goods_id'), 
                'u.node_id' => $this->node_id))
            ->count();
        $p = new Page($count, 10);
        $log_arr = M()->table("tweb_log_info w")->field("w.*")
            ->join('tuser_info u ON w.user_id=u.user_id')
            ->where(
            array(
                'w.log_type' => 'edit' . I('goods_id'), 
                'u.node_id' => $this->node_id))
            ->order('w.add_time desc')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $goodsInfo = M('tgoods_info')->getByGoods_id(I('goods_id'));
        if (! empty($log_arr)) {
            foreach ($log_arr as $kg => $vg) {
                $log_detail_arr = json_decode($vg['log_detail'], true);
                if ($log_detail_arr['goods_end_date'] !=
                     $log_detail_arr['pre_goods_end_date']) {
                    $log_arr[$kg]['mod_content'] = "有效期";
                }
                if ($log_detail_arr['print_text'] !=
                     $log_detail_arr['pre_print_text']) {
                    $log_arr[$kg]['mod_content'] .= "　打印小票";
                }
                if ($log_detail_arr['goods_desc'] !=
                     $log_detail_arr['pre_goods_desc']) {
                    $log_arr[$kg]['mod_content'] .= "　使用须知";
                }
                if ($log_detail_arr['img_resp'] !=
                     $log_detail_arr['pre_img_resp']) {
                    $log_arr[$kg]['mod_content'] .= " 图片";
                }
                
                if ($log_detail_arr['onlineVerify'] !=
                     $log_detail_arr['preOnlineVerify']) {
                    $log_arr[$kg]['mod_content'] .= "　提领";
                }
                
                if ($log_detail_arr['shop'] != $log_detail_arr['pre_shop']) {
                    $log_arr[$kg]['mod_content'] .= "　验证门店";
                } else {
                    if ($log_detail_arr['shop'] == '2') {
                        $pre_shop_idstr_arr = explode(',', 
                            $log_detail_arr['pre_shop_idstr']);
                        $shop_idstr_arr = explode(',', 
                            $log_detail_arr['shop_idstr']);
                        sort($pre_shop_idstr_arr);
                        sort($shop_idstr_arr);
                        if (count($pre_shop_idstr_arr) != count($shop_idstr_arr)) {
                            $log_arr[$kg]['mod_content'] .= "　验证门店";
                        } else {
                            foreach ($shop_idstr_arr as $ks => $vs) {
                                if ($shop_idstr_arr[$ks] !=
                                     $pre_shop_idstr_arr[$ks]) {
                                    $log_arr[$kg]['mod_content'] .= "　验证门店";
                                    break;
                                }
                            }
                        }
                    }
                }
                if (empty($log_arr[$kg]['mod_content'])) {
                    $log_arr[$kg]['mod_content'] = "无";
                }
            }
        }
        $page = $p->show();
        $this->assign('top_type', 5);
        $this->assign('log_arr', $log_arr);
        $this->assign('goods_id', I('goods_id'));
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('page', $page);
        $this->display('numGoodsDetail');
    }

    /**
     * 卡券详情编辑记录详情
     */
    public function editDetailLog() {
        $id = I('id', '', 'trim,htmlspecialchars');
        $log_detail = M('tweb_log_info')->getFieldByLog_id($id, 'log_detail');
        $log_detail_arr = json_decode($log_detail, true);
        $log_diff_new = array();
        if ($log_detail_arr['goods_end_date'] !=
             $log_detail_arr['pre_goods_end_date']) {
            $log_diff_new['date_type'] = 1;
            $log_diff_new['date_pre'] = $log_detail_arr['pre_goods_end_date'];
            $log_diff_new['date_cur'] = $log_detail_arr['goods_end_date'];
        }
        if ($log_detail_arr['print_text'] != $log_detail_arr['pre_print_text']) {
            $log_diff_new['print_type'] = "1";
            $log_diff_new['print_pre'] = $log_detail_arr['pre_print_text'];
            $log_diff_new['print_cur'] = $log_detail_arr['print_text'];
        }
        if ($log_detail_arr['goods_desc'] != $log_detail_arr['pre_goods_desc']) {
            $log_diff_new['desc_type'] = "1";
            $log_diff_new['desc_pre'] = $log_detail_arr['pre_goods_desc'];
            $log_diff_new['desc_cur'] = $log_detail_arr['goods_desc'];
        }
        if ($log_detail_arr['onlineVerify'] != $log_detail_arr['preOnlineVerify']) {
            $log_diff_new['onlineVerifyType'] = "1";
            if ($log_detail_arr['preOnlineVerify'] == '0') {
                $log_diff_new['preOnlineVerify'] = '不支持提领';
            } else {
                $log_diff_new['preOnlineVerify'] = '支持提领';
            }
            
            if ($log_detail_arr['onlineVerify'] == '0') {
                $log_diff_new['onlineVerifyCur'] = '不支持提领';
            } else {
                $log_diff_new['onlineVerifyCur'] = '支持提领';
            }
        }
        
        if ($log_detail_arr['img_resp'] != $log_detail_arr['pre_img_resp']) {
            $log_diff_new['img_type'] = "1";
            $log_diff_new['img_pre'] = $log_detail_arr['pre_img_resp'];
            $log_diff_new['img_cur'] = $log_detail_arr['img_resp'];
        }
        
        if ($log_detail_arr['shop'] != $log_detail_arr['pre_shop']) {
            $log_diff_new['shop_type'] = "1";
            $log_diff_new['shop_pre'] = $log_detail_arr['pre_shop'];
            $log_diff_new['shop_cur'] = $log_detail_arr['shop'];
            $pre_shop_idstr_arr = explode(',', 
                $log_detail_arr['pre_shop_idstr']);
            $shop_idstr_arr = explode(',', $log_detail_arr['shop_idstr']);
            if (! empty($pre_shop_idstr_arr)) {
                foreach ($pre_shop_idstr_arr as $kp => $vp) {
                    $pre_shop_idstr_arr[$kp] = M('tstore_info')->getFieldByStore_id(
                        $vp, 'store_name');
                }
                $json_pre_shop = implode('<br />', $pre_shop_idstr_arr);
            }
            if (! empty($shop_idstr_arr)) {
                foreach ($shop_idstr_arr as $kp => $vp) {
                    $shop_idstr_arr[$kp] = M('tstore_info')->getFieldByStore_id(
                        $vp, 'store_name');
                }
                $json_shop = implode('<br />', $shop_idstr_arr);
            }
            $log_diff_new['shop_idstr_pre'] = $json_pre_shop;
            $log_diff_new['shop_idstr_cur'] = $json_shop;
        } else {
            if ($log_detail_arr['shop'] == '2') {
                $pre_shop_idstr_arr = explode(',', 
                    $log_detail_arr['pre_shop_idstr']);
                $shop_idstr_arr = explode(',', $log_detail_arr['shop_idstr']);
                sort($pre_shop_idstr_arr);
                sort($shop_idstr_arr);
                if (count($pre_shop_idstr_arr) != count($shop_idstr_arr)) {
                    $log_diff_new['shop_type'] = "1";
                    if (! empty($pre_shop_idstr_arr)) {
                        foreach ($pre_shop_idstr_arr as $kp => $vp) {
                            $pre_shop_idstr_arr[$kp] = M('tstore_info')->getFieldByStore_id(
                                $vp, 'store_name');
                        }
                        $json_pre_shop = implode('<br />', $pre_shop_idstr_arr);
                    }
                    if (! empty($shop_idstr_arr)) {
                        foreach ($shop_idstr_arr as $kp => $vp) {
                            $shop_idstr_arr[$kp] = M('tstore_info')->getFieldByStore_id(
                                $vp, 'store_name');
                        }
                        $json_shop = implode('<br />', $shop_idstr_arr);
                    }
                    $log_diff_new['shop_idstr_pre'] = $json_pre_shop;
                    $log_diff_new['shop_idstr_cur'] = $json_shop;
                } else {
                    foreach ($shop_idstr_arr as $ks => $vs) {
                        if ($shop_idstr_arr[$ks] != $pre_shop_idstr_arr[$ks]) {
                            $log_diff_new['shop_type'] = "1";
                            if (! empty($pre_shop_idstr_arr)) {
                                foreach ($pre_shop_idstr_arr as $kp => $vp) {
                                    $pre_shop_idstr_arr[$kp] = M('tstore_info')->getFieldByStore_id(
                                        $vp, 'store_name');
                                }
                                $json_pre_shop = implode('<br />', 
                                    $pre_shop_idstr_arr);
                            }
                            if (! empty($shop_idstr_arr)) {
                                foreach ($shop_idstr_arr as $kp => $vp) {
                                    $shop_idstr_arr[$kp] = M('tstore_info')->getFieldByStore_id(
                                        $vp, 'store_name');
                                }
                                $json_shop = implode('<br />', $shop_idstr_arr);
                            }
                            $log_diff_new['shop_idstr_pre'] = $json_pre_shop;
                            $log_diff_new['shop_idstr_cur'] = $json_shop;
                            break;
                        }
                    }
                }
            }
        }
        $this->assign('log_diff_new', $log_diff_new);
        $this->display('numGoodsEditLogMore');
    }

    /*
     * 话费/Q币/流量充值记录
     */
    public function topUpTrace() {
        $goodsId = I('goods_id', '', 'trim,htmlspecialchars');
        $goodsData = self::getGoodsData($goodsId);
        if (empty($goodsData))
            $this->error('未找到该商品或该商品已停用或过期');
        if(!in_array($goodsData['goods_type'],['7','8','15']))
            $this->error('卡券错误，请选择话费/流量包');
        $mobileTraceName = 'tmobile_date_send_trace';
        if($goodsData['goods_type'] == '7')
            $mobileTraceName = 'tmobile_bill_send_trace';
        import("ORG.Util.Page");
        $map = [
                'goods_id' => $goodsId,
                'node_id'  => ['exp', "in (".$this->nodein().")"],
                'status'   => ['IN',[2,3,5]]
        ];
        $count = M()->table($mobileTraceName)->where($map)->count();
        $p = new Page($count, 10);
        $list = M()->table($mobileTraceName)->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $status = array(
                '2' => '充值成功',
                '3' => '充值失败',
                '5' => '充值失败，已退回'
        );
        $map['status'] = '3';
        $failure = M($mobileTraceName)->where($map)->find();
        $titleArr = ['7'=>'话费','15'=>'流量包'];
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('goodsInfo', $goodsData);
        $this->assign('goods_id', $goodsId);
        $this->assign('status', $status);
        $this->assign('failure',$failure);
        $this->assign('titleArr',$titleArr);
        $this->display();
    }

    /*
     * 话费Q币退回
     */
    public function backAccountNum() {
        $error = "";
        $goodsId = I('goods_id', null, 'mysql_escape_string');
        if (! check_str($goodsId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $backNum = I('back_num', null, 'mysql_escape_string');
        if (! check_str($backNum, 
            array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1'), $error)) {
            $this->error("申请退回数量{$error}");
        }
        $map = array(
            'source' => '1', 
            'goods_type' => array(
                'in', 
                '7,8'), 
            'goods_id' => $goodsId, 
            'status' => '0', 
            'purchase_type' => '0');
        $goodsModel = D('Goods');
        // 获取交易流水流水号
        $transactionInfo = M()->query(
            "SELECT _nextval('client_9988') as id FROM DUAL");
        $transactionID = str_pad($transactionInfo[0]['id'], 11, "0", 
            STR_PAD_LEFT);
        // 开始事物
        $goodsModel->startTrans();
        $goodsInfo = M('tgoods_info')->where($map)
            ->lock(true)
            ->find();
        if (! $goodsInfo) {
            $goodsModel->rollback();
            $this->error('未找到有效卡券信息');
        }
        if ($backNum > $goodsInfo['remain_num']) {
            $goodsModel->rollback();
            $this->error('申请回退数量大于当前卡券剩余库存');
        }
        // 扣减当前库存
        $reduc = $goodsModel->storagenum_reduc($goodsInfo['goods_id'], $backNum, 
            '', '15', '', $backNum);
        if (! $reduc) {
            $goodsModel->rollback();
            $this->error('库存扣减:' . $goodsModel->getError());
        }
        // 增加被采购卡券库存
        $reduc = $goodsModel->storagenum_reduc($goodsInfo['purchase_goods_id'], 
            $backNum * - 1, '', '15');
        if (! $reduc) {
            $goodsModel->rollback();
            $this->error('库存增加:' . $goodsModel->getError());
        }
        // 营帐退款
        $yzResult = $goodsModel->yzFreezeAccount($transactionID, 
            $this->clientId, $goodsInfo['goods_type'] == '7' ? '2' : '1', '3', 
            $goodsInfo['goods_amt'] * $backNum);
        if (! $yzResult) {
            $goodsModel->rollback();
            $this->error('余额扣减失败:' . $goodsModel->getError());
        }
        $goodsModel->commit();
        // 记录流水
        $traceData = array(
            'request_seq' => $transactionID, 
            'trans_type' => '3', 
            'recharge_type' => $goodsInfo['goods_type'] == '6' ? '0' : '1', 
            'amount' => $goodsInfo['goods_amt'] * $backNum, 
            'node_id' => $this->nodeId, 
            'add_time' => date('YmdHis'), 
            'status' => '1');
        $traneResult = M('tphone_bills_account_trace')->add($traceData);
        if (! $traneResult) {
            log_write("话费Q币流水记录失败" . print_r($traceData, TRUE));
        }
        $this->success('退回成功');
    }
    
    // 卡券趋势列表
    public function goodsTrend() {
        $goodsModel = D('Goods');
        $startTime = I('start_time', null, 'mysql_escape_string');
        if (empty($startTime)) {
            // 一周内各类型卡券发码,验码,验证率
            $weekago = mktime(0, 0, 0, date("m"), date("d") - 6, date("Y"));
            $startTime = date('Ymd', $weekago); // 一周前日期
        }
        $endTime = I('end_time', null, 'mysql_escape_string');
        if (empty($endTime)) {
            $endTime = date('Ymd');
        }
        
        // 排名前五的
        $topVerifyGoodsInfo = $goodsModel->getTopVerifyGoods(
            array(
                'exp', 
                "in ({$this->nodeIn()})"), dateformat($startTime, 'Y-m-d'), 
            dateformat($endTime, 'Y-m-d'));
        $this->assign('topVerifyGoodsInfo', $topVerifyGoodsInfo);
        $this->assign('bTime', dateformat($startTime, 'Y-m-d'));
        $this->assign('eTime', dateformat($endTime, 'Y-m-d'));
        $this->display();
    }
    // 获取终端组门店信息
    public function getPosgroupShop() {
        $groupId = I('group_id');
        if (empty($groupId))
            return false;
        import("ORG.Util.Page");
        $count = M()->table("tgroup_pos_relation g")->field(
            "COUNT(DISTINCT(g.store_id)) AS num")
            ->join('tstore_info t ON g.store_id=t.store_id')
            ->where("g.group_id = '{$groupId}' AND g.node_id='{$this->nodeId}'")
            ->find();
        $p = new Page($count['num'], 10);
        $shopList = M()->table("tgroup_pos_relation g")->join(
            'tstore_info t ON g.store_id=t.store_id')
            ->where("g.group_id = '{$groupId}' AND g.node_id='{$this->nodeId}'")
            ->field('t.*')
            ->order('t.add_time')
            ->group('g.store_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $this->assign('shopList', $shopList);
        $this->assign("page", $page);
        $this->display();
    }

    public function OnlineStoreStatus() {
        $onlineStoreInfo = M('tstore_info')->where(
            array(
                'node_id' => $this->nodeId, 
                'status' => 0, 
                'type' => 3))->find();
        if (empty($onlineStoreInfo)) {
            $this->error("没有线上门店");
        } else {
            $this->success("存在线上门店");
        }
    }
    
    // 充值记录-重新充值
    public function numCharge() {
        $id = I('id');
        $number = I('number');
        $dataInfo = M('tphone_bills_trace')->where(
            "id_str='{$id}' and recharge_number<>'{$number}' and status='3'")->find();
        // echo M()->getLastSql();
        if (! $dataInfo)
            $this->error('数据有误');
        $uData = array(
            'recharge_number' => $number, 
            'status' => '1');
        $result = M('tphone_bills_trace')->where("id_str='{$id}'")->save($uData);
        if ($result === false)
            $this->error('系统出错更新失败');
        $this->success('修改成功');
    }

    /**
     * 微信红包创建
     */
    public function creatWeChat() {
        $map = ['node_id'=>$this->nodeId,'status'=>'1','bonus_flag'=>'1'];
        $wxpay = M('tnode_wxpay_config')->where($map)->find();
        !empty($wxpay) or redirect(U('WangcaiPc/NumGoods/weChatFile'));

        if ($this->ispost()) {
            $error = '';
            $price = I('price');
            if (! check_str($price, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '1'), $error)) {
                $this->error("红包金额{$error}");
            }
            $num = I('num');
            if (! check_str($num, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("红包数量{$error}");
            }
            $batchName = I('batch_name');
            if (! check_str($batchName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("红包名称{$error}");
            }
            $nodeName = I('node_name');
            if (! check_str($nodeName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wishing = I('wishing');
            if (! check_str($wishing, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("祝福语{$error}");
            }
            $remark = I('remark');
            if (! check_str($remark, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '15'), $error)) {
                $this->error("备注{$error}");
            }
            $data = array(
                'goods_id' => get_goods_id(), 
                'goods_name' => $batchName, 
                'goods_amt' => $price, 
                'goods_type' => '0', 
                'print_text' => $wishing,  // 祝福语
                'goods_desc' => $remark,  // 备注
                'customer_no' => $nodeName, 
                'storage_type' => '1', 
                'source' => '6', 
                'storage_num' => $num, 
                'remain_num' => $num, 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'add_time' => date('YmdHis'));
            $result = M('tgoods_info')->add($data);
            if (! $result)
                $this->error('数据出错,创建失败');
            $this->success('创建成功');
            exit();
        }
        $this->display();
    }

    /**
     * 微信红包首页
     */
    public function weChatIndex() {
        $map = ['node_id'=>$this->nodeId,'status'=>'1','bonus_flag'=>'1'];
        $wxpay = M('tnode_wxpay_config')->where($map)->find();
        !empty($wxpay) or redirect(U('WangcaiPc/NumGoods/weChatFile'));

        import("ORG.Util.Page");
        $map = ['source' => '6', 'node_id' => $this->nodeId];
        $count = M('tgoods_info')->where($map)->count();
        $p = new Page($count, 10);
        $list = M('tgoods_info')->where($map)->order('id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        // 分页显示
        $this->assign('list', $list);
        $this->assign("page", $p->show());
        $this->display();
    }

    /**
     * 微信红包详情
     */
    public function weChatInfo() {
        $goodsId = I('goods_id');
        $map = array(
            'g.node_id' => $this->nodeId, 
            'g.goods_id' => $goodsId);
        
        $weChatInfo = M()->table("tgoods_info g")->field('g.*,u.user_name')
            ->join("tuser_info u ON g.node_id=u.node_id")
            ->where($map)
            ->find();
        if (! $weChatInfo)
            $this->error('未找到有效数据');
            // 分页显示
        $this->assign('tab', '1'); // 控制tab显示
        $this->assign('weChatInfo', $weChatInfo);
        $this->assign('weChatType',$weChatInfo['source']);//代理,自己
        $this->assign('goodsName', $weChatInfo['goods_name']);
        $this->display();
    }

    /**
     * 微信红包库存变动
     */
    public function weChatStorageTrace() {
        $goods_id = I('goods_id', '', 'trim,htmlspecialchars');
        $goodsInfo = M('tgoods_info')->where(
            array(
                'goods_id' => $goods_id, 
                'node_id' => $this->nodeId))->find();
        $map = array(
            'goods_id' => $goodsInfo['id'], 
            'node_id' => $this->nodeId);
        // 查询处理
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $changeFlag = I('change_flag', null, 'mysql_real_escape_string');
        if ($changeFlag == '1') {
            $map['_string'] = 'current_num-pre_num > 0';
        } elseif ($changeFlag == '2') {
            $map['_string'] = 'current_num-pre_num < 0';
        }
        $count = M('tgoods_storage_trace')->where($map)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $dataList = (array) M('tgoods_storage_trace')->field(
            "*,(current_num-pre_num) as change_flag")
            ->where($map)
            ->order('add_time desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        // echo M()->getLastSql();
        if (! empty($dataList)) {
            $batchName = C('BATCH_TYPE_NAME');
            foreach ($dataList as $k => $v) {
                $batchInfo = array();
                if ($v['opt_type'] == '0') { // 营销活动信息
                    $batchInfo =M()->table("tcj_batch b")->field("m.name,m.batch_type")
                        ->join("tmarketing_info m ON b.batch_id=m.id")
                        ->where("b.id='{$v['relation_id']}'")
                        ->find();
                }
                $dataList[$k]['batch_name'] = empty($batchInfo['name']) ? '--' : $batchInfo['name'];
                $dataList[$k]['batch_type'] = empty($batchInfo['batch_type']) ? '--' : $batchName[$batchInfo['batch_type']];
            }
        }
        $goodsModel = D('Goods');
        $show = $Page->show();
        $this->assign('dataList', $dataList);
        $this->assign('page', $show);
        $this->assign('tab', 2);
        $this->assign('goods_id', $goods_id);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('storageTraceOptType',$goodsModel->getStorageTraceOptType());
        $this->assign('weChatType',$goodsInfo['source']);//代理,自己
        $this->assign('goodsName', $goodsInfo['goods_name']);
        $this->display('weChatInfo');
    }

    /**
     * 微信红包发放记录
     */
    public function weChatSend() {
        $goodsId = I('goods_id');
        $map = array(
            'w.node_id' => $this->nodeId, 
            'w.goods_id' => $goodsId);
        import("ORG.Util.Page");
        $count = M()->table("twx_bonus_send_trace w")->where($map)->count();
        $p = new Page($count, 10);
        
        $list =  M()->table("twx_bonus_send_trace w")->field(
            'w.*,g.goods_name,m.name,m.batch_type,u.nickname')
            ->join("tgoods_info g ON w.goods_id=g.goods_id")
            ->join("tmarketing_info m ON w.m_id=m.id")
            ->join("twx_wap_user u ON w.openid=u.openid")
            ->where($map)
            ->order('id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $goodsInfo = M('tgoods_info')
            ->field('goods_name,source')
            ->where("node_id='{$this->nodeId}' and goods_id='{$goodsId}'")
            ->find();
        $this->assign('tab', '3'); // 控制tab显示
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign('weChatType',$goodsInfo['source']);//代理,自己
        $this->assign('goodsName', $goodsInfo['goods_name']);
        $this->assign('batchTypeName', C('BATCH_TYPE_NAME'));
        $this->display('weChatInfo');
    }

    /**
     * 微信红包添加库存
     */
    public function weChataddStorageNum() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $addNum = I('addNum', 0, 'intval');
        if (! check_str($addNum, 
            array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1'), $error)) {
            $this->error("库存数量{$error}");
        }
        $goodsModel = D('Goods');
        $goodsCount = $goodsModel->where("goods_id='{$goodsId}' and source='6'")->count();
        if(empty($goodsCount)) $this->error('未找到红包信息');
        M()->startTrans();
        $flag = $goodsModel->storagenum_reduc($goodsId, - 1 * $addNum, '', 3, 
            '微信红包库存增加', - 1 * $addNum);
        if (! $flag) {
            M()->rollback();
            $this->error('微信红包库存增加失败');
        }
        M()->commit();
        $this->success('库存增加成功！');
    }

    /**
     * 微信红包配置页面
     */
    public function weChatFile() {
        if (IS_POST) {
            $wxAccountId = I('post.wxAccountId');
            if (!empty($wxAccountId) && preg_match('/[\d]+/', $wxAccountId)) {
                $sql = 'UPDATE tnode_wxpay_config 
                    SET bonus_flag = 
                    CASE WHEN id=' . $wxAccountId . ' THEN 1 ELSE 0 END 
                    WHERE node_id=\'' . $this->nodeId . '\' AND status=1';
                $ret = M()->execute($sql);
                if ($ret === false) {
                    $this->error('提交失败');
                } else {
                    $this->success('提交成功');
                }
            } else {
                $this->error('请选择微信账户' . $wxAccountId);
            }
        }
        $showFlag = 0;
        if ('1' == I('get.show', 0)) {
            $map = ['node_id' => $this->nodeId, 'status' => '1', 'bonus_flag' => '1'];
            $ret = M('tnode_wxpay_config')->where($map)->find();
            if (!empty($ret)) {
                $showFlag = 1;
                $this->assign('selectInfo',$ret);
            }
        }
        $map   = ['node_id' => $this->nodeId, 'status' => '1'];
        $wxpay = M('tnode_wxpay_config')->where($map)->select();
        $this->assign('list', $wxpay);
        $this->assign('showFlag', $showFlag);
        $this->display();
    }
    
    /**
     * 翼码代发微信红包创建
     */
    public function creatYmWeChat() {
        $redPackModel = D('RedPack');
        $remainAmt = $redPackModel->getYmRedPackNodePrice($this->nodeId);
        if ($this->ispost()) {
            $error = '';
            $price = I('price');
            if (! check_str($price,array('null' => false,'strtype' => 'number','minval' => '1'), $error)) {
                $this->error("红包金额{$error}");
            }
            $num = I('num');
            if (! check_str($num,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
                $this->error("红包数量{$error}");
            }
            $batchName = I('batch_name');
            if (! check_str($batchName,array('null' => false,'maxlen_cn' => '10'), $error)) {
                $this->error("红包名称{$error}");
            }
            $nodeName = I('node_name');
            if (! check_str($nodeName,array('null' => false,'maxlen_cn' => '10'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wishing = I('wishing');
            if (! check_str($wishing,array('null' => false,'maxlen_cn' => '20'), $error)) {
                $this->error("祝福语{$error}");
            }
            $remark = I('remark');
            if (! check_str($remark,array('null' => false,'maxlen_cn' => '15'), $error)) {
                $this->error("备注{$error}");
            }
            $data = array(
                'goods_id' => get_goods_id(),
                'goods_name' => $batchName,
                'goods_amt' => $price,
                'goods_type' => '0',
                'print_text' => $wishing,  // 祝福语
                'goods_desc' => $remark,  // 备注
                'customer_no' => $nodeName,
                'storage_type' => '1',
                'source' => '7',
                'storage_num' => $num,
                'remain_num' => $num,
                'node_id' => $this->nodeId,
                'user_id' => $this->userId,
                'add_time' => date('YmdHis')
            );
            M()->startTrans();
            //扣减余额
            $result = $redPackModel->YmRedPackDeductionBalance($this->nodeId,$price*$num);
            if(!$result){
                M()->rollback();
                $this->error('余额扣减失败'.$redPackModel->getError());
            }
            $result = M('tgoods_info')->add($data);
            if (! $result){
                M()->rollback();
                $this->error('数据出错,创建失败');
            }
            M()->commit();
            $this->success('创建成功');
            exit();
        }
        
        $this->assign('remainAmt',$remainAmt);
        $this->display();
    }
    
    /**
     * 翼码代理微信卡券列表
     */
    public function ymWeChatIndex(){

        $map = array(
            'source' => '7',
            'node_id' => $this->nodeId
        );
        import("ORG.Util.Page");
        $count = M('tgoods_info')->where($map)->count();
        $p = new Page($count, 10);
        
        $list = M('tgoods_info')->where($map)
            ->order('id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        //获取余额
        $redPackModel = D('RedPack');
        $remainAmt = $redPackModel->getYmRedPackNodePrice($this->nodeId);
        // 分页显示
        $page = $p->show();
        $this->assign('remainAmt',$remainAmt);
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->display();
    }
    
    /**
     * 翼码代发红包库存更新
     */
    public function weChatUpYmStorageNum() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $num = I('up_num');
        $type = I('storage_type');
        if (! check_str($num,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
            $this->error("库存数量{$error}");
        }
        $goodsModel = D('Goods');
        $redPackModel = D('RedPack');
        $goodsInfo = $goodsModel->field('goods_amt')->where("goods_id='{$goodsId}' and source='7'")->find();
        if(empty($goodsInfo)) $this->error('未找到红包信息');
        M()->startTrans();
        if($type == '1'){
            //增加库存
            $result = $goodsModel->storagenum_reduc($goodsId, - 1 * $num, '', 3,'微信红包库存增加', - 1 * $num);
            if (! $result) {
                M()->rollback();
                $this->error('微信红包库存增加失败');
            }
            //扣减余额
            $result = $redPackModel->YmRedPackDeductionBalance($this->nodeId,$goodsInfo['goods_amt']*$num);
            if(!$result){
                M()->rollback();
                $this->error('余额扣减失败'.$redPackModel->getError());
            }
        }else{
            //减少库存
            $result = $goodsModel->storagenum_reduc($goodsId, $num, '', 18,'微信红包库存扣减',$num);
            if (! $result) {
                M()->rollback();
                $this->error('微信红包库存扣减失败');
            }
            //返还余额
            $result = $redPackModel->YmRedPackDeductionBalance($this->nodeId,-1*$goodsInfo['goods_amt']*$num);
            if(!$result){
                M()->rollback();
                $this->error('余额返还失败'.$redPackModel->getError());
            }
        }
        M()->commit();
        $this->success('库存更新成功！');
    }
    
    /**
     * 卡券详情使用记录使用详情
     */
    public function getGoodsVerifyDetail(){
    	$seq = I('seq',null,'mysql_real_escape_string');
    	$dataInfo = M()->table("tpos_trace p")->field("p.pos_id,p.status,p.exchange_amt,p.trans_time,o.store_name")
    		->join("tpos_info o ON p.pos_id=o.pos_id")
    		->where("p.req_seq='{$seq}' and p.node_id='{$this->nodeId}'")->find();
    	if(!$dataInfo) $this->error('记录未找到');
    	$statusArr = array('成功','失败','冲正','支付宝支付待确认');
    	$dataInfo['status'] = $statusArr[$dataInfo['status']];
    	$dataInfo['trans_time'] = dateformat($dataInfo['trans_time'],'Y-m-d H:i:s');
    	$this->ajaxReturn($dataInfo,'','1');
    }
    
    /**
     * 奖品卡券库存退回
     */
    public function prizeBack(){
    	$goodsId = I('goods_id');
    	$wh = array(
    		'c.node_id' => $this->nodeId,
    		'c.goods_id' => $goodsId,
    		'm.batch_type' => array('in',implode(',',C('PRIZE_STOREGE_BACK.BATCH_TYPE'))),
    	);
    	if($this->ispost()){
    		$mId = I('m_id');
    		$wh['c.batch_id'] = array('in',implode(',',$mId));
    		$wh['m.end_time'] = array('lt',date('Ymd').'000000');
    		$wh['b.remain_num'] = array('gt','0');
    		$prizList = M('tcj_batch c')->field('c.id,c.batch_id,b.remain_num')
    			->join('tmarketing_info m ON m.id=c.batch_id')
    			->join("tbatch_info b ON c.b_id=b.id")
    			->where($wh)
    			->select();
    		//echo M()->getLastSql();exit;
    		if(!$prizList) $this->error('未找到有效的活动信息');
    		$cjSetMeldel = D('CjSet');
    		$backNum = 0;
    		foreach($prizList as $k=>$v){
    			$cjSetMeldel->startTrans();
    			$resutl = $cjSetMeldel->storageBack($this->nodeId,$v['batch_id'],$v['id'],$this->userId);
    			if(!$resutl){
    				$cjSetMeldel->rollback();
    				$this->error($cjSetMeldel->getError());
    			}
    			$backNum += $v['remain_num'];
    		}
    		$cjSetMeldel->commit();
    		$this->success("{$backNum}");
    		exit;
    	}
    	//获取过期活动列表
    	
    	import("ORG.Util.Page");
    	$sql = M("tcj_batch c")->field('c.*')->join("tmarketing_info m ON m.id=c.batch_id")->where($wh)->group('c.batch_id')->select(false);
    	$count = M()->table($sql.' a')->count();
    	$p = new Page($count, 10);
    	$list = M('tcj_batch c')->field('m.id,m.name,m.batch_type,m.start_time,m.end_time,SUM(b.storage_num) as storage_num,SUM(b.remain_num) as remain_num')
    		->join("tmarketing_info m ON m.id=c.batch_id")
    		->join("tbatch_info b ON c.b_id=b.id")
    		->group('c.batch_id')
    		->where($wh)
    		->order('c.id desc')
    		->limit($p->firstRow . ',' . $p->listRows)
    		->select();
    	//echo M()->getLastSql();
    	// 分页显示
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('list',$list);
    	$this->display();
    }
    
}